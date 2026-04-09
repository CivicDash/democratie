<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommuneEvenementInscription extends Model
{
    protected $fillable = [
        'evenement_id',
        'user_id',
        'nb_personnes',
        'commentaire',
        'statut',
    ];

    protected $casts = [
        'nb_personnes' => 'integer',
    ];

    // ========================================================================
    // RELATIONS
    // ========================================================================

    public function evenement(): BelongsTo
    {
        return $this->belongsTo(CommuneEvenement::class, 'evenement_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ========================================================================
    // ACCESSEURS
    // ========================================================================

    public function getStatutLabelAttribute(): string
    {
        return match ($this->statut) {
            'inscrit' => 'Inscrit',
            'liste_attente' => 'Liste d\'attente',
            'annule' => 'Annulé',
            default => ucfirst($this->statut),
        };
    }

    // ========================================================================
    // MÉTHODES
    // ========================================================================

    public function annuler(): void
    {
        if ($this->statut === 'inscrit') {
            $this->evenement->decrement('inscrits_count', $this->nb_personnes);
        }

        $this->update(['statut' => 'annule']);
    }
}
