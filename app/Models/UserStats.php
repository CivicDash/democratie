<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserStats extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'level',
        'xp',
        'xp_to_next_level',
        'current_streak',
        'longest_streak',
        'last_activity_date',
        'total_votes',
        'total_topics_created',
        'total_posts_created',
        'total_comments',
        'total_proposals_followed',
        'total_legislative_votes',
        'total_budget_allocations',
        'upvotes_received',
        'downvotes_received',
        'reputation_score',
        'total_achievements',
        'rare_achievements',
        'epic_achievements',
        'legendary_achievements',
        'global_rank',
        'category_rank',
    ];

    protected $casts = [
        'last_activity_date' => 'date',
    ];

    /**
     * L'utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtenir ou créer les stats pour un utilisateur
     */
    public static function getForUser(int $userId): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            [
                'level' => 1,
                'xp' => 0,
                'xp_to_next_level' => 100,
                'current_streak' => 0,
                'longest_streak' => 0,
            ]
        );
    }

    /**
     * Ajouter de l'XP
     */
    public function addXp(int $amount): bool
    {
        $this->xp += $amount;
        
        // Vérifier si on level up
        while ($this->xp >= $this->xp_to_next_level) {
            $this->levelUp();
        }
        
        return $this->save();
    }

    /**
     * Level up !
     */
    private function levelUp(): void
    {
        $this->level++;
        $this->xp -= $this->xp_to_next_level;
        
        // Formule exponentielle pour XP requis
        // Level 1: 100 XP, Level 2: 150 XP, Level 3: 225 XP, etc.
        $this->xp_to_next_level = (int)($this->xp_to_next_level * 1.5);
    }

    /**
     * Mettre à jour le streak
     */
    public function updateStreak(): bool
    {
        $today = now()->startOfDay();
        $lastActivity = $this->last_activity_date?->startOfDay();
        
        if (!$lastActivity) {
            // Première activité
            $this->current_streak = 1;
            $this->longest_streak = 1;
            $this->last_activity_date = $today;
        } elseif ($lastActivity->isSameDay($today)) {
            // Déjà actif aujourd'hui, ne rien faire
            return false;
        } elseif ($lastActivity->copy()->addDay()->isSameDay($today)) {
            // Jour consécutif !
            $this->current_streak++;
            $this->last_activity_date = $today;
            
            if ($this->current_streak > $this->longest_streak) {
                $this->longest_streak = $this->current_streak;
            }
        } else {
            // Streak cassé :(
            $this->current_streak = 1;
            $this->last_activity_date = $today;
        }
        
        return $this->save();
    }

    /**
     * Incrémenter un compteur
     */
    public function incrementCounter(string $field, int $amount = 1): bool
    {
        if (!in_array($field, $this->fillable)) {
            return false;
        }
        
        $this->$field += $amount;
        return $this->save();
    }

    /**
     * Calculer le score de réputation
     */
    public function calculateReputation(): int
    {
        $reputation = 0;
        
        // Points de base par niveau
        $reputation += $this->level * 10;
        
        // Upvotes reçus
        $reputation += $this->upvotes_received * 2;
        
        // Downvotes retirent des points
        $reputation -= $this->downvotes_received;
        
        // Bonus pour streak
        if ($this->current_streak >= 7) {
            $reputation += 50;
        }
        if ($this->current_streak >= 30) {
            $reputation += 100;
        }
        
        // Bonus pour achievements
        $reputation += $this->total_achievements * 5;
        $reputation += $this->rare_achievements * 10;
        $reputation += $this->epic_achievements * 25;
        $reputation += $this->legendary_achievements * 100;
        
        return max(0, $reputation);
    }

    /**
     * Mettre à jour le score de réputation
     */
    public function updateReputation(): bool
    {
        $this->reputation_score = $this->calculateReputation();
        return $this->save();
    }

    /**
     * Obtenir le pourcentage de progression vers le prochain niveau
     */
    public function getLevelProgressPercentageAttribute(): int
    {
        if ($this->xp_to_next_level == 0) {
            return 100;
        }
        
        return min(100, (int)(($this->xp / $this->xp_to_next_level) * 100));
    }

    /**
     * Obtenir le titre de niveau
     */
    public function getLevelTitleAttribute(): string
    {
        return match(true) {
            $this->level >= 50 => 'Légende Démocratique',
            $this->level >= 40 => 'Visionnaire Citoyen',
            $this->level >= 30 => 'Leader d\'Opinion',
            $this->level >= 20 => 'Expert Engagé',
            $this->level >= 10 => 'Citoyen Actif',
            $this->level >= 5 => 'Participant Régulier',
            default => 'Nouveau Citoyen',
        };
    }

    /**
     * Obtenir la couleur du niveau
     */
    public function getLevelColorAttribute(): string
    {
        return match(true) {
            $this->level >= 50 => 'text-yellow-500',
            $this->level >= 40 => 'text-purple-500',
            $this->level >= 30 => 'text-red-500',
            $this->level >= 20 => 'text-blue-500',
            $this->level >= 10 => 'text-green-500',
            default => 'text-gray-500',
        };
    }
}
