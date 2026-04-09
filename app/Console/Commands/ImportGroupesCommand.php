<?php

namespace App\Console\Commands;

use App\Models\GroupeParlementaire;
use App\Services\LegislationService;
use Illuminate\Console\Command;

class ImportGroupesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'groupes:import 
                            {--source=assemblee : Source (assemblee ou senat)}
                            {--legislature= : Numéro de législature}
                            {--force : Forcer la réimportation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importe les groupes parlementaires depuis les APIs Assemblée/Sénat';

    /**
     * Execute the console command.
     */
    public function handle(LegislationService $legislationService): int
    {
        $source = $this->option('source');
        $legislature = $this->option('legislature') ?? 17;
        $force = $this->option('force');

        $this->info('🏛️  Import des groupes parlementaires');
        $this->info("Source: {$source}");
        $this->info("Législature: {$legislature}");
        $this->newLine();

        // Vérifier si déjà importé
        $existingCount = GroupeParlementaire::where('source', $source)
            ->where('legislature', $legislature)
            ->count();

        if ($existingCount > 0 && ! $force) {
            $this->warn("⚠️  {$existingCount} groupes déjà présents pour {$source} (législature {$legislature})");

            if (! $this->confirm('Voulez-vous continuer et mettre à jour ?', true)) {
                $this->info('Import annulé');

                return Command::SUCCESS;
            }
        }

        $this->info('📡 Récupération des données depuis l\'API...');

        try {
            $groupesData = $legislationService->getGroupesParlementaires($source, $legislature);

            if (empty($groupesData)) {
                $this->error('❌ Aucun groupe trouvé');

                return Command::FAILURE;
            }

            $this->info("✓ {$this->count($groupesData)} groupes récupérés");
            $this->newLine();

            $bar = $this->output->createProgressBar(count($groupesData));
            $bar->setFormat('verbose');

            $created = 0;
            $updated = 0;

            foreach ($groupesData as $groupeData) {
                $groupe = GroupeParlementaire::updateOrCreate(
                    [
                        'source' => $source,
                        'sigle' => $groupeData['sigle'],
                        'legislature' => $legislature,
                    ],
                    [
                        'uid' => $groupeData['uid'] ?? null,
                        'nom' => $groupeData['nom'],
                        'couleur_hex' => $groupeData['couleur_hex'] ?? '#6B7280',
                        'position_politique' => $groupeData['position_politique'] ?? 'centre',
                        'nombre_membres' => $groupeData['nombre_membres'] ?? 0,
                        'actif' => true,
                    ]
                );

                if ($groupe->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            $this->info('✅ Import terminé !');
            $this->table(
                ['Métrique', 'Valeur'],
                [
                    ['Créés', $created],
                    ['Mis à jour', $updated],
                    ['Total', $created + $updated],
                ]
            );

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Erreur lors de l'import: {$e->getMessage()}");
            $this->error($e->getTraceAsString());

            return Command::FAILURE;
        }
    }
}
