<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\UserAchievement;
use App\Models\UserStats;
use App\Services\GamificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AchievementController extends Controller
{
    public function __construct(
        private GamificationService $gamificationService
    ) {}

    /**
     * Obtenir tous les achievements (catalogue)
     */
    public function index(Request $request)
    {
        $query = Achievement::active();

        // Filtrer par catégorie
        if ($request->has('category')) {
            $query->category($request->category);
        }

        // Filtrer par rareté
        if ($request->has('rarity')) {
            $query->rarity($request->rarity);
        }

        // Masquer les secrets si l'utilisateur n'est pas connecté
        if (!Auth::check()) {
            $query->visible();
        }

        $achievements = $query->orderBy('order')->get();

        // Si l'utilisateur est connecté, enrichir avec sa progression
        if (Auth::check()) {
            $user = Auth::user();
            $userAchievements = UserAchievement::where('user_id', $user->id)
                ->pluck('progress', 'achievement_id');

            $achievements->each(function ($achievement) use ($userAchievements) {
                $achievement->user_progress = $userAchievements[$achievement->id] ?? 0;
                $achievement->user_progress_percentage = $achievement->getProgressPercentage(
                    Auth::user(),
                    $achievement->user_progress
                );
            });
        }

        return response()->json([
            'achievements' => $achievements,
            'stats' => [
                'total' => $achievements->count(),
                'by_category' => $achievements->groupBy('category')->map->count(),
                'by_rarity' => $achievements->groupBy('rarity')->map->count(),
            ]
        ]);
    }

    /**
     * Obtenir les achievements d'un utilisateur
     */
    public function userAchievements(Request $request)
    {
        $user = Auth::user();
        $unlockedOnly = $request->boolean('unlocked_only', false);

        $achievements = $this->gamificationService->getUserAchievements($user, $unlockedOnly);

        return response()->json([
            'achievements' => $achievements->map(function ($userAchievement) {
                return [
                    'id' => $userAchievement->achievement->id,
                    'code' => $userAchievement->achievement->code,
                    'name' => $userAchievement->achievement->name,
                    'description' => $userAchievement->achievement->description,
                    'icon' => $userAchievement->achievement->icon,
                    'color' => $userAchievement->achievement->color,
                    'category' => $userAchievement->achievement->category,
                    'rarity' => $userAchievement->achievement->rarity,
                    'rarity_color' => $userAchievement->achievement->rarity_color,
                    'rarity_gradient' => $userAchievement->achievement->rarity_gradient,
                    'points' => $userAchievement->achievement->points,
                    'progress' => $userAchievement->progress,
                    'progress_target' => $userAchievement->progress_target,
                    'progress_percentage' => $userAchievement->progress_percentage,
                    'is_unlocked' => $userAchievement->is_unlocked,
                    'unlocked_at' => $userAchievement->unlocked_at,
                    'is_almost_unlocked' => $userAchievement->isAlmostUnlocked(),
                ];
            }),
        ]);
    }

    /**
     * Obtenir les achievements récents
     */
    public function recent()
    {
        $user = Auth::user();
        $recent = $this->gamificationService->getRecentAchievements($user, 10);

        return response()->json([
            'achievements' => $recent->map(fn($ua) => [
                'id' => $ua->achievement->id,
                'name' => $ua->achievement->name,
                'description' => $ua->achievement->description,
                'icon' => $ua->achievement->icon,
                'rarity' => $ua->achievement->rarity,
                'points' => $ua->achievement->points,
                'unlocked_at' => $ua->unlocked_at,
            ]),
        ]);
    }

    /**
     * Obtenir les achievements presque débloqués
     */
    public function almostUnlocked()
    {
        $user = Auth::user();
        $almost = $this->gamificationService->getAlmostUnlockedAchievements($user, 5);

        return response()->json([
            'achievements' => $almost->map(fn($ua) => [
                'id' => $ua->achievement->id,
                'name' => $ua->achievement->name,
                'description' => $ua->achievement->description,
                'icon' => $ua->achievement->icon,
                'progress' => $ua->progress,
                'progress_target' => $ua->progress_target,
                'progress_percentage' => $ua->progress_percentage,
            ]),
        ]);
    }

    /**
     * Obtenir les statistiques utilisateur
     */
    public function stats()
    {
        $user = Auth::user();
        $stats = UserStats::getForUser($user->id);

        return response()->json([
            'stats' => [
                'level' => $stats->level,
                'level_title' => $stats->level_title,
                'level_color' => $stats->level_color,
                'xp' => $stats->xp,
                'xp_to_next_level' => $stats->xp_to_next_level,
                'level_progress_percentage' => $stats->level_progress_percentage,
                'current_streak' => $stats->current_streak,
                'longest_streak' => $stats->longest_streak,
                'last_activity_date' => $stats->last_activity_date,
                'reputation_score' => $stats->reputation_score,
                'global_rank' => $this->gamificationService->getUserRank($user),
                'counters' => [
                    'total_votes' => $stats->total_votes,
                    'total_topics_created' => $stats->total_topics_created,
                    'total_posts_created' => $stats->total_posts_created,
                    'total_legislative_votes' => $stats->total_legislative_votes,
                    'total_budget_allocations' => $stats->total_budget_allocations,
                    'upvotes_received' => $stats->upvotes_received,
                    'downvotes_received' => $stats->downvotes_received,
                ],
                'achievements' => [
                    'total' => $stats->total_achievements,
                    'rare' => $stats->rare_achievements,
                    'epic' => $stats->epic_achievements,
                    'legendary' => $stats->legendary_achievements,
                ],
            ],
        ]);
    }

    /**
     * Obtenir le classement global
     */
    public function leaderboard(Request $request)
    {
        $limit = $request->input('limit', 100);
        $orderBy = $request->input('order_by', 'reputation_score');

        $leaderboard = $this->gamificationService->getLeaderboard($limit, $orderBy);

        return response()->json([
            'leaderboard' => $leaderboard->map(function ($stats, $index) {
                return [
                    'rank' => $index + 1,
                    'user' => [
                        'id' => $stats->user->id,
                        'name' => $stats->user->name,
                        'avatar' => $stats->user->avatar_url ?? null,
                    ],
                    'level' => $stats->level,
                    'level_title' => $stats->level_title,
                    'xp' => $stats->xp,
                    'reputation_score' => $stats->reputation_score,
                    'current_streak' => $stats->current_streak,
                    'total_achievements' => $stats->total_achievements,
                ];
            }),
        ]);
    }

    /**
     * Marquer un achievement comme notifié
     */
    public function markNotified($achievementId)
    {
        $user = Auth::user();
        
        $userAchievement = UserAchievement::where('user_id', $user->id)
            ->where('achievement_id', $achievementId)
            ->firstOrFail();

        $userAchievement->markAsNotified();

        return response()->json(['success' => true]);
    }

    /**
     * Partager un achievement
     */
    public function share($achievementId)
    {
        $user = Auth::user();
        
        $userAchievement = UserAchievement::where('user_id', $user->id)
            ->where('achievement_id', $achievementId)
            ->where('is_unlocked', true)
            ->firstOrFail();

        $userAchievement->markAsShared();

        return response()->json([
            'success' => true,
            'share_url' => route('achievement.public', [
                'user' => $user->id,
                'achievement' => $achievementId,
            ]),
        ]);
    }
}
