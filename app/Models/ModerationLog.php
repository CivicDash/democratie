<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ModerationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'moderatable_type',
        'moderatable_id',
        'user_id',
        'action',
        'original_word',
        'replacement_word',
        'context',
        'ip_address',
    ];

    /**
     * Actions possibles
     */
    public const ACTIONS = [
        'word_replaced' => 'Mot remplacé',
        'content_blocked' => 'Contenu bloqué',
        'user_warned' => 'Utilisateur averti',
        'content_flagged' => 'Contenu signalé',
    ];

    /**
     * Relation polymorphique
     */
    public function moderatable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Utilisateur concerné
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: par action
     */
    public function scopeOfAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope: remplacements
     */
    public function scopeReplacements($query)
    {
        return $query->where('action', 'word_replaced');
    }

    /**
     * Scope: aujourd'hui
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope: cette semaine
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }
}
