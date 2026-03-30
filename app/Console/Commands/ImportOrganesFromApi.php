<?php

namespace App\Console\Commands;

use App\Models\DeputeSenateur;
use App\Models\MembreOrgane;
use App\Models\OrganeParlementaire;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImportOrganesFromApi extends Command
{
    protected $signature = 'import:organes-parlementaires 
                            {--source=both : Source (assemblee/senat/both)}
                            {--type=all : Type (groupe/commission/delegation/all)}';

    protected $description = 'Importe les organes parlementaires (groupes, commissions, délégations) et leurs membres';

    private int $organesImported = 0;

    private int $membresImported = 0;

    private int $errors = 0;

    public function handle()
    {
        $this->info('🏛️  Import des organes parlementaires...');
        $this->newLine();

        $source = $this->option('source');
        $type = $this->option('type');

        // Assemblée Nationale
        if ($source === 'both' || $source === 'assemblee') {
            $this->info('📥 Assemblée Nationale...');
            $this->importOrganesAssemblee($type);
            $this->newLine();
        }

        // Sénat
        if ($source === 'both' || $source === 'senat') {
            $this->info('📥 Sénat...');
            $this->importOrganesSenat($type);
            $this->newLine();
        }

        $this->displaySummary();

        return Command::SUCCESS;
    }

    /**
     * Import organes Assemblée Nationale
     */
    private function importOrganesAssemblee(string $type)
    {
        $baseUrl = 'https://www.nosdeputes.fr';

        // 1. Groupes politiques
        if ($type === 'all' || $type === 'groupe') {
            $this->info('  📊 Groupes politiques...');
            $this->importGroupes($baseUrl, 'assemblee');
        }

        // 2. Commissions
        if ($type === 'all' || $type === 'commission') {
            $this->info('  📋 Commissions...');
            $this->importOrganesParType($baseUrl, 'assemblee', 'parlementaire');
        }

        // 3. Délégations
        if ($type === 'all' || $type === 'delegation') {
            $this->info('  🔖 Délégations...');
            // Inclus dans "parlementaire"
        }
    }

    /**
     * Import organes Sénat
     */
    private function importOrganesSenat(string $type)
    {
        $baseUrl = 'https://www.nossenateurs.fr';

        // 1. Groupes politiques
        if ($type === 'all' || $type === 'groupe') {
            $this->info('  📊 Groupes politiques...');
            $this->importGroupes($baseUrl, 'senat');
        }

        // 2. Commissions
        if ($type === 'all' || $type === 'commission') {
            $this->info('  📋 Commissions...');
            $this->importOrganesParType($baseUrl, 'senat', 'parlementaire');
        }
    }

    /**
     * Import des groupes politiques
     */
    private function importGroupes(string $baseUrl, string $source)
    {
        try {
            $response = Http::timeout(30)->get("{$baseUrl}/organismes/groupe/json");

            if (! $response->successful()) {
                $this->error("❌ Erreur API groupes {$source}");
                $this->errors++;

                return;
            }

            $data = $response->json();
            $organismes = $data['organismes'] ?? [];

            foreach ($organismes as $orgData) {
                try {
                    $org = $orgData['organisme'] ?? $orgData;

                    $organe = OrganeParlementaire::updateOrCreate(
                        [
                            'source' => $source,
                            'slug' => $org['slug'] ?? '',
                        ],
                        [
                            'type' => 'groupe',
                            'sigle' => $org['acronyme'] ?? $org['sigle'] ?? null,
                            'nom' => $org['nom'] ?? '',
                            'nom_long' => $org['nom_long'] ?? null,
                            'couleur_hex' => $org['couleur'] ?? null,
                            'position_politique' => $org['position'] ?? null,
                            'url_nosdeputes' => "{$baseUrl}/organisme/{$org['slug']}",
                        ]
                    );

                    // Importer les membres du groupe
                    $this->importMembresOrgane($baseUrl, $source, $organe, $org['slug']);

                    $this->organesImported++;
                } catch (\Exception $e) {
                    Log::error('Erreur import groupe', ['error' => $e->getMessage()]);
                }
            }
        } catch (\Exception $e) {
            $this->errors++;
            Log::error("Erreur import groupes {$source}", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Import des organes parlementaires (commissions, délégations, etc.)
     */
    private function importOrganesParType(string $baseUrl, string $source, string $typeApi)
    {
        try {
            $response = Http::timeout(30)->get("{$baseUrl}/organismes/{$typeApi}/json");

            if (! $response->successful()) {
                $this->error("❌ Erreur API organes {$typeApi} {$source}");
                $this->errors++;

                return;
            }

            $data = $response->json();
            $organismes = $data['organismes'] ?? [];

            foreach ($organismes as $orgData) {
                try {
                    $org = $orgData['organisme'] ?? $orgData;

                    // Déterminer le type d'organe
                    $type = $this->determineTypeOrgane($org['nom'] ?? '');

                    $organe = OrganeParlementaire::updateOrCreate(
                        [
                            'source' => $source,
                            'slug' => $org['slug'] ?? '',
                        ],
                        [
                            'type' => $type,
                            'sigle' => $org['acronyme'] ?? $org['sigle'] ?? null,
                            'nom' => $org['nom'] ?? '',
                            'nom_long' => $org['nom_long'] ?? null,
                            'description' => $org['description'] ?? null,
                            'url_nosdeputes' => "{$baseUrl}/organisme/{$org['slug']}",
                        ]
                    );

                    // Importer les membres
                    $this->importMembresOrgane($baseUrl, $source, $organe, $org['slug']);

                    $this->organesImported++;
                } catch (\Exception $e) {
                    Log::error('Erreur import organe', ['error' => $e->getMessage()]);
                }
            }
        } catch (\Exception $e) {
            $this->errors++;
            Log::error("Erreur import organes {$typeApi} {$source}", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Import des membres d'un organe
     */
    private function importMembresOrgane(string $baseUrl, string $source, OrganeParlementaire $organe, string $slug)
    {
        try {
            $response = Http::timeout(30)->get("{$baseUrl}/organisme/{$slug}/json");

            if (! $response->successful()) {
                return;
            }

            $data = $response->json();
            $parlementaires = $data['parlementaires'] ?? [];

            $ordre = 1;
            foreach ($parlementaires as $parlData) {
                try {
                    $parl = $parlData['parlementaire'] ?? $parlData;

                    // Trouver le député/sénateur par slug
                    $deputeSenateur = DeputeSenateur::where('source', $source)
                        ->where(function ($q) use ($parl) {
                            $slug = $parl['slug'] ?? '';
                            $nom = explode('-', $slug);
                            if (count($nom) >= 2) {
                                $prenom = $nom[0];
                                $nomFamille = implode(' ', array_slice($nom, 1));
                                $q->where('prenom', 'ILIKE', $prenom)
                                    ->where('nom', 'ILIKE', $nomFamille);
                            }
                        })
                        ->first();

                    if (! $deputeSenateur) {
                        continue;
                    }

                    MembreOrgane::updateOrCreate(
                        [
                            'organe_id' => $organe->id,
                            'depute_senateur_id' => $deputeSenateur->id,
                            'date_debut' => $this->parseDate($parl['debut_fonction'] ?? now()),
                        ],
                        [
                            'fonction' => $parl['fonction'] ?? 'membre',
                            'ordre' => $ordre++,
                            'date_fin' => $this->parseDate($parl['fin_fonction'] ?? null),
                            'actif' => empty($parl['fin_fonction']),
                            'groupe_a_fin_fonction' => $parl['groupe_a_fin_fonction'] ?? null,
                        ]
                    );

                    $this->membresImported++;
                } catch (\Exception $e) {
                    // Ignorer les erreurs individuelles
                }
            }

            // Mettre à jour le nombre de membres
            $organe->update(['nombre_membres' => $organe->membres()->where('actif', true)->count()]);
        } catch (\Exception $e) {
            // Ignorer si pas de membres
        }
    }

    /**
     * Déterminer le type d'organe depuis son nom
     */
    private function determineTypeOrgane(string $nom): string
    {
        $nom = strtolower($nom);

        if (str_contains($nom, 'commission')) {
            return 'commission';
        }

        if (str_contains($nom, 'délégation')) {
            return 'delegation';
        }

        if (str_contains($nom, 'mission')) {
            return 'mission';
        }

        if (str_contains($nom, 'office')) {
            return 'office';
        }

        return 'commission'; // Par défaut
    }

    /**
     * Parser une date
     */
    private function parseDate($date)
    {
        if (! $date) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($date);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Afficher le résumé
     */
    private function displaySummary()
    {
        $this->info('✅ Import terminé !');
        $this->newLine();

        $this->info('📊 Résumé :');
        $this->line("   ✓ {$this->organesImported} organes importés");
        $this->line("   👥 {$this->membresImported} membres importés");

        if ($this->errors > 0) {
            $this->warn("   ⚠️  {$this->errors} erreurs");
        }

        $this->newLine();

        // Statistiques
        try {
            $totalOrganes = OrganeParlementaire::count();
            $totalGroupes = OrganeParlementaire::where('type', 'groupe')->count();
            $totalCommissions = OrganeParlementaire::where('type', 'commission')->count();
            $totalMembres = MembreOrgane::where('actif', true)->count();

            $this->info('📈 Total en base de données :');
            $this->line("   {$totalOrganes} organes ({$totalGroupes} groupes, {$totalCommissions} commissions)");
            $this->line("   {$totalMembres} membres actifs");
        } catch (\Exception $e) {
            $this->warn('⚠️  Tables non créées. Lancer: php artisan migrate');
        }
    }
}
