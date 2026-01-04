<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Mention d'un utilisateur dans un contenu
 * 
 * @property int $id
 * @property int $user_id - L'utilisateur mentionné
 * @property int $mentioned_by - L'auteur de la mention
 * @property string $mentionable_type
 * @property int $mentionable_id
 * @property bool $is_read
 * @property \Carbon\Carbon|null $notified_at
 */
class UserMention extends Model
{
    protected $fillable = [
        'user_id',
        'mentioned_by',
        'mentionable_type',
        'mentionable_id',
        'is_read',
        'notified_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'notified_at' => 'datetime',
    ];

    /**
     * L'utilisateur mentionné
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * L'auteur de la mention
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentioned_by');
    }

    /**
     * Le contenu où la mention apparaît
     */
    public function mentionable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Marquer comme lu
     */
    public function markAsRead(): void
    {
        $this->update(['is_read' => true]);
    }

    /**
     * Scope: mentions non lues
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope: pour un utilisateur donné
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
