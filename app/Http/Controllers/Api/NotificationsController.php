<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationsController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Liste des notifications de l'utilisateur connecté
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        // Filtres
        if ($request->has('unread_only') && $request->boolean('unread_only')) {
            $query->unread();
        }

        if ($request->has('type')) {
            $query->ofType($request->input('type'));
        }

        if ($request->has('priority')) {
            $query->withPriority($request->input('priority'));
        }

        // Pagination
        $perPage = $request->input('per_page', 20);
        $notifications = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'notifications' => $notifications->items(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    /**
     * Nombre de notifications non lues
     */
    public function unreadCount(): JsonResponse
    {
        $user = Auth::user();

        $count = Notification::where('user_id', $user->id)
            ->unread()
            ->count();

        return response()->json([
            'success' => true,
            'count' => $count,
        ]);
    }

    /**
     * Marquer une notification comme lue
     */
    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();

        $notification = Notification::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marquée comme lue',
            'notification' => $notification->fresh(),
        ]);
    }

    /**
     * Marquer toutes les notifications comme lues
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = Auth::user();

        $count = $this->notificationService->markAllAsRead($user);

        return response()->json([
            'success' => true,
            'message' => "$count notification(s) marquée(s) comme lue(s)",
            'count' => $count,
        ]);
    }

    /**
     * Marquer une notification comme non lue
     */
    public function markAsUnread(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();

        $notification = Notification::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $notification->markAsUnread();

        return response()->json([
            'success' => true,
            'message' => 'Notification marquée comme non lue',
            'notification' => $notification->fresh(),
        ]);
    }

    /**
     * Supprimer une notification
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();

        $notification = Notification::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification supprimée',
        ]);
    }

    /**
     * Supprimer toutes les notifications lues
     */
    public function clearRead(Request $request): JsonResponse
    {
        $user = Auth::user();

        $count = Notification::where('user_id', $user->id)
            ->read()
            ->delete();

        return response()->json([
            'success' => true,
            'message' => "$count notification(s) supprimée(s)",
            'count' => $count,
        ]);
    }

    /**
     * Statistiques des notifications
     */
    public function stats(): JsonResponse
    {
        $user = Auth::user();

        $stats = $this->notificationService->getStats($user);

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }

    /**
     * Tester l'envoi d'une notification (dev uniquement)
     */
    public function test(Request $request): JsonResponse
    {
        if (!app()->environment('local')) {
            return response()->json([
                'success' => false,
                'message' => 'Disponible uniquement en environnement local',
            ], 403);
        }

        $user = Auth::user();

        $notification = $this->notificationService->send(
            user: $user,
            type: Notification::TYPE_SYSTEM,
            title: 'Notification de test',
            message: 'Ceci est une notification de test envoyée à ' . now()->format('H:i:s'),
            priority: Notification::PRIORITY_NORMAL,
            icon: '🧪'
        );

        return response()->json([
            'success' => true,
            'message' => 'Notification de test envoyée',
            'notification' => $notification,
        ]);
    }
}
