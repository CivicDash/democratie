<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Message de débat (avec threading)
 * 
 * @property int $id
 * @property int $topic_id
 * @property int $user_id
 * @property int|null $parent_id
 * @property string $content
 * @property bool $is_official
 * @property int $upvotes
 * @property int $downvotes
 * @property bool $is_pinned
 * @property bool $is_hidden
 * @property string|null $hidden_reason
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'topic_id',
        'user_id',
        'parent_id',
        'content',
        'is_official',
        'upvotes',
        'downvotes',
        'is_pinned',
        'is_hidden',
        'hidden_reason',
    ];

    protected $casts = [
        'is_official' => 'boolean',
        'is_pinned' => 'boolean',
        'is_hidden' => 'boolean',
        'upvotes' => 'integer',
        'downvotes' => 'integer',
    ];

    /**
     * Topic parent
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * Auteur du post
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Post parent (si réponse)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'parent_id');
    }

    /**
     * Réponses à ce post
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Post::class, 'parent_id');
    }

    /**
     * Votes sur ce post
     */
    public function votes(): HasMany
    {
        return $this->hasMany(PostVote::class);
    }

    /**
     * Documents attachés
     */
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    /**
     * Signalements
     */
    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    /**
     * Score net (upvotes - downvotes)
     */
    public function getScoreAttribute(): int
    {
        return $this->upvotes - $this->downvotes;
    }

    /**
     * Incrémente les upvotes
     */
    public function incrementUpvotes(): void
    {
        $this->increment('upvotes');
    }

    /**
     * Décrémente les upvotes
     */
    public function decrementUpvotes(): void
    {
        $this->decrement('upvotes');
    }

    /**
     * Incrémente les downvotes
     */
    public function incrementDownvotes(): void
    {
        $this->increment('downvotes');
    }

    /**
     * Décrémente les downvotes
     */
    public function decrementDownvotes(): void
    {
        $this->decrement('downvotes');
    }

    /**
     * Masque le post
     */
    public function hide(string $reason): void
    {
        $this->update([
            'is_hidden' => true,
            'hidden_reason' => $reason,
        ]);
    }

    /**
     * Rend le post visible
     */
    public function unhide(): void
    {
        $this->update([
            'is_hidden' => false,
            'hidden_reason' => null,
        ]);
    }

    /**
     * Scope: posts visibles
     */
    public function scopeVisible($query)
    {
        return $query->where('is_hidden', false);
    }

    /**
     * Scope: posts officiels
     */
    public function scopeOfficial($query)
    {
        return $query->where('is_official', true);
    }

    /**
     * Scope: posts épinglés
     */
    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    /**
     * Scope: posts racines (pas de parent)
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope: tri par score
     */
    public function scopeOrderByScore($query, string $direction = 'desc')
    {
        return $query->orderByRaw('(upvotes - downvotes) ' . $direction);
    }

    /**
     * Scope: tri par popularité (upvotes)
     */
    public function scopeOrderByPopularity($query, string $direction = 'desc')
    {
        return $query->orderBy('upvotes', $direction);
    }
}

