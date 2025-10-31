<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Achievement;
use App\Models\User;
use App\Models\UserAchievement;
use App\Models\UserStats;
use App\Services\GamificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GamificationController extends Controller
{
    public function __construct(
        private GamificationService $gamificationService
    ) {}

    /**
     * Obtenir les statistiques de l'utilisateur connecté
     */
    public function myStats(): JsonResponse
    {
        $user = Auth::user();
        $stats = UserStats::getForUser($user->id);
        
        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'level_title' => $stats->level_title,
                'level_color' => $stats->level_color,
                'level_progress_percentage' => $stats->level_progress_percentage,
                'rank' => $this->gamificationService->getUserRank($user),
            ],
        ]);
    }

    /**
     * Obtenir les achievements de l'utilisateur connecté
     */
    public function myAchievements(Request $request): JsonResponse
    {
        $user = Auth::user();
        $unlockedOnly = $request->boolean('unlocked_only', false);
        $category = $request->input('category');
        
        $query = UserAchievement::with('achievement')
            ->where('user_id', $user->id);
        
        if ($unlockedOnly) {
            $query->unlocked();
        }
        
        if ($category) {
            $query->whereHas('achievement', function ($q) use ($category) {
                $q->where('category', $category);
            });
        }
        
        $achievements = $query->get();
        
        return response()->json([
            'success' => true,
            'data' => $achievements,
        ]);
    }

    /**
     * Obtenir les achievements récents
     */
    public function recentAchievements(): JsonResponse
    {
        $user = Auth::user();
        $recent = $this->gamificationService->getRecentAchievements($user, 10);
        
        return response()->json([
            'success' => true,
            'data' => $recent,
        ]);
    }

    /**
     * Obtenir les achievements presque débloqués
     */
    public function almostUnlocked(): JsonResponse
    {
        $user = Auth::user();
        $almost = $this->gamificationService->getAlmostUnlockedAchievements($user, 5);
        
        return response()->json([
            'success' => true,
            'data' => $almost,
        ]);
    }

    /**
     * Obtenir tous les achievements disponibles
     */
    public function allAchievements(Request $request): JsonResponse
    {
        $query = Achievement::active();
        
        if ($category = $request->input('category')) {
            $query->category($category);
        }
        
        if ($rarity = $request->input('rarity')) {
            $query->rarity($rarity);
        }
        
        if ($request->boolean('visible_only', true)) {
            $query->visible();
        }
        
        $achievements = $query->orderBy('order')->get();
        
        return response()->json([
            'success' => true,
            'data' => $achievements,
        ]);
    }

    /**
     * Obtenir le classement global
     */
    public function leaderboard(Request $request): JsonResponse
    {
        $limit = min($request->integer('limit', 100), 500);
        $orderBy = $request->input('order_by', 'reputation_score');
        
        // Vérifier que le champ de tri est valide
        $validOrderBy = ['reputation_score', 'level', 'xp', 'total_achievements', 'current_streak'];
        if (!in_array($orderBy, $validOrderBy)) {
            $orderBy = 'reputation_score';
        }
        
        $leaderboard = $this->gamificationService->getLeaderboard($limit, $orderBy);
        
        // Ajouter le rang de l'utilisateur connecté
        $user = Auth::user();
        $myRank = $this->gamificationService->getUserRank($user, $orderBy);
        $myStats = UserStats::getForUser($user->id);
        
        return response()->json([
            'success' => true,
            'data' => [
                'leaderboard' => $leaderboard,
                'my_rank' => $myRank,
                'my_stats' => $myStats,
                'total_users' => UserStats::count(),
            ],
        ]);
    }

    /**
     * Obtenir les statistiques d'un autre utilisateur
     */
    public function userStats(int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);
        $stats = UserStats::getForUser($userId);
        
        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'stats' => $stats,
                'level_title' => $stats->level_title,
                'rank' => $this->gamificationService->getUserRank($user),
            ],
        ]);
    }

    /**
     * Obtenir les achievements d'un autre utilisateur
     */
    public function userAchievements(int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);
        $achievements = $this->gamificationService->getUserAchievements($user, true);
        
        return response()->json([
            'success' => true,
            'data' => $achievements,
        ]);
    }

    /**
     * Tester le système de gamification (dev only)
     */
    public function test(Request $request): JsonResponse
    {
        if (!app()->environment(['local', 'development'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cette fonctionnalité n\'est disponible qu\'en développement',
            ], 403);
        }
        
        $user = Auth::user();
        $eventType = $request->input('event_type', Achievement::TRIGGER_VOTE_COUNT);
        $value = $request->integer('value', 1);
        
        $unlocked = $this->gamificationService->processEvent($user, $eventType, $value);
        
        return response()->json([
            'success' => true,
            'message' => 'Événement traité',
            'data' => [
                'event_type' => $eventType,
                'value' => $value,
                'unlocked_achievements' => $unlocked,
            ],
        ]);
    }

    /**
     * Initialiser la gamification pour l'utilisateur (si pas déjà fait)
     */
    public function initialize(): JsonResponse
    {
        $user = Auth::user();
        
        // Vérifier si déjà initialisé
        $stats = UserStats::where('user_id', $user->id)->first();
        if ($stats) {
            return response()->json([
                'success' => true,
                'message' => 'Gamification déjà initialisée',
                'data' => $stats,
            ]);
        }
        
        // Initialiser
        $this->gamificationService->initializeUser($user);
        $stats = UserStats::getForUser($user->id);
        
        return response()->json([
            'success' => true,
            'message' => 'Gamification initialisée avec succès',
            'data' => $stats,
        ]);
    }

    /**
     * Marquer un achievement comme partagé
     */
    public function shareAchievement(int $achievementId): JsonResponse
    {
        $user = Auth::user();
        
        $userAchievement = UserAchievement::where('user_id', $user->id)
            ->where('achievement_id', $achievementId)
            ->firstOrFail();
        
        if (!$userAchievement->is_unlocked) {
            return response()->json([
                'success' => false,
                'message' => 'Vous devez débloquer ce badge avant de le partager',
            ], 400);
        }
        
        $userAchievement->markAsShared();
        
        return response()->json([
            'success' => true,
            'message' => 'Badge partagé !',
        ]);
    }

    /**
     * Obtenir les catégories et statistiques globales
     */
    public function globalStats(): JsonResponse
    {
        $totalUsers = User::count();
        $activeUsers = UserStats::where('last_activity_date', '>=', now()->subDays(7))->count();
        
        $categoryStats = Achievement::selectRaw('category, COUNT(*) as total')
            ->active()
            ->groupBy('category')
            ->get();
        
        $rarityStats = Achievement::selectRaw('rarity, COUNT(*) as total')
            ->active()
            ->groupBy('rarity')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => [
                'total_users' => $totalUsers,
                'active_users_7d' => $activeUsers,
                'total_achievements' => Achievement::active()->count(),
                'categories' => $categoryStats,
                'rarities' => $rarityStats,
            ],
        ]);
    }
}

