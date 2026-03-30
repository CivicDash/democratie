<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InseeCommune extends Model
{
    protected $table = 'insee_communes';

    protected $fillable = [
        'code_insee', 'nom', 'code_departement', 'code_region',
        'population', 'population_annee', 'densite', 'superficie',
        'revenu_median', 'taux_pauvrete', 'taux_chomage',
        'part_moins_25', 'part_plus_65', 'taux_natalite', 'taux_mortalite',
        'taux_proprietaires', 'taux_logements_vacants',
        'latitude', 'longitude',
    ];

    protected $casts = [
        'population' => 'integer',
        'population_annee' => 'integer',
        'densite' => 'decimal:2',
        'superficie' => 'decimal:2',
        'revenu_median' => 'decimal:2',
        'taux_pauvrete' => 'decimal:2',
        'taux_chomage' => 'decimal:2',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    // Accessors
    public function getPopulationFormateAttribute(): string
    {
        if (! $this->population) {
            return 'N/A';
        }

        return number_format($this->population, 0, ',', ' ').' hab.';
    }

    public function getRevenuMedianFormateAttribute(): string
    {
        if (! $this->revenu_median) {
            return 'N/A';
        }

        return number_format($this->revenu_median, 0, ',', ' ').' €/an';
    }

    public function getDensiteFormateAttribute(): string
    {
        if (! $this->densite) {
            return 'N/A';
        }

        return number_format($this->densite, 0, ',', ' ').' hab/km²';
    }
}
