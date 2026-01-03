<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Centre de notifications (page complète)
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $filter = $request->get('filter', 'all'); // all, unread, unacknowledged

        $query = Notification::forUser($user->id)
            ->orderByDesc('created_at');

        if ($filter === 'unread') {
            $query->unread();
        } elseif ($filter === 'unacknowledged') {
            $query->unacknowledged();
        }

        $notifications = $query->paginate(20);

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
            'stats' => $this->notificationService->getStats($user),
            'filter' => $filter,
            'categories' => Notification::CATEGORIES,
        ]);
    }

    /**
     * API: Obtenir les notifications récentes (pour le dropdown)
     */
    public function recent(Request $request): JsonResponse
    {
        $user = $request->user();
        $limit = min($request->get('limit', 10), 50);

        $notifications = $this->notificationService->getNotifications($user, $limit);
        $unreadCount = $this->notificationService->getUnreadCount($user);

        return response()->json([
            'notifications' => $notifications->map(fn ($n) => [
                'id' => $n->id,
                'title' => $n->title,
                'message' => $n->message,
                'icon' => $n->icon,
                'category' => $n->category,
                'action_url' => $n->action_url,
                'read_at' => $n->read_at,
                'acknowledged_at' => $n->acknowledged_at,
                'actioned_at' => $n->actioned_at,
                'created_at' => $n->created_at,
                'time_ago' => $n->created_at->diffForHumans(),
            ]),
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * API: Compter les non lues
     */
    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'count' => $this->notificationService->getUnreadCount($request->user()),
        ]);
    }

    /**
     * Marquer une notification comme lue
     */
    public function markAsRead(Request $request, Notification $notification): JsonResponse
    {
        // Vérifier que la notification appartient à l'utilisateur
        if ($notification->user_id !== $request->user()->id) {
            abort(403);
        }

        $notification = $this->notificationService->markAsRead($notification);

        return response()->json([
            'success' => true,
            'notification' => $notification,
        ]);
    }

    /**
     * Acquitter une notification (confirmer lecture)
     */
    public function acknowledge(Request $request, Notification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            abort(403);
        }

        $notification = $this->notificationService->acknowledge($notification);

        return response()->json([
            'success' => true,
            'notification' => $notification,
        ]);
    }

    /**
     * Marquer comme traitée
     */
    public function action(Request $request, Notification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            abort(403);
        }

        $actionType = $request->get('action_type', 'completed');
        $notification = $this->notificationService->markAsActioned($notification, $actionType);

        return response()->json([
            'success' => true,
            'notification' => $notification,
        ]);
    }

    /**
     * Marquer toutes comme lues
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $count = $this->notificationService->markAllAsRead($request->user());

        return response()->json([
            'success' => true,
            'marked' => $count,
        ]);
    }

    /**
     * Supprimer une notification
     */
    public function destroy(Request $request, Notification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            abort(403);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Page des préférences de notification
     */
    public function preferences(Request $request): Response
    {
        $user = $request->user();
        $preferences = \App\Models\NotificationPreference::getOrCreateForUser($user->id);

        return Inertia::render('Profile/NotificationPreferences', [
            'preferences' => $preferences,
            'categories' => Notification::CATEGORIES,
            'channels' => Notification::CHANNELS,
        ]);
    }

    /**
     * Mettre à jour les préférences
     */
    public function updatePreferences(Request $request)
    {
        $validated = $request->validate([
            'channel_in_app' => 'boolean',
            'channel_email' => 'boolean',
            'email_frequency' => 'nullable|string|in:instant,daily,weekly',
            'notify_new_reply' => 'boolean',
            'notify_new_vote_on_topic' => 'boolean',
            'notify_legislative_vote_result' => 'boolean',
            'notify_mention' => 'boolean',
            'notify_vote_on_my_proposal' => 'boolean',
            'notify_new_thematique_proposition' => 'boolean',
            'notify_system_announcement' => 'boolean',
            'notify_followed_topic_update' => 'boolean',
            'notify_followed_legislation_update' => 'boolean',
            'group_similar_notifications' => 'boolean',
            'quiet_hours_start' => 'nullable|string',
            'quiet_hours_end' => 'nullable|string',
        ]);

        $preference = \App\Models\NotificationPreference::getOrCreateForUser($request->user()->id);
        $preference->fill($validated);
        $preference->save();

        return back()->with('success', 'Préférences de notification mises à jour !');
    }
}
