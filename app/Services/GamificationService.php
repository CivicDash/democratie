<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\User;
use App\Models\UserAchievement;
use App\Models\UserStats;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class GamificationService
{
    /**
     * Initialiser la gamification pour un nouvel utilisateur
     */
    public function initializeUser(User $user): void
    {
        // Créer les stats utilisateur
        UserStats::getForUser($user->id);
        
        // Créer les progressions pour tous les badges actifs
        $achievements = Achievement::active()->get();
        
        foreach ($achievements as $achievement) {
            UserAchievement::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'achievement_id' => $achievement->id,
                ],
                [
                    'progress' => 0,
                    'progress_target' => $achievement->required_value,
                ]
            );
        }
    }

    /**
     * Traiter un événement et vérifier les achievements
     */
    public function processEvent(User $user, string $eventType, int $value = 1, ?array $data = null): array
    {
        $unlockedAchievements = [];
        
        // Mettre à jour les stats
        $this->updateStats($user, $eventType, $value);
        
        // Vérifier les achievements correspondants
        $achievements = Achievement::active()
            ->where('trigger_type', $eventType)
            ->get();
        
        foreach ($achievements as $achievement) {
            $userAchievement = UserAchievement::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'achievement_id' => $achievement->id,
                ],
                [
                    'progress' => 0,
                    'progress_target' => $achievement->required_value,
                ]
            );
            
            if (!$userAchievement->is_unlocked) {
                // Vérifier les conditions spéciales
                if ($this->checkConditions($achievement, $user, $data)) {
                    $currentValue = $this->getCurrentValue($achievement, $user);
                    
                    if ($userAchievement->updateProgress($currentValue, $data)) {
                        if ($userAchievement->is_unlocked) {
                            $unlockedAchievements[] = $achievement;
                            $this->onAchievementUnlocked($user, $achievement);
                        }
                    }
                }
            }
        }
        
        return $unlockedAchievements;
    }

    /**
     * Mettre à jour les statistiques utilisateur
     */
    private function updateStats(User $user, string $eventType, int $value): void
    {
        $stats = UserStats::getForUser($user->id);
        
        // Mettre à jour le streak
        $stats->updateStreak();
        
        // Incrémenter les compteurs selon l'événement
        $fieldMap = [
            Achievement::TRIGGER_VOTE_COUNT => 'total_votes',
            Achievement::TRIGGER_TOPIC_CREATED => 'total_topics_created',
            Achievement::TRIGGER_POST_CREATED => 'total_posts_created',
            Achievement::TRIGGER_LEGISLATIVE_VOTE => 'total_legislative_votes',
            Achievement::TRIGGER_BUDGET_ALLOCATION => 'total_budget_allocations',
            Achievement::TRIGGER_FOLLOW_COUNT => 'total_proposals_followed',
        ];
        
        if (isset($fieldMap[$eventType])) {
            $stats->incrementCounter($fieldMap[$eventType], $value);
        }
        
        // Ajouter de l'XP
        $xpAmount = $this->calculateXp($eventType, $value);
        $stats->addXp($xpAmount);
        
        // Mettre à jour la réputation
        $stats->updateReputation();
    }

    /**
     * Calculer l'XP gagné pour un événement
     */
    private function calculateXp(string $eventType, int $value): int
    {
        $xpMap = [
            Achievement::TRIGGER_VOTE_COUNT => 5,
            Achievement::TRIGGER_TOPIC_CREATED => 50,
            Achievement::TRIGGER_POST_CREATED => 10,
            Achievement::TRIGGER_LEGISLATIVE_VOTE => 15,
            Achievement::TRIGGER_BUDGET_ALLOCATION => 25,
            Achievement::TRIGGER_FOLLOW_COUNT => 10,
            Achievement::TRIGGER_UPVOTES_RECEIVED => 3,
        ];
        
        return ($xpMap[$eventType] ?? 1) * $value;
    }

    /**
     * Vérifier les conditions spéciales d'un achievement
     */
    private function checkConditions(Achievement $achievement, User $user, ?array $data): bool
    {
        if (empty($achievement->trigger_conditions)) {
            return true;
        }
        
        $conditions = $achievement->trigger_conditions;
        
        // Vérifier chaque condition
        foreach ($conditions as $key => $value) {
            switch ($key) {
                case 'min_level':
                    $stats = UserStats::getForUser($user->id);
                    if ($stats->level < $value) {
                        return false;
                    }
                    break;
                    
                case 'category':
                    if (isset($data['category']) && $data['category'] !== $value) {
                        return false;
                    }
                    break;
                    
                case 'rarity':
                    if (isset($data['rarity']) && $data['rarity'] !== $value) {
                        return false;
                    }
                    break;
            }
        }
        
        return true;
    }

    /**
     * Obtenir la valeur actuelle pour un achievement
     */
    private function getCurrentValue(Achievement $achievement, User $user): int
    {
        $stats = UserStats::getForUser($user->id);
        
        return match($achievement->trigger_type) {
            Achievement::TRIGGER_VOTE_COUNT => $stats->total_votes,
            Achievement::TRIGGER_TOPIC_CREATED => $stats->total_topics_created,
            Achievement::TRIGGER_POST_CREATED => $stats->total_posts_created,
            Achievement::TRIGGER_UPVOTES_RECEIVED => $stats->upvotes_received,
            Achievement::TRIGGER_STREAK => $stats->current_streak,
            Achievement::TRIGGER_LEGISLATIVE_VOTE => $stats->total_legislative_votes,
            Achievement::TRIGGER_BUDGET_ALLOCATION => $stats->total_budget_allocations,
            Achievement::TRIGGER_FOLLOW_COUNT => $stats->total_proposals_followed,
            Achievement::TRIGGER_LEVEL_REACHED => $stats->level,
            default => 0,
        };
    }

    /**
     * Actions après déblocage d'un achievement
     */
    private function onAchievementUnlocked(User $user, Achievement $achievement): void
    {
        // Ajouter des XP bonus
        $stats = UserStats::getForUser($user->id);
        $stats->addXp($achievement->points);
        
        // Mettre à jour les compteurs d'achievements
        $stats->total_achievements++;
        
        switch ($achievement->rarity) {
            case Achievement::RARITY_RARE:
                $stats->rare_achievements++;
                break;
            case Achievement::RARITY_EPIC:
                $stats->epic_achievements++;
                break;
            case Achievement::RARITY_LEGENDARY:
                $stats->legendary_achievements++;
                break;
        }
        
        $stats->save();
        
        // Envoyer une notification
        $this->sendAchievementNotification($user, $achievement);
        
        Log::info("Achievement unlocked", [
            'user_id' => $user->id,
            'achievement' => $achievement->code,
            'rarity' => $achievement->rarity,
        ]);
    }

    /**
     * Envoyer une notification pour un achievement débloqué
     */
    private function sendAchievementNotification(User $user, Achievement $achievement): void
    {
        Notification::create([
            'user_id' => $user->id,
            'type' => 'achievement_unlocked',
            'title' => "🏆 Badge débloqué : {$achievement->name} !",
            'message' => $achievement->description,
            'icon' => $achievement->icon,
            'link' => '/profile/achievements',
            'data' => [
                'achievement_id' => $achievement->id,
                'achievement_code' => $achievement->code,
                'rarity' => $achievement->rarity,
                'points' => $achievement->points,
            ],
            'priority' => Notification::PRIORITY_NORMAL,
        ]);
    }

    /**
     * Obtenir tous les achievements d'un utilisateur
     */
    public function getUserAchievements(User $user, bool $unlockedOnly = false): Collection
    {
        $query = UserAchievement::with('achievement')
            ->where('user_id', $user->id);
        
        if ($unlockedOnly) {
            $query->unlocked();
        }
        
        return $query->get();
    }

    /**
     * Obtenir les achievements récents d'un utilisateur
     */
    public function getRecentAchievements(User $user, int $limit = 5): Collection
    {
        return UserAchievement::with('achievement')
            ->where('user_id', $user->id)
            ->unlocked()
            ->orderBy('unlocked_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtenir les achievements presque débloqués
     */
    public function getAlmostUnlockedAchievements(User $user, int $limit = 3): Collection
    {
        return UserAchievement::with('achievement')
            ->where('user_id', $user->id)
            ->where('is_unlocked', false)
            ->where('progress', '>', 0)
            ->orderByRaw('(progress * 100 / progress_target) DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtenir le classement global
     */
    public function getLeaderboard(int $limit = 100, string $orderBy = 'reputation_score'): Collection
    {
        return UserStats::with('user')
            ->orderBy($orderBy, 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtenir le rang d'un utilisateur
     */
    public function getUserRank(User $user, string $metric = 'reputation_score'): int
    {
        $stats = UserStats::getForUser($user->id);
        $userValue = $stats->$metric;
        
        return UserStats::where($metric, '>', $userValue)->count() + 1;
    }

    /**
     * Recalculer tous les rangs
     */
    public function recalculateRanks(): void
    {
        $users = UserStats::orderBy('reputation_score', 'desc')->get();
        
        foreach ($users as $index => $stats) {
            $stats->global_rank = $index + 1;
            $stats->save();
        }
    }
}

