<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Log de modération pour les candidatures
 */
class CandidatureModerationLog extends Model
{
    public $timestamps = false;

    protected $table = 'candidatures_moderation_logs';

    protected $fillable = [
        'moderatable_type',
        'moderatable_id',
        'action',
        'ancien_statut',
        'nouveau_statut',
        'commentaire',
        'metadata',
        'moderator_id',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    // =========================================================================
    // BOOT
    // =========================================================================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($log) {
            $log->created_at = now();
        });
    }

    // =========================================================================
    // RELATIONS
    // =========================================================================

    /**
     * Entité modérée (Liste ou Candidat)
     */
    public function moderatable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Modérateur
     */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Action formatée
     */
    public function getActionFormateeAttribute(): string
    {
        return match($this->action) {
            'creation' => 'Création',
            'soumission' => 'Soumission pour validation',
            'demande_documents' => 'Demande de documents',
            'validation' => 'Validation',
            'rejet' => 'Rejet',
            'suspension' => 'Suspension',
            'reactivation' => 'Réactivation',
            'modification' => 'Modification',
            'commentaire' => 'Commentaire',
            default => $this->action,
        };
    }

    /**
     * Icône de l'action
     */
    public function getActionIconeAttribute(): string
    {
        return match($this->action) {
            'creation' => '➕',
            'soumission' => '📤',
            'demande_documents' => '📋',
            'validation' => '✅',
            'rejet' => '❌',
            'suspension' => '⏸️',
            'reactivation' => '▶️',
            'modification' => '✏️',
            'commentaire' => '💬',
            default => '📝',
        };
    }

    /**
     * Couleur de l'action
     */
    public function getActionCouleurAttribute(): string
    {
        return match($this->action) {
            'creation' => 'blue',
            'soumission' => 'indigo',
            'demande_documents' => 'yellow',
            'validation' => 'green',
            'rejet' => 'red',
            'suspension' => 'red',
            'reactivation' => 'green',
            'modification' => 'gray',
            'commentaire' => 'gray',
            default => 'gray',
        };
    }
}
