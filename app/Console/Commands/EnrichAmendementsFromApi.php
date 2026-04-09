<?php

namespace App\Console\Commands;

use App\Models\AmendementParlementaire;
use App\Models\DeputeSenateur;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EnrichAmendementsFromApi extends Command
{
    protected $signature = 'enrich:amendements 
                            {--limit= : Limiter le nombre de députés/sénateurs} 
                            {--depute= : UID d\'un député/sénateur spécifique}
                            {--source=both : Source (assemblee/senat/both)}';

    protected $description = 'Enrichit les amendements depuis les APIs NosDéputés.fr et NosSénateurs.fr';

    private int $parlementairesProcessed = 0;

    private int $amendementsImported = 0;

    private int $errors = 0;

    public function handle()
    {
        $this->info('📝 Enrichissement des amendements...');
        $this->newLine();

        $limit = $this->option('limit');
        $deputeUid = $this->option('depute');
        $source = $this->option('source');

        // Récupérer les parlementaires à enrichir
        $query = DeputeSenateur::where('en_exercice', true);

        if ($deputeUid) {
            $query->where('uid', $deputeUid);
        } elseif ($source !== 'both') {
            $query->where('source', $source);
        }

        if ($limit) {
            $query->limit($limit);
            $this->warn("⚠️  Mode TEST : {$limit} parlementaires maximum");
        }

        $parlementaires = $query->get();

        if ($parlementaires->isEmpty()) {
            $this->warn('⚠️  Aucun parlementaire à enrichir');

            return Command::SUCCESS;
        }

        $this->info("📊 {$parlementaires->count()} parlementaires à enrichir");
        $this->info('⏱️  Estimation : '.($parlementaires->count() * 2).' secondes (pause de 2s par parlementaire)');
        $this->newLine();

        $bar = $this->output->createProgressBar($parlementaires->count());
        $bar->setFormat('verbose');

        foreach ($parlementaires as $parlementaire) {
            $this->enrichAmendements($parlementaire);
            $bar->advance();

            // Pause obligatoire pour ne pas surcharger l'API
            sleep(2);
        }

        $bar->finish();
        $this->newLine(2);

        $this->displaySummary();

        return Command::SUCCESS;
    }

    /**
     * Enrichir les amendements d'un parlementaire
     */
    private function enrichAmendements(DeputeSenateur $parlementaire)
    {
        try {
            // Construire le slug
            $slug = $this->buildSlug($parlementaire);

            if (! $slug) {
                $this->errors++;

                return;
            }

            // Déterminer l'URL de base selon la source
            $baseUrl = $parlementaire->source === 'assemblee'
                ? 'https://www.nosdeputes.fr'
                : 'https://www.nossenateurs.fr';

            // Appeler l'endpoint /slug/amendements/json
            $response = Http::timeout(30)->get("{$baseUrl}/{$slug}/amendements/json");

            if (! $response->successful()) {
                $this->errors++;

                return;
            }

            $data = $response->json();
            $amendements = $data['amendements'] ?? [];

            foreach ($amendements as $amendementData) {
                try {
                    $amend = $amendementData['amendement'] ?? $amendementData;

                    // Extraire les co-signataires
                    $cosignataires = [];
                    $nombreCosignataires = 0;

                    if (! empty($amend['cosignataires'])) {
                        $cosignataires = is_array($amend['cosignataires'])
                            ? $amend['cosignataires']
                            : [$amend['cosignataires']];
                        $nombreCosignataires = count($cosignataires);
                    }

                    AmendementParlementaire::updateOrCreate(
                        [
                            'depute_senateur_id' => $parlementaire->id,
                            'numero' => $amend['numero'] ?? '',
                            'legislature' => $amend['legislature'] ?? $parlementaire->legislature,
                        ],
                        [
                            'numero_long' => $amend['numero_long'] ?? $amend['numero'] ?? '',
                            'date_depot' => $this->parseDate($amend['date'] ?? null),
                            'session' => $amend['session'] ?? null,
                            'titre' => $amend['titre'] ?? $amend['sujet'] ?? null,
                            'expose' => $amend['expose'] ?? $amend['expose_sommaire'] ?? null,
                            'dispositif' => $amend['dispositif'] ?? $amend['texte'] ?? null,
                            'sort' => $this->normalizeSortAmendement($amend['sort'] ?? null),
                            'sujet' => $amend['sujet'] ?? null,
                            'texte_loi_reference' => $amend['texteloi_titre'] ?? $amend['texte_reference'] ?? null,
                            'url_nosdeputes' => $amend['url'] ?? null,
                            'url_assemblee' => $amend['url_senat'] ?? $amend['url_assemblee'] ?? null,
                            'cosignataires' => $cosignataires,
                            'nombre_cosignataires' => $nombreCosignataires,
                            'groupe_politique' => $parlementaire->groupe_politique,
                        ]
                    );

                    $this->amendementsImported++;
                } catch (\Exception $e) {
                    // Ignorer les erreurs individuelles
                    Log::debug('Erreur import amendement', [
                        'parlementaire' => $parlementaire->nom,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->parlementairesProcessed++;

        } catch (\Exception $e) {
            $this->errors++;
            Log::error("Erreur enrichissement amendements {$parlementaire->nom}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Construire le slug d'un parlementaire pour l'API
     */
    private function buildSlug(DeputeSenateur $parlementaire): ?string
    {
        $prenom = strtolower($parlementaire->prenom);
        $nom = strtolower($parlementaire->nom);

        // Normaliser
        $prenom = $this->slugify($prenom);
        $nom = $this->slugify($nom);

        // Prendre le premier prénom uniquement
        $prenomParts = explode('-', $prenom);
        $prenom = $prenomParts[0];

        return $prenom.'-'.$nom;
    }

    /**
     * Slugify une chaîne
     */
    private function slugify(string $str): string
    {
        $str = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
        $str = preg_replace('/[^a-z0-9\s-]/', '', $str);
        $str = preg_replace('/[\s-]+/', '-', $str);
        $str = trim($str, '-');

        return $str;
    }

    /**
     * Parser une date
     */
    private function parseDate(?string $date)
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
     * Normaliser le sort d'un amendement
     */
    private function normalizeSortAmendement(?string $sort): ?string
    {
        if (! $sort) {
            return null;
        }

        $sort = strtolower($sort);

        return match (true) {
            str_contains($sort, 'adopt') => 'adopte',
            str_contains($sort, 'rejet') => 'rejete',
            str_contains($sort, 'retir') => 'retire',
            str_contains($sort, 'tomb') => 'tombe',
            str_contains($sort, 'non') => 'non-vote',
            default => $sort,
        };
    }

    /**
     * Afficher le résumé
     */
    private function displaySummary()
    {
        $this->info('✅ Enrichissement terminé !');
        $this->newLine();

        $this->info('📊 Résumé :');
        $this->line("   ✓ {$this->parlementairesProcessed} parlementaires traités");
        $this->line("   📝 {$this->amendementsImported} amendements importés");

        if ($this->errors > 0) {
            $this->warn("   ⚠️  {$this->errors} erreurs");
        }

        $this->newLine();

        // Statistiques globales
        try {
            $totalAmendements = AmendementParlementaire::count();
            $totalAdoptes = AmendementParlementaire::where('sort', 'adopte')->count();
            $totalRejetes = AmendementParlementaire::where('sort', 'rejete')->count();
            $tauxAdoption = $totalAmendements > 0
                ? round(($totalAdoptes / $totalAmendements) * 100, 2)
                : 0;

            $this->info('📈 Total en base de données :');
            $this->line("   {$totalAmendements} amendements");
            $this->line("   {$totalAdoptes} adoptés ({$tauxAdoption}%)");
            $this->line("   {$totalRejetes} rejetés");
        } catch (\Exception $e) {
            $this->warn('⚠️  Tables non créées. Lancer: php artisan migrate');
        }
    }
}
