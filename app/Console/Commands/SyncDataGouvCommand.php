<?php

namespace App\Console\Commands;

use App\Services\DataGouvService;
use Illuminate\Console\Command;

/**
 * Commande pour synchroniser les données depuis data.gouv.fr
 * À exécuter quotidiennement via le scheduler
 * 
 * Usage:
 *   php artisan datagouv:sync
 *   php artisan datagouv:sync --type=budgets
 */
class SyncDataGouvCommand extends Command
{
    protected $signature = 'datagouv:sync
                            {--type= : Type de données à synchroniser (budgets, all)}
                            {--force : Forcer la synchronisation même si données récentes}';

    protected $description = 'Synchronise les données depuis data.gouv.fr';

    public function __construct(
        private DataGouvService $dataGouvService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $type = $this->option('type') ?? 'all';
        $force = $this->option('force');

        $this->info('🔄 Synchronisation des données data.gouv.fr');
        $this->newLine();

        $synced = 0;
        $errors = 0;

        // Synchroniser les budgets
        if ($type === 'budgets' || $type === 'all') {
            $this->info('📊 Synchronisation des budgets...');
            [$budgetsSynced, $budgetsErrors] = $this->syncBudgets($force);
            $synced += $budgetsSynced;
            $errors += $budgetsErrors;
        }

        // Ajouter d'autres types de données ici (élections, propositions de loi, etc.)

        $this->newLine();
        $this->info("✅ Synchronisation terminée: {$synced} mises à jour, {$errors} erreurs");

        return self::SUCCESS;
    }

    private function syncBudgets(bool $force): array
    {
        $synced = 0;
        $errors = 0;

        try {
            // Récupérer toutes les communes en cache
            $communes = \App\Models\CommuneBudget::select('code_insee')
                ->distinct()
                ->pluck('code_insee');

            if ($communes->isEmpty()) {
                $this->warn('⚠️  Aucune commune en cache');
                return [$synced, $errors];
            }

            $bar = $this->output->createProgressBar($communes->count());

            foreach ($communes as $code) {
                try {
                    // Vérifier si les données sont périmées
                    $latest = \App\Models\CommuneBudget::where('code_insee', $code)
                        ->orderBy('fetched_at', 'desc')
                        ->first();

                    if (!$force && $latest && $latest->fetched_at > now()->subDays(30)) {
                        // Données récentes, on skip
                        $bar->advance();
                        continue;
                    }

                    // Invalider le cache et re-fetch
                    \Cache::forget("budget:commune:{$code}:" . date('Y'));
                    
                    $budget = app(\App\Services\BudgetTerritorialService::class)
                        ->getCommuneBudget($code);

                    if ($budget) {
                        $synced++;
                    }

                    $bar->advance();
                    usleep(100000); // 100ms pause
                } catch (\Exception $e) {
                    $errors++;
                    $this->error(" ❌ {$code}: {$e->getMessage()}");
                    $bar->advance();
                }
            }

            $bar->finish();
            $this->newLine();
        } catch (\Exception $e) {
            $this->error("❌ Erreur lors de la synchronisation des budgets: {$e->getMessage()}");
            $errors++;
        }

        return [$synced, $errors];
    }
}

