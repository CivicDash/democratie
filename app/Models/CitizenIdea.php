<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Idée/Proposition citoyenne
 *
 * Permet aux citoyens de soumettre des idées et propositions
 * à différents niveaux géographiques (national, régional, local)
 */
class CitizenIdea extends Model
{
    use SoftDeletes;

    protected $table = 'citizen_ideas';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'slug',
        'scope',
        'region_code',
        'departement_code',
        'commune_code',
        'loi_cod',
        'legislature',
        'tag_id',
        'status',
        'rejection_reason',
        'moderated_at',
        'moderated_by',
        'published_at',
    ];

    protected $casts = [
        'votes_pour' => 'integer',
        'votes_contre' => 'integer',
        'score' => 'integer',
        'comments_count' => 'integer',
        'views_count' => 'integer',
        'moderated_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    // =========================================================================
    // BOOT
    // =========================================================================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($idea) {
            if (empty($idea->slug)) {
                $idea->slug = Str::slug($idea->title).'-'.Str::random(6);
            }
        });
    }

    // =========================================================================
    // RELATIONS
    // =========================================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }

    public function loi(): BelongsTo
    {
        return $this->belongsTo(Loi::class, 'loi_cod', 'loicod');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(CitizenIdeaVote::class, 'idea_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(CitizenIdeaComment::class, 'idea_id');
    }

    public function rootComments(): HasMany
    {
        return $this->hasMany(CitizenIdeaComment::class, 'idea_id')
            ->whereNull('parent_id')
            ->orderByDesc('score');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeNational($query)
    {
        return $query->where('scope', 'national');
    }

    public function scopeForScope($query, string $scope, ?string $code = null)
    {
        $query->where('scope', $scope);

        if ($code) {
            match ($scope) {
                'regional' => $query->where('region_code', $code),
                'departemental' => $query->where('departement_code', $code),
                'communal' => $query->where('commune_code', $code),
                default => null,
            };
        }

        return $query;
    }

    public function scopePopular($query)
    {
        return $query->orderByDesc('score');
    }

    public function scopeRecent($query)
    {
        return $query->orderByDesc('published_at');
    }

    public function scopeMostDebated($query)
    {
        return $query->orderByDesc('comments_count');
    }

    // =========================================================================
    // ACTIONS
    // =========================================================================

    /**
     * Publie l'idée (après modération)
     */
    public function publish(?int $moderatorId = null): self
    {
        $this->update([
            'status' => 'published',
            'moderated_at' => now(),
            'moderated_by' => $moderatorId,
            'published_at' => now(),
        ]);

        return $this;
    }

    /**
     * Rejette l'idée
     */
    public function reject(string $reason, ?int $moderatorId = null): self
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'moderated_at' => now(),
            'moderated_by' => $moderatorId,
        ]);

        return $this;
    }

    /**
     * Recalcule les statistiques
     */
    public function recalculateStats(): self
    {
        $pour = $this->votes()->where('vote', 1)->count();
        $contre = $this->votes()->where('vote', -1)->count();
        $comments = $this->comments()->count();

        $this->update([
            'votes_pour' => $pour,
            'votes_contre' => $contre,
            'score' => $pour - $contre,
            'comments_count' => $comments,
        ]);

        return $this;
    }

    /**
     * Incrémente le compteur de vues
     */
    public function incrementViews(): self
    {
        $this->increment('views_count');

        return $this;
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    public function getScopeLabelAttribute(): string
    {
        return match ($this->scope) {
            'national' => '🇫🇷 National',
            'regional' => '🗺️ Régional',
            'departemental' => '📍 Départemental',
            'communal' => '🏠 Communal',
            default => $this->scope,
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Brouillon',
            'pending' => 'En attente',
            'published' => 'Publiée',
            'rejected' => 'Rejetée',
            'archived' => 'Archivée',
            default => $this->status,
        };
    }

    public function getPctPourAttribute(): float
    {
        $total = $this->votes_pour + $this->votes_contre;

        return $total > 0 ? round(($this->votes_pour / $total) * 100, 1) : 0;
    }

    public function getPctContreAttribute(): float
    {
        $total = $this->votes_pour + $this->votes_contre;

        return $total > 0 ? round(($this->votes_contre / $total) * 100, 1) : 0;
    }

    public function getIsPublishedAttribute(): bool
    {
        return $this->status === 'published';
    }

    // =========================================================================
    // API FORMAT
    // =========================================================================

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'description' => $this->description,
            'scope' => $this->scope,
            'scope_label' => $this->scope_label,
            'region_code' => $this->region_code,
            'departement_code' => $this->departement_code,
            'commune_code' => $this->commune_code,
            'loi_cod' => $this->loi_cod,
            'tag' => $this->tag ? [
                'id' => $this->tag->id,
                'nom' => $this->tag->nom,
                'couleur' => $this->tag->couleur,
                'icone' => $this->tag->icone,
            ] : null,
            'author' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
            'votes_pour' => $this->votes_pour,
            'votes_contre' => $this->votes_contre,
            'score' => $this->score,
            'pct_pour' => $this->pct_pour,
            'comments_count' => $this->comments_count,
            'views_count' => $this->views_count,
            'status' => $this->status,
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
