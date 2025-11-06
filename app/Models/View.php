<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Vue d'un contenu (Topic, PropositionLoi, Post, etc.)
 * 
 * @property int $id
 * @property int $user_id
 * @property int $viewable_id
 * @property string $viewable_type
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class View extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'viewable_id',
        'viewable_type',
        'ip_address',
        'user_agent',
    ];

    /**
     * Utilisateur qui a vu le contenu
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Contenu visualisé (polymorphic)
     */
    public function viewable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope: vues uniques par utilisateur
     */
    public function scopeUniqueByUser($query, int $userId, string $viewableType, int $viewableId)
    {
        return $query->where('user_id', $userId)
            ->where('viewable_type', $viewableType)
            ->where('viewable_id', $viewableId);
    }
}

