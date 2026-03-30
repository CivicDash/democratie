<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InseeRegion extends Model
{
    protected $table = 'insee_regions';

    protected $fillable = [
        'code', 'nom', 'chef_lieu',
        'population', 'population_annee', 'densite', 'superficie',
        'nb_departements', 'nb_communes',
        'pib', 'pib_par_habitant', 'revenu_median', 'taux_chomage', 'taux_activite',
        'part_moins_25', 'part_plus_65', 'solde_migratoire',
    ];

    protected $casts = [
        'population' => 'integer',
        'nb_departements' => 'integer',
        'nb_communes' => 'integer',
        'pib' => 'decimal:2',
        'pib_par_habitant' => 'decimal:2',
        'taux_chomage' => 'decimal:2',
    ];

    // Relations
    public function departements(): HasMany
    {
        return $this->hasMany(InseeDepartement::class, 'code_region', 'code');
    }

    // Accessors
    public function getPopulationFormateAttribute(): string
    {
        if (! $this->population) {
            return 'N/A';
        }

        return number_format($this->population / 1_000_000, 1, ',', ' ').'M hab.';
    }

    public function getPibFormateAttribute(): string
    {
        if (! $this->pib) {
            return 'N/A';
        }

        return number_format($this->pib / 1_000, 0, ',', ' ').' Md€';
    }
}
