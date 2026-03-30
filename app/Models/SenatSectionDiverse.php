<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Section de discussion non-législative au Sénat
 *
 * Questions au gouvernement, hommages, déclarations, etc.
 */
class SenatSectionDiverse extends Model
{
    protected $table = 'senat_sections_diverses';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'type_section',
        'date_seance',
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
        return $this->hasMany(SenatInterventionDiverse::class, 'section_id', 'id')->orderBy('ordre');
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
}
