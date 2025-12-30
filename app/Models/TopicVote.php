<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vote citoyen sur un Topic (idée/proposition)
 */
class TopicVote extends Model
{
    use HasFactory;

    protected $table = 'topic_votes';

    protected $fillable = [
        'user_id',
        'topic_id',
        'vote',
    ];

    protected $casts = [
        'vote' => 'integer',
    ];

    // =========================================================================
    // RELATIONS
    // =========================================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopePour($query)
    {
        return $query->where('vote', 1);
    }

    public function scopeContre($query)
    {
        return $query->where('vote', -1);
    }

    // =========================================================================
    // HELPERS STATIQUES
    // =========================================================================

    /**
     * Enregistrer ou mettre à jour un vote
     */
    public static function castVote(int $userId, int $topicId, int $vote): self
    {
        $topicVote = self::updateOrCreate(
            [
                'user_id' => $userId,
                'topic_id' => $topicId,
            ],
            [
                'vote' => $vote,
            ]
        );

        // Recalculer les stats du topic
        static::recalculateTopicStats($topicId);

        return $topicVote;
    }

    /**
     * Supprimer un vote
     */
    public static function removeVote(int $userId, int $topicId): bool
    {
        $deleted = self::where('user_id', $userId)
            ->where('topic_id', $topicId)
            ->delete();

        if ($deleted) {
            static::recalculateTopicStats($topicId);
        }

        return $deleted > 0;
    }

    /**
     * Récupérer le vote d'un utilisateur
     */
    public static function getUserVote(int $userId, int $topicId): ?int
    {
        $vote = self::where('user_id', $userId)
            ->where('topic_id', $topicId)
            ->first();

        return $vote?->vote;
    }

    /**
     * Recalculer les statistiques d'un topic
     */
    public static function recalculateTopicStats(int $topicId): void
    {
        $pour = self::where('topic_id', $topicId)->pour()->count();
        $contre = self::where('topic_id', $topicId)->contre()->count();
        $total = $pour + $contre;

        // Wilson score pour le classement (confiance statistique)
        $score = static::calculateWilsonScore($pour, $total);

        Topic::where('id', $topicId)->update([
            'votes_pour' => $pour,
            'votes_contre' => $contre,
            'score' => (int) ($score * 10000), // Score entier pour tri
        ]);
    }

    /**
     * Calculer le Wilson score (lower bound)
     * https://www.evanmiller.org/how-not-to-sort-by-average-rating.html
     */
    protected static function calculateWilsonScore(int $positive, int $total): float
    {
        if ($total === 0) {
            return 0;
        }

        $z = 1.96; // 95% confidence
        $p = $positive / $total;
        
        $left = $p + ($z * $z) / (2 * $total);
        $right = $z * sqrt(($p * (1 - $p) + ($z * $z) / (4 * $total)) / $total);
        $under = 1 + ($z * $z) / $total;

        return ($left - $right) / $under;
    }
}
