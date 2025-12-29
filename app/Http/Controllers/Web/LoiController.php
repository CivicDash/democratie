<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Loi;
use App\Models\EtatLoi;
use App\Models\TypeLoi;
use App\Models\ThematiqueLoi;
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

        return Inertia::render('Legislation/Lois/Index', [
            'lois' => $lois,
            'stats' => $stats,
            'etats' => $etats,
            'types' => $types,
            'annees' => $annees,
            'filters' => $request->only(['etat', 'type', 'annee', 'search', 'sort']),
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
            'parcours' => $parcours,
            'loisSimilaires' => $loisSimilaires->map(fn ($l) => [
                'loicod' => trim($l->loicod),
                'titre' => $l->titre_court,
                'numero' => trim($l->numero ?? ''),
                'date_jo' => $l->loidatjo?->format('d/m/Y'),
                'etat_couleur' => $l->etat_couleur,
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

