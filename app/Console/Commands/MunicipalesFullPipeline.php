<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MunicipalesFullPipeline extends Command
{
    protected $signature = 'municipales:full-pipeline
                            {--tour=all : Tour à traiter (1, 2, ou all)}
                            {--skip-download : Utiliser les fichiers déjà téléchargés}
                            {--dry-run : Simuler sans écrire en base}';

    protected $description = 'Pipeline complet : import candidatures + résultats + transition maires + stats';

    public function handle(): int
    {
        $tour = $this->option('tour');
        $dryRun = $this->option('dry-run');
        $skipDownload = $this->option('skip-download');

        $this->info('Pipeline complet des élections municipales 2026');
        $this->newLine();

        $steps = $this->buildSteps($tour);
        $total = count($steps);

        foreach ($steps as $i => $step) {
            $num = $i + 1;
            $this->newLine();
            $this->info("=== Étape {$num}/{$total} : {$step['label']} ===");
            $this->newLine();

            $args = $step['args'];
            if ($dryRun && isset($args['--dry-run'])) {
                $args['--dry-run'] = true;
            }

            $exitCode = $this->call($step['command'], $args);

            if ($exitCode !== self::SUCCESS) {
                $this->error("L'étape {$num} a échoué ({$step['command']}). Arrêt du pipeline.");

                return self::FAILURE;
            }

            $this->info("Étape {$num}/{$total} terminée.");
        }

        $this->newLine(2);
        $this->info('Pipeline complet terminé avec succès !');

        return self::SUCCESS;
    }

    private function buildSteps(string $tour): array
    {
        $steps = [];

        if ($tour === 'all' || $tour === '1') {
            $steps[] = [
                'label' => 'Import candidatures T1',
                'command' => 'municipales:import-candidatures',
                'args' => ['tour' => 1, '--dry-run' => false],
            ];
            $steps[] = [
                'label' => 'Import résultats T1',
                'command' => 'municipales:import-resultats',
                'args' => ['tour' => 1, '--dry-run' => false],
            ];
            $steps[] = [
                'label' => 'Enrichissement têtes de liste T1',
                'command' => 'municipales:enrich-tetes-liste',
                'args' => ['tour' => 1, '--dry-run' => false],
            ];
        }

        if ($tour === 'all' || $tour === '2') {
            $steps[] = [
                'label' => 'Import candidatures T2',
                'command' => 'municipales:import-candidatures',
                'args' => ['tour' => 2, '--dry-run' => false],
            ];
            $steps[] = [
                'label' => 'Import résultats T2',
                'command' => 'municipales:import-resultats',
                'args' => ['tour' => 2, '--dry-run' => false],
            ];
            $steps[] = [
                'label' => 'Enrichissement têtes de liste T2',
                'command' => 'municipales:enrich-tetes-liste',
                'args' => ['tour' => 2, '--dry-run' => false],
            ];
        }

        $steps[] = [
            'label' => 'Import RNE maires (mise à jour)',
            'command' => 'import:maires-datagouv',
            'args' => [],
        ];

        $steps[] = [
            'label' => 'Transition maires 2020→2026',
            'command' => 'municipales:transition-maires',
            'args' => ['--dry-run' => false],
        ];

        $steps[] = [
            'label' => 'Calcul stats municipales',
            'command' => 'municipales:calculate-stats',
            'args' => [],
        ];

        $steps[] = [
            'label' => 'Recalcul stats villes',
            'command' => 'stats:villes',
            'args' => ['--force' => true],
        ];

        $steps[] = [
            'label' => 'Recalcul stats globales élus',
            'command' => 'calculate:elus-global-stats',
            'args' => ['--force' => true],
        ];

        return $steps;
    }
}
