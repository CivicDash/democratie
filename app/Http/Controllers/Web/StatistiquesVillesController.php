<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CommuneBudget;
use App\Models\Ville;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StatistiquesVillesController extends Controller
{
    /**
     * Page principale des statistiques des villes
     */
    public function index(Request $request): Response
    {
        // Utiliser la dernière année disponible par défaut
        $anneesDisponibles = $this->getAnneesDisponibles();
        $anneeDefaut = ! empty($anneesDisponibles) ? $anneesDisponibles[0] : date('Y');
        $annee = $request->input('annee', $anneeDefaut);

        // Stats globales (cache 1h)
        $statsGlobales = Cache::remember('stats_villes_globales', 3600, function () {
            return $this->getStatsGlobales();
        });

        // Stats par région
        $statsParRegion = Cache::remember('stats_villes_par_region', 3600, function () {
            return $this->getStatsParRegion();
        });

        // Stats par tranche de population
        $statsParTaille = Cache::remember('stats_villes_par_taille', 3600, function () {
            return $this->getStatsParTaille();
        });

        // Top villes par population
        $topVilles = Cache::remember('stats_villes_top_population', 3600, function () {
            return $this->getTopVilles(20);
        });

        // Stats budgétaires agrégées
        $statsBudget = Cache::remember("stats_villes_budget_{$annee}", 3600, function () use ($annee) {
            return $this->getStatsBudgetaires($annee);
        });

        // Évolution démographique nationale
        $evolutionPopulation = Cache::remember('stats_villes_evolution_pop', 3600, function () {
            return $this->getEvolutionPopulation();
        });

        return Inertia::render('Statistics/Villes/Index', [
            'statsGlobales' => $statsGlobales,
            'statsParRegion' => $statsParRegion,
            'statsParTaille' => $statsParTaille,
            'topVilles' => $topVilles,
            'statsBudget' => $statsBudget,
            'evolutionPopulation' => $evolutionPopulation,
            'annee' => (int) $annee,
            'anneesDisponibles' => $this->getAnneesDisponibles(),
            'breadcrumbs' => [
                ['label' => 'Accueil', 'href' => route('dashboard'), 'icon' => '🏠'],
                ['label' => 'Données', 'icon' => '📊'],
                ['label' => 'Statistiques Villes', 'current' => true, 'icon' => '🏘️'],
            ],
        ]);
    }

    /**
     * Statistiques globales
     */
    private function getStatsGlobales(): array
    {
        $totalVilles = Ville::where('arrondissement_municipal', false)->count();
        $totalPopulation = Ville::where('arrondissement_municipal', false)->sum('population');
        $totalSuperficie = Ville::where('arrondissement_municipal', false)->sum('superficie_km2');

        $densiteMoyenne = $totalSuperficie > 0
            ? round($totalPopulation / $totalSuperficie, 1)
            : 0;

        $villesAvecMaire = Ville::whereNotNull('maire_actuel_id')->count();
        $nbRegions = Ville::distinct('region_code')->count('region_code');
        $nbDepartements = Ville::distinct('departement_code')->count('departement_code');

        // Tranches de population
        $moins1000 = Ville::where('arrondissement_municipal', false)
            ->where('population', '<', 1000)->count();
        $entre1000et10000 = Ville::where('arrondissement_municipal', false)
            ->whereBetween('population', [1000, 9999])->count();
        $entre10000et50000 = Ville::where('arrondissement_municipal', false)
            ->whereBetween('population', [10000, 49999])->count();
        $plus50000 = Ville::where('arrondissement_municipal', false)
            ->where('population', '>=', 50000)->count();

        // Ville la plus densément peuplée (avec superficie > 0)
        $villePlusDense = Ville::where('arrondissement_municipal', false)
            ->whereNotNull('superficie_km2')
            ->where('superficie_km2', '>', 0)
            ->whereNotNull('population')
            ->where('population', '>', 0)
            ->orderByRaw('population / superficie_km2 DESC')
            ->first();

        $villePlusDenseInfo = null;
        if ($villePlusDense) {
            $villePlusDenseInfo = [
                'nom' => $villePlusDense->nom,
                'departement' => $villePlusDense->departement_nom,
                'url' => $villePlusDense->url,
                'densite' => $villePlusDense->superficie_km2 > 0
                    ? round($villePlusDense->population / $villePlusDense->superficie_km2)
                    : 0,
                'population' => $villePlusDense->population,
                'population_formate' => $villePlusDense->population_formate,
            ];
        }

        // Ville la moins densément peuplée (avec population > 0 et superficie > 0)
        $villeMoinsDense = Ville::where('arrondissement_municipal', false)
            ->whereNotNull('superficie_km2')
            ->where('superficie_km2', '>', 0)
            ->whereNotNull('population')
            ->where('population', '>', 0)
            ->orderByRaw('population / superficie_km2 ASC')
            ->first();

        $villeMoinsDenseInfo = null;
        if ($villeMoinsDense) {
            $villeMoinsDenseInfo = [
                'nom' => $villeMoinsDense->nom,
                'departement' => $villeMoinsDense->departement_nom,
                'url' => $villeMoinsDense->url,
                'densite' => $villeMoinsDense->superficie_km2 > 0
                    ? round($villeMoinsDense->population / $villeMoinsDense->superficie_km2, 2)
                    : 0,
                'population' => $villeMoinsDense->population,
                'population_formate' => $villeMoinsDense->population_formate,
                'superficie' => round($villeMoinsDense->superficie_km2, 1),
            ];
        }

        return [
            'total_villes' => $totalVilles,
            'total_villes_formate' => number_format($totalVilles, 0, ',', ' '),
            'total_population' => $totalPopulation,
            'total_population_formate' => number_format($totalPopulation, 0, ',', ' '),
            'total_population_millions' => round($totalPopulation / 1_000_000, 1),
            'total_superficie_km2' => round($totalSuperficie),
            'densite_moyenne' => $densiteMoyenne,
            'villes_avec_maire' => $villesAvecMaire,
            'pct_villes_avec_maire' => $totalVilles > 0
                ? round(($villesAvecMaire / $totalVilles) * 100, 1)
                : 0,
            'nb_regions' => $nbRegions,
            'nb_departements' => $nbDepartements,
            'repartition_taille' => [
                ['label' => '< 1 000 hab.', 'count' => $moins1000, 'pct' => round($moins1000 / $totalVilles * 100, 1)],
                ['label' => '1 000 - 10 000', 'count' => $entre1000et10000, 'pct' => round($entre1000et10000 / $totalVilles * 100, 1)],
                ['label' => '10 000 - 50 000', 'count' => $entre10000et50000, 'pct' => round($entre10000et50000 / $totalVilles * 100, 1)],
                ['label' => '> 50 000 hab.', 'count' => $plus50000, 'pct' => round($plus50000 / $totalVilles * 100, 1)],
            ],
            // Extrêmes de densité
            'ville_plus_dense' => $villePlusDenseInfo,
            'ville_moins_dense' => $villeMoinsDenseInfo,
        ];
    }

    /**
     * Statistiques par région
     */
    private function getStatsParRegion(): array
    {
        return Ville::where('arrondissement_municipal', false)
            ->whereNotNull('region_nom')
            ->select('region_code', 'region_nom')
            ->selectRaw('COUNT(*) as nb_villes')
            ->selectRaw('SUM(population) as population')
            ->selectRaw('SUM(superficie_km2) as superficie')
            ->selectRaw('AVG(population) as pop_moyenne')
            ->groupBy('region_code', 'region_nom')
            ->orderByDesc('population')
            ->get()
            ->map(fn ($r) => [
                'code' => $r->region_code,
                'nom' => $r->region_nom,
                'nb_villes' => (int) $r->nb_villes,
                'population' => (int) $r->population,
                'population_formate' => number_format($r->population, 0, ',', ' '),
                'population_millions' => round($r->population / 1_000_000, 2),
                'superficie' => round($r->superficie ?? 0),
                'densite' => ($r->superficie ?? 0) > 0
                    ? round($r->population / $r->superficie, 1)
                    : 0,
                'pop_moyenne' => round($r->pop_moyenne ?? 0),
            ])
            ->toArray();
    }

    /**
     * Répartition par taille de ville
     */
    private function getStatsParTaille(): array
    {
        $tranches = [
            ['min' => 0, 'max' => 500, 'label' => '< 500 hab.'],
            ['min' => 500, 'max' => 2000, 'label' => '500 - 2 000'],
            ['min' => 2000, 'max' => 10000, 'label' => '2 000 - 10 000'],
            ['min' => 10000, 'max' => 50000, 'label' => '10 000 - 50 000'],
            ['min' => 50000, 'max' => 200000, 'label' => '50 000 - 200 000'],
            ['min' => 200000, 'max' => PHP_INT_MAX, 'label' => '> 200 000'],
        ];

        $result = [];
        foreach ($tranches as $tranche) {
            $query = Ville::where('arrondissement_municipal', false)
                ->where('population', '>=', $tranche['min']);

            if ($tranche['max'] < PHP_INT_MAX) {
                $query->where('population', '<', $tranche['max']);
            }

            $count = $query->count();
            $population = $query->sum('population');

            $result[] = [
                'label' => $tranche['label'],
                'count' => $count,
                'population' => $population,
                'population_formate' => number_format($population, 0, ',', ' '),
            ];
        }

        return $result;
    }

    /**
     * Top villes par population
     */
    private function getTopVilles(int $limit = 20): array
    {
        return Ville::where('arrondissement_municipal', false)
            ->whereNotNull('population')
            ->where('population', '>', 0)
            ->orderByDesc('population')
            ->limit($limit)
            ->with('maireActuel:id,nom,prenom,civilite,photo_url')
            ->get()
            ->map(fn ($v) => [
                'id' => $v->id,
                'nom' => $v->nom,
                'slug' => $v->slug,
                'departement' => $v->departement_nom,
                'region' => $v->region_nom,
                'population' => $v->population,
                'population_formate' => $v->population_formate,
                'densite' => $v->densite,
                'url' => $v->url,
                'maire' => $v->maireActuel ? [
                    'nom' => $v->maireActuel->nom_complet ?? trim($v->maireActuel->prenom.' '.$v->maireActuel->nom),
                    'photo_url' => $v->maireActuel->photo_url,
                ] : null,
            ])
            ->toArray();
    }

    /**
     * Statistiques budgétaires agrégées
     */
    private function getStatsBudgetaires(int $annee): array
    {
        $budgets = CommuneBudget::where('annee', $annee)->get();

        if ($budgets->isEmpty()) {
            // Essayer l'année précédente
            $budgets = CommuneBudget::where('annee', $annee - 1)->get();
            $annee = $annee - 1;
        }

        if ($budgets->isEmpty()) {
            return [
                'annee' => null,
                'nb_communes' => 0,
                'has_data' => false,
            ];
        }

        $totalRecettesFonct = $budgets->sum('recettes_fonctionnement');
        $totalDepensesFonct = $budgets->sum('depenses_fonctionnement');
        $totalRecettesInvest = $budgets->sum('recettes_investissement');
        $totalDepensesInvest = $budgets->sum('depenses_investissement');
        $totalDette = $budgets->sum('encours_dette');

        $populationCouverte = Ville::whereIn('code_insee', $budgets->pluck('insee_code'))
            ->sum('population');

        // % de villes endettées (dette > 0)
        $villesEndettees = $budgets->filter(fn ($b) => ($b->encours_dette ?? 0) > 0)->count();
        $pctVillesEndettees = $budgets->count() > 0
            ? round(($villesEndettees / $budgets->count()) * 100, 1)
            : 0;

        // Ville avec le plus gros budget (dépenses fonctionnement)
        $villeMaxBudget = CommuneBudget::where('annee', $annee)
            ->whereNotNull('depenses_fonctionnement')
            ->orderByDesc('depenses_fonctionnement')
            ->first();
        $villeMaxBudgetInfo = null;
        if ($villeMaxBudget) {
            $villeMax = Ville::where('code_insee', $villeMaxBudget->insee_code)->first();
            $villeMaxBudgetInfo = [
                'nom' => $villeMax?->nom ?? 'Inconnue',
                'code_insee' => $villeMaxBudget->insee_code,
                'url' => $villeMax?->url,
                'montant' => $villeMaxBudget->depenses_fonctionnement,
                'montant_formate' => CommuneBudget::formatMontant($villeMaxBudget->depenses_fonctionnement),
            ];
        }

        // Ville avec le plus petit budget (dépenses fonctionnement > 0)
        $villeMinBudget = CommuneBudget::where('annee', $annee)
            ->whereNotNull('depenses_fonctionnement')
            ->where('depenses_fonctionnement', '>', 0)
            ->orderBy('depenses_fonctionnement')
            ->first();
        $villeMinBudgetInfo = null;
        if ($villeMinBudget) {
            $villeMin = Ville::where('code_insee', $villeMinBudget->insee_code)->first();
            $villeMinBudgetInfo = [
                'nom' => $villeMin?->nom ?? 'Inconnue',
                'code_insee' => $villeMinBudget->insee_code,
                'url' => $villeMin?->url,
                'montant' => $villeMinBudget->depenses_fonctionnement,
                'montant_formate' => CommuneBudget::formatMontant($villeMinBudget->depenses_fonctionnement),
            ];
        }

        // Moyenne de budget par ville
        $moyenneBudget = $budgets->count() > 0
            ? $totalDepensesFonct / $budgets->count()
            : 0;

        // Moyenne d'endettement par habitant
        $moyenneDetteParHab = $populationCouverte > 0
            ? round($totalDette / $populationCouverte)
            : 0;

        return [
            'annee' => $annee,
            'nb_communes' => $budgets->count(),
            'has_data' => true,
            'population_couverte' => $populationCouverte,
            'population_couverte_formate' => number_format($populationCouverte, 0, ',', ' '),
            'recettes_fonctionnement' => $totalRecettesFonct,
            'recettes_fonctionnement_md' => round($totalRecettesFonct / 1_000_000_000, 1),
            'depenses_fonctionnement' => $totalDepensesFonct,
            'depenses_fonctionnement_md' => round($totalDepensesFonct / 1_000_000_000, 1),
            'recettes_investissement' => $totalRecettesInvest,
            'recettes_investissement_md' => round($totalRecettesInvest / 1_000_000_000, 1),
            'depenses_investissement' => $totalDepensesInvest,
            'depenses_investissement_md' => round($totalDepensesInvest / 1_000_000_000, 1),
            'dette_totale' => $totalDette,
            'dette_totale_md' => round($totalDette / 1_000_000_000, 1),
            'dette_par_habitant' => $moyenneDetteParHab,
            'solde_fonctionnement' => $totalRecettesFonct - $totalDepensesFonct,
            'solde_fonctionnement_md' => round(($totalRecettesFonct - $totalDepensesFonct) / 1_000_000_000, 1),
            // Nouvelles stats
            'pct_villes_endettees' => $pctVillesEndettees,
            'nb_villes_endettees' => $villesEndettees,
            'ville_max_budget' => $villeMaxBudgetInfo,
            'ville_min_budget' => $villeMinBudgetInfo,
            'moyenne_budget' => $moyenneBudget,
            'moyenne_budget_formate' => CommuneBudget::formatMontant($moyenneBudget),
            'moyenne_dette_par_habitant' => $moyenneDetteParHab,
        ];
    }

    /**
     * Évolution de la population nationale
     */
    private function getEvolutionPopulation(): array
    {
        // Si on a des données historiques dans villes_population
        $evolution = DB::table('villes_population')
            ->select('annee')
            ->selectRaw('SUM(population) as total')
            ->groupBy('annee')
            ->orderBy('annee')
            ->get();

        if ($evolution->isEmpty()) {
            // Fallback: données actuelles uniquement
            $currentPop = Ville::where('arrondissement_municipal', false)->sum('population');

            return [
                ['annee' => date('Y'), 'population' => $currentPop, 'population_millions' => round($currentPop / 1_000_000, 1)],
            ];
        }

        return $evolution->map(fn ($e) => [
            'annee' => (int) $e->annee,
            'population' => (int) $e->total,
            'population_millions' => round($e->total / 1_000_000, 1),
        ])->toArray();
    }

    /**
     * Années disponibles pour les budgets
     */
    private function getAnneesDisponibles(): array
    {
        return CommuneBudget::select('annee')
            ->distinct()
            ->orderByDesc('annee')
            ->pluck('annee')
            ->toArray();
    }
}
