<?php

namespace App\Console\Commands\Cache;

use App\Services\CacheService;
use Illuminate\Console\Command;

class ClearVoteCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:clear-vote {topic_id? : ID du topic (optionnel)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vider le cache des résultats de vote';

    /**
     * Execute the console command.
     */
    public function handle(CacheService $cacheService): int
    {
        $topicId = $this->argument('topic_id');

        if ($topicId) {
            // Vider le cache d'un topic spécifique
            if ($cacheService->invalidateVoteResults((int) $topicId)) {
                $this->info("Cache des résultats de vote vidé pour le topic #{$topicId}");
            } else {
                $this->warn("Aucun cache trouvé pour le topic #{$topicId}");
            }
        } else {
            // Vider tout le cache de vote
            $count = $cacheService->forgetPattern(CacheService::PREFIX_VOTE_RESULTS . '*');
            $this->info("Cache de vote vidé : {$count} clé(s) supprimée(s)");
        }

        return self::SUCCESS;
    }
}
