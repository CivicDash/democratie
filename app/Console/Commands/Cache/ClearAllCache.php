<?php

namespace App\Console\Commands\Cache;

use App\Services\CacheService;
use Illuminate\Console\Command;

class ClearAllCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:clear-civicdash {--force : Ne pas demander de confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vider TOUT le cache CivicDash (vote, budget, modération, documents)';

    /**
     * Execute the console command.
     */
    public function handle(CacheService $cacheService): int
    {
        if (!$this->option('force')) {
            if (!$this->confirm('Êtes-vous sûr de vouloir vider TOUT le cache CivicDash ?')) {
                $this->info('Opération annulée.');
                return self::SUCCESS;
            }
        }

        $this->info('Vidage du cache en cours...');

        // Vote
        $voteCount = $cacheService->forgetPattern(CacheService::PREFIX_VOTE_RESULTS . '*');
        $this->line("✓ Vote : {$voteCount} clé(s)");

        // Budget
        $budgetCount = $cacheService->invalidateBudgetCache();
        $this->line("✓ Budget : {$budgetCount} clé(s)");

        // Modération
        $moderationCount = $cacheService->invalidateModerationCache() ? 1 : 0;
        $this->line("✓ Modération : {$moderationCount} clé(s)");

        // Documents
        $documentsCount = $cacheService->invalidateDocumentCache() ? 1 : 0;
        $this->line("✓ Documents : {$documentsCount} clé(s)");

        // Topics
        $topicsCount = $cacheService->forgetPattern(CacheService::PREFIX_TOPIC_STATS . '*');
        $this->line("✓ Topics : {$topicsCount} clé(s)");

        $total = $voteCount + $budgetCount + $moderationCount + $documentsCount + $topicsCount;
        
        $this->newLine();
        $this->info("✅ Cache CivicDash vidé : {$total} clé(s) supprimée(s) au total");

        return self::SUCCESS;
    }
}
