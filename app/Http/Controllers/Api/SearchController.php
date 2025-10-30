<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Topic;
use App\Models\Post;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Controller pour la recherche full-text Meilisearch
 */
class SearchController extends Controller
{
    /**
     * Recherche globale (tous les modèles)
     * 
     * GET /api/search?q=piste+cyclable&type=topics&scope=region&limit=20
     */
    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:2|max:200',
            'type' => 'sometimes|string|in:topics,posts,documents,all',
            'limit' => 'sometimes|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation échouée',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = $request->input('q');
        $type = $request->input('type', 'all');
        $limit = $request->input('limit', 20);

        $results = [];

        try {
            // Rechercher dans Topics
            if ($type === 'topics' || $type === 'all') {
                $topicsQuery = Topic::search($query);

                // Filtres optionnels
                if ($request->has('scope')) {
                    $topicsQuery->where('scope', $request->input('scope'));
                }
                if ($request->has('type_topic')) {
                    $topicsQuery->where('type', $request->input('type_topic'));
                }
                if ($request->has('region_id')) {
                    $topicsQuery->where('region_id', $request->input('region_id'));
                }

                $topics = $topicsQuery->take($limit)->get();
                
                $results['topics'] = $topics->map(function ($topic) {
                    return [
                        'id' => $topic->id,
                        'title' => $topic->title,
                        'description' => substr($topic->description, 0, 200) . '...',
                        'type' => $topic->type,
                        'scope' => $topic->scope,
                        'author' => $topic->author?->name,
                        'created_at' => $topic->created_at->diffForHumans(),
                        'url' => "/topics/{$topic->id}",
                    ];
                });
            }

            // Rechercher dans Posts
            if ($type === 'posts' || $type === 'all') {
                $postsQuery = Post::search($query);

                if ($request->has('topic_id')) {
                    $postsQuery->where('topic_id', $request->input('topic_id'));
                }

                $posts = $postsQuery->take($limit)->get();
                
                $results['posts'] = $posts->map(function ($post) {
                    return [
                        'id' => $post->id,
                        'content' => substr($post->content, 0, 200) . '...',
                        'topic_id' => $post->topic_id,
                        'topic_title' => $post->topic?->title,
                        'author' => $post->author?->name,
                        'upvotes' => $post->upvotes,
                        'created_at' => $post->created_at->diffForHumans(),
                        'url' => "/topics/{$post->topic_id}#post-{$post->id}",
                    ];
                });
            }

            // Rechercher dans Documents
            if ($type === 'documents' || $type === 'all') {
                $documentsQuery = Document::search($query);

                if ($request->has('mime_type')) {
                    $documentsQuery->where('mime_type', $request->input('mime_type'));
                }

                $documents = $documentsQuery->take($limit)->get();
                
                $results['documents'] = $documents->map(function ($document) {
                    return [
                        'id' => $document->id,
                        'title' => $document->title,
                        'description' => $document->description,
                        'filename' => $document->filename,
                        'mime_type' => $document->mime_type,
                        'uploader' => $document->uploader?->name,
                        'created_at' => $document->created_at->diffForHumans(),
                        'url' => "/documents/{$document->id}",
                    ];
                });
            }

            // Compteurs totaux
            $totalResults = collect($results)->sum(fn($items) => $items->count());

            return response()->json([
                'success' => true,
                'query' => $query,
                'type' => $type,
                'results' => $results,
                'total' => $totalResults,
                'took_ms' => round((microtime(true) - LARAVEL_START) * 1000, 2),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur de recherche',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Autocomplete / Suggestions
     * 
     * GET /api/search/autocomplete?q=pist
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:2|max:100',
            'limit' => 'sometimes|integer|min:1|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation échouée',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = $request->input('q');
        $limit = $request->input('limit', 5);

        try {
            // Recherche rapide dans les titres de topics uniquement
            $topics = Topic::search($query)
                ->take($limit)
                ->get();

            $suggestions = $topics->map(function ($topic) {
                return [
                    'id' => $topic->id,
                    'title' => $topic->title,
                    'type' => $topic->type,
                    'url' => "/topics/{$topic->id}",
                ];
            });

            return response()->json([
                'success' => true,
                'query' => $query,
                'suggestions' => $suggestions,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur autocomplete',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Statistiques de recherche
     * 
     * GET /api/search/stats
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = [
                'topics_indexed' => Topic::count(),
                'posts_indexed' => Post::where('is_hidden', false)->count(),
                'documents_indexed' => Document::where('is_public', true)
                    ->where('status', 'verified')
                    ->count(),
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats,
                'total_indexed' => array_sum($stats),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur stats',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

