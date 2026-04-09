<?php

namespace App\Console\Commands;

use App\Models\DeputeSenateur;
use App\Models\GroupeParlementaire;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EnrichDeputesFromApi extends Command
{
    protected $signature = 'enrich:deputes {--limit= : Limiter le nombre de députés à enrichir (pour test)} {--force : Forcer la mise à jour même si déjà enrichi}';

    protected $description = 'Enrichit les données des députés via l\'API NosDéputés.fr (groupes, photos, stats)';

    private const API_BASE_URL = 'https://www.nosdeputes.fr';

    private const API_SYNTHESE = 'https://www.nosdeputes.fr/synthese/legislature/17/json';

    private int $successCount = 0;

    private int $errorCount = 0;

    private int $skippedCount = 0;

    public function handle()
    {
        $this->info('🏛️  Enrichissement des députés via NosDéputés.fr...');
        $this->newLine();

        $limit = $this->option('limit');
        $force = $this->option('force');

        if ($limit) {
            $this->warn("⚠️  Mode TEST : Enrichissement limité à {$limit} députés");
        }

        try {
            // 1. Récupérer tous les députés depuis l'API
            $this->info('📥 Récupération de la liste des députés depuis l\'API...');
            $apiDeputes = $this->fetchDeputesFromApi();

            if (empty($apiDeputes)) {
                $this->error('❌ Impossible de récupérer les données de l\'API');

                return Command::FAILURE;
            }

            $this->info("✅ {$apiDeputes->count()} députés récupérés de l'API");
            $this->newLine();

            // 2. Récupérer nos députés en base
            $query = DeputeSenateur::where('source', 'assemblee');

            if (! $force) {
                $query->where(function ($q) {
                    $q->whereNull('groupe_politique')
                        ->orWhereNull('photo_url')
                        ->orWhere('nb_propositions', 0);
                });
            }

            if ($limit) {
                $query->limit($limit);
            }

            $deputes = $query->get();

            if ($deputes->isEmpty()) {
                $this->warn('⚠️  Aucun député à enrichir');

                return Command::SUCCESS;
            }

            $this->info("📊 {$deputes->count()} députés à enrichir");
            $bar = $this->output->createProgressBar($deputes->count());
            $bar->setFormat('verbose');

            // 3. Enrichir chaque député
            foreach ($deputes as $depute) {
                $this->enrichDepute($depute, $apiDeputes);
                $bar->advance();

                // Pause pour ne pas surcharger l'API
                usleep(100000); // 0.1 seconde
            }

            $bar->finish();
            $this->newLine(2);

            // 4. Résumé
            $this->displaySummary();

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Erreur lors de l\'enrichissement: '.$e->getMessage());
            Log::error('EnrichDeputes error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return Command::FAILURE;
        }
    }

    /**
     * Récupérer tous les députés depuis l'API NosDéputés.fr
     */
    private function fetchDeputesFromApi()
    {
        try {
            $response = Http::timeout(30)->get(self::API_SYNTHESE);

            if (! $response->successful()) {
                return collect([]);
            }

            $data = $response->json();

            // L'API retourne un objet avec les députés comme clés
            return collect($data['deputes'] ?? []);

        } catch (\Exception $e) {
            $this->warn('⚠️  Erreur API: '.$e->getMessage());

            return collect([]);
        }
    }

    /**
     * Enrichir un député avec les données de l'API
     */
    private function enrichDepute(DeputeSenateur $depute, $apiDeputes)
    {
        try {
            // Chercher le député dans les données API par nom/prénom
            $apiDepute = $this->findDeputeInApi($depute, $apiDeputes);

            if (! $apiDepute) {
                $this->skippedCount++;

                return;
            }

            // Mettre à jour les données
            $updated = false;

            // Groupe politique
            if (! empty($apiDepute['groupe_sigle'])) {
                $depute->groupe_sigle = $apiDepute['groupe_sigle'];
                $depute->groupe_politique = $apiDepute['groupe'] ?? null;
                $updated = true;

                // Créer/mettre à jour le groupe parlementaire si nécessaire
                $this->upsertGroupe($apiDepute);
            }

            // Photo
            if (! empty($apiDepute['url_an'])) {
                // Construire l'URL de la photo depuis l'ID
                if (preg_match('/\/(\d+)$/', $apiDepute['url_an'], $matches)) {
                    $deputeId = $matches[1];
                    $depute->photo_url = "https://www.nosdeputes.fr/depute/photo/{$deputeId}/200";
                    $updated = true;
                }
            }

            // URL profil
            if (! empty($apiDepute['slug'])) {
                $depute->url_profil = self::API_BASE_URL.'/'.$apiDepute['slug'];
                $updated = true;
            }

            // Statistiques
            if (isset($apiDepute['nb_propositions'])) {
                $depute->nb_propositions = (int) $apiDepute['nb_propositions'];
                $updated = true;
            }

            if (isset($apiDepute['nb_amendements'])) {
                $depute->nb_amendements = (int) $apiDepute['nb_amendements'];
                $updated = true;
            }

            if (isset($apiDepute['presences_commission'])) {
                // Calculer un taux de présence approximatif
                $presences = (float) str_replace('%', '', $apiDepute['presences_commission']);
                $depute->taux_presence = $presences;
                $updated = true;
            }

            // Fonctions (si disponibles)
            if (! empty($apiDepute['responsabilites'])) {
                $fonctions = [];
                foreach ($apiDepute['responsabilites'] as $resp) {
                    $fonctions[] = [
                        'libelle' => $resp['organisme'] ?? '',
                        'fonction' => $resp['fonction'] ?? '',
                    ];
                }
                $depute->fonctions = $fonctions;
                $updated = true;
            }

            if ($updated) {
                $depute->save();
                $this->successCount++;
            } else {
                $this->skippedCount++;
            }

        } catch (\Exception $e) {
            $this->errorCount++;
            Log::warning("Erreur enrichissement député {$depute->nom}", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Trouver un député dans les données API par correspondance nom/prénom
     */
    private function findDeputeInApi(DeputeSenateur $depute, $apiDeputes)
    {
        $nom = $this->normalizeString($depute->nom);
        $prenom = $this->normalizeString($depute->prenom);

        foreach ($apiDeputes as $slug => $apiDepute) {
            $apiNom = $this->normalizeString($apiDepute['nom'] ?? '');
            $apiPrenom = $this->normalizeString($apiDepute['prenom'] ?? '');

            // Correspondance exacte ou partielle
            if ($this->namesMatch($nom, $prenom, $apiNom, $apiPrenom)) {
                return $apiDepute;
            }
        }

        return null;
    }

    /**
     * Vérifier si deux noms correspondent
     */
    private function namesMatch(string $nom1, string $prenom1, string $nom2, string $prenom2): bool
    {
        // Correspondance exacte
        if ($nom1 === $nom2 && $prenom1 === $prenom2) {
            return true;
        }

        // Correspondance partielle (premier prénom uniquement)
        $prenom1Parts = explode(' ', $prenom1);
        $prenom2Parts = explode(' ', $prenom2);

        if ($nom1 === $nom2 && ! empty($prenom1Parts[0]) && ! empty($prenom2Parts[0])) {
            if ($prenom1Parts[0] === $prenom2Parts[0]) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normaliser une chaîne pour la comparaison
     */
    private function normalizeString(string $str): string
    {
        $str = strtolower($str);
        $str = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
        $str = preg_replace('/[^a-z0-9\s-]/', '', $str);
        $str = trim($str);

        return $str;
    }

    /**
     * Créer ou mettre à jour un groupe parlementaire
     */
    private function upsertGroupe(array $apiDepute)
    {
        if (empty($apiDepute['groupe_sigle'])) {
            return;
        }

        try {
            GroupeParlementaire::updateOrCreate(
                [
                    'source' => 'assemblee',
                    'sigle' => $apiDepute['groupe_sigle'],
                ],
                [
                    'nom' => $apiDepute['groupe'] ?? $apiDepute['groupe_sigle'],
                    'slug' => \Illuminate\Support\Str::slug($apiDepute['groupe'] ?? $apiDepute['groupe_sigle']),
                    'legislature' => 17,
                ]
            );
        } catch (\Exception $e) {
            // Ignorer les erreurs de groupe
        }
    }

    /**
     * Afficher le résumé
     */
    private function displaySummary()
    {
        $this->info('✅ Enrichissement terminé !');
        $this->newLine();

        $this->info('📊 Résumé :');
        $this->line("   ✓ {$this->successCount} députés enrichis");
        $this->line("   ↻ {$this->skippedCount} députés ignorés");

        if ($this->errorCount > 0) {
            $this->warn("   ⚠️  {$this->errorCount} erreurs");
        }

        $this->newLine();

        // Statistiques finales
        $stats = DeputeSenateur::where('source', 'assemblee')
            ->selectRaw('
                COUNT(*) as total,
                COUNT(CASE WHEN groupe_politique IS NOT NULL THEN 1 END) as avec_groupe,
                COUNT(CASE WHEN photo_url IS NOT NULL THEN 1 END) as avec_photo,
                AVG(nb_propositions) as avg_propositions,
                AVG(nb_amendements) as avg_amendements
            ')
            ->first();

        $this->info('📈 Statistiques globales :');
        $this->line("   Total députés : {$stats->total}");
        $this->line("   Avec groupe : {$stats->avec_groupe}");
        $this->line("   Avec photo : {$stats->avec_photo}");
        $this->line('   Moy. propositions : '.round($stats->avg_propositions, 1));
        $this->line('   Moy. amendements : '.round($stats->avg_amendements, 1));
    }
}
