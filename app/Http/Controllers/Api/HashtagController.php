<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hashtag;
use App\Models\Post;
use App\Models\Topic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Controller pour les hashtags
 */
class HashtagController extends Controller
{
    /**
     * Liste des hashtags tendance (24h)
     * 
     * GET /api/hashtags/trending
     */
    public function trending(Request $request): JsonResponse
    {
        $limit = $request->query('limit', 20);
        
        $hashtags = Hashtag::trending()
            ->limit($limit)
            ->get(['id', 'slug', 'display_name', 'usage_count', 'last_used_at']);

        return response()->json([
            'hashtags' => $hashtags,
            'period' => '24h',
        ]);
    }

    /**
     * Liste des hashtags populaires (all-time)
     * 
     * GET /api/hashtags/popular
     */
    public function popular(Request $request): JsonResponse
    {
        $limit = $request->query('limit', 50);
        
        $hashtags = Hashtag::popular($limit)
            ->get(['id', 'slug', 'display_name', 'usage_count', 'is_official', 'description']);

        return response()->json([
            'hashtags' => $hashtags,
        ]);
    }

    /**
     * Recherche d'hashtags (autocomplete)
     * 
     * GET /api/hashtags/search?q=climat
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->query('q', '');
        
        if (strlen($query) < 2) {
            return response()->json(['hashtags' => []]);
        }

        $hashtags = Hashtag::search($query)
            ->limit(10)
            ->get(['id', 'slug', 'display_name', 'usage_count']);

        return response()->json([
            'hashtags' => $hashtags,
            'query' => $query,
        ]);
    }

    /**
     * Détails d'un hashtag + contenu associé
     * 
     * GET /api/hashtags/:slug
     */
    public function show(Request $request, string $slug): JsonResponse
    {
        $hashtag = Hashtag::where('slug', Hashtag::normalize($slug))->first();

        if (!$hashtag) {
            return response()->json(['error' => 'Hashtag not found'], 404);
        }

        // Paramètres pagination
        $postsLimit = $request->query('posts_limit', 20);
        $topicsLimit = $request->query('topics_limit', 10);

        // Charger posts avec ce hashtag
        $posts = Post::withHashtag($slug)
            ->with(['user.profile', 'topic'])
            ->orderBy('created_at', 'desc')
            ->limit($postsLimit)
            ->get();

        // Charger topics avec ce hashtag
        $topics = Topic::withHashtag($slug)
            ->with(['author.profile'])
            ->orderBy('created_at', 'desc')
            ->limit($topicsLimit)
            ->get();

        return response()->json([
            'hashtag' => [
                'id' => $hashtag->id,
                'slug' => $hashtag->slug,
                'display_name' => $hashtag->display_name,
                'usage_count' => $hashtag->usage_count,
                'is_official' => $hashtag->is_official,
                'is_trending' => $hashtag->is_trending,
                'description' => $hashtag->description,
                'last_used_at' => $hashtag->last_used_at,
            ],
            'posts' => $posts,
            'topics' => $topics,
            'meta' => [
                'posts_count' => $hashtag->posts()->count(),
                'topics_count' => $hashtag->topics()->count(),
            ],
        ]);
    }

    /**
     * Hashtags officiels (thématiques)
     * 
     * GET /api/hashtags/official
     */
    public function official(): JsonResponse
    {
        $hashtags = Hashtag::official()
            ->orderBy('usage_count', 'desc')
            ->get(['id', 'slug', 'display_name', 'description', 'usage_count']);

        return response()->json([
            'hashtags' => $hashtags,
        ]);
    }
}
