<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\FrenchPostalCode;
use App\Models\Maire;
use App\Models\Senateur;
use App\Services\LocalisationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CommuneController extends Controller
{
    public function __construct(
        private LocalisationService $localisationService
    ) {}

    /**
     * Page de recherche des communes
     */
    public function index(Request $request): Response
    {
        $query = $request->input('q', '');
        $departement = $request->input('departement');
        
        $communes = collect();
        
        if (strlen($query) >= 2) {
            $communes = $this->localisationService->search($query, 50);
        } elseif ($departement) {
            $communes = FrenchPostalCode::where('department_code', $departement)
                ->orderByDesc('population')
                ->limit(100)
                ->get()
                ->map(fn($c) => $this->formatCommune($c));
        }

        $departements = Cache::remember('departements_list_unified', 3600, function () {
            return DB::table('french_postal_codes')
                ->select('department_code', 'department_name')
                ->distinct()
                ->orderBy('department_code')
                ->get()
                ->map(fn($d) => [
                    'code' => $d->department_code,
                    'nom' => $d->department_name ?? 'Département ' . $d->department_code,
                ]);
        });

        // Stats globales
        $stats = Cache::remember('communes_stats', 3600, function () {
            return [
                'total_communes' => DB::table('french_postal_codes')
                    ->selectRaw('COUNT(DISTINCT insee_code) as count')
                    ->value('count'),
                'total_population' => DB::table('french_postal_codes')
                    ->selectRaw('SUM(population) as total')
                    ->value('total') ?? 0,
            ];
        });

        return Inertia::render('Communes/Index', [
            'communes' => $communes,
            'query' => $query,
            'departement' => $departement,
            'departements' => $departements,
            'stats' => $stats,
        ]);
    }

    /**
     * Fiche détaillée d'une commune (par code INSEE)
     */
    public function show(string $inseeCode): Response
    {
        // Récupérer la commune principale (premier code postal)
        $commune = FrenchPostalCode::where('insee_code', $inseeCode)
            ->orderBy('postal_code')
            ->first();

        if (!$commune) {
            abort(404, 'Commune non trouvée');
        }

        // Récupérer tous les codes postaux de cette commune
        $codesPostaux = FrenchPostalCode::where('insee_code', $inseeCode)
            ->orderBy('postal_code')
            ->pluck('postal_code')
            ->unique()
            ->values();

        // Récupérer les représentants
        $representants = $this->localisationService->getRepresentants($inseeCode);

        // Budgets disponibles
        $budgets = $commune->budgets()
            ->orderByDesc('annee')
            ->get()
            ->map(fn($b) => [
                'annee' => $b->annee,
                'recettes_fonctionnement' => $b->recettes_fonctionnement,
                'depenses_fonctionnement' => $b->depenses_fonctionnement,
                'recettes_investissement' => $b->recettes_investissement,
                'depenses_investissement' => $b->depenses_investissement,
                'dette' => $b->encours_dette,
                'euros_par_habitant' => $b->euros_par_habitant,
            ]);

        // Communes voisines (même département, population similaire)
        $communesVoisines = $this->getCommunesVoisines($commune);

        return Inertia::render('Communes/Show', [
            'commune' => [
                'id' => $commune->id,
                'insee_code' => $commune->insee_code,
                'nom' => $commune->nom_commune,
                'city_name' => $commune->city_name,
                'codes_postaux' => $codesPostaux,
                'code_postal_principal' => $commune->postal_code,
                'department_code' => $commune->department_code,
                'department_name' => $commune->department_name,
                'region_name' => $commune->region_name,
                'population' => $commune->population,
                'population_formatted' => $commune->population_formatted,
                'superficie' => $commune->superficie,
                'densite' => $commune->densite,
                'latitude' => $commune->latitude,
                'longitude' => $commune->longitude,
                'epci_code' => $commune->epci_code,
                'epci_nom' => $commune->epci_nom,
                'circonscription' => $commune->circonscription,
                'est_chef_lieu_dep' => $commune->est_chef_lieu_dep,
                'est_chef_lieu_region' => $commune->est_chef_lieu_region,
                'zone_montagne' => $commune->zone_montagne,
                'zone_rurale' => $commune->zone_rurale,
                'outre_mer' => $commune->outre_mer,
            ],
            'maire' => $representants['maire'] ?? null,
            'senateurs' => $representants['senateurs'] ?? [],
            'deputes' => $representants['deputes'] ?? [],
            'budgets' => $budgets,
            'communesVoisines' => $communesVoisines,
            'breadcrumbs' => [
                ['label' => 'Accueil', 'href' => route('dashboard'), 'icon' => '🏠'],
                ['label' => 'Communes', 'href' => route('communes.index'), 'icon' => '🏘️'],
                ['label' => $commune->department_name, 'href' => route('communes.index', ['departement' => $commune->department_code])],
                ['label' => $commune->nom_commune, 'current' => true],
            ],
        ]);
    }

    /**
     * API de recherche pour l'autocomplete
     */
    public function search(Request $request)
    {
        $query = $request->input('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $results = $this->localisationService->search($query, 10);

        return response()->json($results);
    }

    /**
     * Récupérer les communes voisines (même département)
     */
    private function getCommunesVoisines(FrenchPostalCode $commune): array
    {
        return DB::table('french_postal_codes')
            ->select('insee_code', 'city_name', 'postal_code', 'population')
            ->selectRaw("CASE WHEN city_name ~ '\\d{2}$' THEN REGEXP_REPLACE(city_name, '\\s+\\d{2}$', '') ELSE city_name END as nom_commune")
            ->where('department_code', $commune->department_code)
            ->where('insee_code', '!=', $commune->insee_code)
            ->whereNotNull('population')
            ->where('population', '>', 0)
            ->orderByRaw('ABS(population - ?) ASC', [$commune->population ?? 0])
            ->limit(5)
            ->get()
            ->unique('insee_code')
            ->map(fn($c) => [
                'insee_code' => $c->insee_code,
                'nom' => ucwords(strtolower($c->nom_commune)),
                'code_postal' => $c->postal_code,
                'population' => $c->population,
                'population_formatted' => number_format($c->population, 0, ',', ' ') . ' hab.',
                'url' => route('communes.show', $c->insee_code),
            ])
            ->values()
            ->toArray();
    }

    private function formatCommune($data): array
    {
        // Si c'est un objet FrenchPostalCode
        if ($data instanceof FrenchPostalCode) {
            return [
                'insee_code' => $data->insee_code,
                'nom' => $data->nom_commune,
                'city_name' => $data->city_name,
                'code_postal' => $data->postal_code,
                'department_name' => $data->department_name,
                'region_name' => $data->region_name,
                'population' => $data->population,
                'population_formatted' => $data->population_formatted,
                'url' => route('communes.show', $data->insee_code),
            ];
        }

        // Si c'est un tableau (depuis LocalisationService)
        return [
            'insee_code' => $data['insee_code'] ?? null,
            'nom' => isset($data['city_name']) ? ucwords(strtolower(preg_replace('/\s+\d{2}$/', '', $data['city_name']))) : null,
            'city_name' => $data['city_name'] ?? null,
            'code_postal' => $data['postal_code'] ?? null,
            'department_name' => $data['department_name'] ?? null,
            'region_name' => $data['region_name'] ?? null,
            'population' => $data['population'] ?? null,
            'population_formatted' => isset($data['population']) && $data['population'] 
                ? number_format($data['population'], 0, ',', ' ') . ' hab.' 
                : 'N/A',
            'url' => isset($data['insee_code']) ? route('communes.show', $data['insee_code']) : '#',
        ];
    }
}
