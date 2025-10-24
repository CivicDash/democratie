<?php

namespace App\Console\Commands\Cache;

use App\Services\CacheService;
use Illuminate\Console\Command;

class ClearBudgetCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:clear-budget';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vider tout le cache budget (stats, allocations, ranking)';

    /**
     * Execute the console command.
     */
    public function handle(CacheService $cacheService): int
    {
        $count = $cacheService->invalidateBudgetCache();
        
        $this->info("Cache budget vidé : {$count} clé(s) supprimée(s)");
        $this->comment('- Stats budget');
        $this->comment('- Allocations moyennes');
        $this->comment('- Ranking des secteurs');
        $this->comment('- Allocations utilisateurs');

        return self::SUCCESS;
    }
}
