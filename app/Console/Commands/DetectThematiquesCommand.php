<?php

namespace App\Console\Commands;

use App\Models\PropositionLoi;
use App\Services\ThematiqueDetectionService;
use Illuminate\Console\Command;

class DetectThematiquesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'thematiques:detect
                            {--all : Détecter pour toutes les propositions sans thématique}
                            {--recalculate : Recalculer pour toutes les propositions}
                            {--id= : ID spécifique d\'une proposition}
                            {--limit=100 : Nombre max de propositions à traiter}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Détecte automatiquement les thématiques des propositions de loi';

    /**
     * Execute the console command.
     */
    public function handle(ThematiqueDetectionService $thematiqueService): int
    {
        $all = $this->option('all');
        $recalculate = $this->option('recalculate');
        $id = $this->option('id');
        $limit = (int) $this->option('limit');

        $this->info("🏷️  Détection automatique des thématiques");
        $this->newLine();

        // Cas 1: Proposition spécifique
        if ($id) {
            return $this->detectForProposition($id, $thematiqueService, $recalculate);
        }

        // Cas 2: Toutes les propositions (recalcul)
        if ($recalculate) {
            return $this->recalculateAll($thematiqueService, $limit);
        }

        // Cas 3: Propositions sans thématique
        if ($all) {
            return $this->detectForAll($thematiqueService, $limit);
        }

        // Par défaut: afficher les statistiques
        return $this->showStatistics($thematiqueService);
    }

    /**
     * Détecte pour une proposition spécifique
     */
    private function detectForProposition(int $id, ThematiqueDetectionService $service, bool $recalculate): int
    {
        $proposition = PropositionLoi::find($id);

        if (!$proposition) {
            $this->error("❌ Proposition {$id} non trouvée");
            return Command::FAILURE;
        }

        $this->info("📄 Proposition: {$proposition->titre}");
        $this->newLine();

        try {
            if ($recalculate) {
                $this->info('🔄 Recalcul des thématiques...');
                $thematiques = $service->recalculer($proposition);
            } else {
                $this->info('🔍 Détection des thématiques...');
                $thematiques = $service->detecter($proposition, false, true);
            }

            if ($thematiques->isEmpty()) {
                $this->warn('⚠️  Aucune thématique détectée');
                return Command::SUCCESS;
            }

            $this->info("✅ {$thematiques->count()} thématique(s) détectée(s):");
            $this->newLine();

            $rows = [];
            foreach ($thematiques as $item) {
                $rows[] = [
                    $item['thematique']->nom,
                    $item['thematique']->code,
                    $item['score'] . '%',
                    $item['est_principal'] ? '⭐ Principal' : 'Secondaire',
                ];
            }

            $this->table(
                ['Thématique', 'Code', 'Score', 'Type'],
                $rows
            );

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Erreur: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    /**
     * Détecte pour toutes les propositions sans thématique
     */
    private function detectForAll(ThematiqueDetectionService $service, int $limit): int
    {
        $propositions = PropositionLoi::whereDoesntHave('thematiques')
            ->orderBy('date_depot', 'desc')
            ->limit($limit)
            ->get();

        if ($propositions->isEmpty()) {
            $this->info('✅ Toutes les propositions ont déjà des thématiques !');
            return Command::SUCCESS;
        }

        $this->info("📦 {$propositions->count()} proposition(s) sans thématique trouvée(s)");
        $this->newLine();

        if (!$this->confirm('Lancer la détection ?', true)) {
            $this->info('Détection annulée');
            return Command::SUCCESS;
        }

        $bar = $this->output->createProgressBar($propositions->count());
        $bar->setFormat('verbose');

        try {
            $stats = $service->detecterBatch($propositions);

            $bar->finish();
            $this->newLine(2);

            $this->info("✅ Détection terminée !");
            $this->table(
                ['Métrique', 'Valeur'],
                [
                    ['Total traité', $stats['total']],
                    ['Avec thématique', $stats['avec_thematique']],
                    ['Sans thématique', $stats['sans_thematique']],
                    ['Erreurs', $stats['erreurs']],
                ]
            );

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Erreur: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    /**
     * Recalcule pour toutes les propositions
     */
    private function recalculateAll(ThematiqueDetectionService $service, int $limit): int
    {
        $propositions = PropositionLoi::orderBy('date_depot', 'desc')
            ->limit($limit)
            ->get();

        $this->warn("⚠️  Recalcul de {$propositions->count()} proposition(s)");
        $this->warn("Les thématiques auto-détectées existantes seront remplacées");
        $this->newLine();

        if (!$this->confirm('Confirmer le recalcul ?', false)) {
            $this->info('Recalcul annulé');
            return Command::SUCCESS;
        }

        $bar = $this->output->createProgressBar($propositions->count());
        $bar->setFormat('verbose');

        try {
            $recalculated = 0;

            foreach ($propositions as $proposition) {
                $service->recalculer($proposition);
                $recalculated++;
                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            $this->info("✅ Recalcul terminé !");
            $this->info("📊 {$recalculated} proposition(s) recalculée(s)");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Erreur: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    /**
     * Affiche les statistiques
     */
    private function showStatistics(ThematiqueDetectionService $service): int
    {
        try {
            $stats = $service->getStatistiques();

            $this->info("📊 Statistiques de détection");
            $this->newLine();

            $this->table(
                ['Métrique', 'Valeur'],
                [
                    ['Total propositions', $stats['total_propositions']],
                    ['Avec thématique', $stats['avec_thematique']],
                    ['Sans thématique', $stats['sans_thematique']],
                    ['Auto-détectées', $stats['auto_detectees']],
                    ['Manuelles', $stats['manuelles']],
                    ['Taux de couverture', $stats['taux_couverture'] . '%'],
                ]
            );

            $this->newLine();
            $this->info("💡 Commandes disponibles:");
            $this->line("  • php artisan thematiques:detect --all          → Détecter pour les propositions sans thématique");
            $this->line("  • php artisan thematiques:detect --recalculate  → Recalculer toutes les thématiques");
            $this->line("  • php artisan thematiques:detect --id=123       → Détecter pour une proposition spécifique");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Erreur: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}

