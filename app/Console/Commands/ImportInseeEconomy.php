<?php

namespace App\Console\Commands;

use App\Models\FranceEconomy;
use App\Models\ImportLog;
use App\Services\InseeApiService;
use Illuminate\Console\Command;

/**
 * Importe les indicateurs économiques depuis l'API INSEE (BDM)
 *
 * Séries BDM confirmées :
 * - 001688527 : Taux de chômage BIT France hors Mayotte (trimestriel, %)
 * - 001764626 : IPC annuel base 2015 (permet de calculer l'inflation)
 * - 010565692 : Évolution du PIB trimestriel (%, série arrêtée 2024-Q1)
 *
 * Les autres indicateurs (PIB en valeur, dette, balance commerciale) viendront
 * de Melodi une fois l'accès validé.
 */
class ImportInseeEconomy extends Command
{
    protected $signature = 'import:insee-economy
                            {--year= : Année spécifique à importer}
                            {--years=5 : Nombre d\'années à importer}
                            {--dry-run : Afficher sans écrire en base}';

    protected $description = 'Importe les indicateurs économiques depuis l\'API INSEE (chômage, inflation, PIB)';

    private const SERIES = [
        '001688527' => 'unemployment_rate',
        '001759970' => 'ipc_monthly',
        '010565692' => 'gdp_growth_rate',
    ];

    public function handle(InseeApiService $insee): int
    {
        $importLog = ImportLog::start('import:insee-economy', 'insee');
        $this->info('💰 Import des indicateurs économiques INSEE (BDM)');

        if (! $insee->isConfigured()) {
            $this->warn('⚠️  Clés INSEE non configurées — utilisation du fallback');

            return $this->handleFallback($importLog);
        }

        $yearsCount = (int) $this->option('years');
        $currentYear = (int) ($this->option('year') ?? date('Y'));
        $startYear = $currentYear - $yearsCount + 1;

        $this->info("   Période : {$startYear} → {$currentYear}");

        $seriesIds = array_keys(self::SERIES);
        $data = $insee->getMultipleSeries($seriesIds, $startYear - 1, $currentYear);

        if (! $data) {
            $this->error('❌ Échec de récupération des séries INSEE');
            $importLog->fail('Échec API INSEE');

            return Command::FAILURE;
        }

        $this->info('   ✓ '.count($data).' séries reçues');

        $yearData = $this->buildYearData($data, $startYear, $currentYear);

        $created = 0;
        $updated = 0;
        $errors = 0;

        foreach ($yearData as $year => $attributes) {
            $this->info("   📅 {$year}...");

            if (empty($attributes)) {
                $this->warn("   ⚠️  Aucune donnée pour {$year}");
                $errors++;

                continue;
            }

            if ($this->option('dry-run')) {
                $this->table(
                    ['Champ', 'Valeur'],
                    collect($attributes)->map(fn ($v, $k) => [$k, $v])->values()->toArray()
                );

                continue;
            }

            $existing = FranceEconomy::forYear($year)->annual()->first();
            if ($existing) {
                $existing->update($attributes);
                $updated++;
                $this->line("   ✏️  Mis à jour {$year}");
            } else {
                FranceEconomy::create(array_merge(['year' => $year, 'quarter' => null], $attributes));
                $created++;
                $this->line("   ✅ Créé {$year}");
            }
        }

        $importLog->finish($created, $updated, 0, $errors);
        $this->newLine();
        $this->info("✅ Terminé — {$created} créés, {$updated} mis à jour, {$errors} erreurs");

        return Command::SUCCESS;
    }

    /**
     * Construit les attributs annuels à partir des séries BDM.
     * - Chômage : trimestriel (YYYY-QN) → on prend Q4 pour l'annuel
     * - IPC mensuel (base 2015) → moyenne annuelle puis glissement = inflation
     * - PIB growth : trimestriel → moyenne annuelle des variations
     */
    private function buildYearData(array $parsedSeries, int $startYear, int $endYear): array
    {
        $yearData = [];

        $ipcObs = $parsedSeries['001759970']['observations'] ?? [];
        $ipcAnnualAvg = $this->computeAnnualAverage($ipcObs);

        for ($year = $startYear; $year <= $endYear; $year++) {
            $attrs = [];

            $unemploymentObs = $parsedSeries['001688527']['observations'] ?? [];
            $q4Key = "{$year}-Q4";
            $q3Key = "{$year}-Q3";
            $q2Key = "{$year}-Q2";
            $q1Key = "{$year}-Q1";
            $uRate = $unemploymentObs[$q4Key]
                ?? $unemploymentObs[$q3Key]
                ?? $unemploymentObs[$q2Key]
                ?? $unemploymentObs[$q1Key]
                ?? null;
            if ($uRate !== null) {
                $attrs['unemployment_rate'] = round($uRate, 2);
            }

            $avgCurrent = $ipcAnnualAvg[$year] ?? null;
            $avgPrevious = $ipcAnnualAvg[$year - 1] ?? null;
            if ($avgCurrent !== null && $avgPrevious !== null && $avgPrevious > 0) {
                $attrs['inflation_rate'] = round(
                    (($avgCurrent - $avgPrevious) / $avgPrevious) * 100,
                    2
                );
            }

            $gdpObs = $parsedSeries['010565692']['observations'] ?? [];
            $gdpQuarters = [];
            foreach ([$q1Key, $q2Key, $q3Key, $q4Key] as $qKey) {
                if (isset($gdpObs[$qKey])) {
                    $gdpQuarters[] = $gdpObs[$qKey];
                }
            }
            if (! empty($gdpQuarters)) {
                $attrs['gdp_growth_rate'] = round(array_sum($gdpQuarters) / count($gdpQuarters), 2);
            }

            if (! empty($attrs)) {
                $yearData[$year] = $attrs;
            }
        }

        return $yearData;
    }

    /**
     * Calcule la moyenne annuelle d'un indice mensuel (YYYY-MM → YYYY).
     */
    private function computeAnnualAverage(array $monthlyObs): array
    {
        $grouped = [];
        foreach ($monthlyObs as $period => $value) {
            $year = (int) substr($period, 0, 4);
            $grouped[$year][] = $value;
        }

        $averages = [];
        foreach ($grouped as $year => $values) {
            $averages[$year] = array_sum($values) / count($values);
        }

        return $averages;
    }

    private function handleFallback(ImportLog $importLog): int
    {
        $this->info('   📦 Import via fallback (données publiques estimées)');

        $currentYear = (int) ($this->option('year') ?? date('Y'));
        $created = 0;
        $updated = 0;

        // Valeurs de repli corrigées le 04/11/2026. PIB en valeur, croissance en volume, dette et
        // ratio : Eurostat (mise à jour du 21/10/2026, comptes en base 2020). Inflation : moyenne
        // annuelle de l'indice des prix de l'Insee (0,9 % en 2025 selon la Cour des comptes, juin 2026).
        // Chômage et balance commerciale : anciennes valeurs, non revérifiées.
        $baseData = [
            2020 => ['gdp_billions_euros' => 2318.3, 'gdp_growth_rate' => -7.4, 'unemployment_rate' => 8.0, 'inflation_rate' => 0.5, 'public_debt_billions_euros' => 2663.9, 'public_debt_gdp_percentage' => 114.9, 'trade_balance_billions_euros' => -65.2, 'gdp_per_capita_euros' => 34230],
            2021 => ['gdp_billions_euros' => 2508.1, 'gdp_growth_rate' => 6.9, 'unemployment_rate' => 7.9, 'inflation_rate' => 1.6, 'public_debt_billions_euros' => 2828.8, 'public_debt_gdp_percentage' => 112.8, 'trade_balance_billions_euros' => -84.7, 'gdp_per_capita_euros' => 36910],
            2022 => ['gdp_billions_euros' => 2654.0, 'gdp_growth_rate' => 2.7, 'unemployment_rate' => 7.3, 'inflation_rate' => 5.2, 'public_debt_billions_euros' => 2955.6, 'public_debt_gdp_percentage' => 111.4, 'trade_balance_billions_euros' => -163.6, 'gdp_per_capita_euros' => 38870],
            2023 => ['gdp_billions_euros' => 2833.8, 'gdp_growth_rate' => 1.6, 'unemployment_rate' => 7.1, 'inflation_rate' => 4.9, 'public_debt_billions_euros' => 3103.2, 'public_debt_gdp_percentage' => 109.5, 'trade_balance_billions_euros' => -99.6, 'gdp_per_capita_euros' => 41340],
            2024 => ['gdp_billions_euros' => 2935.2, 'gdp_growth_rate' => 1.5, 'unemployment_rate' => 7.3, 'inflation_rate' => 2.0, 'public_debt_billions_euros' => 3306.1, 'public_debt_gdp_percentage' => 112.6, 'trade_balance_billions_euros' => -81.2, 'gdp_per_capita_euros' => 42680],
            2025 => ['gdp_billions_euros' => 2991.1, 'gdp_growth_rate' => 0.8, 'unemployment_rate' => 7.5, 'inflation_rate' => 0.9, 'public_debt_billions_euros' => 3460.1, 'public_debt_gdp_percentage' => 115.7, 'trade_balance_billions_euros' => -78.0, 'gdp_per_capita_euros' => 43350],
        ];

        foreach ($baseData as $year => $data) {
            if ($year > $currentYear) {
                continue;
            }

            if ($this->option('dry-run')) {
                $this->info("   [DRY] {$year}: PIB={$data['gdp_billions_euros']}Md€");

                continue;
            }

            // Le repli ne fait que combler une année absente : il ne remplace jamais une valeur déjà
            // en base, qui peut venir de l'API ou d'une correction plus récente que ce tableau.
            if (FranceEconomy::forYear($year)->annual()->exists()) {
                continue;
            }
            FranceEconomy::create(array_merge(['year' => $year, 'quarter' => null], $data));
            $created++;
        }

        $importLog->finish($created, $updated);
        $this->info("✅ Fallback terminé — {$created} créés, {$updated} mis à jour");

        return Command::SUCCESS;
    }
}
