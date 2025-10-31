<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Achievement extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'icon',
        'color',
        'image_url',
        'category',
        'rarity',
        'points',
        'trigger_type',
        'trigger_conditions',
        'required_value',
        'order',
        'is_secret',
        'is_active',
        'unlock_count',
    ];

    protected $casts = [
        'trigger_conditions' => 'array',
        'is_secret' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Catégories
    public const CATEGORY_PARTICIPATION = 'participation';
    public const CATEGORY_LEGISLATIVE = 'legislative';
    public const CATEGORY_BUDGET = 'budget';
    public const CATEGORY_SOCIAL = 'social';
    public const CATEGORY_ENGAGEMENT = 'engagement';
    public const CATEGORY_EXPERTISE = 'expertise';

    // Rareté
    public const RARITY_COMMON = 'common';
    public const RARITY_RARE = 'rare';
    public const RARITY_EPIC = 'epic';
    public const RARITY_LEGENDARY = 'legendary';

    // Types de déclencheurs
    public const TRIGGER_VOTE_COUNT = 'vote_count';
    public const TRIGGER_TOPIC_CREATED = 'topic_created';
    public const TRIGGER_POST_CREATED = 'post_created';
    public const TRIGGER_UPVOTES_RECEIVED = 'upvotes_received';
    public const TRIGGER_STREAK = 'streak';
    public const TRIGGER_LEGISLATIVE_VOTE = 'legislative_vote';
    public const TRIGGER_BUDGET_ALLOCATION = 'budget_allocation';
    public const TRIGGER_FOLLOW_COUNT = 'follow_count';
    public const TRIGGER_LEVEL_REACHED = 'level_reached';

    /**
     * Utilisateurs ayant débloqué ce badge
     */
    public function users(): HasMany
    {
        return $this->hasMany(UserAchievement::class);
    }

    /**
     * Utilisateurs ayant débloqué (relation directe)
     */
    public function unlockedBy()
    {
        return $this->belongsToMany(User::class, 'user_achievements')
            ->wherePivot('is_unlocked', true)
            ->withPivot(['unlocked_at', 'progress']);
    }

    /**
     * Scope : badges actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope : par catégorie
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope : par rareté
     */
    public function scopeRarity($query, string $rarity)
    {
        return $query->where('rarity', $rarity);
    }

    /**
     * Scope : badges non secrets
     */
    public function scopeVisible($query)
    {
        return $query->where('is_secret', false);
    }

    /**
     * Obtenir la couleur CSS selon la rareté
     */
    public function getRarityColorAttribute(): string
    {
        return match($this->rarity) {
            self::RARITY_COMMON => 'gray',
            self::RARITY_RARE => 'blue',
            self::RARITY_EPIC => 'purple',
            self::RARITY_LEGENDARY => 'yellow',
            default => 'gray',
        };
    }

    /**
     * Obtenir le gradient CSS selon la rareté
     */
    public function getRarityGradientAttribute(): string
    {
        return match($this->rarity) {
            self::RARITY_COMMON => 'from-gray-400 to-gray-600',
            self::RARITY_RARE => 'from-blue-400 to-blue-600',
            self::RARITY_EPIC => 'from-purple-400 to-purple-600',
            self::RARITY_LEGENDARY => 'from-yellow-400 via-orange-500 to-red-600',
            default => 'from-gray-400 to-gray-600',
        };
    }

    /**
     * Incrémenter le compteur de déblocages
     */
    public function incrementUnlockCount(): void
    {
        $this->increment('unlock_count');
    }

    /**
     * Vérifier si un utilisateur peut débloquer ce badge
     */
    public function canUnlock(User $user, $currentValue): bool
    {
        return $currentValue >= $this->required_value;
    }

    /**
     * Obtenir le pourcentage de complétion pour un utilisateur
     */
    public function getProgressPercentage(User $user, $currentValue): int
    {
        if ($this->required_value == 0) {
            return 100;
        }
        
        return min(100, (int)(($currentValue / $this->required_value) * 100));
    }
}
