<?php

namespace App\Console\Commands;

use App\Models\Topic;
use App\Models\Post;
use App\Models\Document;
use Illuminate\Console\Command;

/**
 * Affiche les statistiques de recherche Meilisearch
 * 
 * Usage:
 *   php artisan search:stats
 */
class SearchStatsCommand extends Command
{
    protected $signature = 'search:stats';

    protected $description = 'Display Meilisearch indexing statistics';

    public function handle(): int
    {
        $this->info('📊 Statistiques Meilisearch');
        $this->newLine();

        try {
            $topicsCount = Topic::count();
            $topicsSearchable = Topic::where('status', 'published')->count();
            
            $postsCount = Post::count();
            $postsSearchable = Post::where('is_hidden', false)->whereNull('deleted_at')->count();
            
            $documentsCount = Document::count();
            $documentsSearchable = Document::where('is_public', true)
                ->where('status', 'verified')
                ->whereNull('deleted_at')
                ->count();

            $this->table(
                ['Model', 'Total', 'Indexable', 'Pourcentage'],
                [
                    [
                        'Topics',
                        $topicsCount,
                        $topicsSearchable,
                        $topicsCount > 0 ? round(($topicsSearchable / $topicsCount) * 100, 1) . '%' : 'N/A',
                    ],
                    [
                        'Posts',
                        $postsCount,
                        $postsSearchable,
                        $postsCount > 0 ? round(($postsSearchable / $postsCount) * 100, 1) . '%' : 'N/A',
                    ],
                    [
                        'Documents',
                        $documentsCount,
                        $documentsSearchable,
                        $documentsCount > 0 ? round(($documentsSearchable / $documentsCount) * 100, 1) . '%' : 'N/A',
                    ],
                    [
                        'TOTAL',
                        $topicsCount + $postsCount + $documentsCount,
                        $topicsSearchable + $postsSearchable + $documentsSearchable,
                        '-',
                    ],
                ]
            );

            $this->newLine();
            $this->info('💡 Pour importer les données:');
            $this->line('   php artisan search:import');
            $this->line('   php artisan search:import --fresh  (réinitialiser)');
            $this->newLine();

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Erreur: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}

