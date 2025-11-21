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
        $this->info("🔍 Enrichissement Wikipedia des sénateurs...");
        $this->newLine();

        $limit = (int) $this->option('limit');
        $force = $this->option('force');

        // Query
        $query = Senateur::where('etat', 'ACTIF');
        
        if (!$force) {
            $query->whereNull('wikipedia_url');
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        $senateurs = $query->get();
        $total = $senateurs->count();

        if ($total === 0) {
            $this->warn('Aucun sénateur à enrichir.');
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
        // Construire le titre Wikipedia
        $searchTerm = "{$senateur->prenom_usuel} {$senateur->nom_usuel} sénateur";
        
        // Rechercher sur Wikipedia
        $wikiData = $this->wikipediaService->searchPerson($searchTerm);

        if (!$wikiData || !isset($wikiData['url'])) {
            $this->notFound++;
            return;
        }

        // Mettre à jour le sénateur
        $senateur->update([
            'wikipedia_url' => $wikiData['url'],
            'wikipedia_photo' => $wikiData['photo'] ?? null,
            'wikipedia_extract' => $wikiData['extract'] ?? null,
            'wikipedia_last_sync' => now(),
        ]);

        $this->enriched++;
    }
}

