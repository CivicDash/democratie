<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultatListeMunicipale extends Model
{
    protected $table = 'resultats_listes_municipales';

    protected $fillable = [
        'resultat_commune_id',
        'liste_id',
        'numero_panneau',
        'nom_liste',
        'nuance_politique',
        'tete_de_liste_nom',
        'tete_de_liste_prenom',
        'voix',
        'pourcentage_exprimes',
        'pourcentage_inscrits',
        'elu',
        'sieges_obtenus',
        'sieges_conseil_communautaire',
    ];

    protected $casts = [
        'voix' => 'integer',
        'pourcentage_exprimes' => 'decimal:2',
        'pourcentage_inscrits' => 'decimal:2',
        'elu' => 'boolean',
        'sieges_obtenus' => 'integer',
        'sieges_conseil_communautaire' => 'integer',
    ];

    // ========================================================================
    // RELATIONS
    // ========================================================================

    public function resultatCommune(): BelongsTo
    {
        return $this->belongsTo(ResultatMunicipal::class, 'resultat_commune_id');
    }

    public function liste(): BelongsTo
    {
        return $this->belongsTo(ListeElectorale::class, 'liste_id');
    }

    // ========================================================================
    // SCOPES
    // ========================================================================

    public function scopeElues($query)
    {
        return $query->where('elu', true);
    }

    public function scopeByNuance($query, string $nuance)
    {
        return $query->where('nuance_politique', $nuance);
    }

    // ========================================================================
    // ACCESSEURS
    // ========================================================================

    public function getTeteDeListeNomCompletAttribute(): string
    {
        return trim(($this->tete_de_liste_prenom ?? '').' '.($this->tete_de_liste_nom ?? ''));
    }

    public function getPourcentageFormateAttribute(): string
    {
        return number_format($this->pourcentage_exprimes, 2, ',', ' ').' %';
    }

    public function getVoixFormateAttribute(): string
    {
        return number_format($this->voix, 0, ',', ' ');
    }
}
