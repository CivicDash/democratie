<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BudgetAnnuel;
use App\Models\FranceBudgetRevenue;
use App\Models\FranceBudgetSpending;
use App\Models\FranceDemographics;
use App\Models\FranceEconomy;
use App\Models\FranceEducation;
use App\Models\FranceEmploymentDetailed;
use App\Models\FranceEnvironment;
use App\Models\FranceHealth;
use App\Models\FranceSecurity;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Contrôleur Admin unifié pour toutes les statistiques France
 * Une seule source de données, éditable via l'admin
 */
class StatistiquesFranceController extends Controller
{
    /**
     * Vue d'ensemble de toutes les statistiques
     */
    public function index(Request $request): Response
    {
        $annee = $request->input('annee', date('Y'));

        // Récupérer les années disponibles
        $anneesDisponibles = collect([
            FranceDemographics::pluck('year'),
            FranceEconomy::whereNull('quarter')->pluck('year'),
            BudgetAnnuel::pluck('annee'),
        ])->flatten()->unique()->sort()->reverse()->values()->toArray();

        // Stats par catégorie
        $statsCategories = [
            'demographie' => [
                'label' => 'Démographie',
                'icon' => '👥',
                'count' => FranceDemographics::count(),
                'lastYear' => FranceDemographics::max('year'),
                'route' => 'admin.stats-france.demographie',
            ],
            'economie' => [
                'label' => 'Économie',
                'icon' => '📊',
                'count' => FranceEconomy::whereNull('quarter')->count(),
                'lastYear' => FranceEconomy::whereNull('quarter')->max('year'),
                'route' => 'admin.stats-france.economie',
            ],
            'budget' => [
                'label' => 'Budget État',
                'icon' => '💰',
                'count' => BudgetAnnuel::count(),
                'lastYear' => BudgetAnnuel::max('annee'),
                'route' => 'admin.stats-france.budget',
            ],
            'recettes' => [
                'label' => 'Recettes Consolidées',
                'icon' => '📈',
                'count' => FranceBudgetRevenue::count(),
                'lastYear' => FranceBudgetRevenue::max('year'),
                'route' => 'admin.stats-france.recettes',
            ],
            'depenses' => [
                'label' => 'Dépenses Publiques',
                'icon' => '📉',
                'count' => FranceBudgetSpending::count(),
                'lastYear' => FranceBudgetSpending::max('year'),
                'route' => 'admin.stats-france.depenses',
            ],
            'education' => [
                'label' => 'Éducation',
                'icon' => '🎓',
                'count' => FranceEducation::count(),
                'lastYear' => FranceEducation::max('year'),
                'route' => 'admin.stats-france.education',
            ],
            'sante' => [
                'label' => 'Santé',
                'icon' => '🏥',
                'count' => FranceHealth::count(),
                'lastYear' => FranceHealth::max('year'),
                'route' => 'admin.stats-france.sante',
            ],
            'environnement' => [
                'label' => 'Environnement',
                'icon' => '🌱',
                'count' => FranceEnvironment::count(),
                'lastYear' => FranceEnvironment::max('year'),
                'route' => 'admin.stats-france.environnement',
            ],
            'securite' => [
                'label' => 'Sécurité',
                'icon' => '🛡️',
                'count' => FranceSecurity::count(),
                'lastYear' => FranceSecurity::max('year'),
                'route' => 'admin.stats-france.securite',
            ],
            'emploi' => [
                'label' => 'Emploi',
                'icon' => '💼',
                'count' => FranceEmploymentDetailed::count(),
                'lastYear' => FranceEmploymentDetailed::max('year'),
                'route' => 'admin.stats-france.emploi',
            ],
        ];

        return Inertia::render('Admin/StatistiquesFrance/Index', [
            'annee' => $annee,
            'anneesDisponibles' => $anneesDisponibles,
            'statsCategories' => $statsCategories,
            'stats' => [
                'total_annees' => count($anneesDisponibles),
                'derniere_annee' => max($anneesDisponibles ?: [date('Y')]),
                'categories' => count($statsCategories),
            ],
        ]);
    }

    /**
     * Édition des données démographiques
     */
    public function demographie(Request $request): Response
    {
        $annee = $request->input('annee', date('Y'));
        $data = FranceDemographics::where('year', $annee)->first();
        $anneesDisponibles = FranceDemographics::orderBy('year', 'desc')->pluck('year')->toArray();

        return Inertia::render('Admin/StatistiquesFrance/Demographie', [
            'annee' => $annee,
            'anneesDisponibles' => $anneesDisponibles,
            'data' => $data,
        ]);
    }

    public function updateDemographie(Request $request, int $annee)
    {
        $validated = $request->validate([
            'population_total' => 'required|integer|min:0',
            'birth_rate' => 'nullable|numeric',
            'death_rate' => 'nullable|numeric',
            'life_expectancy_male' => 'nullable|numeric',
            'life_expectancy_female' => 'nullable|numeric',
            'median_salary_euros' => 'nullable|numeric',
            'population_by_age_group' => 'nullable|array',
            'population_by_gender' => 'nullable|array',
        ]);

        FranceDemographics::updateOrCreate(
            ['year' => $annee],
            $validated
        );

        return redirect()->route('admin.stats-france.demographie', ['annee' => $annee])
            ->with('success', 'Données démographiques mises à jour.');
    }

    /**
     * Édition des données économiques
     */
    public function economie(Request $request): Response
    {
        $annee = $request->input('annee', date('Y'));
        $data = FranceEconomy::where('year', $annee)->whereNull('quarter')->first();
        $dataQuarterly = FranceEconomy::where('year', $annee)->whereNotNull('quarter')->get();
        $anneesDisponibles = FranceEconomy::whereNull('quarter')->orderBy('year', 'desc')->pluck('year')->toArray();

        return Inertia::render('Admin/StatistiquesFrance/Economie', [
            'annee' => $annee,
            'anneesDisponibles' => $anneesDisponibles,
            'data' => $data,
            'dataQuarterly' => $dataQuarterly,
        ]);
    }

    public function updateEconomie(Request $request, int $annee)
    {
        $validated = $request->validate([
            'gdp_billions_euros' => 'nullable|numeric',
            'gdp_growth_rate' => 'nullable|numeric',
            'unemployment_rate' => 'nullable|numeric',
            'inflation_rate' => 'nullable|numeric',
            'public_debt_billions_euros' => 'nullable|numeric',
            'public_debt_gdp_percentage' => 'nullable|numeric',
            'trade_balance_billions_euros' => 'nullable|numeric',
            'exports_billions_euros' => 'nullable|numeric',
            'imports_billions_euros' => 'nullable|numeric',
            'gdp_per_capita_euros' => 'nullable|numeric',
        ]);

        FranceEconomy::updateOrCreate(
            ['year' => $annee, 'quarter' => null],
            $validated
        );

        return redirect()->route('admin.stats-france.economie', ['annee' => $annee])
            ->with('success', 'Données économiques mises à jour.');
    }

    /**
     * Édition des données Budget État (lié à BudgetEtat)
     */
    public function budget(Request $request): Response
    {
        $annee = $request->input('annee', BudgetAnnuel::max('annee') ?? date('Y'));
        $data = BudgetAnnuel::where('annee', $annee)->first();
        $anneesDisponibles = BudgetAnnuel::orderBy('annee', 'desc')->pluck('annee')->toArray();

        // Missions et programmes liés
        $missions = \App\Models\BudgetMission::where('annee', $annee)
            ->orderByDesc('credits_cp')
            ->get();

        return Inertia::render('Admin/StatistiquesFrance/Budget', [
            'annee' => $annee,
            'anneesDisponibles' => $anneesDisponibles,
            'data' => $data,
            'missions' => $missions,
        ]);
    }

    public function updateBudget(Request $request, int $annee)
    {
        $validated = $request->validate([
            'recettes_nettes' => 'nullable|numeric',
            'depenses_nettes' => 'nullable|numeric',
            'deficit_excedent' => 'nullable|numeric',
            'dette_pib_pct' => 'nullable|numeric',
        ]);

        BudgetAnnuel::updateOrCreate(
            ['annee' => $annee],
            $validated
        );

        return redirect()->route('admin.stats-france.budget', ['annee' => $annee])
            ->with('success', 'Budget de l\'État mis à jour.');
    }

    /**
     * Édition des recettes consolidées
     */
    public function recettes(Request $request): Response
    {
        $annee = $request->input('annee', FranceBudgetRevenue::max('year') ?? date('Y'));
        $data = FranceBudgetRevenue::where('year', $annee)->first();
        $anneesDisponibles = FranceBudgetRevenue::orderBy('year', 'desc')->pluck('year')->toArray();

        return Inertia::render('Admin/StatistiquesFrance/Recettes', [
            'annee' => $annee,
            'anneesDisponibles' => $anneesDisponibles,
            'data' => $data,
        ]);
    }

    public function updateRecettes(Request $request, int $annee)
    {
        $validated = $request->validate([
            'income_tax_billions_euros' => 'nullable|numeric',
            'vat_billions_euros' => 'nullable|numeric',
            'corporate_tax_billions_euros' => 'nullable|numeric',
            'wealth_tax_billions_euros' => 'nullable|numeric',
            'local_taxes_billions_euros' => 'nullable|numeric',
            'social_contributions_billions_euros' => 'nullable|numeric',
            'social_spending_billions_euros' => 'nullable|numeric',
            'social_balance_billions_euros' => 'nullable|numeric',
            'other_revenue_billions_euros' => 'nullable|numeric',
            'total_revenue_billions_euros' => 'nullable|numeric',
        ]);

        FranceBudgetRevenue::updateOrCreate(
            ['year' => $annee],
            $validated
        );

        return redirect()->route('admin.stats-france.recettes', ['annee' => $annee])
            ->with('success', 'Recettes consolidées mises à jour.');
    }

    /**
     * Édition des dépenses publiques
     */
    public function depenses(Request $request): Response
    {
        $annee = $request->input('annee', FranceBudgetSpending::max('year') ?? date('Y'));
        $data = FranceBudgetSpending::where('year', $annee)->first();
        $anneesDisponibles = FranceBudgetSpending::orderBy('year', 'desc')->pluck('year')->toArray();

        return Inertia::render('Admin/StatistiquesFrance/Depenses', [
            'annee' => $annee,
            'anneesDisponibles' => $anneesDisponibles,
            'data' => $data,
        ]);
    }

    public function updateDepenses(Request $request, int $annee)
    {
        $validated = $request->validate([
            'education_billions_euros' => 'nullable|numeric',
            'health_billions_euros' => 'nullable|numeric',
            'defense_billions_euros' => 'nullable|numeric',
            'social_protection_billions_euros' => 'nullable|numeric',
            'public_order_billions_euros' => 'nullable|numeric',
            'general_services_billions_euros' => 'nullable|numeric',
            'economic_affairs_billions_euros' => 'nullable|numeric',
            'environment_billions_euros' => 'nullable|numeric',
            'housing_billions_euros' => 'nullable|numeric',
            'culture_billions_euros' => 'nullable|numeric',
            'total_spending_billions_euros' => 'nullable|numeric',
        ]);

        FranceBudgetSpending::updateOrCreate(
            ['year' => $annee],
            $validated
        );

        return redirect()->route('admin.stats-france.depenses', ['annee' => $annee])
            ->with('success', 'Dépenses publiques mises à jour.');
    }

    /**
     * Édition des données éducation
     */
    public function education(Request $request): Response
    {
        $annee = $request->input('annee', FranceEducation::max('year') ?? date('Y'));
        $data = FranceEducation::where('year', $annee)->first();
        $anneesDisponibles = FranceEducation::orderBy('year', 'desc')->pluck('year')->toArray();

        return Inertia::render('Admin/StatistiquesFrance/Education', [
            'annee' => $annee,
            'anneesDisponibles' => $anneesDisponibles,
            'data' => $data,
        ]);
    }

    public function updateEducation(Request $request, int $annee)
    {
        $validated = $request->validate([
            'students_count' => 'nullable|integer',
            'teachers_count' => 'nullable|integer',
            'schools_count' => 'nullable|integer',
            'baccalaureat_success_rate' => 'nullable|numeric',
            'higher_education_rate' => 'nullable|numeric',
            'literacy_rate' => 'nullable|numeric',
            'education_spending_gdp_pct' => 'nullable|numeric',
        ]);

        FranceEducation::updateOrCreate(
            ['year' => $annee],
            $validated
        );

        return redirect()->route('admin.stats-france.education', ['annee' => $annee])
            ->with('success', 'Données éducation mises à jour.');
    }

    /**
     * Édition des données santé
     */
    public function sante(Request $request): Response
    {
        $annee = $request->input('annee', FranceHealth::max('year') ?? date('Y'));
        $data = FranceHealth::where('year', $annee)->first();
        $anneesDisponibles = FranceHealth::orderBy('year', 'desc')->pluck('year')->toArray();

        return Inertia::render('Admin/StatistiquesFrance/Sante', [
            'annee' => $annee,
            'anneesDisponibles' => $anneesDisponibles,
            'data' => $data,
        ]);
    }

    public function updateSante(Request $request, int $annee)
    {
        $validated = $request->validate([
            'doctors_per_100k' => 'nullable|numeric',
            'nurses_per_100k' => 'nullable|numeric',
            'hospital_beds_per_100k' => 'nullable|numeric',
            'health_spending_gdp_pct' => 'nullable|numeric',
            'life_expectancy' => 'nullable|numeric',
            'infant_mortality_rate' => 'nullable|numeric',
        ]);

        FranceHealth::updateOrCreate(
            ['year' => $annee],
            $validated
        );

        return redirect()->route('admin.stats-france.sante', ['annee' => $annee])
            ->with('success', 'Données santé mises à jour.');
    }

    /**
     * Édition des données environnement
     */
    public function environnement(Request $request): Response
    {
        $annee = $request->input('annee', FranceEnvironment::max('year') ?? date('Y'));
        $data = FranceEnvironment::where('year', $annee)->first();
        $anneesDisponibles = FranceEnvironment::orderBy('year', 'desc')->pluck('year')->toArray();

        return Inertia::render('Admin/StatistiquesFrance/Environnement', [
            'annee' => $annee,
            'anneesDisponibles' => $anneesDisponibles,
            'data' => $data,
        ]);
    }

    public function updateEnvironnement(Request $request, int $annee)
    {
        $validated = $request->validate([
            'co2_emissions_mt' => 'nullable|numeric',
            'renewable_energy_pct' => 'nullable|numeric',
            'recycling_rate' => 'nullable|numeric',
            'air_quality_index' => 'nullable|numeric',
            'protected_areas_pct' => 'nullable|numeric',
        ]);

        FranceEnvironment::updateOrCreate(
            ['year' => $annee],
            $validated
        );

        return redirect()->route('admin.stats-france.environnement', ['annee' => $annee])
            ->with('success', 'Données environnement mises à jour.');
    }

    /**
     * Édition des données sécurité
     */
    public function securite(Request $request): Response
    {
        $annee = $request->input('annee', FranceSecurity::max('year') ?? date('Y'));
        $data = FranceSecurity::where('year', $annee)->first();
        $anneesDisponibles = FranceSecurity::orderBy('year', 'desc')->pluck('year')->toArray();

        return Inertia::render('Admin/StatistiquesFrance/Securite', [
            'annee' => $annee,
            'anneesDisponibles' => $anneesDisponibles,
            'data' => $data,
        ]);
    }

    public function updateSecurite(Request $request, int $annee)
    {
        $validated = $request->validate([
            'crime_rate_per_100k' => 'nullable|numeric',
            'burglary_rate' => 'nullable|numeric',
            'homicide_rate' => 'nullable|numeric',
            'road_deaths' => 'nullable|integer',
            'police_per_100k' => 'nullable|numeric',
        ]);

        FranceSecurity::updateOrCreate(
            ['year' => $annee],
            $validated
        );

        return redirect()->route('admin.stats-france.securite', ['annee' => $annee])
            ->with('success', 'Données sécurité mises à jour.');
    }

    /**
     * Édition des données emploi
     */
    public function emploi(Request $request): Response
    {
        $annee = $request->input('annee', FranceEmploymentDetailed::max('year') ?? date('Y'));
        $data = FranceEmploymentDetailed::where('year', $annee)->first();
        $anneesDisponibles = FranceEmploymentDetailed::orderBy('year', 'desc')->pluck('year')->toArray();

        return Inertia::render('Admin/StatistiquesFrance/Emploi', [
            'annee' => $annee,
            'anneesDisponibles' => $anneesDisponibles,
            'data' => $data,
        ]);
    }

    public function updateEmploi(Request $request, int $annee)
    {
        $validated = $request->validate([
            'employment_rate' => 'nullable|numeric',
            'unemployment_rate' => 'nullable|numeric',
            'youth_unemployment_rate' => 'nullable|numeric',
            'part_time_rate' => 'nullable|numeric',
            'minimum_wage_euros' => 'nullable|numeric',
            'average_wage_euros' => 'nullable|numeric',
        ]);

        FranceEmploymentDetailed::updateOrCreate(
            ['year' => $annee],
            $validated
        );

        return redirect()->route('admin.stats-france.emploi', ['annee' => $annee])
            ->with('success', 'Données emploi mises à jour.');
    }

    /**
     * Créer des données pour une nouvelle année
     */
    public function createYear(Request $request)
    {
        $annee = $request->input('annee');

        if (! $annee || $annee < 2000 || $annee > 2030) {
            return back()->with('error', 'Année invalide.');
        }

        // Créer les entrées vides pour toutes les tables avec valeurs par défaut
        FranceDemographics::firstOrCreate(
            ['year' => $annee],
            [
                'population_total' => 0,
                'population_by_age_group' => json_encode([]),
                'population_by_gender' => json_encode(['hommes' => 0, 'femmes' => 0]),
            ]
        );
        FranceEconomy::firstOrCreate(['year' => $annee, 'quarter' => null]);
        BudgetAnnuel::firstOrCreate(['annee' => $annee]);
        FranceBudgetRevenue::firstOrCreate(['year' => $annee]);
        FranceBudgetSpending::firstOrCreate(['year' => $annee]);
        FranceEducation::firstOrCreate(['year' => $annee]);
        FranceHealth::firstOrCreate(['year' => $annee]);
        FranceEnvironment::firstOrCreate(['year' => $annee]);
        FranceSecurity::firstOrCreate(['year' => $annee]);
        FranceEmploymentDetailed::firstOrCreate(['year' => $annee]);

        return redirect()->route('admin.stats-france.index', ['annee' => $annee])
            ->with('success', "Année {$annee} créée avec succès.");
    }
}
