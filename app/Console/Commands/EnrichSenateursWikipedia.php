<?php

namespace App\Console\Commands;

use App\Models\Senateur;
use App\Services\WikipediaService;
use Illuminate\Console\Command;

class EnrichSenateursWikipedia extends Command
{
    protected $signature = 'enrich:senateurs-wikipedia 
                            {--limit=10 : Nombre de sénateurs à enrichir (0 = tous)}
                            {--force : Forcer la mise à jour même si déjà enrichi}';

    protected $description = 'Enrichit les profils des sénateurs avec les données Wikipedia (photo, URL, extrait)';

    private WikipediaService $wikipediaService;

    private int $enriched = 0;

    private int $notFound = 0;

    private int $errors = 0;

    public function __construct(WikipediaService $wikipediaService)
    {
        parent::__construct();
        $this->wikipediaService = $wikipediaService;
    }

    public function handle(): int
    {
        $this->info('🔍 Enrichissement Wikipedia des sénateurs...');
        $this->newLine();

        $limit = (int) $this->option('limit');
        $force = $this->option('force');

        // Vérifier si la table senateurs_wikipedia existe
        if (! \Schema::hasTable('senateurs_wikipedia')) {
            $this->warn('⚠️ La table senateurs_wikipedia n\'existe pas.');
            $this->info('Création de la table...');

            \Schema::create('senateurs_wikipedia', function ($table) {
                $table->string('senateur_matricule', 20)->primary();
                $table->string('wikipedia_url', 500)->nullable();
                $table->string('photo_wikipedia_url', 500)->nullable();
                $table->text('wikipedia_extract')->nullable();
                $table->timestamp('wikipedia_last_sync')->nullable();
                $table->timestamps();
            });

            $this->info('✅ Table créée.');
        }

        // Query - on récupère les sénateurs actifs
        $query = Senateur::where('etat', 'ACTIF');

        if (! $force) {
            // Vérifier dans la table annexe si déjà enrichi
            $enrichedMatricules = \DB::table('senateurs_wikipedia')
                ->whereNotNull('wikipedia_url')
                ->pluck('senateur_matricule')
                ->toArray();

            if (! empty($enrichedMatricules)) {
                $query->whereNotIn('matricule', $enrichedMatricules);
            }
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        $senateurs = $query->get();
        $total = $senateurs->count();

        if ($total === 0) {
            $this->warn('Aucun sénateur à enrichir.');
            $this->info('💡 Utilisez --force pour forcer la mise à jour des sénateurs déjà enrichis.');

            return Command::SUCCESS;
        }

        $this->info("📊 {$total} sénateurs à traiter");
        $this->newLine();

        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        foreach ($senateurs as $senateur) {
            try {
                $this->enrichSenateur($senateur);
            } catch (\Exception $e) {
                $this->errors++;
                $this->error("\n❌ Erreur pour {$senateur->nom_complet}: {$e->getMessage()}");
            }

            $progressBar->advance();
            usleep(100000); // 100ms entre chaque requête pour ne pas surcharger Wikipedia
        }

        $progressBar->finish();
        $this->newLine(2);

        // Résumé
        $this->info("✅ Enrichis : {$this->enriched}");
        $this->warn("⚠️  Non trouvés : {$this->notFound}");
        if ($this->errors > 0) {
            $this->error("❌ Erreurs : {$this->errors}");
        }

        return Command::SUCCESS;
    }

    private function enrichSenateur(Senateur $senateur): void
    {
        // Rechercher sur Wikipedia
        $searchTerm = str_replace(' ', '_', "{$senateur->prenom_usuel}_{$senateur->nom_usuel}");

        // Essayer d'abord avec l'API summary directe
        $wikiData = $this->wikipediaService->getPageSummary($searchTerm);

        // Si pas trouvé, essayer avec la recherche
        if (! $wikiData) {
            $wikiData = $this->wikipediaService->searchByName(
                $senateur->nom_usuel,
                $senateur->prenom_usuel
            );
        }

        if (! $wikiData || ! isset($wikiData['wikipedia_url'])) {
            $this->notFound++;

            return;
        }

        // Insérer ou mettre à jour dans la table annexe senateurs_wikipedia
        \DB::table('senateurs_wikipedia')->updateOrInsert(
            ['senateur_matricule' => $senateur->id],
            [
                'wikipedia_url' => $wikiData['wikipedia_url'],
                'photo_wikipedia_url' => $wikiData['thumbnail'] ?? $wikiData['photo_wikipedia_url'] ?? null,
                'wikipedia_extract' => $wikiData['extract'] ?? $wikiData['wikipedia_extract'] ?? null,
                'wikipedia_last_sync' => now(),
                'updated_at' => now(),
            ]
        );

        $this->enriched++;
    }
}
