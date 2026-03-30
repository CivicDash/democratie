<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Commentaire sur une idée citoyenne
 * Supporte les réponses imbriquées (threads)
 */
class CitizenIdeaComment extends Model
{
    use SoftDeletes;

    protected $table = 'citizen_idea_comments';

    protected $fillable = [
        'idea_id',
        'user_id',
        'parent_id',
        'content',
        'is_hidden',
        'hidden_reason',
    ];

    protected $casts = [
        'votes_pour' => 'integer',
        'votes_contre' => 'integer',
        'score' => 'integer',
        'is_hidden' => 'boolean',
    ];

    // =========================================================================
    // RELATIONS
    // =========================================================================

    public function idea(): BelongsTo
    {
        return $this->belongsTo(CitizenIdea::class, 'idea_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderByDesc('score');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(CitizenCommentVote::class, 'comment_id');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopeVisible($query)
    {
        return $query->where('is_hidden', false);
    }

    public function scopeRootComments($query)
    {
        return $query->whereNull('parent_id');
    }

    // =========================================================================
    // ACTIONS
    // =========================================================================

    /**
     * Masque le commentaire
     */
    public function hide(string $reason): self
    {
        $this->update([
            'is_hidden' => true,
            'hidden_reason' => $reason,
        ]);

        return $this;
    }

    /**
     * Recalcule les statistiques du commentaire
     */
    public function recalculateStats(): self
    {
        $pour = $this->votes()->where('vote', 1)->count();
        $contre = $this->votes()->where('vote', -1)->count();

        $this->update([
            'votes_pour' => $pour,
            'votes_contre' => $contre,
            'score' => $pour - $contre,
        ]);

        return $this;
    }

    // =========================================================================
    // API FORMAT
    // =========================================================================

    public function toApiArray(bool $includeReplies = true): array
    {
        $data = [
            'id' => $this->id,
            'content' => $this->is_hidden ? '[Commentaire masqué]' : $this->content,
            'is_hidden' => $this->is_hidden,
            'author' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
            'votes_pour' => $this->votes_pour,
            'votes_contre' => $this->votes_contre,
            'score' => $this->score,
            'created_at' => $this->created_at->toIso8601String(),
            'replies_count' => $this->replies()->count(),
        ];

        if ($includeReplies && $this->parent_id === null) {
            $data['replies'] = $this->replies()
                ->visible()
                ->with('user')
                ->limit(10)
                ->get()
                ->map(fn ($r) => $r->toApiArray(false));
        }

        return $data;
    }
}
