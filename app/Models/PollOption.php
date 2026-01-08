<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Option de réponse dans un sondage
 * 
 * @property int $id
 * @property int $topic_id
 * @property string $label
 * @property string|null $description
 * @property string|null $color
 * @property string|null $icon
 * @property int $position
 * @property int $votes_count
 */
class PollOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'topic_id',
        'label',
        'description',
        'color',
        'icon',
        'position',
        'votes_count',
    ];

    protected $casts = [
        'position' => 'integer',
        'votes_count' => 'integer',
    ];

    // Couleurs par défaut pour les graphiques
    public const DEFAULT_COLORS = [
        '#10b981', // emerald
        '#3b82f6', // blue
        '#f59e0b', // amber
        '#ef4444', // red
        '#8b5cf6', // violet
        '#ec4899', // pink
        '#06b6d4', // cyan
        '#84cc16', // lime
    ];

    /**
     * Topic parent
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * Votes pour cette option
     */
    public function votes(): HasMany
    {
        return $this->hasMany(PollVote::class);
    }

    /**
     * Vérifie si un utilisateur a voté pour cette option
     */
    public function hasUserVoted(?int $userId): bool
    {
        if (!$userId) {
            return false;
        }
        
        return $this->votes()->where('user_id', $userId)->exists();
    }

    /**
     * Pourcentage de votes (calculé dynamiquement)
     */
    public function getPercentageAttribute(): float
    {
        $totalVotes = $this->topic->totalPollVotes();
        
        if ($totalVotes === 0) {
            return 0;
        }
        
        return round(($this->votes_count / $totalVotes) * 100, 1);
    }

    /**
     * Couleur par défaut basée sur la position
     */
    public function getDisplayColorAttribute(): string
    {
        return $this->color ?? self::DEFAULT_COLORS[$this->position % count(self::DEFAULT_COLORS)];
    }

    /**
     * Incrémenter le compteur de votes (avec cache)
     */
    public function incrementVotes(): void
    {
        $this->increment('votes_count');
    }

    /**
     * Décrémenter le compteur de votes (avec cache)
     */
    public function decrementVotes(): void
    {
        $this->decrement('votes_count');
    }

    /**
     * Recalculer le compteur de votes depuis la BDD
     */
    public function recalculateVotesCount(): void
    {
        $this->update([
            'votes_count' => $this->votes()->count(),
        ]);
    }
}
