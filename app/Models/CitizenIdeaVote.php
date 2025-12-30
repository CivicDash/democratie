<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vote sur une idée citoyenne
 */
class CitizenIdeaVote extends Model
{
    protected $table = 'citizen_idea_votes';

    protected $fillable = [
        'user_id',
        'idea_id',
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

    public function idea(): BelongsTo
    {
        return $this->belongsTo(CitizenIdea::class, 'idea_id');
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Enregistre ou met à jour un vote et recalcule les stats
     */
    public static function castVote(int $userId, int $ideaId, int $vote): self
    {
        $voteRecord = self::updateOrCreate(
            [
                'user_id' => $userId,
                'idea_id' => $ideaId,
            ],
            [
                'vote' => $vote,
            ]
        );

        // Recalculer les stats de l'idée
        $idea = CitizenIdea::find($ideaId);
        if ($idea) {
            $idea->recalculateStats();
        }

        return $voteRecord;
    }

    /**
     * Supprime un vote
     */
    public static function removeVote(int $userId, int $ideaId): bool
    {
        $deleted = self::where('user_id', $userId)
            ->where('idea_id', $ideaId)
            ->delete();

        if ($deleted) {
            $idea = CitizenIdea::find($ideaId);
            if ($idea) {
                $idea->recalculateStats();
            }
        }

        return $deleted > 0;
    }

    /**
     * Récupère le vote d'un utilisateur sur une idée
     */
    public static function getUserVote(int $userId, int $ideaId): ?int
    {
        return self::where('user_id', $userId)
            ->where('idea_id', $ideaId)
            ->value('vote');
    }
}
