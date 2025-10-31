<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotificationPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NotificationPreferencesController extends Controller
{
    /**
     * Obtenir les préférences de notification de l'utilisateur
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();
        $preferences = NotificationPreference::getForUser($user->id);

        return response()->json([
            'success' => true,
            'preferences' => $preferences->toPreferencesArray(),
        ]);
    }

    /**
     * Mettre à jour les préférences
     */
    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notify_new_reply' => 'sometimes|boolean',
            'notify_new_vote_on_topic' => 'sometimes|boolean',
            'notify_legislative_vote_result' => 'sometimes|boolean',
            'notify_mention' => 'sometimes|boolean',
            'notify_vote_on_my_proposal' => 'sometimes|boolean',
            'notify_new_thematique_proposition' => 'sometimes|boolean',
            'notify_system_announcement' => 'sometimes|boolean',
            'notify_followed_topic_update' => 'sometimes|boolean',
            'notify_followed_legislation_update' => 'sometimes|boolean',
            'channel_in_app' => 'sometimes|boolean',
            'channel_email' => 'sometimes|boolean',
            'email_frequency' => 'sometimes|string|in:instant,daily,weekly,never',
            'quiet_hours_start' => 'sometimes|nullable|date_format:H:i:s',
            'quiet_hours_end' => 'sometimes|nullable|date_format:H:i:s',
            'group_similar_notifications' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $preferences = NotificationPreference::getForUser($user->id);

        $preferences->update($request->only([
            'notify_new_reply',
            'notify_new_vote_on_topic',
            'notify_legislative_vote_result',
            'notify_mention',
            'notify_vote_on_my_proposal',
            'notify_new_thematique_proposition',
            'notify_system_announcement',
            'notify_followed_topic_update',
            'notify_followed_legislation_update',
            'channel_in_app',
            'channel_email',
            'email_frequency',
            'quiet_hours_start',
            'quiet_hours_end',
            'group_similar_notifications',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Préférences mises à jour',
            'preferences' => $preferences->fresh()->toPreferencesArray(),
        ]);
    }

    /**
     * Réinitialiser aux valeurs par défaut
     */
    public function reset(): JsonResponse
    {
        $user = Auth::user();
        $preferences = NotificationPreference::getForUser($user->id);

        $preferences->update([
            'notify_new_reply' => true,
            'notify_new_vote_on_topic' => true,
            'notify_legislative_vote_result' => true,
            'notify_mention' => true,
            'notify_vote_on_my_proposal' => true,
            'notify_new_thematique_proposition' => false,
            'notify_system_announcement' => true,
            'notify_followed_topic_update' => true,
            'notify_followed_legislation_update' => true,
            'channel_in_app' => true,
            'channel_email' => false,
            'email_frequency' => 'instant',
            'quiet_hours_start' => null,
            'quiet_hours_end' => null,
            'group_similar_notifications' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Préférences réinitialisées',
            'preferences' => $preferences->fresh()->toPreferencesArray(),
        ]);
    }

    /**
     * Activer/désactiver toutes les notifications
     */
    public function toggleAll(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'enabled' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $preferences = NotificationPreference::getForUser($user->id);
        $enabled = $request->boolean('enabled');

        $preferences->update([
            'notify_new_reply' => $enabled,
            'notify_new_vote_on_topic' => $enabled,
            'notify_legislative_vote_result' => $enabled,
            'notify_mention' => $enabled,
            'notify_vote_on_my_proposal' => $enabled,
            'notify_new_thematique_proposition' => $enabled,
            'notify_system_announcement' => $enabled,
            'notify_followed_topic_update' => $enabled,
            'notify_followed_legislation_update' => $enabled,
        ]);

        return response()->json([
            'success' => true,
            'message' => $enabled ? 'Toutes les notifications activées' : 'Toutes les notifications désactivées',
            'preferences' => $preferences->fresh()->toPreferencesArray(),
        ]);
    }
}
