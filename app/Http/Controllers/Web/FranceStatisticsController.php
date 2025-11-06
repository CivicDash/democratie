<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\FranceDemographics;
use App\Models\FranceEconomy;
use App\Models\FranceMigration;
use App\Models\FranceBudgetRevenue;
use App\Models\FranceBudgetSpending;
use App\Models\FranceLostRevenue;
use App\Models\FranceRegionalData;
use App\Models\FranceDepartmentalData;
use App\Models\FranceQualityOfLife;
use App\Models\FranceEducation;
use App\Models\FranceHealth;
use App\Models\FranceHousing;
use App\Models\FranceEnvironment;
use App\Models\FranceSecurity;
use App\Models\FranceEmploymentDetailed;

class FranceStatisticsController extends Controller
{
    /**
     * Afficher la page principale des statistiques France
     */
    public function index(Request $request): Response
    {
        $selectedYear = $request->input('year', 2024);

        // Récupérer toutes les données pour l'année sélectionnée
        $demographics = FranceDemographics::forYear($selectedYear)->first();
        $economyAnnual = FranceEconomy::forYear($selectedYear)->annual()->first();
        $economyQuarterly = FranceEconomy::forYear($selectedYear)->quarterly()->get();
        $migration = FranceMigration::forYear($selectedYear)->first();
        $revenue = FranceBudgetRevenue::forYear($selectedYear)->first();
        $spending = FranceBudgetSpending::forYear($selectedYear)->first();
        $lostRevenue = FranceLostRevenue::forYear($selectedYear)->first();
        $regionalData = FranceRegionalData::forYear($selectedYear)->get();
        
        // Nouveaux indicateurs sociaux
        $qualityOfLife = FranceQualityOfLife::forYear($selectedYear)->first();
        $education = FranceEducation::forYear($selectedYear)->first();
        $health = FranceHealth::forYear($selectedYear)->first();
        $housing = FranceHousing::forYear($selectedYear)->first();
        $environment = FranceEnvironment::forYear($selectedYear)->first();
        $security = FranceSecurity::forYear($selectedYear)->first();
        $employmentDetailed = FranceEmploymentDetailed::forYear($selectedYear)->first();

        // Années disponibles
        $availableYears = FranceDemographics::orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        // Données comparatives (5 dernières années pour les graphiques)
        $demographicsHistory = FranceDemographics::latestYears(5)
            ->orderBy('year')
            ->get();

        $economyHistory = FranceEconomy::annual()
            ->latestYears(5)
            ->orderBy('year')
            ->get();

        $budgetRevenueHistory = FranceBudgetRevenue::latestYears(5)
            ->orderBy('year')
            ->get();

        $budgetSpendingHistory = FranceBudgetSpending::latestYears(5)
            ->orderBy('year')
            ->get();

        $lostRevenueHistory = FranceLostRevenue::latestYears(5)
            ->orderBy('year')
            ->get();

        // Historiques pour les nouveaux indicateurs
        $educationHistory = FranceEducation::latestYears(5)->orderBy('year')->get();
        $healthHistory = FranceHealth::latestYears(5)->orderBy('year')->get();
        $housingHistory = FranceHousing::latestYears(5)->orderBy('year')->get();
        $environmentHistory = FranceEnvironment::latestYears(5)->orderBy('year')->get();
        $securityHistory = FranceSecurity::latestYears(5)->orderBy('year')->get();
        $employmentDetailedHistory = FranceEmploymentDetailed::latestYears(5)->orderBy('year')->get();
        $qualityOfLifeHistory = FranceQualityOfLife::latestYears(5)->orderBy('year')->get();

        return Inertia::render('Statistics/France/Index', [
            'selectedYear' => $selectedYear,
            'availableYears' => $availableYears,
            
            // Données de l'année sélectionnée
            'demographics' => $demographics,
            'economyAnnual' => $economyAnnual,
            'economyQuarterly' => $economyQuarterly,
            'migration' => $migration,
            'revenue' => $revenue,
            'spending' => $spending,
            'lostRevenue' => $lostRevenue,
            'regionalData' => $regionalData,
            
            // Nouveaux indicateurs sociaux
            'qualityOfLife' => $qualityOfLife,
            'education' => $education,
            'health' => $health,
            'housing' => $housing,
            'environment' => $environment,
            'security' => $security,
            'employmentDetailed' => $employmentDetailed,
            
            // Historiques pour graphiques
            'demographicsHistory' => $demographicsHistory,
            'economyHistory' => $economyHistory,
            'budgetRevenueHistory' => $budgetRevenueHistory,
            'budgetSpendingHistory' => $budgetSpendingHistory,
            'lostRevenueHistory' => $lostRevenueHistory,
            'educationHistory' => $educationHistory,
            'healthHistory' => $healthHistory,
            'housingHistory' => $housingHistory,
            'environmentHistory' => $environmentHistory,
            'securityHistory' => $securityHistory,
            'employmentDetailedHistory' => $employmentDetailedHistory,
            'qualityOfLifeHistory' => $qualityOfLifeHistory,
        ]);
    }

    /**
     * API: Récupérer les données d'une région spécifique
     */
    public function getRegionData(Request $request, string $regionCode): array
    {
        $year = $request->input('year', 2024);
        
        $data = FranceRegionalData::forYear($year)
            ->forRegion($regionCode)
            ->first();

        if (!$data) {
            return ['error' => 'Région non trouvée'];
        }

        return $data->toArray();
    }

    /**
     * API: Récupérer les données d'un département spécifique
     */
    public function getDepartmentData(Request $request, string $departmentCode): array
    {
        $year = $request->input('year', 2024);
        
        $data = FranceDepartmentalData::forYear($year)
            ->forDepartment($departmentCode)
            ->first();

        if (!$data) {
            return ['error' => 'Département non trouvé'];
        }

        return $data->toArray();
    }

    /**
     * API: Comparer deux années
     */
    public function compareYears(Request $request): array
    {
        $year1 = $request->input('year1', 2023);
        $year2 = $request->input('year2', 2024);

        return [
            'demographics' => [
                'year1' => FranceDemographics::forYear($year1)->first(),
                'year2' => FranceDemographics::forYear($year2)->first(),
            ],
            'economy' => [
                'year1' => FranceEconomy::forYear($year1)->annual()->first(),
                'year2' => FranceEconomy::forYear($year2)->annual()->first(),
            ],
            'budget' => [
                'revenue' => [
                    'year1' => FranceBudgetRevenue::forYear($year1)->first(),
                    'year2' => FranceBudgetRevenue::forYear($year2)->first(),
                ],
                'spending' => [
                    'year1' => FranceBudgetSpending::forYear($year1)->first(),
                    'year2' => FranceBudgetSpending::forYear($year2)->first(),
                ],
            ],
            'lostRevenue' => [
                'year1' => FranceLostRevenue::forYear($year1)->first(),
                'year2' => FranceLostRevenue::forYear($year2)->first(),
            ],
        ];
    }
}
