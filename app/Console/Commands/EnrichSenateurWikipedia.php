<?php

namespace App\Console\Commands;

use App\Models\Senateur;
use App\Services\WikipediaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class EnrichSenateurWikipedia extends Command
{
    protected $signature = 'enrich:senateurs-wikipedia 
                            {--limit= : Limite du nombre de sénateurs à enrichir}
                            {--fresh : Réinitialiser les données Wikipedia existantes}';

    protected $description = 'Enrichit les profils des sénateurs avec les données Wikipedia';

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
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $fresh = $this->option('fresh');

        $this->info("🏛️  Enrichissement Wikipedia des sénateurs...");
        $this->newLine();

        if ($fresh) {
            $this->warn("⚠️  Mode --fresh : réinitialisation des données Wikipedia...");
            Senateur::query()->update([
                'wikipedia_url' => null,
                'wikipedia_photo' => null,
                'wikipedia_extract' => null,
            ]);
        }

        // Récupérer les sénateurs actifs sans données Wikipedia
        $query = Senateur::query()
            ->where('etat', 'ACTIF')
            ->whereNull('wikipedia_url');

        if ($limit) {
            $query->limit($limit);
            $this->warn("⚠️  Mode TEST : {$limit} sénateurs maximum");
        }

        $senateurs = $query->get();
        $this->info("📊 {$senateurs->count()} sénateur(s) à enrichir");
        $this->newLine();

        $progressBar = $this->output->createProgressBar($senateurs->count());
        $progressBar->start();

        foreach ($senateurs as $senateur) {
            try {
                $this->enrichSenateur($senateur);
            } catch (\Exception $e) {
                $this->errors++;
                if ($this->errors <= 5) {
                    $this->newLine();
                    $this->error("❌ Erreur pour {$senateur->nom_complet} : " . $e->getMessage());
                }
            }

            $progressBar->advance();
            
            // Pause pour ne pas surcharger l'API Wikipedia
            usleep(200000); // 0.2 secondes
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->displaySummary();

        return Command::SUCCESS;
    }

    private function enrichSenateur(Senateur $senateur): void
    {
        // Construire le nom de recherche
        $searchName = $this->buildSearchName($senateur);
        
        // Rechercher sur Wikipedia
        $wikiData = $this->wikipediaService->searchPerson($searchName);

        if (!$wikiData) {
            // Essayer avec des variantes du nom
            $variants = $this->getNameVariants($senateur);
            
            foreach ($variants as $variant) {
                $wikiData = $this->wikipediaService->searchPerson($variant);
                if ($wikiData) {
                    break;
                }
            }
        }

        if (!$wikiData) {
            $this->notFound++;
            return;
        }

        // Mettre à jour le sénateur
        $senateur->update([
            'wikipedia_url' => $wikiData['url'] ?? null,
            'wikipedia_photo' => $wikiData['photo'] ?? null,
            'wikipedia_extract' => $wikiData['extract'] ?? null,
        ]);

        $this->enriched++;
    }

    private function buildSearchName(Senateur $senateur): string
    {
        // Format : "Prénom Nom (sénateur)"
        return trim("{$senateur->prenom_usuel} {$senateur->nom_usuel}") . " (sénateur)";
    }

    private function getNameVariants(Senateur $senateur): array
    {
        $variants = [];

        // Variante 1 : Sans "(sénateur)"
        $variants[] = trim("{$senateur->prenom_usuel} {$senateur->nom_usuel}");

        // Variante 2 : Avec "(homme politique)"
        $variants[] = trim("{$senateur->prenom_usuel} {$senateur->nom_usuel}") . " (homme politique)";

        // Variante 3 : Avec "(femme politique)" si c'est une femme
        if ($senateur->civilite === 'Mme') {
            $variants[] = trim("{$senateur->prenom_usuel} {$senateur->nom_usuel}") . " (femme politique)";
        }

        // Variante 4 : Inversion prénom/nom
        $variants[] = trim("{$senateur->nom_usuel} {$senateur->prenom_usuel}");

        return $variants;
    }

    private function displaySummary(): void
    {
        $this->info('✅ Enrichissement terminé !');
        $this->newLine();
        
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['✓ Sénateurs enrichis', $this->enriched],
                ['⊘ Non trouvés sur Wikipedia', $this->notFound],
                ['⚠ Erreurs', $this->errors],
            ]
        );

        $total = Senateur::whereNotNull('wikipedia_url')->count();
        $totalActifs = Senateur::where('etat', 'ACTIF')->count();
        $coverage = $totalActifs > 0 ? round(($total / $totalActifs) * 100, 1) : 0;

        $this->newLine();
        $this->info("📊 Couverture Wikipedia : {$total}/{$totalActifs} sénateurs ({$coverage}%)");
    }
}

