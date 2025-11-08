<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FranceDemographics;
use App\Models\FranceEconomy;
use App\Models\FranceMigration;
use App\Models\FranceBudgetRevenue;
use App\Models\FranceBudgetSpending;
use App\Models\FranceLostRevenue;
use App\Models\FranceRegionalData;
use App\Models\FranceDepartmentalData;

class FranceStatisticsSeeder extends Seeder
{
    /**
     * Seed France statistics with real data from INSEE, Government sources
     * Data for 2023 and 2024 (demo purposes)
     */
    public function run(): void
    {
        echo "📊 Seeding France Statistics (2023-2024)...\n";

        $this->seedDemographics();
        $this->seedEconomy();
        $this->seedMigration();
        $this->seedBudgetRevenue();
        $this->seedBudgetSpending();
        $this->seedLostRevenue();
        $this->seedRegionalData();

        echo "✅ France Statistics seeded successfully!\n";
    }

    /**
     * Démographie - Sources: INSEE
     */
    private function seedDemographics(): void
    {
        echo "  👥 Démographie...\n";

        $demographics = [
            [
                'year' => 2023,
                'population_total' => 68042591, // INSEE 1er janvier 2024
                'population_by_age_group' => [
                    '0-14' => 11800000,
                    '15-24' => 7900000,
                    '25-39' => 12900000,
                    '40-54' => 12800000,
                    '55-64' => 8600000,
                    '65-74' => 7400000,
                    '75+' => 6600000,
                ],
                'population_by_gender' => [
                    'male' => 33100000,
                    'female' => 34900000,
                ],
                'birth_rate' => 10.9, // Pour 1000 habitants
                'death_rate' => 9.8,
                'life_expectancy_male' => 79.3,
                'life_expectancy_female' => 85.2,
                'median_salary_euros' => 28280, // Salaire médian net mensuel * 12
            ],
            [
                'year' => 2024,
                'population_total' => 68200000, // Estimation
                'population_by_age_group' => [
                    '0-14' => 11750000,
                    '15-24' => 7850000,
                    '25-39' => 12950000,
                    '40-54' => 12750000,
                    '55-64' => 8650000,
                    '65-74' => 7500000,
                    '75+' => 6750000,
                ],
                'population_by_gender' => [
                    'male' => 33200000,
                    'female' => 35000000,
                ],
                'birth_rate' => 10.7,
                'death_rate' => 9.9,
                'life_expectancy_male' => 79.4,
                'life_expectancy_female' => 85.3,
                'median_salary_euros' => 28920, // +2.3% vs 2023
            ],
        ];

        foreach ($demographics as $data) {
            FranceDemographics::updateOrCreate(
                ['year' => $data['year']],
                $data
            );
        }
    }

    /**
     * Économie - Sources: INSEE, Banque de France
     */
    private function seedEconomy(): void
    {
        echo "  💰 Économie...\n";

        $economy = [
            // 2023 - Données annuelles
            [
                'year' => 2023,
                'quarter' => null,
                'gdp_billions_euros' => 2923.0, // PIB 2023
                'gdp_growth_rate' => 0.9, // Croissance 2023
                'unemployment_rate' => 7.3,
                'inflation_rate' => 4.9,
                'public_debt_billions_euros' => 3101.0,
                'public_debt_gdp_percentage' => 110.6,
                'trade_balance_billions_euros' => -99.6,
                'exports_billions_euros' => 635.0,
                'imports_billions_euros' => 734.6,
            ],
            // 2023 - Données trimestrielles
            [
                'year' => 2023,
                'quarter' => 1,
                'gdp_billions_euros' => 728.0,
                'gdp_growth_rate' => 0.2,
                'unemployment_rate' => 7.1,
                'inflation_rate' => 5.9,
                'trade_balance_billions_euros' => -24.5,
                'exports_billions_euros' => 157.0,
                'imports_billions_euros' => 181.5,
            ],
            [
                'year' => 2023,
                'quarter' => 2,
                'gdp_billions_euros' => 731.0,
                'gdp_growth_rate' => 0.5,
                'unemployment_rate' => 7.3,
                'inflation_rate' => 5.3,
                'trade_balance_billions_euros' => -25.2,
                'exports_billions_euros' => 159.0,
                'imports_billions_euros' => 184.2,
            ],
            [
                'year' => 2023,
                'quarter' => 3,
                'gdp_billions_euros' => 732.0,
                'gdp_growth_rate' => 0.1,
                'unemployment_rate' => 7.4,
                'inflation_rate' => 4.9,
                'trade_balance_billions_euros' => -24.8,
                'exports_billions_euros' => 160.0,
                'imports_billions_euros' => 184.8,
            ],
            [
                'year' => 2023,
                'quarter' => 4,
                'gdp_billions_euros' => 732.0,
                'gdp_growth_rate' => 0.0,
                'unemployment_rate' => 7.5,
                'inflation_rate' => 3.7,
                'trade_balance_billions_euros' => -25.1,
                'exports_billions_euros' => 159.0,
                'imports_billions_euros' => 184.1,
            ],
            // 2024 - Données annuelles (estimation)
            [
                'year' => 2024,
                'quarter' => null,
                'gdp_billions_euros' => 2980.0,
                'gdp_growth_rate' => 1.1,
                'unemployment_rate' => 7.4,
                'inflation_rate' => 2.5,
                'public_debt_billions_euros' => 3200.0,
                'public_debt_gdp_percentage' => 112.0,
                'trade_balance_billions_euros' => -95.0,
                'exports_billions_euros' => 650.0,
                'imports_billions_euros' => 745.0,
            ],
            // 2024 - Données trimestrielles
            [
                'year' => 2024,
                'quarter' => 1,
                'gdp_billions_euros' => 738.0,
                'gdp_growth_rate' => 0.2,
                'unemployment_rate' => 7.5,
                'inflation_rate' => 2.9,
                'trade_balance_billions_euros' => -23.5,
                'exports_billions_euros' => 161.0,
                'imports_billions_euros' => 184.5,
            ],
            [
                'year' => 2024,
                'quarter' => 2,
                'gdp_billions_euros' => 742.0,
                'gdp_growth_rate' => 0.3,
                'unemployment_rate' => 7.3,
                'inflation_rate' => 2.6,
                'trade_balance_billions_euros' => -24.0,
                'exports_billions_euros' => 162.0,
                'imports_billions_euros' => 186.0,
            ],
            [
                'year' => 2024,
                'quarter' => 3,
                'gdp_billions_euros' => 745.0,
                'gdp_growth_rate' => 0.4,
                'unemployment_rate' => 7.4,
                'inflation_rate' => 2.2,
                'trade_balance_billions_euros' => -23.8,
                'exports_billions_euros' => 163.0,
                'imports_billions_euros' => 186.8,
            ],
            [
                'year' => 2024,
                'quarter' => 4,
                'gdp_billions_euros' => 755.0,
                'gdp_growth_rate' => 0.2,
                'unemployment_rate' => 7.4,
                'inflation_rate' => 2.3,
                'trade_balance_billions_euros' => -23.7,
                'exports_billions_euros' => 164.0,
                'imports_billions_euros' => 187.7,
            ],
        ];

        foreach ($economy as $data) {
            FranceEconomy::updateOrCreate(
                ['year' => $data['year'], 'quarter' => $data['quarter']],
                $data
            );
        }
    }

    /**
     * Migration - Sources: INSEE, OFPRA
     */
    private function seedMigration(): void
    {
        echo "  🌍 Flux migratoires...\n";

        $migration = [
            [
                'year' => 2023,
                'immigration_total' => 320000,
                'emigration_total' => 140000,
                'net_migration' => 180000,
                'immigration_by_origin' => [
                    'UE' => 95000,
                    'Afrique' => 120000,
                    'Asie' => 65000,
                    'Amérique' => 25000,
                    'Autres' => 15000,
                ],
                'emigration_by_destination' => [
                    'UE' => 50000,
                    'Amérique_du_Nord' => 35000,
                    'Asie' => 20000,
                    'Afrique' => 15000,
                    'Autres' => 20000,
                ],
                'asylum_requests' => 142500,
                'asylum_granted' => 38500,
            ],
            [
                'year' => 2024,
                'immigration_total' => 310000,
                'emigration_total' => 145000,
                'net_migration' => 165000,
                'immigration_by_origin' => [
                    'UE' => 90000,
                    'Afrique' => 115000,
                    'Asie' => 70000,
                    'Amérique' => 22000,
                    'Autres' => 13000,
                ],
                'emigration_by_destination' => [
                    'UE' => 52000,
                    'Amérique_du_Nord' => 38000,
                    'Asie' => 22000,
                    'Afrique' => 13000,
                    'Autres' => 20000,
                ],
                'asylum_requests' => 135000,
                'asylum_granted' => 36000,
            ],
        ];

        foreach ($migration as $data) {
            FranceMigration::updateOrCreate(
                ['year' => $data['year']],
                $data
            );
        }
    }

    /**
     * Budget - Recettes - Sources: Ministère des Finances, Cour des Comptes
     */
    private function seedBudgetRevenue(): void
    {
        echo "  💶 Budget - Recettes...\n";

        $revenue = [
            [
                'year' => 2023,
                'total_billions_euros' => 1485.0, // Recettes nettes totales (Budget général + Sécurité sociale)
                'tva_billions_euros' => 93.5, // TVA nette
                'income_tax_billions_euros' => 86.2, // Impôt sur le revenu
                'corporate_tax_billions_euros' => 58.7, // Impôt sur les sociétés
                'property_tax_billions_euros' => 35.8, // Taxe foncière
                'housing_tax_billions_euros' => 2.1, // Taxe d'habitation (résiduelle)
                'fuel_tax_billions_euros' => 14.3, // TICPE
                'social_contributions_billions_euros' => 585.0, // Cotisations sociales (part importante!)
                'other_taxes_billions_euros' => 609.4, // Autres recettes (CSG, CRDS, taxes diverses)
            ],
            [
                'year' => 2024,
                'total_billions_euros' => 1501.6, // Loi de finances 2024 (Budget général + Sécurité sociale)
                'tva_billions_euros' => 96.8,
                'income_tax_billions_euros' => 89.5,
                'corporate_tax_billions_euros' => 61.2,
                'property_tax_billions_euros' => 37.2,
                'housing_tax_billions_euros' => 1.8,
                'fuel_tax_billions_euros' => 14.8,
                'social_contributions_billions_euros' => 595.0, // Cotisations sociales 2024
                'other_taxes_billions_euros' => 605.3, // Autres recettes (CSG, CRDS, etc.)
            ],
        ];

        foreach ($revenue as $data) {
            FranceBudgetRevenue::updateOrCreate(
                ['year' => $data['year']],
                $data
            );
        }
    }

    /**
     * Budget - Dépenses - Sources: Ministère des Finances
     */
    private function seedBudgetSpending(): void
    {
        echo "  💸 Budget - Dépenses...\n";

        $spending = [
            [
                'year' => 2023,
                'total_billions_euros' => 1650.0, // Dépenses totales (Budget général + Sécurité sociale + collectivités)
                'health_billions_euros' => 265.0, // Santé (Sécurité sociale incluse)
                'education_billions_euros' => 85.0, // Éducation nationale + recherche
                'security_defense_billions_euros' => 53.2, // Défense + Sécurité
                'justice_billions_euros' => 9.8,
                'social_welfare_billions_euros' => 180.0, // Solidarité, insertion, famille
                'unemployment_billions_euros' => 38.2, // Pôle emploi, indemnisation
                'pensions_billions_euros' => 365.0, // Retraites (grosse part!)
                'business_subsidies_billions_euros' => 110.0, // Aides aux entreprises
                'infrastructure_billions_euros' => 85.0, // Transports, logement, équipements
                'environment_billions_euros' => 35.0, // Écologie, énergie, transition
                'culture_billions_euros' => 15.0, // Culture, sport
                'debt_interest_billions_euros' => 52.0, // Charge de la dette
                'other_spending_billions_euros' => 356.8, // Autres (administrations, collectivités, etc.)
            ],
            [
                'year' => 2024,
                'total_billions_euros' => 1670.2, // Loi de finances 2024
                'health_billions_euros' => 275.0,
                'education_billions_euros' => 88.0,
                'security_defense_billions_euros' => 56.8,
                'justice_billions_euros' => 10.2,
                'social_welfare_billions_euros' => 185.0,
                'unemployment_billions_euros' => 39.5,
                'pensions_billions_euros' => 375.0,
                'business_subsidies_billions_euros' => 100.0,
                'infrastructure_billions_euros' => 90.0,
                'environment_billions_euros' => 38.0,
                'culture_billions_euros' => 16.0,
                'debt_interest_billions_euros' => 55.0, // Hausse des taux d'intérêt
                'other_spending_billions_euros' => 341.7,
            ],
        ];

        foreach ($spending as $data) {
            FranceBudgetSpending::updateOrCreate(
                ['year' => $data['year']],
                $data
            );
        }
    }

    /**
     * Recettes perdues - Sources: Syndicat Solidaires Finances Publiques, 
     * Cour des Comptes, Tax Justice Network, Gabriel Zucman
     */
    private function seedLostRevenue(): void
    {
        echo "  🚨 Recettes perdues (fraude, évasion)...\n";

        $lostRevenue = [
            [
                'year' => 2023,
                'vat_fraud_billions_euros' => 14.5, // Fraude à la TVA (carrousel, etc.)
                'income_tax_fraud_billions_euros' => 8.2,
                'corporate_tax_fraud_billions_euros' => 12.3,
                'social_fraud_billions_euros' => 7.8, // Travail dissimulé, etc.
                'tax_evasion_billions_euros' => 80.0, // Évasion fiscale (estimation basse)
                'tax_optimization_billions_euros' => 25.0, // Optimisation légale mais agressive
                'offshore_billions_euros' => 35.0, // Paradis fiscaux
                'total_lost_billions_euros' => 182.8,
                'sources' => 'Syndicat Solidaires Finances Publiques, Cour des Comptes, Tax Justice Network',
                'notes' => 'Estimations conservatrices. Certaines études (Gabriel Zucman) évaluent la perte totale entre 80 et 100 Md€ pour l\'évasion seule.',
            ],
            [
                'year' => 2024,
                'vat_fraud_billions_euros' => 15.2,
                'income_tax_fraud_billions_euros' => 8.5,
                'corporate_tax_fraud_billions_euros' => 13.1,
                'social_fraud_billions_euros' => 8.2,
                'tax_evasion_billions_euros' => 85.0,
                'tax_optimization_billions_euros' => 26.5,
                'offshore_billions_euros' => 37.0,
                'total_lost_billions_euros' => 193.5,
                'sources' => 'Syndicat Solidaires Finances Publiques, Cour des Comptes, Tax Justice Network',
                'notes' => 'La fraude à la TVA augmente avec le e-commerce transfrontalier. L\'évasion fiscale reste massive malgré les efforts de lutte.',
            ],
        ];

        foreach ($lostRevenue as $data) {
            FranceLostRevenue::updateOrCreate(
                ['year' => $data['year']],
                $data
            );
        }
    }

    /**
     * Données régionales - Sources: INSEE
     * Quelques régions pour la démo
     */
    private function seedRegionalData(): void
    {
        echo "  🗺️ Données régionales (échantillon)...\n";

        $regions = [
            // Île-de-France
            [
                'year' => 2023,
                'region_code' => '11',
                'region_name' => 'Île-de-France',
                'population' => 12271794,
                'unemployment_rate' => 7.8,
                'gdp_billions_euros' => 765.0,
                'median_income_euros' => 24990.0,
                'poverty_rate' => 15.8,
            ],
            [
                'year' => 2024,
                'region_code' => '11',
                'region_name' => 'Île-de-France',
                'population' => 12320000,
                'unemployment_rate' => 7.6,
                'gdp_billions_euros' => 780.0,
                'median_income_euros' => 25400.0,
                'poverty_rate' => 15.5,
            ],
            // Auvergne-Rhône-Alpes
            [
                'year' => 2023,
                'region_code' => '84',
                'region_name' => 'Auvergne-Rhône-Alpes',
                'population' => 8078652,
                'unemployment_rate' => 6.5,
                'gdp_billions_euros' => 295.0,
                'median_income_euros' => 22580.0,
                'poverty_rate' => 12.3,
            ],
            [
                'year' => 2024,
                'region_code' => '84',
                'region_name' => 'Auvergne-Rhône-Alpes',
                'population' => 8100000,
                'unemployment_rate' => 6.4,
                'gdp_billions_euros' => 302.0,
                'median_income_euros' => 23000.0,
                'poverty_rate' => 12.1,
            ],
            // Provence-Alpes-Côte d'Azur
            [
                'year' => 2023,
                'region_code' => '93',
                'region_name' => 'Provence-Alpes-Côte d\'Azur',
                'population' => 5098666,
                'unemployment_rate' => 8.9,
                'gdp_billions_euros' => 178.0,
                'median_income_euros' => 21250.0,
                'poverty_rate' => 17.2,
            ],
            [
                'year' => 2024,
                'region_code' => '93',
                'region_name' => 'Provence-Alpes-Côte d\'Azur',
                'population' => 5120000,
                'unemployment_rate' => 8.7,
                'gdp_billions_euros' => 182.0,
                'median_income_euros' => 21600.0,
                'poverty_rate' => 17.0,
            ],
            // Occitanie
            [
                'year' => 2023,
                'region_code' => '76',
                'region_name' => 'Occitanie',
                'population' => 5973969,
                'unemployment_rate' => 8.2,
                'gdp_billions_euros' => 168.0,
                'median_income_euros' => 21180.0,
                'poverty_rate' => 16.5,
            ],
            [
                'year' => 2024,
                'region_code' => '76',
                'region_name' => 'Occitanie',
                'population' => 6010000,
                'unemployment_rate' => 8.0,
                'gdp_billions_euros' => 172.0,
                'median_income_euros' => 21500.0,
                'poverty_rate' => 16.2,
            ],
            // Hauts-de-France
            [
                'year' => 2023,
                'region_code' => '32',
                'region_name' => 'Hauts-de-France',
                'population' => 5997734,
                'unemployment_rate' => 9.5,
                'gdp_billions_euros' => 165.0,
                'median_income_euros' => 20120.0,
                'poverty_rate' => 18.1,
            ],
            [
                'year' => 2024,
                'region_code' => '32',
                'region_name' => 'Hauts-de-France',
                'population' => 6005000,
                'unemployment_rate' => 9.3,
                'gdp_billions_euros' => 168.0,
                'median_income_euros' => 20450.0,
                'poverty_rate' => 17.9,
            ],
        ];

        foreach ($regions as $data) {
            FranceRegionalData::updateOrCreate(
                ['year' => $data['year'], 'region_code' => $data['region_code']],
                $data
            );
        }
    }
}
