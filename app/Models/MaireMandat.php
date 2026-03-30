<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Historique des mandats de maires
 */
class MaireMandat extends Model
{
    protected $table = 'maires_mandats';

    protected $fillable = [
        'ville_id',
        'maire_id',
        'nom',
        'prenom',
        'sexe',
        'date_debut',
        'date_fin',
        'type_mandat',
        'cause_fin',
        'annee_election',
        'nuance_politique',
        'parti',
        'score_election_pct',
        'tour_election',
        'mandature',
        'est_actuel',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'annee_election' => 'integer',
        'score_election_pct' => 'decimal:2',
        'tour_election' => 'integer',
        'est_actuel' => 'boolean',
    ];

    // ========================================================================
    // RELATIONS
    // ========================================================================

    public function ville(): BelongsTo
    {
        return $this->belongsTo(Ville::class);
    }

    public function maire(): BelongsTo
    {
        return $this->belongsTo(Maire::class);
    }

    // ========================================================================
    // ACCESSEURS
    // ========================================================================

    public function getNomCompletAttribute(): string
    {
        if ($this->maire) {
            return $this->maire->nom_complet ?? trim($this->maire->prenom.' '.$this->maire->nom);
        }

        return trim($this->prenom.' '.$this->nom);
    }

    public function getDureeMandatAttribute(): ?int
    {
        if (! $this->date_debut) {
            return null;
        }

        $fin = $this->date_fin ?? now();

        return (int) $this->date_debut->diffInMonths($fin);
    }

    public function getDureeFormateAttribute(): string
    {
        $mois = $this->duree_mandat;
        if (! $mois) {
            return 'N/A';
        }

        $annees = floor($mois / 12);
        $moisRestants = $mois % 12;

        if ($annees > 0 && $moisRestants > 0) {
            return "{$annees} an".($annees > 1 ? 's' : '')." et {$moisRestants} mois";
        } elseif ($annees > 0) {
            return "{$annees} an".($annees > 1 ? 's' : '');
        }

        return "{$mois} mois";
    }

    public function getPeriodeAttribute(): string
    {
        $debut = $this->date_debut?->format('Y') ?? '?';
        $fin = $this->date_fin?->format('Y') ?? 'présent';

        return "{$debut} - {$fin}";
    }
}
