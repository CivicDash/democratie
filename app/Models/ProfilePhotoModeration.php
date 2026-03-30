<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfilePhotoModeration extends Model
{
    protected $fillable = [
        'user_id',
        'moderator_id',
        'photo_path',
        'action',
        'reason',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Utilisateur concerné
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Modérateur qui a effectué l'action
     */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    /**
     * Libellé de l'action
     */
    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'submitted' => 'Photo soumise',
            'approved' => 'Photo approuvée',
            'rejected' => 'Photo refusée',
            default => $this->action,
        };
    }

    /**
     * Couleur associée à l'action
     */
    public function getActionColorAttribute(): string
    {
        return match ($this->action) {
            'submitted' => 'yellow',
            'approved' => 'green',
            'rejected' => 'red',
            default => 'gray',
        };
    }
}
