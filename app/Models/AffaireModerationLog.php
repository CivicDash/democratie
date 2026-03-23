<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffaireModerationLog extends Model
{
    protected $table = 'affaires_moderation_logs';

    public $timestamps = false;

    protected $fillable = [
        'affaire_id', 'action', 'ancien_statut', 'nouveau_statut',
        'commentaire', 'metadata', 'moderator_id',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function affaire(): BelongsTo
    {
        return $this->belongsTo(AffaireJudiciaire::class, 'affaire_id');
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    public function getActionFormateeAttribute(): string
    {
        return match ($this->action) {
            'detection' => 'Détection automatique',
            'prise_en_charge' => 'Prise en charge',
            'validation' => 'Validation et publication',
            'rejet' => 'Rejet',
            'demande_complement' => 'Demande de complément',
            'contestation' => 'Contestation reçue',
            'mise_a_jour' => 'Mise à jour',
            'archivage' => 'Archivage',
            'correction' => 'Correction',
            default => ucfirst(str_replace('_', ' ', $this->action)),
        };
    }

    public function getActionIconeAttribute(): string
    {
        return match ($this->action) {
            'detection' => 'magnifying-glass',
            'prise_en_charge' => 'hand-raised',
            'validation' => 'check-circle',
            'rejet' => 'x-circle',
            'demande_complement' => 'question-mark-circle',
            'contestation' => 'exclamation-triangle',
            'mise_a_jour' => 'pencil',
            'archivage' => 'archive-box',
            'correction' => 'wrench',
            default => 'document',
        };
    }

    public function getActionCouleurAttribute(): string
    {
        return match ($this->action) {
            'validation' => 'green',
            'rejet' => 'red',
            'contestation' => 'yellow',
            'archivage' => 'gray',
            'detection' => 'blue',
            'prise_en_charge' => 'indigo',
            'demande_complement' => 'orange',
            default => 'gray',
        };
    }
}
