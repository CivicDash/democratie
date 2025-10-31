<?php

namespace App\Console\Commands;

use App\Models\PropositionLoi;
use App\Models\LegalReference;
use App\Models\JurisprudenceLink;
use App\Services\LegifranceService;
use App\Services\TextualReferenceParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncLegalReferences extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'legifrance:sync 
                            {proposition_id? : ID spécifique d\'une proposition}
                            {--all : Synchroniser toutes les propositions}
                            {--force : Forcer la re-synchronisation même si déjà fait}
                            {--limit=10 : Limite de propositions à traiter}';

    /**
     * The console command description.
     */
    protected $description = 'Synchroniser les références juridiques des propositions de loi avec Légifrance';

    private LegifranceService $legifranceService;
    private TextualReferenceParser $parser;

    public function __construct(LegifranceService $legifranceService, TextualReferenceParser $parser)
    {
        parent::__construct();
        $this->legifranceService = $legifranceService;
        $this->parser = $parser;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🏛️  Synchronisation des références juridiques avec Légifrance');
        $this->newLine();

        // Vérifier la disponibilité de l'API
        if (!$this->legifranceService->healthCheck()) {
            $this->error('❌ L\'API Légifrance n\'est pas disponible. Vérifiez vos credentials.');
            return self::FAILURE;
        }

        $this->info('✅ Connexion à l\'API Légifrance OK');
        $this->newLine();

        // Déterminer quelles propositions traiter
        $propositions = $this->getPropositions();

        if ($propositions->isEmpty()) {
            $this->warn('Aucune proposition à synchroniser.');
            return self::SUCCESS;
        }

        $this->info("📊 {$propositions->count()} proposition(s) à traiter");
        $this->newLine();

        // Créer une barre de progression
        $bar = $this->output->createProgressBar($propositions->count());
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');

        $stats = [
            'processed' => 0,
            'references_found' => 0,
            'references_synced' => 0,
            'jurisprudence_found' => 0,
            'errors' => 0,
        ];

        foreach ($propositions as $proposition) {
            $bar->setMessage("Traitement: {$proposition->titre}");
            
            try {
                $result = $this->syncProposition($proposition);
                $stats['processed']++;
                $stats['references_found'] += $result['references'];
                $stats['references_synced'] += $result['synced'];
                $stats['jurisprudence_found'] += $result['jurisprudence'];
            } catch (\Exception $e) {
                $stats['errors']++;
                Log::error('Error syncing proposition', [
                    'proposition_id' => $proposition->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Afficher les statistiques
        $this->displayStats($stats);

        return self::SUCCESS;
    }

    /**
     * Récupérer les propositions à traiter
     */
    private function getPropositions()
    {
        $propositionId = $this->argument('proposition_id');
        $all = $this->option('all');
        $force = $this->option('force');
        $limit = (int) $this->option('limit');

        if ($propositionId) {
            return PropositionLoi::where('id', $propositionId)->get();
        }

        $query = PropositionLoi::query()
            ->whereNotNull('texte_integral')
            ->where('texte_integral', '!=', '');

        if (!$force) {
            // Uniquement les propositions pas encore synchronisées
            $query->whereDoesntHave('legalReferences', function ($q) {
                $q->where('sync_success', true);
            });
        }

        if (!$all) {
            $query->limit($limit);
        }

        return $query->orderBy('date_depot', 'desc')->get();
    }

    /**
     * Synchroniser une proposition
     */
    private function syncProposition(PropositionLoi $proposition): array
    {
        $stats = [
            'references' => 0,
            'synced' => 0,
            'jurisprudence' => 0,
        ];

        // Extraire les références du texte
        $references = $this->parser->parse($proposition->texte_integral);
        $stats['references'] = $references->count();

        if ($references->isEmpty()) {
            return $stats;
        }

        // Traiter chaque référence
        foreach ($references as $refData) {
            $legalRef = $this->createOrUpdateReference($proposition, $refData);

            if ($legalRef) {
                // Enrichir avec Légifrance
                $enriched = $this->enrichReference($legalRef, $refData);
                
                if ($enriched) {
                    $stats['synced']++;
                    
                    // Chercher la jurisprudence
                    $juriCount = $this->syncJurisprudence($legalRef, $refData);
                    $stats['jurisprudence'] += $juriCount;
                }
            }
        }

        return $stats;
    }

    /**
     * Créer ou mettre à jour une référence
     */
    private function createOrUpdateReference(PropositionLoi $proposition, array $refData): ?LegalReference
    {
        return LegalReference::updateOrCreate(
            [
                'proposition_loi_id' => $proposition->id,
                'reference_text' => $refData['reference'],
                'code_name' => $refData['code_name'],
            ],
            [
                'position_start' => $refData['position_start'] ?? null,
                'position_end' => $refData['position_end'] ?? null,
                'matched_text' => $refData['matched_text'] ?? null,
                'article_type' => $refData['type'] ?? 'unknown',
                'is_range' => $refData['is_range_start'] ?? false,
                'range_start' => $refData['range_start'] ?? null,
                'range_end' => $refData['range_end'] ?? null,
            ]
        );
    }

    /**
     * Enrichir une référence avec les données Légifrance
     */
    private function enrichReference(LegalReference $legalRef, array $refData): bool
    {
        try {
            // Chercher l'article sur Légifrance
            $article = $this->legifranceService->searchArticle(
                $refData['reference'],
                $refData['code_name']
            );

            if (!$article) {
                $legalRef->markSynced(false, 'Article non trouvé sur Légifrance');
                return false;
            }

            // Récupérer les détails complets
            $details = $this->legifranceService->getArticleDetails($article['id']);

            $legalRef->update([
                'legifrance_id' => $article['id'],
                'article_current_text' => $details ?? $article,
                'context_description' => $article['titre'] ?? null,
            ]);

            $legalRef->markSynced(true);
            
            return true;
        } catch (\Exception $e) {
            $legalRef->markSynced(false, $e->getMessage());
            return false;
        }
    }

    /**
     * Synchroniser la jurisprudence liée
     */
    private function syncJurisprudence(LegalReference $legalRef, array $refData): int
    {
        try {
            $jurisprudences = $this->legifranceService->findJurisprudence(
                $refData['reference'],
                $refData['code_name'],
                5 // Limite à 5 décisions
            );

            $count = 0;

            foreach ($jurisprudences as $juri) {
                // Calculer un score de pertinence basique
                $relevance = $this->calculateRelevance($juri, $refData);

                JurisprudenceLink::updateOrCreate(
                    [
                        'legal_reference_id' => $legalRef->id,
                        'legifrance_juri_id' => $juri['id'],
                    ],
                    [
                        'external_url' => $juri['url'] ?? null,
                        'jurisdiction' => $this->extractJurisdiction($juri),
                        'date_decision' => $juri['date'] ?? now(),
                        'decision_number' => $juri['numero'] ?? null,
                        'title' => $juri['titre'] ?? 'Sans titre',
                        'summary' => $juri['sommaire'] ?? null,
                        'themes' => $juri['themes'] ?? [],
                        'relevance_score' => $relevance,
                        'decision_type' => $juri['nature'] ?? 'autre',
                    ]
                );

                $count++;
            }

            // Mettre à jour le compteur
            $legalRef->update(['jurisprudence_count' => $count]);

            return $count;
        } catch (\Exception $e) {
            Log::warning('Error syncing jurisprudence', [
                'legal_reference_id' => $legalRef->id,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Calculer un score de pertinence basique
     */
    private function calculateRelevance(array $juri, array $refData): int
    {
        $score = 50; // Base

        // Bonus si récent
        if (isset($juri['date'])) {
            $years = now()->diffInYears($juri['date']);
            $score += max(0, 20 - $years * 2);
        }

        // Bonus si haute juridiction
        $jurisdiction = $this->extractJurisdiction($juri);
        if (in_array($jurisdiction, ['CE', 'CC', 'Cass.Civ', 'Cass.Crim'])) {
            $score += 15;
        }

        return min(100, $score);
    }

    /**
     * Extraire la juridiction depuis les données
     */
    private function extractJurisdiction(array $juri): string
    {
        return $juri['juridiction'] ?? $juri['origine'] ?? 'Inconnue';
    }

    /**
     * Afficher les statistiques
     */
    private function displayStats(array $stats): void
    {
        $this->info('📊 Statistiques de synchronisation :');
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Propositions traitées', $stats['processed']],
                ['Références trouvées', $stats['references_found']],
                ['Références synchronisées', $stats['references_synced']],
                ['Jurisprudences trouvées', $stats['jurisprudence_found']],
                ['Erreurs', $stats['errors']],
            ]
        );

        if ($stats['errors'] > 0) {
            $this->warn("⚠️  {$stats['errors']} erreur(s) rencontrée(s). Consultez les logs.");
        }

        $this->info('✅ Synchronisation terminée !');
    }
}
