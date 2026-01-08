<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Intervention non-législative d'un sénateur en séance
 * 
 * Questions au gouvernement, déclarations, hommages, etc.
 */
class SenatInterventionDiverse extends Model
{
    protected $table = 'senat_interventions_diverses';
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
        return $this->belongsTo(SenatSectionDiverse::class, 'section_id', 'id');
    }

    public function senateur(): BelongsTo
    {
        return $this->belongsTo(Senateur::class, 'auteur_code', 'matricule');
    }

    /**
     * Accessors
     */
    public function getUrlCompletAttribute(): ?string
    {
        if (!$this->url) {
            return null;
        }
        return 'https://www.senat.fr' . $this->url;
    }

    public function getResumeAttribute(): string
    {
        if (!$this->analyse) {
            return 'Intervention';
        }
        
        if (strlen($this->analyse) > 200) {
            return mb_substr($this->analyse, 0, 200) . '...';
        }
        
        return $this->analyse;
    }
}
