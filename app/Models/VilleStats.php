<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Statistiques pré-calculées des villes
 */
class VilleStats extends Model
{
    protected $table = 'villes_stats';

    protected $fillable = [
        'ville_id',
        'annee',
        'population',
        'densite',
        'evolution_population_5ans_pct',
        'age_moyen',
        'budget_fonctionnement',
        'budget_investissement',
        'dette_totale',
        'dette_par_habitant',
        'taux_endettement_pct',
        'capacite_autofinancement',
        'taxe_habitation',
        'taxe_fonciere',
        'nb_maires_historique',
        'duree_moyenne_mandat_mois',
        'score_dynamisme',
        'score_sante_financiere',
        'source',
        'calculated_at',
    ];

    protected $casts = [
        'annee' => 'integer',
        'population' => 'integer',
        'densite' => 'decimal:2',
        'evolution_population_5ans_pct' => 'decimal:2',
        'age_moyen' => 'decimal:1',
        'budget_fonctionnement' => 'decimal:2',
        'budget_investissement' => 'decimal:2',
        'dette_totale' => 'decimal:2',
        'dette_par_habitant' => 'decimal:2',
        'taux_endettement_pct' => 'decimal:2',
        'capacite_autofinancement' => 'decimal:2',
        'taxe_habitation' => 'decimal:2',
        'taxe_fonciere' => 'decimal:2',
        'nb_maires_historique' => 'integer',
        'duree_moyenne_mandat_mois' => 'integer',
        'score_dynamisme' => 'integer',
        'score_sante_financiere' => 'integer',
        'calculated_at' => 'datetime',
    ];

    public function ville(): BelongsTo
    {
        return $this->belongsTo(Ville::class);
    }

    // ========================================================================
    // ACCESSEURS
    // ========================================================================

    public function getDetteFormateAttribute(): string
    {
        return CommuneBudget::formatMontant($this->dette_totale);
    }

    public function getDetteParHabitantFormateAttribute(): string
    {
        if (!$this->dette_par_habitant) return 'N/A';
        return number_format($this->dette_par_habitant, 0, ',', ' ') . ' €/hab.';
    }

    public function getTauxEndettementFormateAttribute(): string
    {
        if ($this->taux_endettement_pct === null) return 'N/A';
        return number_format($this->taux_endettement_pct, 1, ',', ' ') . '%';
    }

    public function getScoreSanteFinanciereLibelleAttribute(): string
    {
        $score = $this->score_sante_financiere;
        if ($score === null) return 'Non évalué';
        if ($score >= 80) return 'Excellente';
        if ($score >= 60) return 'Bonne';
        if ($score >= 40) return 'Correcte';
        if ($score >= 20) return 'Fragile';
        return 'Critique';
    }

    public function getScoreSanteFinanciereColorAttribute(): string
    {
        $score = $this->score_sante_financiere;
        if ($score === null) return 'gray';
        if ($score >= 80) return 'emerald';
        if ($score >= 60) return 'green';
        if ($score >= 40) return 'yellow';
        if ($score >= 20) return 'orange';
        return 'red';
    }
}
