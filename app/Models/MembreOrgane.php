<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Membre d'un organe parlementaire
 * 
 * @property int $id
 * @property int $organe_id
 * @property int $depute_senateur_id
 * @property string|null $fonction
 * @property int|null $ordre
 * @property \Carbon\Carbon $date_debut
 * @property \Carbon\Carbon|null $date_fin
 * @property bool $actif
 * @property string|null $groupe_a_fin_fonction
 */
class MembreOrgane extends Model
{
    use HasFactory;

    protected $fillable = [
        'organe_id',
        'depute_senateur_id',
        'fonction',
        'ordre',
        'date_debut',
        'date_fin',
        'actif',
        'groupe_a_fin_fonction',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'actif' => 'boolean',
        'ordre' => 'integer',
    ];

    // ========================================================================
    // RELATIONS
    // ========================================================================

    /**
     * Organe parlementaire
     */
    public function organe(): BelongsTo
    {
        return $this->belongsTo(OrganeParlementaire::class, 'organe_id');
    }

    /**
     * Député ou sénateur membre
     */
    public function deputeSenateur(): BelongsTo
    {
        return $this->belongsTo(DeputeSenateur::class, 'depute_senateur_id');
    }

    // ========================================================================
    // SCOPES
    // ========================================================================

    /**
     * Membres actuellement actifs
     */
    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    /**
     * Présidents uniquement
     */
    public function scopePresidents($query)
    {
        return $query->where('fonction', 'like', '%president%');
    }

    /**
     * Rapporteurs uniquement
     */
    public function scopeRapporteurs($query)
    {
        return $query->where('fonction', 'like', '%rapporteur%');
    }

    // ========================================================================
    // ACCESSEURS
    // ========================================================================

    /**
     * Label de la fonction
     */
    public function getFonctionLabelAttribute(): string
    {
        if (!$this->fonction) {
            return 'Membre';
        }

        return ucfirst($this->fonction);
    }

    /**
     * Durée d'appartenance (en jours)
     */
    public function getDureeJoursAttribute(): int
    {
        $fin = $this->date_fin ?? now();
        return $this->date_debut->diffInDays($fin);
    }

    /**
     * Indique si le membre est président
     */
    public function getIsPresidentAttribute(): bool
    {
        return $this->fonction && str_contains(strtolower($this->fonction), 'president');
    }

    /**
     * Indique si le membre est rapporteur
     */
    public function getIsRapporteurAttribute(): bool
    {
        return $this->fonction && str_contains(strtolower($this->fonction), 'rapporteur');
    }
}

