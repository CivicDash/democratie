<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TagSuggestion extends Model
{
    protected $fillable = [
        'nom',
        'justification',
        'suggested_by',
        'taggable_type',
        'taggable_id',
        'status',
        'reviewed_by',
        'review_comment',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    // ==========================================
    // RELATIONS
    // ==========================================

    public function suggestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suggested_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function taggable(): MorphTo
    {
        return $this->morphTo();
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    // ==========================================
    // MÉTHODES
    // ==========================================

    public function approve(User $reviewer, ?string $comment = null): Tag
    {
        // Créer le tag
        $tag = Tag::firstOrCreate(
            ['slug' => \Str::slug($this->nom)],
            [
                'nom' => $this->nom,
                'type' => 'keyword',
                'source' => 'user',
                'validated' => true,
                'validated_by' => $reviewer->id,
                'validated_at' => now(),
            ]
        );

        // Mettre à jour la suggestion
        $this->update([
            'status' => 'approved',
            'reviewed_by' => $reviewer->id,
            'review_comment' => $comment,
            'reviewed_at' => now(),
        ]);

        // Associer le tag à l'entité
        $this->attachTagToTaggable($tag);

        return $tag;
    }

    public function reject(User $reviewer, ?string $comment = null): void
    {
        $this->update([
            'status' => 'rejected',
            'reviewed_by' => $reviewer->id,
            'review_comment' => $comment,
            'reviewed_at' => now(),
        ]);
    }

    protected function attachTagToTaggable(Tag $tag): void
    {
        $taggable = $this->taggable;

        if (! $taggable) {
            return;
        }

        // Selon le type, utiliser la bonne relation
        if ($taggable instanceof Loi) {
            $tag->lois()->syncWithoutDetaching([
                $taggable->loicod => [
                    'source' => 'user',
                    'validated' => true,
                    'suggested_by' => $this->suggested_by,
                ],
            ]);
        } elseif ($taggable instanceof TexteJO) {
            $tag->textesJo()->syncWithoutDetaching([
                $taggable->id => [
                    'source' => 'user',
                    'validated' => true,
                    'suggested_by' => $this->suggested_by,
                ],
            ]);
        } elseif ($taggable instanceof Topic) {
            $tag->topics()->syncWithoutDetaching([
                $taggable->id => [
                    'source' => 'user',
                ],
            ]);
        }

        $tag->incrementUsage();
    }
}
