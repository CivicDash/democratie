<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle pour les amendements du Sénat (data.senat.fr)
 * 
 * Basé sur la vue SQL amendements_senat qui joint :
 * - senat_ameli_amd (amendements)
 * - senat_ameli_amdsen (auteurs)
 * - sen_ameli (liaison vers matricule sénateur)
 * 
 * @property int $id
 * @property string|null $senateur_matricule - Matricule du sénateur auteur
 * @property string $numero
 * @property string|null $type_amendement
 * @property string|null $dispositif
 * @property string|null $expose
 * @property string|null $sort_code
 * @property string|null $sort_libelle
 * @property \Carbon\Carbon|null $date_depot
 */
class AmendementSenat extends Model
{
    use HasFactory;

    protected $table = 'amendements_senat';
    protected $primaryKey = 'uid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'uid',
        'id',
        'texte_ref',
        'senateur_matricule', // Colonne de la vue SQL
        'legislature',
        'numero',
        'numero_long',
        'type_amendement',
        'subdiv_type',
        'subdiv_titre',
        'subdiv_mult',
        'auteur_type',
        'auteur_nom',
        'auteur_prenom',
        'auteur_groupe_id',
        'auteur_groupe_sigle',
        'cosignataires',
        'nombre_cosignataires',
        'dispositif',
        'expose',
        'sort_code',
        'sort_libelle',
        'date_depot',
        'date_sort',
        'url_senat',
    ];

    protected $casts = [
        'legislature' => 'integer',
        'nombre_cosignataires' => 'integer',
        'cosignataires' => 'array',
        'date_depot' => 'date',
        'date_sort' => 'date',
    ];

    // ========================================================================
    // RELATIONS
    // ========================================================================

    /**
     * Sénateur auteur
     * Note: La vue amendements_senat utilise 'senateur_matricule' (via jointure sen_ameli)
     */
    public function auteur(): BelongsTo
    {
        return $this->belongsTo(Senateur::class, 'senateur_matricule', 'matricule');
    }

    // ========================================================================
    // SCOPES
    // ========================================================================

    /**
     * Amendements adoptés
     * Supporte les codes courts (ADO) et longs (ADOPTE)
     */
    public function scopeAdoptes($query)
    {
        return $query->whereIn('sort_code', ['ADO', 'ADOPTE', 'Adopté']);
    }

    /**
     * Amendements rejetés
     * Supporte les codes courts (REJ) et longs (REJETE)
     */
    public function scopeRejetes($query)
    {
        return $query->whereIn('sort_code', ['REJ', 'REJETE', 'Rejeté']);
    }

    /**
     * Amendements retirés
     * Supporte les codes courts (RET) et longs (RETIRE)
     */
    public function scopeRetires($query)
    {
        return $query->whereIn('sort_code', ['RET', 'RETIRE', 'Retiré']);
    }

    /**
     * Amendements d'une législature spécifique
     */
    public function scopeLegislature($query, int $legislature)
    {
        return $query->where('legislature', $legislature);
    }

    // ========================================================================
    // ACCESSORS
    // ========================================================================

    /**
     * Indique si l'amendement est adopté
     */
    public function getEstAdopteAttribute(): bool
    {
        return in_array($this->sort_code, ['ADO', 'ADOPTE', 'Adopté']);
    }

    /**
     * Indique si l'amendement est rejeté
     */
    public function getEstRejeteAttribute(): bool
    {
        return in_array($this->sort_code, ['REJ', 'REJETE', 'Rejeté']);
    }

    /**
     * Indique si l'amendement est retiré
     */
    public function getEstRetireAttribute(): bool
    {
        return in_array($this->sort_code, ['RET', 'RETIRE', 'Retiré']);
    }
}

