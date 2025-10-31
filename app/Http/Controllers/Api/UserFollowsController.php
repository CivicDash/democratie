<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserFollow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserFollowsController extends Controller
{
    /**
     * Liste des éléments suivis par l'utilisateur
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        $follows = UserFollow::where('user_id', $user->id)
            ->with('followable')
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'follows' => $follows->items(),
            'pagination' => [
                'current_page' => $follows->currentPage(),
                'last_page' => $follows->lastPage(),
                'per_page' => $follows->perPage(),
                'total' => $follows->total(),
            ],
        ]);
    }

    /**
     * Suivre un élément
     */
    public function follow(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string',
            'id' => 'required|integer',
            'settings' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $type = $request->input('type');
        $id = $request->input('id');
        $settings = $request->input('settings', []);

        // Valider le type
        $validTypes = [
            'App\\Models\\Topic',
            'App\\Models\\PropositionLoi',
            'App\\Models\\Post',
            'App\\Models\\ThematiqueLegislation',
        ];

        if (!in_array($type, $validTypes)) {
            return response()->json([
                'success' => false,
                'message' => 'Type non valide',
            ], 400);
        }

        $follow = UserFollow::follow($user->id, $type, $id, $settings);

        return response()->json([
            'success' => true,
            'message' => 'Élément suivi avec succès',
            'follow' => $follow,
        ]);
    }

    /**
     * Ne plus suivre un élément
     */
    public function unfollow(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string',
            'id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $type = $request->input('type');
        $id = $request->input('id');

        $success = UserFollow::unfollow($user->id, $type, $id);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Élément retiré des suivis' : 'Élément introuvable',
        ]);
    }

    /**
     * Vérifier si l'utilisateur suit un élément
     */
    public function check(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string',
            'id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $type = $request->input('type');
        $id = $request->input('id');

        $isFollowing = UserFollow::isFollowing($user->id, $type, $id);

        return response()->json([
            'success' => true,
            'is_following' => $isFollowing,
        ]);
    }

    /**
     * Statistiques de suivi
     */
    public function stats(): JsonResponse
    {
        $user = Auth::user();

        $stats = [
            'total' => UserFollow::where('user_id', $user->id)->count(),
            'by_type' => UserFollow::where('user_id', $user->id)
                ->selectRaw('followable_type, COUNT(*) as count')
                ->groupBy('followable_type')
                ->pluck('count', 'followable_type')
                ->toArray(),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }
}
