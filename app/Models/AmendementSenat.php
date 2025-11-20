<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle pour les amendements du Sénat (data.senat.fr)
 * 
 * @property string $uid
 * @property string|null $texte_ref
 * @property string|null $auteur_senateur_matricule
 * @property int $legislature
 * @property string $numero
 * @property string|null $dispositif
 * @property string|null $expose
 * @property string|null $sort_code
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
        'texte_ref',
        'auteur_senateur_matricule',
        'legislature',
        'numero',
        'numero_long',
        'subdiv_type',
        'subdiv_titre',
        'subdiv_mult',
        'auteur_type',
        'auteur_nom',
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
     */
    public function auteur(): BelongsTo
    {
        return $this->belongsTo(Senateur::class, 'auteur_senateur_matricule', 'matricule');
    }

    // ========================================================================
    // SCOPES
    // ========================================================================

    /**
     * Amendements adoptés
     */
    public function scopeAdoptes($query)
    {
        return $query->where('sort_code', 'ADOPTE');
    }

    /**
     * Amendements rejetés
     */
    public function scopeRejetes($query)
    {
        return $query->where('sort_code', 'REJETE');
    }

    /**
     * Amendements retirés
     */
    public function scopeRetires($query)
    {
        return $query->where('sort_code', 'RETIRE');
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
        return $this->sort_code === 'ADOPTE';
    }

    /**
     * Indique si l'amendement est rejeté
     */
    public function getEstRejeteAttribute(): bool
    {
        return $this->sort_code === 'REJETE';
    }

    /**
     * Indique si l'amendement est retiré
     */
    public function getEstRetireAttribute(): bool
    {
        return $this->sort_code === 'RETIRE';
    }
}

