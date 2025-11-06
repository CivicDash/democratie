<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use App\Traits\Taggable;

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
    use HasFactory, SoftDeletes, Searchable, Taggable;

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
     * Alias pour l'auteur (pour cohérence avec Topics)
     */
    public function author(): BelongsTo
    {
        return $this->user();
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

    /**
     * Scope: ajoute le score de vote calculé (upvotes - downvotes)
     * Ajoute un attribut virtuel 'vote_score' pour éviter de calculer en PHP
     */
    public function scopeWithVoteScore($query)
    {
        return $query->selectRaw('posts.*, (upvotes - downvotes) as vote_score');
    }

    // ========================================================================
    // SCOUT / MEILISEARCH
    // ========================================================================

    /**
     * Get the indexable data array for the model.
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'topic_id' => $this->topic_id,
            'topic_title' => $this->topic?->title,
            'author_name' => $this->author?->name,
            'is_official' => $this->is_official,
            'upvotes' => $this->upvotes,
            'downvotes' => $this->downvotes,
            'created_at' => $this->created_at->timestamp,
        ];
    }

    /**
     * Get the name of the index associated with the model.
     */
    public function searchableAs(): string
    {
        return 'posts_index';
    }

    /**
     * Determine if the model should be searchable.
     */
    public function shouldBeSearchable(): bool
    {
        return !$this->is_hidden && !$this->trashed();
    }
}


