<?php

namespace App\Console\Commands;

use App\Models\Topic;
use App\Models\Post;
use App\Models\Document;
use Illuminate\Console\Command;

/**
 * Commande pour importer les données dans Meilisearch
 * 
 * Usage:
 *   php artisan search:import
 *   php artisan search:import --model=Topic
 *   php artisan search:import --fresh
 */
class ImportSearchDataCommand extends Command
{
    protected $signature = 'search:import
                            {--model= : Model to import (Topic, Post, Document, all)}
                            {--fresh : Flush existing data before import}';

    protected $description = 'Import data into Meilisearch for full-text search';

    public function handle(): int
    {
        $model = $this->option('model') ?: 'all';
        $fresh = $this->option('fresh');

        $this->info('🔍 Import des données dans Meilisearch');
        $this->newLine();

        $models = match(strtolower($model)) {
            'topic' => ['Topic'],
            'post' => ['Post'],
            'document' => ['Document'],
            'all' => ['Topic', 'Post', 'Document'],
            default => ['Topic', 'Post', 'Document'],
        };

        foreach ($models as $modelName) {
            $this->importModel($modelName, $fresh);
        }

        $this->newLine();
        $this->info('✅ Import terminé !');
        $this->newLine();

        // Afficher les stats
        $this->call('search:stats');

        return self::SUCCESS;
    }

    private function importModel(string $modelName, bool $fresh): void
    {
        $modelClass = "App\\Models\\{$modelName}";
        
        if (!class_exists($modelClass)) {
            $this->error("❌ Model {$modelName} n'existe pas");
            return;
        }

        $this->info("📦 Import du model: {$modelName}");

        try {
            // Flush si demandé
            if ($fresh) {
                $this->warn("  🗑️  Suppression des données existantes...");
                $this->call('scout:flush', ['model' => $modelClass]);
            }

            // Count total
            $total = $modelClass::count();
            
            if ($total === 0) {
                $this->warn("  ⚠️  Aucune donnée à importer");
                return;
            }

            $this->line("  📊 {$total} entrée(s) à importer");

            // Import
            $bar = $this->output->createProgressBar($total);
            $bar->setFormat('  [%bar%] %percent:3s%% (%current%/%max%) %elapsed%');
            $bar->start();

            // Import par chunks
            $modelClass::chunk(100, function ($items) use ($bar) {
                foreach ($items as $item) {
                    if (method_exists($item, 'shouldBeSearchable') && !$item->shouldBeSearchable()) {
                        continue;
                    }
                    $item->searchable();
                    $bar->advance();
                }
            });

            $bar->finish();
            $this->newLine();
            $this->info("  ✅ {$modelName} importé avec succès");
            $this->newLine();
        } catch (\Exception $e) {
            $this->error("  ❌ Erreur lors de l'import: {$e->getMessage()}");
        }
    }
}

