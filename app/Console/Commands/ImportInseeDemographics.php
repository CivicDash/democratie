<?php

namespace App\Console\Commands;

use App\Models\FranceDemographics;
use App\Models\ImportLog;
use App\Services\InseeApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Importe les données démographiques depuis l'API INSEE (BDM)
 *
 * Séries annuelles utilisées (France métropolitaine) :
 * - 000067670 : Population totale au 1er janvier
 * - 000067677 : Naissances vivantes annuelles
 * - 000067679 : Décès annuels tous âges
 * - 001641591 : Taux de natalité (‰)
 * - 000067680 : Taux de mortalité (‰)
 * - 000067672 : Solde naturel
 *
 * Séries mensuelles :
 * - 001641607 : Population mensuelle France (x1000)
 */
class ImportInseeDemographics extends Command
{
    protected $signature = 'import:insee-demographics
                            {--year= : Année spécifique à importer}
                            {--years=5 : Nombre d\'années à importer}
                            {--dry-run : Afficher sans écrire en base}';

    protected $description = 'Importe les données démographiques depuis l\'API INSEE (population, natalité, mortalité)';

    private const SERIES = [
        '000067670' => 'population_total',
        '001641591' => 'birth_rate',
        '000067680' => 'death_rate',
        '000067677' => 'births',
        '000067679' => 'deaths',
        '000067672' => 'natural_balance',
    ];

    public function handle(InseeApiService $insee): int
    {
        $importLog = ImportLog::start('import:insee-demographics', 'insee');
        $this->info('📊 Import des données démographiques INSEE (BDM)');

        if (! $insee->isConfigured()) {
            $this->warn('⚠️  Clés INSEE non configurées — utilisation du fallback');

            return $this->handleFallback($importLog);
        }

        $yearsCount = (int) $this->option('years');
        $currentYear = (int) ($this->option('year') ?? date('Y'));
        $startYear = $currentYear - $yearsCount + 1;

        $this->info("   Période : {$startYear} → {$currentYear}");

        $seriesIds = array_keys(self::SERIES);
        $data = $insee->getMultipleSeries($seriesIds, $startYear, $currentYear);

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
                    collect($attributes)->map(fn ($v, $k) => [$k, is_array($v) ? json_encode($v) : number_format((float) $v, 2, ',', ' ')])->values()->toArray()
                );

                continue;
            }

            $existing = FranceDemographics::forYear($year)->first();
            if ($existing) {
                $existing->update($attributes);
                $updated++;
                $this->line("   ✏️  Mis à jour {$year}");
            } else {
                FranceDemographics::create(array_merge(['year' => $year], $attributes));
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
     * Reconstruit un tableau [année => attributs] à partir des séries parsées.
     * Chaque série peut avoir des périodes annuelles (2024) ou mensuelles (2024-01).
     * Pour les mensuelles, on prend la valeur de janvier (population au 1er jan).
     */
    private function buildYearData(array $parsedSeries, int $startYear, int $endYear): array
    {
        $yearData = [];

        for ($year = $startYear; $year <= $endYear; $year++) {
            $attrs = [];

            foreach (self::SERIES as $idBank => $field) {
                if (! isset($parsedSeries[$idBank])) {
                    continue;
                }

                $obs = $parsedSeries[$idBank]['observations'];
                $value = $obs[(string) $year]
                    ?? $obs["{$year}-01"]
                    ?? null;

                if ($value === null) {
                    continue;
                }

                switch ($field) {
                    case 'population_total':
                        $attrs['population_total'] = (int) $value;
                        break;
                    case 'birth_rate':
                        $attrs['birth_rate'] = round($value, 2);
                        break;
                    case 'death_rate':
                        $attrs['death_rate'] = round($value, 2);
                        break;
                    default:
                        break;
                }
            }

            if (! empty($attrs)) {
                $yearData[$year] = $attrs;
            }
        }

        return $yearData;
    }

    /**
     * Fallback : utilise des estimations basées sur les tendances connues
     * quand les clés INSEE ne sont pas disponibles.
     */
    private function handleFallback(ImportLog $importLog): int
    {
        $this->info('   📦 Import via fallback (données publiques estimées)');

        $currentYear = (int) ($this->option('year') ?? date('Y'));
        $created = 0;
        $updated = 0;

        $ageGroupBase = ['0-14' => 12100000, '15-24' => 7800000, '25-49' => 19500000, '50-64' => 13200000, '65+' => 14000000];
        $genderBase = ['hommes' => 33200000, 'femmes' => 34200000];

        $baseData = [
            2020 => ['population_total' => 67390000, 'birth_rate' => 10.7, 'death_rate' => 10.4, 'life_expectancy_male' => 79.2, 'life_expectancy_female' => 85.3, 'median_salary_euros' => 1940, 'population_by_age_group' => $ageGroupBase, 'population_by_gender' => $genderBase],
            2021 => ['population_total' => 67560000, 'birth_rate' => 10.9, 'death_rate' => 9.7, 'life_expectancy_male' => 79.3, 'life_expectancy_female' => 85.4, 'median_salary_euros' => 1980, 'population_by_age_group' => $ageGroupBase, 'population_by_gender' => $genderBase],
            2022 => ['population_total' => 67750000, 'birth_rate' => 10.7, 'death_rate' => 10.1, 'life_expectancy_male' => 79.3, 'life_expectancy_female' => 85.2, 'median_salary_euros' => 2024, 'population_by_age_group' => $ageGroupBase, 'population_by_gender' => $genderBase],
            2023 => ['population_total' => 67970000, 'birth_rate' => 10.3, 'death_rate' => 9.6, 'life_expectancy_male' => 79.5, 'life_expectancy_female' => 85.5, 'median_salary_euros' => 2091, 'population_by_age_group' => $ageGroupBase, 'population_by_gender' => $genderBase],
            2024 => ['population_total' => 68170000, 'birth_rate' => 10.0, 'death_rate' => 9.5, 'life_expectancy_male' => 79.6, 'life_expectancy_female' => 85.6, 'median_salary_euros' => 2150, 'population_by_age_group' => $ageGroupBase, 'population_by_gender' => $genderBase],
            2025 => ['population_total' => 68370000, 'birth_rate' => 9.8, 'death_rate' => 9.4, 'life_expectancy_male' => 79.8, 'life_expectancy_female' => 85.7, 'median_salary_euros' => 2200, 'population_by_age_group' => $ageGroupBase, 'population_by_gender' => $genderBase],
        ];

        foreach ($baseData as $year => $data) {
            if ($year > $currentYear) {
                continue;
            }

            if ($this->option('dry-run')) {
                $this->info("   [DRY] {$year}: pop={$data['population_total']}");

                continue;
            }

            $existing = FranceDemographics::forYear($year)->first();
            if ($existing) {
                $existing->update($data);
                $updated++;
            } else {
                FranceDemographics::create(array_merge(['year' => $year], $data));
                $created++;
            }
        }

        $importLog->finish($created, $updated);
        $this->info("✅ Fallback terminé — {$created} créés, {$updated} mis à jour");

        return Command::SUCCESS;
    }
}
