<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\FranceRegionalData;
use App\Models\Ville;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StatistiquesRegionsController extends Controller
{
    /**
     * Liste des régions avec statistiques
     */
    public function index(Request $request): Response
    {
        $annee = $request->input('annee', 2024);

        // Régions depuis FranceRegionalData (indicateurs économiques/sociaux)
        $regionsData = FranceRegionalData::forYear($annee)->get()->keyBy('region_code');

        // Agrégation depuis les villes (communes, population réelle)
        $villesStats = Ville::where('arrondissement_municipal', false)
            ->whereNotNull('region_code')
            ->select('region_code', 'region_nom')
            ->selectRaw('COUNT(*) as nb_communes')
            ->selectRaw('SUM(population) as population_insee')
            ->selectRaw('SUM(superficie_km2) as superficie')
            ->groupBy('region_code', 'region_nom')
            ->orderByDesc('population_insee')
            ->get();

        // Fusionner les données
        $regions = $villesStats->map(function ($vs) use ($regionsData) {
            $rd = $regionsData->get($vs->region_code);

            return [
                'code' => $vs->region_code,
                'nom' => $vs->region_nom,
                'nb_communes' => (int) $vs->nb_communes,
                'population' => (int) ($rd?->population ?? $vs->population_insee),
                'population_formate' => number_format($rd?->population ?? $vs->population_insee, 0, ',', ' '),
                'population_millions' => round(($rd?->population ?? $vs->population_insee) / 1_000_000, 2),
                'superficie' => round($vs->superficie ?? 0),
                'densite' => ($vs->superficie ?? 0) > 0
                    ? round(($rd?->population ?? $vs->population_insee) / $vs->superficie, 1)
                    : 0,
                // Indicateurs économiques/sociaux (depuis FranceRegionalData)
                'pib' => $rd?->gdp_billion_euros,
                'pib_formate' => $rd?->gdp_billion_euros ? number_format($rd->gdp_billion_euros, 1, ',', ' ').' Md€' : null,
                'taux_chomage' => $rd?->unemployment_rate,
                'taux_pauvrete' => $rd?->poverty_rate,
                'revenu_median' => $rd?->median_income,
                'revenu_median_formate' => $rd?->median_income ? number_format($rd->median_income, 0, ',', ' ').' €' : null,
                'esperance_vie' => $rd?->life_expectancy,
                'est_drom' => in_array($vs->region_code, ['01', '02', '03', '04', '06']),
            ];
        })->values()->toArray();

        // Totaux nationaux
        $totaux = [
            'nb_regions' => count($regions),
            'nb_communes' => array_sum(array_column($regions, 'nb_communes')),
            'population' => array_sum(array_column($regions, 'population')),
            'population_formate' => number_format(array_sum(array_column($regions, 'population')), 0, ',', ' '),
            'population_millions' => round(array_sum(array_column($regions, 'population')) / 1_000_000, 1),
            'superficie' => array_sum(array_column($regions, 'superficie')),
        ];

        // Années disponibles
        $anneesDisponibles = FranceRegionalData::distinct('year')
            ->orderByDesc('year')
            ->pluck('year')
            ->toArray();

        if (empty($anneesDisponibles)) {
            $anneesDisponibles = [2024, 2023, 2022];
        }

        return Inertia::render('Statistics/Regions/Index', [
            'regions' => $regions,
            'totaux' => $totaux,
            'annee' => $annee,
            'anneesDisponibles' => $anneesDisponibles,
            'breadcrumbs' => [
                ['label' => 'Accueil', 'url' => route('dashboard')],
                ['label' => 'Statistiques', 'url' => route('statistics.france')],
                ['label' => 'Régions'],
            ],
        ]);
    }

    /**
     * Détail d'une région
     */
    public function show(Request $request, string $code): Response
    {
        $annee = $request->input('annee', 2024);

        // Données régionales depuis FranceRegionalData
        $regionData = FranceRegionalData::forYear($annee)
            ->where('region_code', $code)
            ->first();

        // Infos depuis les villes
        $villeInfo = Ville::where('arrondissement_municipal', false)
            ->where('region_code', $code)
            ->select('region_code', 'region_nom')
            ->selectRaw('COUNT(*) as nb_communes')
            ->selectRaw('SUM(population) as population')
            ->selectRaw('SUM(superficie_km2) as superficie')
            ->groupBy('region_code', 'region_nom')
            ->first();

        if (! $villeInfo) {
            abort(404, 'Région non trouvée');
        }

        $region = [
            'code' => $villeInfo->region_code,
            'nom' => $villeInfo->region_nom,
            'nb_communes' => (int) $villeInfo->nb_communes,
            'population' => (int) ($regionData?->population ?? $villeInfo->population),
            'population_formate' => number_format($regionData?->population ?? $villeInfo->population, 0, ',', ' '),
            'superficie' => round($villeInfo->superficie ?? 0),
            'densite' => ($villeInfo->superficie ?? 0) > 0
                ? round(($regionData?->population ?? $villeInfo->population) / $villeInfo->superficie, 1)
                : 0,
            // Indicateurs économiques
            'pib' => $regionData?->gdp_billion_euros,
            'pib_formate' => $regionData?->gdp_billion_euros ? number_format($regionData->gdp_billion_euros, 1, ',', ' ').' Md€' : null,
            'taux_chomage' => $regionData?->unemployment_rate,
            'taux_pauvrete' => $regionData?->poverty_rate,
            'revenu_median' => $regionData?->median_income,
            'revenu_median_formate' => $regionData?->median_income ? number_format($regionData->median_income, 0, ',', ' ').' €' : null,
            'esperance_vie' => $regionData?->life_expectancy,
            'est_drom' => in_array($code, ['01', '02', '03', '04', '06']),
        ];

        // Départements de la région
        $departements = Ville::where('arrondissement_municipal', false)
            ->where('region_code', $code)
            ->whereNotNull('departement_code')
            ->select('departement_code', 'departement_nom')
            ->selectRaw('COUNT(*) as nb_communes')
            ->selectRaw('SUM(population) as population')
            ->selectRaw('SUM(superficie_km2) as superficie')
            ->groupBy('departement_code', 'departement_nom')
            ->orderByDesc('population')
            ->get()
            ->map(fn ($d) => [
                'code' => $d->departement_code,
                'nom' => $d->departement_nom,
                'nb_communes' => (int) $d->nb_communes,
                'population' => (int) $d->population,
                'population_formate' => number_format($d->population, 0, ',', ' '),
                'superficie' => round($d->superficie ?? 0),
                'densite' => ($d->superficie ?? 0) > 0
                    ? round($d->population / $d->superficie, 1)
                    : 0,
            ])
            ->toArray();

        // Top 10 villes de la région
        $topVilles = Ville::where('arrondissement_municipal', false)
            ->where('region_code', $code)
            ->whereNotNull('population')
            ->where('population', '>', 0)
            ->orderByDesc('population')
            ->take(10)
            ->get()
            ->map(fn ($v) => [
                'id' => $v->id,
                'nom' => $v->nom,
                'slug' => $v->slug,
                'code_insee' => $v->code_insee,
                'departement' => $v->departement_nom,
                'population' => $v->population,
                'population_formate' => number_format($v->population, 0, ',', ' '),
                'url' => route('villes.show', $v->slug),
                'est_prefecture' => $v->est_prefecture,
            ])
            ->toArray();

        // Préfecture régionale
        $prefecture = Ville::where('region_code', $code)
            ->where('est_chef_lieu_region', true)
            ->first();

        // Moyennes nationales pour comparaison
        $moyennesNationales = [
            'taux_chomage' => 7.4,
            'taux_pauvrete' => 14.5,
            'revenu_median' => 22500,
        ];

        return Inertia::render('Statistics/Regions/Show', [
            'region' => $region,
            'departements' => $departements,
            'topVilles' => $topVilles,
            'prefecture' => $prefecture ? [
                'nom' => $prefecture->nom,
                'slug' => $prefecture->slug,
                'url' => route('villes.show', $prefecture->slug),
            ] : null,
            'moyennesNationales' => $moyennesNationales,
            'annee' => $annee,
            'breadcrumbs' => [
                ['label' => 'Accueil', 'url' => route('dashboard')],
                ['label' => 'Statistiques', 'url' => route('statistics.france')],
                ['label' => 'Régions', 'url' => route('statistics.regions.index')],
                ['label' => $region['nom']],
            ],
        ]);
    }
}
