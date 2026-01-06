<?php

namespace App\Console\Commands;

use App\Models\CommuneBudget;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Import des budgets communaux depuis l'API OFGL (data.ofgl.fr)
 * 
 * Source: https://data.ofgl.fr/explore/dataset/ofgl-base-communes-consolidee
 * Documentation API: https://help.opendatasoft.com/apis/ods-explore-v2/
 */
class ImportOFGLBudgets extends Command
{
    protected $signature = 'import:ofgl-budgets 
                            {--annee= : Année spécifique (2017-2024)}
                            {--commune= : Code INSEE d\'une commune spécifique}
                            {--departement= : Code département (ex: 75, 13)}
                            {--limit=100 : Limite de records par batch (max 100 pour l\'API OFGL)}
                            {--force : Forcer la mise à jour même si existant}';

    protected $description = 'Importe les budgets des communes depuis l\'API OFGL (Observatoire des Finances et de la Gestion Publique Locales)';

    private const API_BASE_URL = 'https://data.ofgl.fr/api/explore/v2.1/catalog/datasets/ofgl-base-communes-consolidee/records';
    
    // Mapping des agrégats OFGL vers nos colonnes
    private const AGREGAT_MAPPING = [
        'Recettes de fonctionnement' => 'recettes_fonctionnement',
        'Dépenses de fonctionnement' => 'depenses_fonctionnement',
        'Recettes d\'investissement' => 'recettes_investissement',
        'Dépenses d\'investissement' => 'depenses_investissement',
        'Encours de dette' => 'encours_dette',
        'Annuité de la dette' => 'annuite_dette',
        'Epargne brute' => 'epargne_brute',
        'Impôts locaux' => 'impots_locaux',
        'Frais de personnel' => 'charges_personnel',
        'Achats et charges externes' => 'achats_services',
        'Dotation globale de fonctionnement' => 'dotations_subventions', // Principal composant
    ];

    private int $importedCount = 0;
    private int $updatedCount = 0;
    private int $skippedCount = 0;

    public function handle(): int
    {
        $annee = $this->option('annee');
        $commune = $this->option('commune');
        $departement = $this->option('departement');
        $limit = (int) $this->option('limit');
        $force = $this->option('force');

        $this->info('📊 Import des budgets communaux depuis OFGL');
        $this->info('   Source: https://data.ofgl.fr');
        $this->newLine();

        // Mode test avec une commune spécifique
        if ($commune) {
            return $this->importCommune($commune, $annee, $force);
        }

        // Import par département
        if ($departement) {
            return $this->importDepartement($departement, $annee, $limit, $force);
        }

        // Import complet avec pagination
        return $this->importAll($annee, $limit, $force);
    }

    /**
     * Construire le filtre WHERE pour l'année (exer est de type date dans l'API)
     */
    private function buildYearFilter(?string $annee): string
    {
        if ($annee) {
            return " AND date_format(exer, 'YYYY')='{$annee}'";
        }
        return '';
    }

    /**
     * Import d'une commune spécifique (mode test/PoC)
     */
    private function importCommune(string $inseeCode, ?string $annee, bool $force): int
    {
        $this->info("🏘️ Import de la commune {$inseeCode}...");

        $where = "com_code='{$inseeCode}'" . $this->buildYearFilter($annee);

        $records = $this->fetchRecords($where, 100);

        if (empty($records)) {
            $this->warn("⚠️ Aucune donnée trouvée pour la commune {$inseeCode}");
            return Command::FAILURE;
        }

        $this->processRecords($records, $force);
        $this->displayStats();

        return Command::SUCCESS;
    }

    /**
     * Import par département - Itération par commune pour éviter la limite API de 10k
     */
    private function importDepartement(string $deptCode, ?string $annee, int $limit, bool $force): int
    {
        $this->info("🗺️ Import du département {$deptCode}...");

        // Utiliser 2024 par défaut (dernière année disponible)
        $anneeFilter = $annee ?? '2024';

        // Récupérer la liste des codes INSEE des communes du département
        $this->info("   📋 Récupération de la liste des communes...");
        $communesCodes = $this->getCommunesCodes($deptCode, $anneeFilter);
        
        if (empty($communesCodes)) {
            $this->warn("⚠️ Aucune commune trouvée pour le département {$deptCode}");
            return Command::FAILURE;
        }

        $totalCommunes = count($communesCodes);
        $this->info("   📍 {$totalCommunes} communes à traiter pour l'année {$anneeFilter}");

        $bar = $this->output->createProgressBar($totalCommunes);

        foreach ($communesCodes as $inseeCode) {
            $where = "com_code='{$inseeCode}'" . $this->buildYearFilter($annee);
            $records = $this->fetchRecords($where, 100); // Max 100, mais une commune a ~50 agrégats

            if (!empty($records)) {
                $this->processRecords($records, $force);
            }

            $bar->advance();

            // Pause pour éviter le rate limiting
            usleep(50000); // 50ms
        }

        $bar->finish();
        $this->newLine(2);
        $this->displayStats();

        return Command::SUCCESS;
    }

    /**
     * Récupérer la liste des codes INSEE des communes d'un département
     */
    private function getCommunesCodes(string $deptCode, string $annee): array
    {
        $codes = [];
        $offset = 0;
        $limit = 100;

        while (true) {
            try {
                $response = Http::timeout(60)->get(self::API_BASE_URL, [
                    'select' => 'com_code',
                    'where' => "dep_code='{$deptCode}' AND date_format(exer, 'YYYY')='{$annee}'",
                    'group_by' => 'com_code',
                    'limit' => $limit,
                    'offset' => $offset,
                ]);

                if (!$response->successful()) {
                    break;
                }

                $results = $response->json()['results'] ?? [];
                if (empty($results)) {
                    break;
                }

                foreach ($results as $row) {
                    $codes[] = $row['com_code'];
                }

                $offset += $limit;

                // L'API limite à 10k, on s'arrête avant
                if ($offset >= 9900) {
                    break;
                }

            } catch (\Exception $e) {
                Log::error("OFGL getCommunesCodes Error: " . $e->getMessage());
                break;
            }
        }

        return array_unique($codes);
    }

    /**
     * Import complet de toutes les communes
     */
    private function importAll(?string $annee, int $limit, bool $force): int
    {
        $this->info("🇫🇷 Import complet de toutes les communes...");
        $this->warn("⚠️ Cette opération peut prendre plusieurs heures.");

        // Si --force est utilisé, on ne demande pas de confirmation
        if (!$force && !$this->confirm('Continuer ?')) {
            return Command::FAILURE;
        }

        // Pour le PoC, limitons à 2023 par défaut
        $anneeFilter = $annee ?? '2023';
        $this->info("   📅 Année: {$anneeFilter}");

        $offset = 0;
        $batchCount = 0;

        while (true) {
            $where = "date_format(exer, 'YYYY')='{$anneeFilter}'";
            $records = $this->fetchRecords($where, $limit, $offset);

            if (empty($records)) {
                break;
            }

            $this->processRecords($records, $force);
            
            $batchCount++;
            $offset += $limit;

            if ($batchCount % 10 === 0) {
                $this->info("   📦 Batch {$batchCount} terminé ({$this->importedCount} importées, {$this->updatedCount} mises à jour)");
            }

            // Pause pour éviter le rate limiting
            usleep(200000); // 200ms
        }

        $this->newLine();
        $this->displayStats();

        return Command::SUCCESS;
    }

    /**
     * Récupérer des enregistrements depuis l'API OFGL
     */
    private function fetchRecords(string $where, int $limit = 100, int $offset = 0): array
    {
        try {
            $response = Http::timeout(120)->get(self::API_BASE_URL, [
                'where' => $where,
                'limit' => $limit,
                'offset' => $offset,
                'order_by' => 'com_code,exer,agregat',
            ]);

            if ($response->successful()) {
                return $response->json()['results'] ?? [];
            }

            $this->error("❌ Erreur API: " . $response->status());
            Log::error("OFGL API Error: " . $response->body());
            return [];

        } catch (\Exception $e) {
            $this->error("❌ Erreur: " . $e->getMessage());
            Log::error("OFGL Fetch Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Traiter les enregistrements et les regrouper par commune/année
     */
    private function processRecords(array $records, bool $force): void
    {
        // Regrouper par commune et année
        $grouped = [];
        
        foreach ($records as $record) {
            $key = $record['com_code'] . '_' . $record['exer'];
            
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'insee_code' => $record['com_code'],
                    'annee' => (int) $record['exer'],
                    'population' => $record['ptot'] ?? null,
                    'com_name' => $record['com_name'] ?? null,
                    'data' => [],
                ];
            }
            
            $agregat = $record['agregat'];
            if (isset(self::AGREGAT_MAPPING[$agregat])) {
                $column = self::AGREGAT_MAPPING[$agregat];
                $grouped[$key]['data'][$column] = $record['montant'];
            }
        }

        // Insérer/mettre à jour en base
        foreach ($grouped as $item) {
            $this->upsertBudget($item, $force);
        }
    }

    /**
     * Insérer ou mettre à jour un budget
     */
    private function upsertBudget(array $item, bool $force): void
    {
        $inseeCode = $item['insee_code'];
        $annee = $item['annee'];
        $data = $item['data'];

        // Vérifier si existe déjà
        $existing = CommuneBudget::where('insee_code', $inseeCode)
            ->where('annee', $annee)
            ->first();

        if ($existing && !$force) {
            $this->skippedCount++;
            return;
        }

        // Calculer euros_par_habitant
        $population = $item['population'];
        $eurosParHab = null;
        if ($population > 0 && isset($data['depenses_fonctionnement'])) {
            $eurosParHab = round($data['depenses_fonctionnement'] / $population, 2);
        }

        $budgetData = array_merge($data, [
            'insee_code' => $inseeCode,
            'annee' => $annee,
            'population' => $population,
            'euros_par_habitant' => $eurosParHab,
            'source' => 'ofgl',
            'updated_at' => now(),
        ]);

        if ($existing) {
            $existing->update($budgetData);
            $this->updatedCount++;
        } else {
            $budgetData['created_at'] = now();
            CommuneBudget::create($budgetData);
            $this->importedCount++;
        }
    }

    /**
     * Afficher les statistiques d'import
     */
    private function displayStats(): void
    {
        $this->newLine();
        $this->info("📊 Résultats de l'import :");
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['✅ Nouveaux budgets', $this->importedCount],
                ['🔄 Mises à jour', $this->updatedCount],
                ['⏭️ Ignorés (déjà existants)', $this->skippedCount],
                ['📦 Total traités', $this->importedCount + $this->updatedCount + $this->skippedCount],
            ]
        );
    }
}
