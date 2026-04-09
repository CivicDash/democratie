<?php

namespace App\Console\Commands;

use App\Models\ActeurAN;
use App\Services\WikipediaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ImportDeputesWikipedia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:deputes-wikipedia
                            {--legislature=17 : Législature à traiter}
                            {--limit= : Limite du nombre de députés à traiter (pour tests)}
                            {--force : Forcer la mise à jour même si déjà synchronisé}
                            {--dry-run : Mode simulation sans écriture en base}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importer les données Wikipedia (URL, photo, extrait) pour les députés';

    protected WikipediaService $wikipediaService;

    /**
     * Execute the console command.
     */
    public function handle(WikipediaService $wikipediaService): int
    {
        $this->wikipediaService = $wikipediaService;

        $legislature = $this->option('legislature');
        $limit = $this->option('limit');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');

        $this->info("🏛️  Import des données Wikipedia pour les députés L{$legislature}");

        if ($dryRun) {
            $this->warn('⚠️  MODE SIMULATION (--dry-run) - Aucune modification en base');
        }

        // Étape 1: Parser le tableau Wikipedia L17
        $this->info("\n📊 Étape 1/3: Parsing du tableau Wikipedia...");

        try {
            $deputesWikipedia = $this->wikipediaService->parseDeputesL17();
        } catch (\Exception $e) {
            $this->error("❌ Erreur lors du parsing Wikipedia: {$e->getMessage()}");

            return self::FAILURE;
        }

        if (empty($deputesWikipedia)) {
            $this->error('❌ Aucun député trouvé dans le tableau Wikipedia');

            return self::FAILURE;
        }

        $countDeputes = count($deputesWikipedia);
        $this->info("✅ {$countDeputes} députés trouvés sur Wikipedia");

        // Étape 2: Récupérer les acteurs AN depuis la base
        $this->info("\n👤 Étape 2/3: Récupération des acteurs AN...");

        $query = ActeurAN::query();

        if (! $force) {
            // Uniquement ceux qui n'ont pas encore de données Wikipedia
            $query->whereNull('wikipedia_url');
        }

        if ($limit) {
            $query->limit((int) $limit);
        }

        $acteurs = $query->get();

        if ($acteurs->isEmpty()) {
            $this->warn('⚠️  Aucun acteur à traiter');

            return self::SUCCESS;
        }

        $countActeurs = $acteurs->count();
        $this->info("✅ {$countActeurs} acteurs à traiter");

        // Étape 3: Matcher et enrichir
        $this->info("\n🔗 Étape 3/3: Matching et enrichissement...");

        $stats = [
            'total' => $acteurs->count(),
            'matched' => 0,
            'with_photo' => 0,
            'not_matched' => 0,
            'errors' => 0,
        ];

        $progressBar = $this->output->createProgressBar($stats['total']);
        $progressBar->start();

        foreach ($acteurs as $acteur) {
            try {
                // Enrichir l'acteur avec les données Wikipedia
                $wikiData = $this->wikipediaService->enrichActeur([
                    'nom' => $acteur->nom,
                    'prenom' => $acteur->prenom,
                ], $deputesWikipedia);

                if ($wikiData) {
                    $stats['matched']++;

                    if (! empty($wikiData['photo_wikipedia_url'])) {
                        $stats['with_photo']++;
                    }

                    if (! $dryRun) {
                        $acteur->update([
                            'wikipedia_url' => $wikiData['wikipedia_url'],
                            'photo_wikipedia_url' => $wikiData['photo_wikipedia_url'],
                            'wikipedia_extract' => $wikiData['wikipedia_extract'],
                            'wikipedia_last_sync' => now(),
                        ]);
                    }

                    Log::info("Wikipedia match pour {$acteur->nom_complet}", [
                        'uid' => $acteur->uid,
                        'similarity' => $wikiData['similarity_score'] ?? null,
                        'has_photo' => ! empty($wikiData['photo_wikipedia_url']),
                    ]);
                } else {
                    $stats['not_matched']++;

                    Log::warning("Pas de match Wikipedia pour {$acteur->nom_complet}", [
                        'uid' => $acteur->uid,
                    ]);
                }

                // Petit délai pour ne pas surcharger l'API Wikipedia
                usleep(100000); // 100ms

            } catch (\Exception $e) {
                $stats['errors']++;
                Log::error("Erreur enrichissement Wikipedia pour {$acteur->nom_complet}: {$e->getMessage()}");
            }

            $progressBar->advance();
        }

        $progressBar->finish();

        // Affichage des statistiques
        $this->newLine(2);
        $this->info('📊 STATISTIQUES');
        $this->table(
            ['Métrique', 'Valeur', 'Pourcentage'],
            [
                ['Total traités', $stats['total'], '100%'],
                ['✅ Matchés', $stats['matched'], $this->percentage($stats['matched'], $stats['total'])],
                ['📷 Avec photo', $stats['with_photo'], $this->percentage($stats['with_photo'], $stats['total'])],
                ['❌ Non matchés', $stats['not_matched'], $this->percentage($stats['not_matched'], $stats['total'])],
                ['⚠️  Erreurs', $stats['errors'], $this->percentage($stats['errors'], $stats['total'])],
            ]
        );

        if ($dryRun) {
            $this->newLine();
            $this->warn("⚠️  Mode simulation - Aucune modification n'a été enregistrée");
            $this->info('💡 Relancer sans --dry-run pour appliquer les modifications');
        }

        $this->newLine();
        $this->info('✅ Import Wikipedia terminé !');

        return self::SUCCESS;
    }

    /**
     * Calculer un pourcentage
     */
    private function percentage(int $value, int $total): string
    {
        if ($total === 0) {
            return '0%';
        }

        return round(($value / $total) * 100, 1).'%';
    }
}
