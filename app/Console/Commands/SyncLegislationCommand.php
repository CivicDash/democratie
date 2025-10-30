<?php

namespace App\Console\Commands;

use App\Services\LegislationService;
use Illuminate\Console\Command;

/**
 * Commande pour synchroniser les données législatives
 * 
 * Usage:
 *   php artisan legislation:sync
 *   php artisan legislation:sync --agenda-only
 */
class SyncLegislationCommand extends Command
{
    protected $signature = 'legislation:sync
                            {--agenda-only : Synchroniser uniquement l\'agenda}
                            {--propositions-only : Synchroniser uniquement les propositions}';

    protected $description = 'Synchronise les données législatives (agenda, propositions, votes)';

    public function __construct(
        private LegislationService $legislationService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('🔄 Synchronisation des données législatives');
        $this->newLine();

        $agendaOnly = $this->option('agenda-only');
        $propositionsOnly = $this->option('propositions-only');

        try {
            // Synchroniser l'agenda
            if (!$propositionsOnly) {
                $this->info('📅 Synchronisation de l\'agenda...');
                $this->syncAgenda();
            }

            // Synchroniser les propositions
            if (!$agendaOnly) {
                $this->info('📜 Synchronisation des propositions...');
                $this->call('legislation:import', [
                    '--source' => 'both',
                    '--recent' => true,
                    '--force' => true,
                ]);
            }

            $this->newLine();
            $this->info('✅ Synchronisation terminée !');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Erreur: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    private function syncAgenda(): void
    {
        $dateDebut = new \DateTime();
        $dateFin = (clone $dateDebut)->modify('+30 days');

        $agenda = $this->legislationService->getAgendaLegislatif('both', $dateDebut, $dateFin);

        $total = 0;
        foreach (['assemblee', 'senat'] as $source) {
            if (isset($agenda[$source])) {
                $count = count($agenda[$source]);
                $total += $count;
                $this->line("  {$source}: {$count} séances");
            }
        }

        $this->info("  ✅ {$total} séances synchronisées");
    }
}

