<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vote sur un commentaire
 */
class CitizenCommentVote extends Model
{
    protected $table = 'citizen_comment_votes';

    protected $fillable = [
        'user_id',
        'comment_id',
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

    public function comment(): BelongsTo
    {
        return $this->belongsTo(CitizenIdeaComment::class, 'comment_id');
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Enregistre ou met à jour un vote
     */
    public static function castVote(int $userId, int $commentId, int $vote): self
    {
        $voteRecord = self::updateOrCreate(
            [
                'user_id' => $userId,
                'comment_id' => $commentId,
            ],
            [
                'vote' => $vote,
            ]
        );

        // Recalculer les stats du commentaire
        $comment = CitizenIdeaComment::find($commentId);
        if ($comment) {
            $comment->recalculateStats();
        }

        return $voteRecord;
    }
}
