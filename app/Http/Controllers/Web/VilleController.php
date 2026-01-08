<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ville;
use App\Models\MaireMandat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class VilleController extends Controller
{
    /**
     * Liste et recherche des villes
     */
    public function index(Request $request): Response
    {
        $query = $request->input('q', '');
        $departement = $request->input('departement');
        $region = $request->input('region');
        $minPop = $request->input('min_pop');
        $perPage = 50;

        $villesQuery = Ville::query()
            ->where('arrondissement_municipal', false)
            ->with(['maireActuel:id,nom,prenom,civilite,photo_url']);

        // Recherche texte
        if (strlen($query) >= 2) {
            $villesQuery->search($query);
        }

        // Filtres
        if ($departement) {
            $villesQuery->byDepartement($departement);
        }
        if ($region) {
            $villesQuery->byRegion($region);
        }
        if ($minPop) {
            $villesQuery->where('population', '>=', (int) $minPop);
        }

        $villes = $villesQuery
            ->orderByDesc('population')
            ->paginate($perPage)
            ->through(fn($v) => $this->formatVilleCard($v));

        // Liste des départements
        $departements = Cache::remember('villes_departements', 3600, function () {
            return Ville::select('departement_code', 'departement_nom')
                ->distinct()
                ->whereNotNull('departement_code')
                ->orderBy('departement_code')
                ->get()
                ->map(fn($d) => [
                    'code' => $d->departement_code,
                    'nom' => $d->departement_nom ?? 'Département ' . $d->departement_code,
                ]);
        });

        // Liste des régions
        $regions = Cache::remember('villes_regions', 3600, function () {
            return Ville::select('region_code', 'region_nom')
                ->distinct()
                ->whereNotNull('region_code')
                ->orderBy('region_nom')
                ->get()
                ->map(fn($r) => [
                    'code' => $r->region_code,
                    'nom' => $r->region_nom ?? 'Région ' . $r->region_code,
                ]);
        });

        // Stats globales
        $stats = Cache::remember('villes_stats_globales', 3600, function () {
            return [
                'total_villes' => Ville::where('arrondissement_municipal', false)->count(),
                'total_population' => Ville::where('arrondissement_municipal', false)->sum('population'),
                'nb_prefectures' => Ville::where('est_prefecture', true)->count(),
                'nb_grandes_villes' => Ville::where('population', '>=', 50000)->count(),
            ];
        });

        return Inertia::render('Villes/Index', [
            'villes' => $villes,
            'filters' => [
                'q' => $query,
                'departement' => $departement,
                'region' => $region,
                'min_pop' => $minPop,
            ],
            'departements' => $departements,
            'regions' => $regions,
            'stats' => $stats,
            'breadcrumbs' => [
                ['label' => 'Accueil', 'href' => route('dashboard'), 'icon' => '🏠'],
                ['label' => 'Villes', 'current' => true, 'icon' => '🏘️'],
            ],
        ]);
    }

    /**
     * Fiche détaillée d'une ville
     */
    public function show(string $slug): Response
    {
        $ville = Ville::where('slug', $slug)
            ->orWhere('code_insee', $slug)
            ->with([
                'maireActuel',
                'mandatsMaires.maire',
                'historiquePopulation',
                'stats',
                'budgets',
                'arrondissements',
            ])
            ->firstOrFail();

        // Maires historiques
        $mandatsMaires = $ville->mandatsMaires->map(fn($m) => [
            'id' => $m->id,
            'nom_complet' => $m->nom_complet,
            'sexe' => $m->sexe,
            'date_debut' => $m->date_debut?->format('d/m/Y'),
            'date_fin' => $m->date_fin?->format('d/m/Y'),
            'periode' => $m->periode,
            'duree' => $m->duree_formate,
            'nuance_politique' => $m->nuance_politique,
            'parti' => $m->parti,
            'score_election' => $m->score_election_pct,
            'est_actuel' => $m->est_actuel,
        ]);

        // Évolution population
        $evolutionPopulation = $ville->historiquePopulation->map(fn($p) => [
            'annee' => $p->annee,
            'population' => $p->population,
            'population_formate' => $p->population_formate,
        ]);

        // Budgets
        $budgets = $ville->budgets->take(10)->map(fn($b) => [
            'annee' => $b->annee,
            'recettes_fonctionnement' => $b->recettes_fonctionnement_formate,
            'depenses_fonctionnement' => $b->depenses_fonctionnement_formate,
            'dette' => $b->encours_dette_formate,
            'dette_par_habitant' => $b->euros_par_habitant 
                ? number_format($b->euros_par_habitant, 0, ',', ' ') . ' €' 
                : 'N/A',
        ]);

        // Statistiques pré-calculées
        $statsVille = $ville->stats ? [
            'taux_endettement' => $ville->stats->taux_endettement_formate,
            'dette_par_habitant' => $ville->stats->dette_par_habitant_formate,
            'evolution_population' => $ville->stats->evolution_population_5ans_pct 
                ? number_format($ville->stats->evolution_population_5ans_pct, 1) . '%'
                : null,
            'score_sante_financiere' => $ville->stats->score_sante_financiere,
            'score_sante_label' => $ville->stats->score_sante_financiere_libelle,
            'score_sante_color' => $ville->stats->score_sante_financiere_color,
            'nb_maires' => $ville->stats->nb_maires_historique,
            'duree_moy_mandat' => $ville->stats->duree_moyenne_mandat_mois 
                ? round($ville->stats->duree_moyenne_mandat_mois / 12, 1) . ' ans'
                : null,
        ] : null;

        // Élus associés (députés, sénateurs)
        $elus = $this->getElusVille($ville);

        // Villes voisines (même département, population similaire)
        $villesVoisines = Ville::where('departement_code', $ville->departement_code)
            ->where('id', '!=', $ville->id)
            ->where('arrondissement_municipal', false)
            ->orderByRaw('ABS(population - ?) ASC', [$ville->population ?? 0])
            ->limit(5)
            ->get()
            ->map(fn($v) => $this->formatVilleCard($v));

        return Inertia::render('Villes/Show', [
            'ville' => [
                'id' => $ville->id,
                'code_insee' => $ville->code_insee,
                'nom' => $ville->nom,
                'nom_complet' => $ville->nom_complet,
                'slug' => $ville->slug,
                'code_postal' => $ville->code_postal_principal,
                'codes_postaux' => $ville->codes_postaux ?? [],
                'departement_code' => $ville->departement_code,
                'departement_nom' => $ville->departement_nom,
                'region_nom' => $ville->region_nom,
                'epci_nom' => $ville->epci_nom,
                'population' => $ville->population,
                'population_formate' => $ville->population_formate,
                'superficie' => $ville->superficie_formate,
                'densite' => $ville->densite_formate,
                'latitude' => $ville->latitude,
                'longitude' => $ville->longitude,
                'est_prefecture' => $ville->est_prefecture,
                'est_sous_prefecture' => $ville->est_sous_prefecture,
                'est_chef_lieu_region' => $ville->est_chef_lieu_region,
                'arrondissements' => $ville->arrondissements->map(fn($a) => [
                    'nom' => $a->nom,
                    'population' => $a->population_formate,
                    'url' => $a->url,
                ]),
                'wikipedia_url' => $ville->wikipedia_url_formate,
                'site_officiel' => $ville->site_officiel,
                'blason_url' => $ville->blason_url,
                'altitude' => $ville->altitude_formate,
            ],
            'maire' => $ville->maireActuel ? [
                'id' => $ville->maireActuel->id,
                'nom' => $ville->maireActuel->nom_complet ?? trim($ville->maireActuel->prenom . ' ' . $ville->maireActuel->nom),
                'civilite' => $ville->maireActuel->civilite,
                'photo_url' => $ville->maireActuel->photo_url,
                'debut_mandat' => $ville->maireActuel->debut_mandat?->locale('fr')->isoFormat('D MMMM YYYY'),
                'nuance_politique' => $ville->maireActuel->nuance_politique,
                'url' => $ville->maireActuel->url ?? null,
            ] : null,
            'mandatsMaires' => $mandatsMaires,
            'evolutionPopulation' => $evolutionPopulation,
            'budgets' => $budgets,
            'stats' => $statsVille,
            'elus' => $elus,
            'villesVoisines' => $villesVoisines,
            'breadcrumbs' => [
                ['label' => 'Accueil', 'href' => route('dashboard'), 'icon' => '🏠'],
                ['label' => 'Villes', 'href' => route('villes.index'), 'icon' => '🏘️'],
                ['label' => $ville->departement_nom ?? $ville->departement_code, 'href' => route('villes.index', ['departement' => $ville->departement_code])],
                ['label' => $ville->nom, 'current' => true],
            ],
        ]);
    }

    /**
     * API de recherche pour autocomplete
     */
    public function search(Request $request)
    {
        $query = $request->input('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $villes = Ville::search($query)
            ->where('arrondissement_municipal', false)
            ->orderByDesc('population')
            ->limit(10)
            ->get()
            ->map(fn($v) => [
                'id' => $v->id,
                'code_insee' => $v->code_insee,
                'nom' => $v->nom,
                'code_postal' => $v->code_postal_principal,
                'departement' => $v->departement_nom,
                'population' => $v->population,
                'population_formate' => $v->population_formate,
                'url' => $v->url,
            ]);

        return response()->json($villes);
    }

    /**
     * Récupère les élus (députés, sénateurs) de la ville
     */
    private function getElusVille(Ville $ville): array
    {
        $elus = [
            'deputes' => [],
            'senateurs' => [],
        ];

        // Députés (via circonscription - format "39-01" = département-numéro)
        if ($ville->circonscription && str_contains($ville->circonscription, '-')) {
            [$numDept, $numCirco] = explode('-', $ville->circonscription, 2);
            
            $deputes = DB::table('deputes_circonscriptions as dc')
                ->join('acteurs_an as a', 'dc.acteur_uid', '=', 'a.uid')
                ->where('dc.num_departement', $numDept)
                ->where('dc.num_circo', (int) $numCirco)
                ->whereNull('dc.date_fin')
                ->select('a.uid', 'a.nom', 'a.prenom', 'a.photo_wikipedia_url')
                ->get();

            $elus['deputes'] = $deputes->map(fn($d) => [
                'uid' => $d->uid,
                'nom' => trim($d->prenom . ' ' . $d->nom),
                'photo_url' => $d->photo_wikipedia_url,
                'url' => route('representants.deputes.show', $d->uid),
            ])->toArray();
        }

        // Sénateurs (via département)
        if ($ville->departement_nom) {
            $senateurs = DB::table('senateurs')
                ->where('circonscription', $ville->departement_nom)
                ->whereNull('date_deces')
                ->select('matricule', 'nom', 'prenom', 'photo_wikipedia_url')
                ->get();

            $elus['senateurs'] = $senateurs->map(function($s) {
                // Construire l'URL photo avec le format correct du Sénat
                $photoUrl = null;
                if ($s->matricule && $s->nom && $s->prenom) {
                    $nom = $this->normalizeForSenatUrl($s->nom);
                    $prenom = $this->normalizeForSenatUrl($s->prenom);
                    $matricule = strtolower($s->matricule);
                    $photoUrl = "https://www.senat.fr/senimg/{$nom}_{$prenom}{$matricule}_carre.jpg";
                }
                
                return [
                    'matricule' => $s->matricule,
                    'nom' => trim($s->prenom . ' ' . $s->nom),
                    'photo_url' => $photoUrl ?? $s->photo_wikipedia_url,
                    'url' => route('representants.senateurs.show', $s->matricule),
                ];
            })->toArray();
        }

        return $elus;
    }

    /**
     * Normalise une chaîne pour une URL de photo Sénat
     */
    private function normalizeForSenatUrl(string $text): string
    {
        $text = strtolower(trim($text));
        // Translittération des accents
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        // Remplacer les tirets par des underscores
        $text = str_replace('-', '_', $text);
        // Supprimer tout ce qui n'est pas alphanumérique ou underscore
        $text = preg_replace('/[^a-z0-9_]/', '', $text);
        return $text;
    }

    private function formatVilleCard(Ville $ville): array
    {
        return [
            'id' => $ville->id,
            'code_insee' => $ville->code_insee,
            'nom' => $ville->nom,
            'slug' => $ville->slug,
            'code_postal' => $ville->code_postal_principal,
            'departement_nom' => $ville->departement_nom,
            'region_nom' => $ville->region_nom,
            'population' => $ville->population,
            'population_formate' => $ville->population_formate,
            'url' => $ville->url,
            'est_prefecture' => $ville->est_prefecture,
            'maire' => $ville->maireActuel ? [
                'nom' => $ville->maireActuel->nom_complet ?? trim($ville->maireActuel->prenom . ' ' . $ville->maireActuel->nom),
                'photo_url' => $ville->maireActuel->photo_url,
            ] : null,
        ];
    }
}
