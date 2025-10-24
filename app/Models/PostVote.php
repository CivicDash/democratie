<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vote up/down sur un post
 * 
 * @property int $id
 * @property int $post_id
 * @property int $user_id
 * @property string $vote up|down
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class PostVote extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'user_id',
        'vote',
    ];

    /**
     * Post voté
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Utilisateur qui a voté
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Vérifie si c'est un upvote
     */
    public function isUpvote(): bool
    {
        return $this->vote === 'up';
    }

    /**
     * Vérifie si c'est un downvote
     */
    public function isDownvote(): bool
    {
        return $this->vote === 'down';
    }

    /**
     * Scope: upvotes uniquement
     */
    public function scopeUpvotes($query)
    {
        return $query->where('vote', 'up');
    }

    /**
     * Scope: downvotes uniquement
     */
    public function scopeDownvotes($query)
    {
        return $query->where('vote', 'down');
    }

    /**
     * Scope: votes d'un user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}

