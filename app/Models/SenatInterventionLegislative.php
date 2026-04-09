<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Intervention législative d'un sénateur en séance
 */
class SenatInterventionLegislative extends Model
{
    protected $table = 'senat_interventions_legislatives';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'auteur_code',
        'section_id',
        'analyse',
        'fonction',
        'url',
        'ordre',
    ];

    protected $casts = [
        'id' => 'integer',
        'section_id' => 'integer',
        'ordre' => 'integer',
    ];

    /**
     * Relations
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(SenatSectionDiscussion::class, 'section_id', 'id');
    }

    /**
     * Récupérer le sénateur associé
     * Le code auteur correspond au matricule dans la table senateurs
     */
    public function senateur(): BelongsTo
    {
        return $this->belongsTo(Senateur::class, 'auteur_code', 'matricule');
    }

    /**
     * Accessors
     */
    public function getUrlCompletAttribute(): ?string
    {
        if (! $this->url) {
            return null;
        }

        return 'https://www.senat.fr/seances/'.ltrim($this->url, '/');
    }

    public function getResumeAttribute(): string
    {
        if (! $this->analyse) {
            return 'Intervention';
        }

        // Tronquer à 200 caractères
        if (strlen($this->analyse) > 200) {
            return mb_substr($this->analyse, 0, 200).'...';
        }

        return $this->analyse;
    }

    /**
     * Scopes
     */
    public function scopeParAuteur($query, string $auteurCode)
    {
        return $query->where('auteur_code', $auteurCode);
    }
}
