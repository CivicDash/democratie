<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\QuestionAN;
use App\Models\ActeurAN;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class QuestionController extends Controller
{
    /**
     * Liste des questions au gouvernement avec filtres
     */
    public function index(Request $request): Response
    {
        $query = QuestionAN::with(['acteur:uid,prenom,nom'])
            ->orderByDesc('date_question');

        // Filtres
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('analyse', 'ilike', "%{$search}%")
                  ->orWhere('rubrique', 'ilike', "%{$search}%")
                  ->orWhere('texte_question', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('rubrique')) {
            $query->where('rubrique', $request->rubrique);
        }

        if ($request->filled('ministere')) {
            $query->where('ministere_sigle', $request->ministere);
        }

        if ($request->filled('groupe')) {
            $query->where('groupe_sigle', $request->groupe);
        }

        if ($request->filled('depute')) {
            $query->where('acteur_ref', $request->depute);
        }

        $questions = $query->paginate(20)->withQueryString();

        // Stats pré-calculées
        $stats = $this->getStats();

        // Données pour filtres
        $rubriques = QuestionAN::select('rubrique')
            ->distinct()
            ->whereNotNull('rubrique')
            ->orderBy('rubrique')
            ->pluck('rubrique');

        $ministeres = QuestionAN::select('ministere_sigle')
            ->distinct()
            ->whereNotNull('ministere_sigle')
            ->orderBy('ministere_sigle')
            ->pluck('ministere_sigle');

        $groupes = QuestionAN::select('groupe_sigle')
            ->distinct()
            ->whereNotNull('groupe_sigle')
            ->orderBy('groupe_sigle')
            ->pluck('groupe_sigle');

        return Inertia::render('Questions/Index', [
            'questions' => $questions,
            'filters' => $request->only(['search', 'rubrique', 'ministere', 'groupe', 'depute']),
            'stats' => $stats,
            'rubriques' => $rubriques,
            'ministeres' => $ministeres,
            'groupes' => $groupes,
        ]);
    }

    /**
     * Détail d'une question
     */
    public function show(string $uid): Response
    {
        $question = QuestionAN::with(['acteur', 'groupe'])
            ->where('uid', $uid)
            ->firstOrFail();

        // Questions similaires (même rubrique)
        $similaires = QuestionAN::with(['acteur:uid,prenom,nom'])
            ->where('rubrique', $question->rubrique)
            ->where('uid', '!=', $uid)
            ->orderByDesc('date_question')
            ->limit(5)
            ->get();

        // Autres questions du même député
        $autresDepute = QuestionAN::where('acteur_ref', $question->acteur_ref)
            ->where('uid', '!=', $uid)
            ->orderByDesc('date_question')
            ->limit(5)
            ->get();

        return Inertia::render('Questions/Show', [
            'question' => $question,
            'similaires' => $similaires,
            'autresDepute' => $autresDepute,
        ]);
    }

    /**
     * Page de statistiques des questions
     */
    public function stats(): Response
    {
        $stats = $this->getDetailedStats();

        return Inertia::render('Questions/Stats', [
            'stats' => $stats,
        ]);
    }

    /**
     * Questions d'un député spécifique
     */
    public function byDepute(string $uid): Response
    {
        $depute = ActeurAN::where('uid', $uid)->firstOrFail();

        $questions = QuestionAN::where('acteur_ref', $uid)
            ->orderByDesc('date_question')
            ->paginate(20);

        // Stats du député
        $deputeStats = [
            'total' => QuestionAN::where('acteur_ref', $uid)->count(),
            'repondues' => QuestionAN::where('acteur_ref', $uid)->whereNotNull('date_reponse')->count(),
            'par_rubrique' => QuestionAN::where('acteur_ref', $uid)
                ->select('rubrique', DB::raw('count(*) as nb'))
                ->groupBy('rubrique')
                ->orderByDesc('nb')
                ->limit(5)
                ->get(),
            'par_ministere' => QuestionAN::where('acteur_ref', $uid)
                ->select('ministere_sigle', DB::raw('count(*) as nb'))
                ->groupBy('ministere_sigle')
                ->orderByDesc('nb')
                ->limit(5)
                ->get(),
        ];

        return Inertia::render('Questions/ByDepute', [
            'depute' => $depute,
            'questions' => $questions,
            'stats' => $deputeStats,
        ]);
    }

    /**
     * Stats globales (cachées)
     */
    protected function getStats(): array
    {
        return Cache::remember('questions_stats', 3600, function () {
            return [
                'total' => QuestionAN::count(),
                'repondues' => QuestionAN::whereNotNull('date_reponse')->count(),
                'en_attente' => QuestionAN::whereNull('date_reponse')->count(),
                'cette_semaine' => QuestionAN::where('date_question', '>=', now()->subWeek())->count(),
                'ce_mois' => QuestionAN::where('date_question', '>=', now()->subMonth())->count(),
                'deputés_actifs' => QuestionAN::distinct('acteur_ref')->count('acteur_ref'),
            ];
        });
    }

    /**
     * Stats détaillées pour page stats
     */
    protected function getDetailedStats(): array
    {
        return Cache::remember('questions_detailed_stats', 3600, function () {
            // Top rubriques
            $topRubriques = QuestionAN::select('rubrique', DB::raw('count(*) as nb'))
                ->whereNotNull('rubrique')
                ->groupBy('rubrique')
                ->orderByDesc('nb')
                ->limit(15)
                ->get();

            // Top ministères
            $topMinisteres = QuestionAN::select('ministere_sigle', 'ministere_nom', DB::raw('count(*) as nb'))
                ->whereNotNull('ministere_sigle')
                ->groupBy('ministere_sigle', 'ministere_nom')
                ->orderByDesc('nb')
                ->limit(10)
                ->get();

            // Top groupes
            $topGroupes = QuestionAN::select('groupe_sigle', 'groupe_nom', DB::raw('count(*) as nb'))
                ->whereNotNull('groupe_sigle')
                ->groupBy('groupe_sigle', 'groupe_nom')
                ->orderByDesc('nb')
                ->get();

            // Top députés
            $topDeputes = QuestionAN::select('acteur_ref', DB::raw('count(*) as nb'))
                ->groupBy('acteur_ref')
                ->orderByDesc('nb')
                ->limit(20)
                ->get()
                ->map(function ($item) {
                    $depute = ActeurAN::where('uid', $item->acteur_ref)->first();
                    return [
                        'uid' => $item->acteur_ref,
                        'nom' => $depute ? $depute->prenom . ' ' . $depute->nom : $item->acteur_ref,
                        'groupe' => $depute?->groupe_sigle,
                        'photo_url' => $depute?->photo_url,
                        'nb' => $item->nb,
                    ];
                });

            // Évolution mensuelle
            $evolutionMensuelle = QuestionAN::select(
                    DB::raw("TO_CHAR(date_question, 'YYYY-MM') as mois"),
                    DB::raw('count(*) as nb')
                )
                ->whereNotNull('date_question')
                ->groupBy('mois')
                ->orderBy('mois')
                ->get();

            // Délai moyen de réponse
            $delaiMoyen = QuestionAN::whereNotNull('date_reponse')
                ->whereNotNull('date_question')
                ->selectRaw('AVG(date_reponse - date_question) as delai_moyen')
                ->first();

            return [
                'global' => $this->getStats(),
                'top_rubriques' => $topRubriques,
                'top_ministeres' => $topMinisteres,
                'top_groupes' => $topGroupes,
                'top_deputes' => $topDeputes,
                'evolution_mensuelle' => $evolutionMensuelle,
                'delai_moyen_jours' => $delaiMoyen ? round($delaiMoyen->delai_moyen) : null,
            ];
        });
    }
}
