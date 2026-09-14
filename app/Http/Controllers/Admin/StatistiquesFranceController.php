<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ChampsStatistiques;
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
    /*
    |--------------------------------------------------------------------------
    | Écrans annuels
    |--------------------------------------------------------------------------
    |
    | Les champs affichés ET les règles de validation dérivent tous deux des
    | colonnes réelles, via ChampsStatistiques. Auparavant chaque écran portait sa
    | propre liste, dérivée à son tour d'une liste du contrôleur elle-même dérivée
    | des colonnes : sur soixante-quatre champs de saisie, dix-neuf atteignaient la
    | base, et quatre écrans n'écrivaient rien en affichant « mises à jour ».
    |
    */

    public function demographie(Request $request): Response
    {
        $annee = $request->input('annee', FranceDemographics::max('year') ?? date('Y'));
        $data = FranceDemographics::where('year', $annee)->first();
        $anneesDisponibles = FranceDemographics::orderBy('year', 'desc')->pluck('year')->toArray();

        return Inertia::render('Admin/StatistiquesFrance/Demographie', [
            'annee' => $annee,
            'anneesDisponibles' => $anneesDisponibles,
            'champs' => ChampsStatistiques::pour(FranceDemographics::class),
            'data' => $data,
        ]);
    }

    public function updateDemographie(Request $request, int $annee)
    {
        $validated = $request->validate(ChampsStatistiques::regles(FranceDemographics::class));

        FranceDemographics::updateOrCreate(['year' => $annee], $validated);

        return redirect()->route('admin.stats-france.demographie', ['annee' => $annee])
            ->with('success', "Données « Démographie » enregistrées pour {$annee}.");
    }

    public function economie(Request $request): Response
    {
        $annee = $request->input('annee', FranceEconomy::whereNull('quarter')->max('year') ?? date('Y'));
        $data = FranceEconomy::where('year', $annee)->whereNull('quarter')->first();
        $anneesDisponibles = FranceEconomy::whereNull('quarter')->orderBy('year', 'desc')->pluck('year')->toArray();
        $dataQuarterly = FranceEconomy::where('year', $annee)->whereNotNull('quarter')->get();

        return Inertia::render('Admin/StatistiquesFrance/Economie', [
            'annee' => $annee,
            'anneesDisponibles' => $anneesDisponibles,
            'champs' => ChampsStatistiques::pour(FranceEconomy::class),
            'data' => $data,
            'dataQuarterly' => $dataQuarterly,
        ]);
    }

    public function updateEconomie(Request $request, int $annee)
    {
        $validated = $request->validate(ChampsStatistiques::regles(FranceEconomy::class));

        FranceEconomy::updateOrCreate(['year' => $annee, 'quarter' => null], $validated);

        return redirect()->route('admin.stats-france.economie', ['annee' => $annee])
            ->with('success', "Données « Économie » enregistrées pour {$annee}.");
    }

    public function budget(Request $request): Response
    {
        $annee = $request->input('annee', BudgetAnnuel::max('annee') ?? date('Y'));
        $data = BudgetAnnuel::where('annee', $annee)->first();
        $anneesDisponibles = BudgetAnnuel::orderBy('annee', 'desc')->pluck('annee')->toArray();
        $missions = \App\Models\BudgetMission::where('annee', $annee)
            ->orderByDesc('credits_cp')
            ->get();

        return Inertia::render('Admin/StatistiquesFrance/Budget', [
            'annee' => $annee,
            'anneesDisponibles' => $anneesDisponibles,
            'champs' => ChampsStatistiques::pour(BudgetAnnuel::class),
            'data' => $data,
            'missions' => $missions,
        ]);
    }

    public function updateBudget(Request $request, int $annee)
    {
        $validated = $request->validate(ChampsStatistiques::regles(BudgetAnnuel::class));

        BudgetAnnuel::updateOrCreate(['annee' => $annee], $validated);

        return redirect()->route('admin.stats-france.budget', ['annee' => $annee])
            ->with('success', "Données « Budget de l'État » enregistrées pour {$annee}.");
    }

    public function recettes(Request $request): Response
    {
        $annee = $request->input('annee', FranceBudgetRevenue::max('year') ?? date('Y'));
        $data = FranceBudgetRevenue::where('year', $annee)->first();
        $anneesDisponibles = FranceBudgetRevenue::orderBy('year', 'desc')->pluck('year')->toArray();

        return Inertia::render('Admin/StatistiquesFrance/Recettes', [
            'annee' => $annee,
            'anneesDisponibles' => $anneesDisponibles,
            'champs' => ChampsStatistiques::pour(FranceBudgetRevenue::class),
            'data' => $data,
        ]);
    }

    public function updateRecettes(Request $request, int $annee)
    {
        $validated = $request->validate(ChampsStatistiques::regles(FranceBudgetRevenue::class));

        FranceBudgetRevenue::updateOrCreate(['year' => $annee], $validated);

        return redirect()->route('admin.stats-france.recettes', ['annee' => $annee])
            ->with('success', "Données « Recettes publiques » enregistrées pour {$annee}.");
    }

    public function depenses(Request $request): Response
    {
        $annee = $request->input('annee', FranceBudgetSpending::max('year') ?? date('Y'));
        $data = FranceBudgetSpending::where('year', $annee)->first();
        $anneesDisponibles = FranceBudgetSpending::orderBy('year', 'desc')->pluck('year')->toArray();

        return Inertia::render('Admin/StatistiquesFrance/Depenses', [
            'annee' => $annee,
            'anneesDisponibles' => $anneesDisponibles,
            'champs' => ChampsStatistiques::pour(FranceBudgetSpending::class),
            'data' => $data,
        ]);
    }

    public function updateDepenses(Request $request, int $annee)
    {
        $validated = $request->validate(ChampsStatistiques::regles(FranceBudgetSpending::class));

        FranceBudgetSpending::updateOrCreate(['year' => $annee], $validated);

        return redirect()->route('admin.stats-france.depenses', ['annee' => $annee])
            ->with('success', "Données « Dépenses publiques » enregistrées pour {$annee}.");
    }

    public function education(Request $request): Response
    {
        $annee = $request->input('annee', FranceEducation::max('year') ?? date('Y'));
        $data = FranceEducation::where('year', $annee)->first();
        $anneesDisponibles = FranceEducation::orderBy('year', 'desc')->pluck('year')->toArray();

        return Inertia::render('Admin/StatistiquesFrance/Education', [
            'annee' => $annee,
            'anneesDisponibles' => $anneesDisponibles,
            'champs' => ChampsStatistiques::pour(FranceEducation::class),
            'data' => $data,
        ]);
    }

    public function updateEducation(Request $request, int $annee)
    {
        $validated = $request->validate(ChampsStatistiques::regles(FranceEducation::class));

        FranceEducation::updateOrCreate(['year' => $annee], $validated);

        return redirect()->route('admin.stats-france.education', ['annee' => $annee])
            ->with('success', "Données « Éducation » enregistrées pour {$annee}.");
    }

    public function sante(Request $request): Response
    {
        $annee = $request->input('annee', FranceHealth::max('year') ?? date('Y'));
        $data = FranceHealth::where('year', $annee)->first();
        $anneesDisponibles = FranceHealth::orderBy('year', 'desc')->pluck('year')->toArray();

        return Inertia::render('Admin/StatistiquesFrance/Sante', [
            'annee' => $annee,
            'anneesDisponibles' => $anneesDisponibles,
            'champs' => ChampsStatistiques::pour(FranceHealth::class),
            'data' => $data,
        ]);
    }

    public function updateSante(Request $request, int $annee)
    {
        $validated = $request->validate(ChampsStatistiques::regles(FranceHealth::class));

        FranceHealth::updateOrCreate(['year' => $annee], $validated);

        return redirect()->route('admin.stats-france.sante', ['annee' => $annee])
            ->with('success', "Données « Santé » enregistrées pour {$annee}.");
    }

    public function environnement(Request $request): Response
    {
        $annee = $request->input('annee', FranceEnvironment::max('year') ?? date('Y'));
        $data = FranceEnvironment::where('year', $annee)->first();
        $anneesDisponibles = FranceEnvironment::orderBy('year', 'desc')->pluck('year')->toArray();

        return Inertia::render('Admin/StatistiquesFrance/Environnement', [
            'annee' => $annee,
            'anneesDisponibles' => $anneesDisponibles,
            'champs' => ChampsStatistiques::pour(FranceEnvironment::class),
            'data' => $data,
        ]);
    }

    public function updateEnvironnement(Request $request, int $annee)
    {
        $validated = $request->validate(ChampsStatistiques::regles(FranceEnvironment::class));

        FranceEnvironment::updateOrCreate(['year' => $annee], $validated);

        return redirect()->route('admin.stats-france.environnement', ['annee' => $annee])
            ->with('success', "Données « Environnement » enregistrées pour {$annee}.");
    }

    public function securite(Request $request): Response
    {
        $annee = $request->input('annee', FranceSecurity::max('year') ?? date('Y'));
        $data = FranceSecurity::where('year', $annee)->first();
        $anneesDisponibles = FranceSecurity::orderBy('year', 'desc')->pluck('year')->toArray();

        return Inertia::render('Admin/StatistiquesFrance/Securite', [
            'annee' => $annee,
            'anneesDisponibles' => $anneesDisponibles,
            'champs' => ChampsStatistiques::pour(FranceSecurity::class),
            'data' => $data,
        ]);
    }

    public function updateSecurite(Request $request, int $annee)
    {
        $validated = $request->validate(ChampsStatistiques::regles(FranceSecurity::class));

        FranceSecurity::updateOrCreate(['year' => $annee], $validated);

        return redirect()->route('admin.stats-france.securite', ['annee' => $annee])
            ->with('success', "Données « Sécurité » enregistrées pour {$annee}.");
    }

    public function emploi(Request $request): Response
    {
        $annee = $request->input('annee', FranceEmploymentDetailed::max('year') ?? date('Y'));
        $data = FranceEmploymentDetailed::where('year', $annee)->first();
        $anneesDisponibles = FranceEmploymentDetailed::orderBy('year', 'desc')->pluck('year')->toArray();

        return Inertia::render('Admin/StatistiquesFrance/Emploi', [
            'annee' => $annee,
            'anneesDisponibles' => $anneesDisponibles,
            'champs' => ChampsStatistiques::pour(FranceEmploymentDetailed::class),
            'data' => $data,
        ]);
    }

    public function updateEmploi(Request $request, int $annee)
    {
        $validated = $request->validate(ChampsStatistiques::regles(FranceEmploymentDetailed::class));

        FranceEmploymentDetailed::updateOrCreate(['year' => $annee], $validated);

        return redirect()->route('admin.stats-france.emploi', ['annee' => $annee])
            ->with('success', "Données « Emploi » enregistrées pour {$annee}.");
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
