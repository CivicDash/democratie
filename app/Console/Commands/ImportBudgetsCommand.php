<?php

namespace App\Console\Commands;

use App\Services\BudgetTerritorialService;
use App\Services\DataGouvService;
use Illuminate\Console\Command;

/**
 * Commande pour importer les budgets des communes depuis data.gouv.fr
 *
 * Usage:
 *   php artisan datagouv:import-budgets 75056 --year=2024
 *   php artisan datagouv:import-budgets --all --year=2024
 *   php artisan datagouv:import-budgets --top=100 --year=2024
 */
class ImportBudgetsCommand extends Command
{
    protected $signature = 'datagouv:import-budgets
                            {codes?* : Codes INSEE des communes à importer (optionnel)}
                            {--year= : Année du budget (défaut: année en cours)}
                            {--all : Importer toutes les communes disponibles}
                            {--top= : Importer les N plus grandes communes}
                            {--force : Forcer la réimportation même si déjà en cache}';

    protected $description = 'Importe les budgets des communes depuis data.gouv.fr';

    public function __construct(
        private BudgetTerritorialService $budgetService,
        private DataGouvService $dataGouvService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $year = $this->option('year') ?? date('Y');
        $force = $this->option('force');

        $this->info("🚀 Import des budgets communaux pour l'année {$year}");
        $this->newLine();

        // Déterminer quelles communes importer
        $codesToImport = $this->getCodesList();

        if (empty($codesToImport)) {
            $this->error('❌ Aucune commune à importer');

            return self::FAILURE;
        }

        $this->info("📊 {count($codesToImport)} communes à traiter");
        $this->newLine();

        $bar = $this->output->createProgressBar(count($codesToImport));
        $bar->setFormat('verbose');

        $imported = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($codesToImport as $code) {
            try {
                if (! $force) {
                    // Vérifier si déjà en cache
                    $existing = \App\Models\CommuneBudget::where('code_insee', $code)
                        ->where('annee', $year)
                        ->exists();

                    if ($existing) {
                        $skipped++;
                        $bar->advance();

                        continue;
                    }
                }

                // Importer le budget
                $budget = $this->budgetService->getCommuneBudget($code, $year);

                if ($budget) {
                    $imported++;
                    $this->line(" ✅ {$budget['nom_commune']} ({$code})");
                } else {
                    $errors++;
                    $this->warn(" ⚠️  {$code}: Budget introuvable");
                }

                $bar->advance();

                // Pause pour ne pas surcharger l'API
                usleep(100000); // 100ms
            } catch (\Exception $e) {
                $errors++;
                $this->error(" ❌ {$code}: {$e->getMessage()}");
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
                ['✅ Importés', $imported],
                ['⏭️  Ignorés (déjà en cache)', $skipped],
                ['❌ Erreurs', $errors],
                ['📦 Total', count($codesToImport)],
            ]
        );

        return self::SUCCESS;
    }

    private function getCodesList(): array
    {
        // Mode 1: Codes fournis en argument
        $codes = $this->argument('codes');
        if (! empty($codes)) {
            return $codes;
        }

        // Mode 2: --all (toutes les communes du dataset)
        if ($this->option('all')) {
            $this->warn('⚠️  Import complet non recommandé (36 000+ communes)');
            if (! $this->confirm('Êtes-vous sûr de vouloir continuer ?', false)) {
                return [];
            }

            return $this->getAllCommunesCodes();
        }

        // Mode 3: --top=N (N plus grandes communes)
        if ($top = $this->option('top')) {
            return $this->getTopCommunesCodes((int) $top);
        }

        // Mode 4: Liste interactive
        return $this->askForCodes();
    }

    private function getAllCommunesCodes(): array
    {
        // Pour l'instant, retourner un tableau vide
        // À implémenter: parser le CSV complet
        $this->warn('⚠️  Fonctionnalité --all pas encore implémentée');

        return [];
    }

    private function getTopCommunesCodes(int $limit): array
    {
        // Codes INSEE des plus grandes communes de France
        $topCommunes = [
            '75056', // Paris
            '13055', // Marseille
            '69123', // Lyon
            '31555', // Toulouse
            '06088', // Nice
            '44109', // Nantes
            '67482', // Strasbourg
            '34172', // Montpellier
            '33063', // Bordeaux
            '59350', // Lille
            '35238', // Rennes
            '51108', // Reims
            '76540', // Le Havre
            '93066', // Saint-Denis
            '93008', // Aubervilliers
            '93001', // Aulnay-sous-Bois
            '92050', // Nanterre
            '92026', // Courbevoie
            '92012', // Boulogne-Billancourt
            '92073', // Suresnes
            '94028', // Créteil
            '94080', // Vitry-sur-Seine
            '95500', // Pontoise
            '95127', // Cergy
            '78646', // Versailles
            '78551', // Saint-Germain-en-Laye
            '77288', // Meaux
            '77186', // Fontainebleau
            '91228', // Évry
            '91377', // Massy
        ];

        return array_slice($topCommunes, 0, $limit);
    }

    private function askForCodes(): array
    {
        $this->info('💡 Entrez les codes INSEE des communes à importer (séparés par des espaces ou virgules)');
        $this->info('   Exemple: 75056 13055 69123');
        $this->newLine();

        $input = $this->ask('Codes INSEE');

        if (empty($input)) {
            return [];
        }

        // Parser l'input (espaces, virgules, etc.)
        $codes = preg_split('/[\s,;]+/', $input, -1, PREG_SPLIT_NO_EMPTY);

        // Valider les codes (5 chiffres)
        $validCodes = array_filter($codes, fn ($code) => preg_match('/^\d{5}$/', $code));

        if (count($validCodes) !== count($codes)) {
            $this->warn('⚠️  Certains codes invalides ont été ignorés');
        }

        return array_values($validCodes);
    }
}
