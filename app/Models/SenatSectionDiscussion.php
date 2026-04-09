<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Section de discussion législative au Sénat
 *
 * Représente une partie du débat (article, amendement, motion, etc.)
 */
class SenatSectionDiscussion extends Model
{
    protected $table = 'senat_sections_discussion';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'lecture_id',
        'type_section',
        'date_seance',
        'numero',
        'objet',
        'url',
        'ordre',
        'parent_id',
    ];

    protected $casts = [
        'id' => 'integer',
        'date_seance' => 'datetime',
        'ordre' => 'integer',
        'parent_id' => 'integer',
    ];

    /**
     * Relations
     */
    public function debat(): BelongsTo
    {
        return $this->belongsTo(SenatDebat::class, 'date_seance', 'date_seance');
    }

    public function typeSection(): BelongsTo
    {
        return $this->belongsTo(SenatTypeSection::class, 'type_section', 'code');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id', 'id');
    }

    public function enfants(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id', 'id')->orderBy('ordre');
    }

    public function interventions(): HasMany
    {
        return $this->hasMany(SenatInterventionLegislative::class, 'section_id', 'id')->orderBy('ordre');
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

    /**
     * Scopes
     */
    public function scopeRacines($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopePourLecture($query, string $lectureId)
    {
        return $query->where('lecture_id', $lectureId);
    }
}
