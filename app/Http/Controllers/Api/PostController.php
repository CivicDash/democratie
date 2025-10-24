<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Http\Requests\Post\VotePostRequest;
use App\Models\Post;
use App\Models\Topic;
use App\Services\TopicService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function __construct(
        protected TopicService $topicService
    ) {}

    /**
     * Display posts for a topic.
     */
    public function index(Request $request, Topic $topic): JsonResponse
    {
        $this->authorize('view', $topic);

        $query = Post::where('topic_id', $topic->id)
            ->where('is_hidden', false)
            ->with(['user', 'user.profile']);

        // Filtrer par parent (thread racine ou réponses)
        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        } else {
            $query->whereNull('parent_id'); // Posts racines uniquement
        }

        // Tri
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        if ($sortBy === 'score') {
            $query->orderByRaw('(upvotes - downvotes) ' . $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $posts = $query->paginate($request->input('per_page', 20));

        return response()->json($posts);
    }

    /**
     * Display a single post with replies.
     */
    public function show(Post $post): JsonResponse
    {
        $this->authorize('view', $post);

        $post->load(['user', 'user.profile', 'topic']);

        // Charger les réponses
        $replies = $this->topicService->getReplies($post);

        return response()->json([
            'post' => $post,
            'replies' => $replies,
        ]);
    }

    /**
     * Store a newly created post.
     */
    public function store(StorePostRequest $request, Topic $topic): JsonResponse
    {
        try {
            $post = $this->topicService->createPost(
                $topic,
                $request->user(),
                $request->content,
                $request->parent_id
            );

            return response()->json([
                'message' => 'Post créé avec succès.',
                'post' => $post->load('user', 'user.profile'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la création du post.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified post.
     */
    public function update(UpdatePostRequest $request, Post $post): JsonResponse
    {
        try {
            $post = $this->topicService->updatePost(
                $post,
                $request->user(),
                $request->content
            );

            return response()->json([
                'message' => 'Post mis à jour avec succès.',
                'post' => $post->load('user', 'user.profile'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la mise à jour du post.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified post.
     */
    public function destroy(Post $post): JsonResponse
    {
        try {
            $this->topicService->deletePost($post, auth()->user());

            return response()->json([
                'message' => 'Post supprimé avec succès.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la suppression du post.',
                'error' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Vote on a post (upvote/downvote).
     */
    public function vote(VotePostRequest $request, Post $post): JsonResponse
    {
        try {
            $result = $this->topicService->voteOnPost(
                $post,
                $request->user(),
                $request->vote
            );

            return response()->json([
                'message' => 'Vote enregistré avec succès.',
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors du vote.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get top posts for a topic.
     */
    public function top(Request $request, Topic $topic): JsonResponse
    {
        $this->authorize('view', $topic);

        $limit = $request->input('limit', 10);
        $posts = $this->topicService->getTopPosts($topic, $limit);

        return response()->json($posts);
    }

    /**
     * Get replies for a post.
     */
    public function replies(Post $post): JsonResponse
    {
        $this->authorize('view', $post);

        $replies = $this->topicService->getReplies($post);

        return response()->json($replies);
    }
}

