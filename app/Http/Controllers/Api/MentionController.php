<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserMention;
use App\Services\MentionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MentionController extends Controller
{
    public function __construct(
        protected MentionService $mentionService
    ) {}

    /**
     * Suggestions d'utilisateurs pour l'autocomplete
     */
    public function suggest(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json(['suggestions' => []]);
        }

        $suggestions = $this->mentionService->suggestUsers($query, 10);

        return response()->json([
            'success' => true,
            'suggestions' => $suggestions,
        ]);
    }

    /**
     * Mes mentions non lues
     */
    public function unread(Request $request): JsonResponse
    {
        $user = $request->user();
        $mentions = $this->mentionService->getUnreadMentions($user);

        return response()->json([
            'success' => true,
            'mentions' => $mentions->map(fn ($m) => [
                'id' => $m->id,
                'author' => [
                    'id' => $m->author->id,
                    'name' => $m->author->display_name,
                ],
                'content_type' => class_basename($m->mentionable_type),
                'created_at' => $m->created_at->diffForHumans(),
            ]),
            'count' => $mentions->count(),
        ]);
    }

    /**
     * Marquer une mention comme lue
     */
    public function markAsRead(Request $request, UserMention $mention): JsonResponse
    {
        $user = $request->user();

        if ($mention->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        $mention->markAsRead();

        return response()->json(['success' => true]);
    }

    /**
     * Marquer toutes les mentions comme lues
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $count = $this->mentionService->markAllAsRead($user);

        return response()->json([
            'success' => true,
            'marked_count' => $count,
        ]);
    }

    /**
     * Preview du rendu des mentions
     */
    public function preview(Request $request): JsonResponse
    {
        $content = $request->input('content', '');
        $rendered = $this->mentionService->renderMentions($content);

        return response()->json([
            'success' => true,
            'rendered' => $rendered,
        ]);
    }
}
