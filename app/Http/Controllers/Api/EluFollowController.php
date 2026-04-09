<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EluFollower;
use App\Services\EluFollowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EluFollowController extends Controller
{
    public function __construct(
        private EluFollowService $followService
    ) {}

    /**
     * Suivre un élu
     */
    public function follow(Request $request): JsonResponse
    {
        $request->validate([
            'elu_type' => 'required|string|in:depute,senateur,maire,ministre',
            'elu_id' => 'required|string',
            'preferences' => 'nullable|array',
            'preferences.notify_votes' => 'nullable|boolean',
            'preferences.notify_interventions' => 'nullable|boolean',
            'preferences.notify_amendements' => 'nullable|boolean',
            'preferences.notify_propositions' => 'nullable|boolean',
            'preferences.notify_rapports' => 'nullable|boolean',
            'preferences.notify_commissions' => 'nullable|boolean',
            'preferences.notify_actualites' => 'nullable|boolean',
            'preferences.notify_site' => 'nullable|boolean',
            'preferences.notify_email' => 'nullable|boolean',
            'preferences.email_frequency' => 'nullable|string|in:instant,daily,weekly',
        ]);

        try {
            $follower = $this->followService->follow(
                Auth::user(),
                $request->input('elu_type'),
                $request->input('elu_id'),
                $request->input('preferences', [])
            );

            return response()->json([
                'success' => true,
                'message' => "Vous suivez maintenant {$follower->elu_nom}",
                'data' => $this->formatFollower($follower),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Ne plus suivre un élu
     */
    public function unfollow(Request $request): JsonResponse
    {
        $request->validate([
            'elu_type' => 'required|string|in:depute,senateur,maire,ministre',
            'elu_id' => 'required|string',
        ]);

        $success = $this->followService->unfollow(
            Auth::user(),
            $request->input('elu_type'),
            $request->input('elu_id')
        );

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Vous ne suivez plus cet élu' : 'Suivi non trouvé',
        ]);
    }

    /**
     * Mettre à jour les préférences de suivi
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $request->validate([
            'elu_type' => 'required|string|in:depute,senateur,maire,ministre',
            'elu_id' => 'required|string',
            'preferences' => 'required|array',
        ]);

        $follower = $this->followService->updatePreferences(
            Auth::user(),
            $request->input('elu_type'),
            $request->input('elu_id'),
            $request->input('preferences')
        );

        if (! $follower) {
            return response()->json([
                'success' => false,
                'message' => 'Suivi non trouvé',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Préférences mises à jour',
            'data' => $this->formatFollower($follower),
        ]);
    }

    /**
     * Vérifier si l'utilisateur suit un élu
     */
    public function checkStatus(Request $request): JsonResponse
    {
        $request->validate([
            'elu_type' => 'required|string|in:depute,senateur,maire,ministre',
            'elu_id' => 'required|string',
        ]);

        $follower = $this->followService->getFollowing(
            Auth::user(),
            $request->input('elu_type'),
            $request->input('elu_id')
        );

        return response()->json([
            'is_following' => $follower !== null,
            'data' => $follower ? $this->formatFollower($follower) : null,
        ]);
    }

    /**
     * Liste des élus suivis par l'utilisateur
     */
    public function myFollowing(): JsonResponse
    {
        $followers = $this->followService->getFollowedElus(Auth::user());

        return response()->json([
            'count' => $followers->count(),
            'data' => $followers->map(fn ($f) => $this->formatFollower($f)),
        ]);
    }

    /**
     * Statistiques des followers d'un élu
     */
    public function eluStats(Request $request): JsonResponse
    {
        $request->validate([
            'elu_type' => 'required|string|in:depute,senateur,maire,ministre',
            'elu_id' => 'required|string',
        ]);

        $stats = $this->followService->getEluStats(
            $request->input('elu_type'),
            $request->input('elu_id')
        );

        return response()->json($stats);
    }

    /**
     * Formater un follower pour l'API
     */
    private function formatFollower(EluFollower $follower): array
    {
        return [
            'id' => $follower->id,
            'elu_type' => $follower->elu_type,
            'elu_type_label' => $follower->elu_type_label,
            'elu_id' => $follower->elu_id,
            'elu_nom' => $follower->elu_nom,
            'elu_photo_url' => $follower->elu_photo_url,
            'elu_groupe' => $follower->elu_groupe,
            'elu_circonscription' => $follower->elu_circonscription,
            'elu_url' => $follower->elu_url,
            'preferences' => [
                'notify_votes' => $follower->notify_votes,
                'notify_interventions' => $follower->notify_interventions,
                'notify_amendements' => $follower->notify_amendements,
                'notify_propositions' => $follower->notify_propositions,
                'notify_rapports' => $follower->notify_rapports,
                'notify_commissions' => $follower->notify_commissions,
                'notify_actualites' => $follower->notify_actualites,
                'notify_site' => $follower->notify_site,
                'notify_email' => $follower->notify_email,
                'email_frequency' => $follower->email_frequency,
            ],
            'followed_at' => $follower->followed_at?->toIso8601String(),
            'notifications_received' => $follower->notifications_received,
            'last_activity_at' => $follower->last_activity_notified_at?->toIso8601String(),
        ];
    }
}
