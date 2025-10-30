<?php

namespace App\Console\Commands;

use App\Services\LegislationService;
use App\Models\PropositionLoi;
use Illuminate\Console\Command;

/**
 * Commande pour importer les propositions de loi depuis l'Assemblée et le Sénat
 * 
 * Usage:
 *   php artisan legislation:import --source=both --limit=50
 *   php artisan legislation:import --source=assemblee --recent
 */
class ImportLegislationCommand extends Command
{
    protected $signature = 'legislation:import
                            {--source=both : Source (assemblee, senat, both)}
                            {--limit=50 : Nombre de propositions à importer}
                            {--recent : Importer uniquement les propositions récentes (30 derniers jours)}
                            {--force : Forcer la réimportation}';

    protected $description = 'Importe les propositions de loi depuis l\'Assemblée nationale et le Sénat';

    public function __construct(
        private LegislationService $legislationService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $source = $this->option('source');
        $limit = (int) $this->option('limit');
        $recent = $this->option('recent');
        $force = $this->option('force');

        $this->info("🏛️  Import des propositions de loi");
        $this->info("Source: {$source} | Limite: {$limit}");
        $this->newLine();

        $filters = [];
        if ($recent) {
            $this->info("📅 Mode: Propositions récentes uniquement");
            $filters['recent'] = true;
        }

        try {
            // Récupérer les propositions depuis data.gouv.fr
            $this->info("📡 Récupération des données depuis l'API...");
            $propositions = $this->legislationService->getPropositionsLoi($source, $limit, $filters);

            if (empty($propositions)) {
                $this->warn("⚠️  Aucune proposition trouvée");
                return self::SUCCESS;
            }

            $this->info("✅ {count($propositions)} propositions récupérées");
            $this->newLine();

            $bar = $this->output->createProgressBar(count($propositions));
            $bar->setFormat('verbose');

            $imported = 0;
            $updated = 0;
            $skipped = 0;
            $errors = 0;

            foreach ($propositions as $propData) {
                try {
                    // Vérifier si existe déjà
                    $existing = PropositionLoi::where('source', $propData['source'])
                        ->where('numero', $propData['numero'])
                        ->where('legislature', $propData['legislature'] ?? 17)
                        ->first();

                    if ($existing && !$force) {
                        $skipped++;
                        $bar->advance();
                        continue;
                    }

                    // Créer ou mettre à jour
                    $data = [
                        'source' => $propData['source'],
                        'legislature' => $propData['legislature'] ?? 17,
                        'numero' => $propData['numero'],
                        'titre' => $propData['titre'],
                        'statut' => $propData['statut'] ?? 'en_cours',
                        'theme' => $propData['theme'] ?? null,
                        'date_depot' => $propData['date_depot'] ?? null,
                        'auteurs' => $propData['auteurs'] ?? [],
                        'url_externe' => $propData['url'] ?? null,
                        'fetched_at' => now(),
                    ];

                    if ($existing) {
                        $existing->update($data);
                        $updated++;
                        $this->line(" ♻️  Mise à jour: {$propData['numero']}");
                    } else {
                        PropositionLoi::create($data);
                        $imported++;
                        $this->line(" ✅ Import: {$propData['numero']} - {$propData['titre']}");
                    }

                    $bar->advance();
                    
                    // Pause pour ne pas surcharger l'API
                    usleep(100000); // 100ms
                } catch (\Exception $e) {
                    $errors++;
                    $this->error(" ❌ Erreur: {$propData['numero']} - {$e->getMessage()}");
                    $bar->advance();
                }
            }

            $bar->finish();
            $this->newLine(2);

            // Résumé
            $this->info('📊 Résumé de l\'import:');
            $this->table(
                ['Statut', 'Nombre'],
                [
                    ['✅ Importées', $imported],
                    ['♻️  Mises à jour', $updated],
                    ['⏭️  Ignorées', $skipped],
                    ['❌ Erreurs', $errors],
                    ['📦 Total traité', count($propositions)],
                ]
            );

            $this->newLine();
            $this->info('✅ Import terminé !');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Erreur fatale: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}

