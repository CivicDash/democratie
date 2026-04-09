<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeputeSenateur;
use App\Models\PropositionLoi;
use App\Models\VotePropositionLoi;
use App\Services\LegislationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Controller pour les données législatives (Assemblée + Sénat)
 */
class LegislationController extends Controller
{
    public function __construct(
        private LegislationService $legislationService
    ) {}

    /**
     * Liste des propositions de loi avec filtres avancés
     *
     * GET /api/legislation/propositions?source=both&limit=20&statut=en_cours
     * Nouveaux filtres: thematiques[], date_from, date_to, sources[], statuts[], sort_by
     */
    public function getPropositions(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'source' => 'sometimes|string|in:assemblee,senat,both',
            'limit' => 'sometimes|integer|min:1|max:100',
            'statut' => 'sometimes|string|in:en_cours,adoptee,rejetee,promulguee',
            'theme' => 'sometimes|string|max:100',
            // Filtres avancés
            'thematiques' => 'sometimes|array',
            'thematiques.*' => 'string',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
            'sources' => 'sometimes|array',
            'sources.*' => 'string|in:assemblee,senat',
            'statuts' => 'sometimes|array',
            'statuts.*' => 'string',
            'sort_by' => 'sometimes|string|in:date_desc,date_asc,title_asc,title_desc,votes_desc,votes_asc',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation échouée',
                'errors' => $validator->errors(),
            ], 422);
        }

        $limit = $request->input('limit', 20);
        $page = $request->input('page', 1);

        try {
            // Construire la requête avec filtres avancés
            $query = PropositionLoi::query();

            // Filtres de source
            if ($request->has('sources') && ! empty($request->input('sources'))) {
                $query->whereIn('source', $request->input('sources'));
            } elseif ($request->has('source') && $request->input('source') !== 'both') {
                $query->where('source', $request->input('source'));
            }

            // Filtres de statut
            if ($request->has('statuts') && ! empty($request->input('statuts'))) {
                $query->whereIn('statut', $request->input('statuts'));
            } elseif ($request->has('statut')) {
                $query->where('statut', $request->input('statut'));
            }

            // Filtres de thématiques
            if ($request->has('thematiques') && ! empty($request->input('thematiques'))) {
                $query->whereHas('thematiques', function ($q) use ($request) {
                    $q->whereIn('thematiques_legislation.code', $request->input('thematiques'));
                });
            }

            // Filtres de dates
            if ($request->has('date_from')) {
                $query->where('date_depot', '>=', $request->input('date_from'));
            }
            if ($request->has('date_to')) {
                $query->where('date_depot', '<=', $request->input('date_to'));
            }

            // Tri
            $sortBy = $request->input('sort_by', 'date_desc');
            switch ($sortBy) {
                case 'date_asc':
                    $query->orderBy('date_depot', 'asc');
                    break;
                case 'title_asc':
                    $query->orderBy('titre', 'asc');
                    break;
                case 'title_desc':
                    $query->orderBy('titre', 'desc');
                    break;
                case 'votes_desc':
                    $query->withCount('votesLegislatifs')->orderBy('votes_legislatifs_count', 'desc');
                    break;
                case 'votes_asc':
                    $query->withCount('votesLegislatifs')->orderBy('votes_legislatifs_count', 'asc');
                    break;
                case 'date_desc':
                default:
                    $query->orderBy('date_depot', 'desc');
                    break;
            }

            // Pagination
            $propositions = $query->with(['thematiques'])
                ->paginate($limit, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => $propositions->items(),
                'count' => $propositions->total(),
                'pagination' => [
                    'current_page' => $propositions->currentPage(),
                    'last_page' => $propositions->lastPage(),
                    'per_page' => $propositions->perPage(),
                    'total' => $propositions->total(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la récupération des propositions',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Détail d'une proposition
     *
     * GET /api/legislation/propositions/{source}/{numero}?legislature=17
     */
    public function getPropositionDetail(string $source, string $numero, Request $request): JsonResponse
    {
        if (! in_array($source, ['assemblee', 'senat'])) {
            return response()->json([
                'error' => 'Source invalide',
                'message' => 'La source doit être "assemblee" ou "senat"',
            ], 400);
        }

        $legislature = $request->input('legislature', 17);

        try {
            $proposition = $this->legislationService->getPropositionDetail($source, $numero, $legislature);

            if (! $proposition) {
                return response()->json([
                    'error' => 'Proposition non trouvée',
                    'source' => $source,
                    'numero' => $numero,
                    'legislature' => $legislature,
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $proposition,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Amendements d'une proposition
     *
     * GET /api/legislation/propositions/{source}/{numero}/amendements
     */
    public function getAmendements(string $source, string $numero, Request $request): JsonResponse
    {
        if (! in_array($source, ['assemblee', 'senat'])) {
            return response()->json(['error' => 'Source invalide'], 400);
        }

        $legislature = $request->input('legislature', 17);

        try {
            $amendements = $this->legislationService->getAmendements($source, $numero, $legislature);

            return response()->json([
                'success' => true,
                'data' => $amendements,
                'count' => count($amendements),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Votes sur une proposition
     *
     * GET /api/legislation/propositions/{source}/{numero}/votes
     */
    public function getVotes(string $source, string $numero, Request $request): JsonResponse
    {
        if (! in_array($source, ['assemblee', 'senat'])) {
            return response()->json(['error' => 'Source invalide'], 400);
        }

        $legislature = $request->input('legislature', 17);

        try {
            $votes = $this->legislationService->getVotes($source, $numero, $legislature);

            return response()->json([
                'success' => true,
                'data' => $votes,
                'count' => count($votes),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 🔥 KILLER FEATURE: Trouve des propositions législatives similaires
     *
     * POST /api/legislation/find-similar
     * Body: { "titre": "...", "description": "...", "tags": [...] }
     */
    public function findSimilar(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'titre' => 'required|string|min:5|max:500',
            'description' => 'required|string|min:10|max:5000',
            'tags' => 'sometimes|array|max:10',
            'tags.*' => 'string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation échouée',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $similar = $this->legislationService->findSimilarPropositions(
                $request->input('titre'),
                $request->input('description'),
                $request->input('tags', [])
            );

            return response()->json([
                'success' => true,
                'data' => $similar,
                'count' => count($similar),
                'message' => count($similar) > 0
                    ? 'Nous avons trouvé '.count($similar).' proposition(s) similaire(s) au Parlement !'
                    : 'Aucune proposition similaire trouvée. Votre idée est peut-être unique !',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Agenda législatif
     *
     * GET /api/legislation/agenda?source=both&date_debut=2025-11-01&date_fin=2025-11-30
     */
    public function getAgenda(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'source' => 'sometimes|string|in:assemblee,senat,both',
            'date_debut' => 'sometimes|date',
            'date_fin' => 'sometimes|date|after_or_equal:date_debut',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation échouée',
                'errors' => $validator->errors(),
            ], 422);
        }

        $source = $request->input('source', 'both');
        $dateDebut = $request->input('date_debut') ? new \DateTime($request->input('date_debut')) : new \DateTime;
        $dateFin = $request->input('date_fin') ? new \DateTime($request->input('date_fin')) : (clone $dateDebut)->modify('+30 days');

        try {
            $agenda = $this->legislationService->getAgendaLegislatif($source, $dateDebut, $dateFin);

            return response()->json([
                'success' => true,
                'data' => $agenda,
                'periode' => [
                    'debut' => $dateDebut->format('Y-m-d'),
                    'fin' => $dateFin->format('Y-m-d'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Statistiques législatives
     *
     * GET /api/legislation/stats?legislature=17
     */
    public function getStatistiques(Request $request): JsonResponse
    {
        $legislature = $request->input('legislature', 17);

        try {
            $stats = $this->legislationService->getStatistiques($legislature);

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Recherche de députés/sénateurs
     *
     * GET /api/legislation/elus/search?q=macron&source=assemblee
     */
    public function searchElus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:2|max:100',
            'source' => 'sometimes|string|in:assemblee,senat,both',
            'groupe' => 'sometimes|string|max:100',
            'circonscription' => 'sometimes|string|max:100',
            'limit' => 'sometimes|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation échouée',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = $request->input('q');
        $source = $request->input('source');
        $groupe = $request->input('groupe');
        $circonscription = $request->input('circonscription');
        $limit = $request->input('limit', 20);

        try {
            $elus = DeputeSenateur::search($query)
                ->enExercice()
                ->when($source && $source !== 'both', function ($q) use ($source) {
                    $q->where('source', $source);
                })
                ->when($groupe, function ($q) use ($groupe) {
                    $q->byGroupe($groupe);
                })
                ->when($circonscription, function ($q) use ($circonscription) {
                    $q->byCirconscription($circonscription);
                })
                ->orderBy('nom')
                ->limit($limit)
                ->get()
                ->map(fn ($elu) => $elu->toApiArray());

            return response()->json([
                'success' => true,
                'data' => $elus,
                'count' => $elus->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Détail d'un député/sénateur
     *
     * GET /api/legislation/elus/{uid}
     */
    public function getEluDetail(string $uid): JsonResponse
    {
        try {
            $elu = DeputeSenateur::where('uid', $uid)->first();

            if (! $elu) {
                return response()->json([
                    'error' => 'Élu non trouvé',
                    'uid' => $uid,
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $elu->toApiArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Propositions locales (depuis la BDD CivicDash)
     *
     * GET /api/legislation/propositions/local?limit=20&statut=en_cours
     */
    public function getPropositionsLocales(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 20);
        $statut = $request->input('statut');
        $source = $request->input('source');
        $theme = $request->input('theme');

        try {
            $query = PropositionLoi::query()
                ->orderBy('date_depot', 'desc');

            if ($statut) {
                $query->where('statut', $statut);
            }

            if ($source) {
                $query->where('source', $source);
            }

            if ($theme) {
                $query->byTheme($theme);
            }

            $propositions = $query->limit($limit)
                ->get()
                ->map(fn ($prop) => $prop->toApiArray());

            return response()->json([
                'success' => true,
                'data' => $propositions,
                'count' => $propositions->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 👍👎 Vote sur une proposition (upvote/downvote)
     *
     * POST /api/legislation/propositions/{id}/vote
     * Body: { "type": "upvote|downvote", "commentaire": "..." }
     */
    public function voteProposition(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:upvote,downvote',
            'commentaire' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation échouée',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $proposition = PropositionLoi::find($id);

            if (! $proposition) {
                return response()->json([
                    'error' => 'Proposition non trouvée',
                    'id' => $id,
                ], 404);
            }

            $userId = Auth::id();
            $typeVote = $request->input('type');
            $commentaire = $request->input('commentaire');

            // Enregistrer le vote
            $vote = VotePropositionLoi::castVote($userId, $id, $typeVote, $commentaire);

            // Récupérer les stats mises à jour
            $stats = VotePropositionLoi::getPropositionStats($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'vote' => [
                        'id' => $vote->id,
                        'type' => $vote->type_vote,
                        'created_at' => $vote->created_at->toIso8601String(),
                    ],
                    'stats' => $stats,
                ],
                'message' => $typeVote === 'upvote'
                    ? '👍 Vous soutenez cette proposition !'
                    : '👎 Vous êtes contre cette proposition.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Annule un vote sur une proposition
     *
     * DELETE /api/legislation/propositions/{id}/vote
     */
    public function removeVoteProposition(int $id): JsonResponse
    {
        try {
            $proposition = PropositionLoi::find($id);

            if (! $proposition) {
                return response()->json([
                    'error' => 'Proposition non trouvée',
                ], 404);
            }

            $userId = Auth::id();
            $removed = VotePropositionLoi::removeVote($userId, $id);

            if (! $removed) {
                return response()->json([
                    'error' => 'Aucun vote à supprimer',
                    'message' => 'Vous n\'avez pas voté pour cette proposition',
                ], 404);
            }

            // Stats mises à jour
            $stats = VotePropositionLoi::getPropositionStats($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                ],
                'message' => 'Vote annulé',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupère le vote de l'utilisateur sur une proposition
     *
     * GET /api/legislation/propositions/{id}/my-vote
     */
    public function getMyVote(int $id): JsonResponse
    {
        try {
            $userId = Auth::id();
            $vote = VotePropositionLoi::getUserVote($userId, $id);

            if (! $vote) {
                return response()->json([
                    'success' => true,
                    'data' => null,
                    'message' => 'Vous n\'avez pas encore voté',
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $vote->id,
                    'type' => $vote->type_vote,
                    'commentaire' => $vote->commentaire,
                    'created_at' => $vote->created_at->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupère les statistiques de vote d'une proposition
     *
     * GET /api/legislation/propositions/{id}/votes/stats
     */
    public function getVoteStats(int $id): JsonResponse
    {
        try {
            $proposition = PropositionLoi::find($id);

            if (! $proposition) {
                return response()->json([
                    'error' => 'Proposition non trouvée',
                ], 404);
            }

            $stats = VotePropositionLoi::getPropositionStats($id);

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Liste les propositions les plus populaires (par score)
     *
     * GET /api/legislation/propositions/trending?limit=10
     */
    public function getTrendingPropositions(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);
        $source = $request->input('source');

        try {
            $query = PropositionLoi::orderByDesc('score_vote');

            if ($source && $source !== 'both') {
                $query->where('source', $source);
            }

            $propositions = $query->limit($limit)
                ->get()
                ->map(function ($prop) {
                    $data = $prop->toApiArray();
                    $data['votes_stats'] = VotePropositionLoi::getPropositionStats($prop->id);

                    return $data;
                });

            return response()->json([
                'success' => true,
                'data' => $propositions,
                'count' => $propositions->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
