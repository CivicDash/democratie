<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

class ResultatMunicipal extends Model
{
    use Searchable;

    protected $table = 'resultats_municipaux';

    protected $fillable = [
        'code_commune',
        'nom_commune',
        'code_departement',
        'tour',
        'inscrits',
        'abstentions',
        'taux_abstention',
        'votants',
        'taux_participation',
        'blancs',
        'nuls',
        'exprimes',
        'nb_sieges_a_pourvoir',
        'nb_sieges_pourvus',
        'nb_listes',
        'statut_commune',
        'ville_id',
    ];

    protected $casts = [
        'tour' => 'integer',
        'inscrits' => 'integer',
        'abstentions' => 'integer',
        'taux_abstention' => 'decimal:2',
        'votants' => 'integer',
        'taux_participation' => 'decimal:2',
        'blancs' => 'integer',
        'nuls' => 'integer',
        'exprimes' => 'integer',
        'nb_sieges_a_pourvoir' => 'integer',
        'nb_sieges_pourvus' => 'integer',
        'nb_listes' => 'integer',
    ];

    // ========================================================================
    // RELATIONS
    // ========================================================================

    public function ville(): BelongsTo
    {
        return $this->belongsTo(Ville::class);
    }

    public function listes(): HasMany
    {
        return $this->hasMany(ResultatListeMunicipale::class, 'resultat_commune_id')
            ->orderByDesc('voix');
    }

    // ========================================================================
    // SCOPES
    // ========================================================================

    public function scopeTour($query, int $tour)
    {
        return $query->where('tour', $tour);
    }

    public function scopeByDepartement($query, string $code)
    {
        return $query->where('code_departement', $code);
    }

    public function scopeElusAuT1($query)
    {
        return $query->where('statut_commune', 'elu_t1');
    }

    public function scopeSecondTour($query)
    {
        return $query->where('statut_commune', 'second_tour');
    }

    public function scopeByCommune($query, string $codeCommune)
    {
        return $query->where('code_commune', $codeCommune);
    }

    // ========================================================================
    // ACCESSEURS
    // ========================================================================

    public function getTauxParticipationFormateAttribute(): string
    {
        return number_format($this->taux_participation, 1, ',', ' ') . ' %';
    }

    public function getTauxAbstentionFormateAttribute(): string
    {
        return number_format($this->taux_abstention, 1, ',', ' ') . ' %';
    }

    public function getListeGagnante(): ?ResultatListeMunicipale
    {
        return $this->listes()->where('elu', true)->first();
    }

    public function getStatutLibelleAttribute(): string
    {
        return match ($this->statut_commune) {
            'elu_t1' => 'Élu au 1er tour',
            'second_tour' => 'Second tour',
            'elu_t2' => 'Élu au 2nd tour',
            'sans_candidat' => 'Sans candidat',
            'annule' => 'Annulé',
            default => $this->statut_commune ?? 'Inconnu',
        };
    }

    // ========================================================================
    // MEILISEARCH
    // ========================================================================

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'code_commune' => $this->code_commune,
            'nom_commune' => $this->nom_commune,
            'code_departement' => $this->code_departement,
            'tour' => $this->tour,
            'taux_participation' => $this->taux_participation,
            'statut_commune' => $this->statut_commune,
        ];
    }

    public function searchableAs(): string
    {
        return 'resultats_municipaux';
    }
}
