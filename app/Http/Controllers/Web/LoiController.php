<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Loi;
use App\Models\LoiStats;
use App\Models\EtatLoi;
use App\Models\TypeLoi;
use App\Models\ThematiqueLoi;
use App\Models\Tag;
use App\Models\ScrutinAN;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LoiController extends Controller
{
    /**
     * Liste des lois avec filtres et pagination
     */
    public function index(Request $request): Response
    {
        $query = Loi::query()
            ->with(['etat', 'typeLoi']);

        // Filtres
        if ($request->filled('etat')) {
            $query->where('etaloicod', $request->etat);
        }

        if ($request->filled('type')) {
            $query->where('typloicod', $request->type);
        }

        if ($request->filled('annee')) {
            $query->whereYear('loidatjo', $request->annee);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('loitit', 'ILIKE', "%{$search}%")
                  ->orWhere('loiint', 'ILIKE', "%{$search}%")
                  ->orWhere('numero', 'ILIKE', "%{$search}%");
            });
        }

        // Filtre par thématique (tag)
        if ($request->filled('thematique')) {
            $tagSlug = $request->thematique;
            $query->whereHas('tags', function ($q) use ($tagSlug) {
                $q->where('slug', $tagSlug);
            });
        }

        // Tri
        $sort = $request->get('sort', 'recent');
        switch ($sort) {
            case 'recent':
                $query->orderByDesc('loidatjo');
                break;
            case 'ancien':
                $query->orderBy('loidatjo');
                break;
            case 'titre':
                $query->orderBy('loitit');
                break;
            default:
                $query->orderByDesc('loidatjo');
        }

        $lois = $query->paginate(20)->withQueryString();

        // Statistiques
        $stats = Cache::remember('lois_stats', 3600, function () {
            return [
                'total' => Loi::count(),
                'promulguees' => Loi::promulguees()->count(),
                'en_cours' => Loi::enCours()->count(),
                'rejetees' => Loi::rejetees()->count(),
                'cette_annee' => Loi::promulguees()
                    ->whereYear('loidatjo', now()->year)
                    ->count(),
            ];
        });

        // Options de filtres
        $etats = EtatLoi::orderBy('etaloicod')->get()->map(fn ($e) => [
            'code' => trim($e->etaloicod),
            'libelle' => trim($e->etaloilib),
        ]);

        $types = TypeLoi::orderBy('typloilib')->get()->map(fn ($t) => [
            'code' => trim($t->typloicod),
            'libelle' => trim($t->typloilib ?? ''),
        ])->filter(fn ($t) => !empty($t['libelle']));

        $annees = DB::table('senat_dosleg_loi')
            ->selectRaw('EXTRACT(YEAR FROM loidatjo) as annee')
            ->whereNotNull('loidatjo')
            ->groupBy(DB::raw('EXTRACT(YEAR FROM loidatjo)'))
            ->orderByDesc('annee')
            ->pluck('annee')
            ->filter()
            ->values();

        // Thématiques (tags officiels)
        $thematiques = Tag::official()
            ->validated()
            ->where('usage_count', '>', 0)
            ->orderByDesc('usage_count')
            ->get()
            ->map(fn ($t) => [
                'slug' => $t->slug,
                'nom' => $t->nom,
                'icone' => $t->icone,
                'couleur' => $t->couleur,
                'count' => $t->usage_count,
            ]);

        return Inertia::render('Legislation/Lois/Index', [
            'lois' => $lois,
            'stats' => $stats,
            'etats' => $etats,
            'types' => $types,
            'annees' => $annees,
            'thematiques' => $thematiques,
            'filters' => $request->only(['etat', 'type', 'annee', 'search', 'sort', 'thematique']),
        ]);
    }

    /**
     * Détail d'une loi avec son parcours législatif
     */
    public function show(string $loicod): Response
    {
        $loi = Loi::with([
            'etat',
            'typeLoi',
            'thematiques',
            'lectures.typeLecture',
            'lectures.passages',
            'lectures.seances',
        ])->where('loicod', $loicod)->firstOrFail();

        // Construire le parcours législatif
        $parcours = $loi->getParcours();

        // Statistiques pré-calculées (ou fallback)
        $loiStats = LoiStats::forLoi(trim($loi->loicod));
        $stats = $loiStats ? $loiStats->toViewArray() : [
            'etapes_total' => count($parcours),
            'amendements_total' => 0,
            'amendements_adoptes' => 0,
            'taux_adoption_amendements' => 0,
            'scrutins_total' => 0,
            'duree_jours' => null,
            'score_engagement' => 0,
            'calculated_at' => null,
        ];

        // Lois similaires (même thématique ou type)
        $loisSimilaires = collect();
        if ($loi->thematiques->isNotEmpty()) {
            $themeCodes = $loi->thematiques->pluck('thecle');
            $loisSimilaires = Loi::with('etat')
                ->whereHas('thematiques', function ($q) use ($themeCodes) {
                    $q->whereIn('senat_dosleg_the.thecle', $themeCodes);
                })
                ->where('loicod', '!=', $loicod)
                ->promulguees()
                ->orderByDesc('loidatjo')
                ->take(5)
                ->get();
        }

        // Scrutins AN liés (recherche par numéro ou titre)
        $scrutinsLies = collect();
        $searchTerms = [];
        
        // Construire les termes de recherche
        if (!empty($loi->numero)) {
            $searchTerms[] = $loi->numero;
        }
        
        // Extraire mots clés du titre (> 5 caractères)
        $titreMots = preg_split('/\s+/', $loi->loitit ?? '');
        $motsSignificatifs = array_filter($titreMots, fn($m) => strlen($m) > 8);
        $motsSignificatifs = array_slice($motsSignificatifs, 0, 3);
        
        if (!empty($searchTerms) || !empty($motsSignificatifs)) {
            $scrutinsQuery = ScrutinAN::query()
                ->where(function ($q) use ($searchTerms, $motsSignificatifs) {
                    foreach ($searchTerms as $term) {
                        $q->orWhere('titre', 'ILIKE', "%{$term}%");
                    }
                    foreach ($motsSignificatifs as $mot) {
                        $q->orWhere('titre', 'ILIKE', "%{$mot}%");
                    }
                })
                ->orderByDesc('date_scrutin')
                ->limit(10);
            
            $scrutinsLies = $scrutinsQuery->get();
        }

        return Inertia::render('Legislation/Lois/Show', [
            'loi' => [
                'loicod' => trim($loi->loicod),
                'numero' => trim($loi->numero ?? ''),
                'titre' => trim($loi->loitit ?? ''),
                'titre_court' => $loi->titre_court,
                'intitule' => trim($loi->loiint ?? ''),
                'etat' => [
                    'code' => trim($loi->etaloicod),
                    'libelle' => $loi->etat_libelle,
                    'couleur' => $loi->etat_couleur,
                ],
                'type' => $loi->typeLoi ? [
                    'code' => trim($loi->typeLoi->typloicod),
                    'libelle' => trim($loi->typeLoi->typloilib ?? ''),
                ] : null,
                'thematiques' => $loi->thematiques->map(fn ($t) => [
                    'code' => trim($t->thecle),
                    'libelle' => $t->libelle,
                    'categorie' => $t->categorie,
                    'couleur' => $t->couleur,
                ]),
                'urgence' => $loi->urgence === 'O',
                'date_jo' => $loi->loidatjo?->format('d/m/Y'),
                'date_loi' => $loi->date_loi?->format('d/m/Y'),
                'url_jo' => $loi->url_jo,
                'url_an' => $loi->url_an,
                'chambre_origine' => $loi->chambre_origine,
                'progression' => $loi->progression,
                'est_promulguee' => $loi->est_promulguee,
            ],
            'stats' => $stats,
            'parcours' => $parcours,
            'loisSimilaires' => $loisSimilaires->map(fn ($l) => [
                'loicod' => trim($l->loicod),
                'titre' => $l->titre_court,
                'numero' => trim($l->numero ?? ''),
                'date_jo' => $l->loidatjo?->format('d/m/Y'),
                'etat_couleur' => $l->etat_couleur,
            ]),
            'scrutins' => $scrutinsLies->map(fn ($s) => [
                'uid' => $s->uid,
                'numero' => $s->numero,
                'titre' => \Str::limit($s->titre, 150),
                'date' => $s->date_scrutin?->format('d/m/Y'),
                'pour' => $s->pour,
                'contre' => $s->contre,
                'abstentions' => $s->abstentions,
                'resultat' => $s->resultat_libelle,
            ]),
        ]);
    }

    /**
     * Timeline JSON pour affichage dynamique
     */
    public function timeline(string $loicod)
    {
        $loi = Loi::with([
            'lectures.typeLecture',
            'lectures.passages',
        ])->where('loicod', $loicod)->firstOrFail();

        return response()->json([
            'loicod' => $loicod,
            'titre' => $loi->titre_court,
            'etat' => $loi->etat_libelle,
            'progression' => $loi->progression,
            'parcours' => $loi->getParcours(),
        ]);
    }

    /**
     * Statistiques globales sur les lois
     */
    public function statistiques(): Response
    {
        $stats = Cache::remember('lois_statistiques_detaillees', 3600, function () {
            // Par état
            $parEtat = DB::table('senat_dosleg_loi as l')
                ->join('senat_dosleg_etaloi as e', 'l.etaloicod', '=', 'e.etaloicod')
                ->select('e.etaloilib', DB::raw('count(*) as total'))
                ->groupBy('e.etaloilib')
                ->orderByDesc('total')
                ->get();

            // Par année (promulguées)
            $parAnnee = DB::table('senat_dosleg_loi')
                ->selectRaw('EXTRACT(YEAR FROM loidatjo) as annee, count(*) as total')
                ->where('etaloicod', '04')
                ->whereNotNull('loidatjo')
                ->groupBy(DB::raw('EXTRACT(YEAR FROM loidatjo)'))
                ->orderBy('annee')
                ->get();

            // Moyenne de lectures
            $moyenneLectures = DB::table('senat_dosleg_lecture')
                ->selectRaw('AVG(nb)::numeric(10,2) as moyenne')
                ->fromSub(
                    DB::table('senat_dosleg_lecture')
                        ->select('loicod', DB::raw('count(*) as nb'))
                        ->groupBy('loicod'),
                    'sub'
                )
                ->first();

            // Répartition AN vs Sénat (première lecture)
            $chambreOrigine = DB::table('senat_dosleg_lecass as la')
                ->join('senat_dosleg_lecture as l', 'la.lecidt', '=', 'l.lecidt')
                ->where('l.typleccod', '1')
                ->where('la.ordreass', 1)
                ->select('la.codass', DB::raw('count(DISTINCT l.loicod) as total'))
                ->groupBy('la.codass')
                ->get();

            return [
                'par_etat' => $parEtat,
                'par_annee' => $parAnnee,
                'moyenne_lectures' => $moyenneLectures->moyenne ?? 0,
                'chambre_origine' => $chambreOrigine,
            ];
        });

        return Inertia::render('Legislation/Lois/Statistiques', [
            'stats' => $stats,
        ]);
    }

    /**
     * Recherche de lois
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $lois = Loi::with('etat')
            ->where(function ($q) use ($query) {
                $q->where('loitit', 'ILIKE', "%{$query}%")
                  ->orWhere('loiint', 'ILIKE', "%{$query}%")
                  ->orWhere('numero', 'ILIKE', "%{$query}%");
            })
            ->orderByDesc('loidatjo')
            ->take(10)
            ->get()
            ->map(fn ($loi) => [
                'loicod' => trim($loi->loicod),
                'titre' => $loi->titre_court,
                'numero' => trim($loi->numero ?? ''),
                'etat' => $loi->etat_libelle,
                'etat_couleur' => $loi->etat_couleur,
                'url' => route('lois.show', $loi->loicod),
            ]);

        return response()->json($lois);
    }
}

