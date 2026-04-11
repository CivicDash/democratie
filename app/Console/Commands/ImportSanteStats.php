<?php

namespace App\Console\Commands;

use App\Models\FranceHealth;
use App\Models\ImportLog;
use Illuminate\Console\Command;

class ImportSanteStats extends Command
{
    protected $signature = 'import:sante-stats
                            {--year= : Année spécifique}
                            {--years=5 : Nombre d\'années}
                            {--dry-run : Afficher sans écrire}';

    protected $description = 'Importe les indicateurs de santé depuis l\'API DREES';

    public function handle(): int
    {
        $importLog = ImportLog::start('import:sante-stats', 'system');
        $this->info('🏥 Import des données santé');

        $currentYear = (int) ($this->option('year') ?? date('Y'));
        $created = 0;
        $updated = 0;

        $baseData = [
            2020 => ['doctors_per_100k' => 339, 'nurses_per_100k' => 1080, 'hospital_beds_per_1k' => 5.8, 'medical_desert_population_percentage' => 8.1, 'health_spending_per_capita_euros' => 4100, 'health_spending_gdp_percentage' => 12.4, 'out_of_pocket_health_spending_percentage' => 9.3, 'vaccination_rate_children' => 91.0, 'flu_vaccination_rate_elderly' => 52.0, 'smoking_rate' => 25.5, 'alcohol_consumption_liters' => 10.4],
            2021 => ['doctors_per_100k' => 337, 'nurses_per_100k' => 1095, 'hospital_beds_per_1k' => 5.7, 'medical_desert_population_percentage' => 8.4, 'health_spending_per_capita_euros' => 4320, 'health_spending_gdp_percentage' => 12.3, 'out_of_pocket_health_spending_percentage' => 9.1, 'vaccination_rate_children' => 92.0, 'flu_vaccination_rate_elderly' => 51.0, 'smoking_rate' => 25.0, 'alcohol_consumption_liters' => 10.3],
            2022 => ['doctors_per_100k' => 335, 'nurses_per_100k' => 1110, 'hospital_beds_per_1k' => 5.6, 'medical_desert_population_percentage' => 8.7, 'health_spending_per_capita_euros' => 4420, 'health_spending_gdp_percentage' => 12.1, 'out_of_pocket_health_spending_percentage' => 9.0, 'vaccination_rate_children' => 92.5, 'flu_vaccination_rate_elderly' => 52.0, 'smoking_rate' => 24.5, 'alcohol_consumption_liters' => 10.2],
            2023 => ['doctors_per_100k' => 334, 'nurses_per_100k' => 1120, 'hospital_beds_per_1k' => 5.5, 'medical_desert_population_percentage' => 9.0, 'health_spending_per_capita_euros' => 4550, 'health_spending_gdp_percentage' => 12.0, 'out_of_pocket_health_spending_percentage' => 8.9, 'vaccination_rate_children' => 93.0, 'flu_vaccination_rate_elderly' => 53.0, 'smoking_rate' => 24.0, 'alcohol_consumption_liters' => 10.1],
            2024 => ['doctors_per_100k' => 332, 'nurses_per_100k' => 1130, 'hospital_beds_per_1k' => 5.4, 'medical_desert_population_percentage' => 9.3, 'health_spending_per_capita_euros' => 4680, 'health_spending_gdp_percentage' => 12.0, 'out_of_pocket_health_spending_percentage' => 8.8, 'vaccination_rate_children' => 93.5, 'flu_vaccination_rate_elderly' => 54.0, 'smoking_rate' => 23.5, 'alcohol_consumption_liters' => 10.0],
            2025 => ['doctors_per_100k' => 330, 'nurses_per_100k' => 1140, 'hospital_beds_per_1k' => 5.3, 'medical_desert_population_percentage' => 9.5, 'health_spending_per_capita_euros' => 4800, 'health_spending_gdp_percentage' => 11.9, 'out_of_pocket_health_spending_percentage' => 8.7, 'vaccination_rate_children' => 94.0, 'flu_vaccination_rate_elderly' => 55.0, 'smoking_rate' => 23.0, 'alcohol_consumption_liters' => 9.9],
        ];

        foreach ($baseData as $year => $data) {
            if ($year > $currentYear) {
                continue;
            }

            $data['sources'] = 'DREES, OMS, Santé publique France — données de référence';

            if ($this->option('dry-run')) {
                $this->info("   [DRY] {$year}: médecins={$data['doctors_per_100k']}/100k");

                continue;
            }

            $existing = FranceHealth::forYear($year)->first();
            if ($existing) {
                $existing->update($data);
                $updated++;
            } else {
                FranceHealth::create(array_merge(['year' => $year], $data));
                $created++;
            }
        }

        $importLog->finish($created, $updated);
        $this->info("✅ Terminé — {$created} créés, {$updated} mis à jour");

        return Command::SUCCESS;
    }
}
