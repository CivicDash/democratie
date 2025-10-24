<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Topic\CreateBallotRequest;
use App\Http\Requests\Topic\StoreTopicRequest;
use App\Http\Requests\Topic\UpdateTopicRequest;
use App\Http\Resources\TopicResource;
use App\Http\Resources\TopicCollection;
use App\Models\Topic;
use App\Services\TopicService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TopicController extends Controller
{
    public function __construct(
        protected TopicService $topicService
    ) {}

    /**
     * Display a listing of topics.
     */
    public function index(Request $request): TopicCollection
    {
        $query = Topic::with(['author', 'region', 'department'])
            ->withCount(['posts', 'ballots']);

        // Filtres
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('scope')) {
            $query->where('scope', $request->scope);
        }

        if ($request->has('status')) {
            match ($request->status) {
                'open' => $query->whereNull('closed_at')->whereNull('archived_at'),
                'closed' => $query->whereNotNull('closed_at'),
                'archived' => $query->whereNotNull('archived_at'),
                default => null,
            };
        }

        // Tri
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $topics = $query->paginate($request->input('per_page', 15));

        return new TopicCollection($topics);
    }

    /**
     * Display a single topic.
     */
    public function show(Topic $topic): TopicResource
    {
        $this->authorize('view', $topic);

        $topic->load([
            'author',
            'region',
            'department',
            'posts.author',
        ])->loadCount(['posts', 'ballots']);

        return new TopicResource($topic);
    }

    /**
     * Store a newly created topic.
     */
    public function store(StoreTopicRequest $request): JsonResponse
    {
        try {
            $topic = $this->topicService->createTopic(
                $request->user(),
                $request->validated()
            );

            return (new TopicResource($topic->load('author', 'region', 'department')))
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la création du topic.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified topic.
     */
    public function update(UpdateTopicRequest $request, Topic $topic): TopicResource
    {
        $topic = $this->topicService->updateTopic(
            $topic,
            $request->validated()
        );

        return new TopicResource($topic->load('author', 'region', 'department'));
    }

    /**
     * Remove the specified topic.
     */
    public function destroy(Topic $topic): JsonResponse
    {
        try {
            $this->topicService->deleteTopic($topic, auth()->user());

            return response()->json([
                'message' => 'Topic supprimé avec succès.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la suppression du topic.',
                'error' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Close a topic.
     */
    public function close(Topic $topic): JsonResponse
    {
        try {
            $topic = $this->topicService->closeTopic($topic, auth()->user());

            return response()->json([
                'message' => 'Topic fermé avec succès.',
                'topic' => $topic,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la fermeture du topic.',
                'error' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Archive a topic.
     */
    public function archive(Topic $topic): JsonResponse
    {
        try {
            $topic = $this->topicService->archiveTopic($topic, auth()->user());

            return response()->json([
                'message' => 'Topic archivé avec succès.',
                'topic' => $topic,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de l\'archivage du topic.',
                'error' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Get trending topics.
     */
    public function trending(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);
        $days = $request->input('days', 7);

        $topics = $this->topicService->getTrendingTopics($limit, $days);

        return response()->json($topics);
    }

    /**
     * Create a ballot for a topic.
     */
    public function createBallot(CreateBallotRequest $request, Topic $topic): JsonResponse
    {
        try {
            $topic->update([
                'has_ballot' => true,
                'ballot_type' => $request->ballot_type,
                'ballot_options' => $request->ballot_options,
                'voting_opens_at' => $request->voting_opens_at,
                'voting_deadline_at' => $request->voting_deadline_at,
            ]);

            return response()->json([
                'message' => 'Scrutin créé avec succès.',
                'topic' => $topic->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la création du scrutin.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get topic statistics.
     */
    public function stats(Topic $topic): JsonResponse
    {
        $this->authorize('view', $topic);

        $stats = $this->topicService->getTopicStats($topic);

        return response()->json($stats);
    }
}

