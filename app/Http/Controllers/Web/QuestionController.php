<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActeurAN;
use App\Models\QuestionAN;
use App\Models\Senateur;
use App\Models\SenateurQuestion;
use App\Models\VideoChapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class QuestionController extends Controller
{
    /**
     * Liste des questions au gouvernement avec filtres
     */
    public function index(Request $request): Response
    {
        $query = QuestionAN::with(['acteur:uid,prenom,nom,photo_wikipedia_url'])
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
        $similaires = QuestionAN::with(['acteur:uid,prenom,nom,photo_wikipedia_url'])
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

        $videoChapter = VideoChapter::where('question_uid', $uid)->first();
        $videoUrl = $videoChapter?->video_url;

        if (! $videoUrl && $question->type === 'QG' && $question->acteur_ref) {
            $videoChapter = VideoChapter::where('chapter_type_key', 4)
                ->where('speaker_an_uid', $question->acteur_ref)
                ->whereHas('reunion', fn ($q) => $q->whereDate('date_debut', $question->date_question))
                ->first();
            $videoUrl = $videoChapter?->video_url;
        }

        return Inertia::render('Questions/Show', [
            'question' => $question,
            'similaires' => $similaires,
            'autresDepute' => $autresDepute,
            'video_url' => $videoUrl,
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
                        'nom' => $depute ? $depute->prenom.' '.$depute->nom : $item->acteur_ref,
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

    // =========================================================================
    // QUESTIONS SÉNAT
    // =========================================================================

    /**
     * Liste des questions au gouvernement - Sénat
     */
    public function indexSenat(Request $request): Response
    {
        $query = SenateurQuestion::with(['senateur:matricule,nom,prenom,photo_url'])
            ->orderByDesc('date_question');

        // Filtres
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('texte_question', 'ilike', "%{$search}%")
                    ->orWhere('theme', 'ilike', "%{$search}%")
                    ->orWhere('ministre_destinataire', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('theme')) {
            $query->where('theme', $request->theme);
        }

        if ($request->filled('statut')) {
            if ($request->statut === 'repondu') {
                $query->repondues();
            } else {
                $query->enAttente();
            }
        }

        if ($request->filled('senateur')) {
            $query->where('senateur_matricule', $request->senateur);
        }

        $questions = $query->paginate(20)->withQueryString();

        // Stats
        $stats = $this->getSenatStats();

        // Données pour filtres
        $themes = SenateurQuestion::select('theme')
            ->distinct()
            ->whereNotNull('theme')
            ->where('theme', '!=', '')
            ->orderBy('theme')
            ->pluck('theme');

        $types = SenateurQuestion::select('type')
            ->distinct()
            ->whereNotNull('type')
            ->orderBy('type')
            ->pluck('type');

        return Inertia::render('Questions/Senat/Index', [
            'questions' => $questions,
            'filters' => $request->only(['search', 'type', 'theme', 'statut', 'senateur']),
            'stats' => $stats,
            'themes' => $themes,
            'types' => $types,
        ]);
    }

    /**
     * Détail d'une question Sénat
     */
    public function showSenat(string $numero): Response
    {
        $question = SenateurQuestion::with(['senateur'])
            ->where('numero', $numero)
            ->firstOrFail();

        // Questions similaires (même thème)
        $similaires = SenateurQuestion::with(['senateur:matricule,nom,prenom,photo_url'])
            ->where('theme', $question->theme)
            ->where('numero', '!=', $numero)
            ->orderByDesc('date_question')
            ->limit(5)
            ->get();

        // Autres questions du même sénateur
        $autresSenateur = SenateurQuestion::where('senateur_matricule', $question->senateur_matricule)
            ->where('numero', '!=', $numero)
            ->orderByDesc('date_question')
            ->limit(5)
            ->get();

        return Inertia::render('Questions/Senat/Show', [
            'question' => $question,
            'similaires' => $similaires,
            'autresSenateur' => $autresSenateur,
        ]);
    }

    /**
     * Statistiques des questions Sénat
     */
    public function statsSenat(): Response
    {
        $stats = $this->getSenatDetailedStats();

        return Inertia::render('Questions/Senat/Stats', [
            'stats' => $stats,
        ]);
    }

    /**
     * Questions d'un sénateur spécifique
     */
    public function bySenateur(string $matricule): Response
    {
        $senateur = Senateur::where('matricule', $matricule)->firstOrFail();

        $questions = SenateurQuestion::where('senateur_matricule', $matricule)
            ->orderByDesc('date_question')
            ->paginate(20);

        // Stats du sénateur
        $senateurStats = [
            'total' => SenateurQuestion::where('senateur_matricule', $matricule)->count(),
            'repondues' => SenateurQuestion::where('senateur_matricule', $matricule)->repondues()->count(),
            'par_theme' => SenateurQuestion::where('senateur_matricule', $matricule)
                ->select('theme', DB::raw('count(*) as nb'))
                ->whereNotNull('theme')
                ->groupBy('theme')
                ->orderByDesc('nb')
                ->limit(5)
                ->get(),
            'par_type' => SenateurQuestion::where('senateur_matricule', $matricule)
                ->select('type', DB::raw('count(*) as nb'))
                ->whereNotNull('type')
                ->groupBy('type')
                ->orderByDesc('nb')
                ->get(),
        ];

        return Inertia::render('Questions/Senat/BySenateur', [
            'senateur' => $senateur,
            'questions' => $questions,
            'stats' => $senateurStats,
        ]);
    }

    /**
     * Stats globales Sénat
     */
    protected function getSenatStats(): array
    {
        return Cache::remember('questions_senat_stats', 3600, function () {
            return [
                'total' => SenateurQuestion::count(),
                'repondues' => SenateurQuestion::repondues()->count(),
                'en_attente' => SenateurQuestion::enAttente()->count(),
                'cette_semaine' => SenateurQuestion::where('date_question', '>=', now()->subWeek())->count(),
                'ce_mois' => SenateurQuestion::where('date_question', '>=', now()->subMonth())->count(),
                'senateurs_actifs' => SenateurQuestion::distinct('senateur_matricule')->count('senateur_matricule'),
            ];
        });
    }

    /**
     * Stats détaillées Sénat
     */
    protected function getSenatDetailedStats(): array
    {
        return Cache::remember('questions_senat_detailed_stats', 3600, function () {
            // Top thèmes
            $topThemes = SenateurQuestion::select('theme', DB::raw('count(*) as nb'))
                ->whereNotNull('theme')
                ->where('theme', '!=', '')
                ->groupBy('theme')
                ->orderByDesc('nb')
                ->limit(15)
                ->get();

            // Top ministères
            $topMinisteres = SenateurQuestion::select('ministre_destinataire', DB::raw('count(*) as nb'))
                ->whereNotNull('ministre_destinataire')
                ->groupBy('ministre_destinataire')
                ->orderByDesc('nb')
                ->limit(10)
                ->get();

            // Top sénateurs
            $topSenateurs = SenateurQuestion::select('senateur_matricule', DB::raw('count(*) as nb'))
                ->groupBy('senateur_matricule')
                ->orderByDesc('nb')
                ->limit(20)
                ->get()
                ->map(function ($item) {
                    $senateur = Senateur::where('matricule', $item->senateur_matricule)->first();

                    return [
                        'matricule' => $item->senateur_matricule,
                        'nom' => $senateur ? $senateur->prenom.' '.$senateur->nom : $item->senateur_matricule,
                        'groupe' => $senateur?->groupe_sigle,
                        'photo_url' => $senateur?->photo_url,
                        'nb' => $item->nb,
                    ];
                });

            // Évolution mensuelle
            $evolutionMensuelle = SenateurQuestion::select(
                DB::raw("TO_CHAR(date_question, 'YYYY-MM') as mois"),
                DB::raw('count(*) as nb')
            )
                ->whereNotNull('date_question')
                ->groupBy('mois')
                ->orderBy('mois')
                ->get();

            // Délai moyen de réponse
            $delaiMoyen = SenateurQuestion::repondues()
                ->whereNotNull('date_question')
                ->selectRaw('AVG(date_reponse - date_question) as delai_moyen')
                ->first();

            // Par type de question
            $parType = SenateurQuestion::select('type', DB::raw('count(*) as nb'))
                ->whereNotNull('type')
                ->groupBy('type')
                ->orderByDesc('nb')
                ->get();

            return [
                'global' => $this->getSenatStats(),
                'top_themes' => $topThemes,
                'top_ministeres' => $topMinisteres,
                'top_senateurs' => $topSenateurs,
                'par_type' => $parType,
                'evolution_mensuelle' => $evolutionMensuelle,
                'delai_moyen_jours' => $delaiMoyen && $delaiMoyen->delai_moyen ? round($delaiMoyen->delai_moyen) : null,
            ];
        });
    }
}
