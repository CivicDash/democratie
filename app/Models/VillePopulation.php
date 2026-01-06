<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Historique démographique des villes
 */
class VillePopulation extends Model
{
    protected $table = 'villes_population';

    protected $fillable = [
        'ville_id',
        'annee',
        'population',
        'population_municipale',
        'population_comptee_a_part',
        'source',
    ];

    protected $casts = [
        'annee' => 'integer',
        'population' => 'integer',
        'population_municipale' => 'integer',
        'population_comptee_a_part' => 'integer',
    ];

    public function ville(): BelongsTo
    {
        return $this->belongsTo(Ville::class);
    }

    public function getPopulationFormateAttribute(): string
    {
        return number_format($this->population, 0, ',', ' ');
    }
}
