<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InseeDepartement extends Model
{
    protected $table = 'insee_departements';

    protected $fillable = [
        'code', 'nom', 'code_region', 'chef_lieu',
        'population', 'population_annee', 'densite', 'superficie', 'nb_communes',
        'revenu_median', 'taux_pauvrete', 'taux_chomage', 'pib_par_habitant',
        'part_moins_25', 'part_plus_65', 'esperance_vie',
        'nb_deputes', 'nb_senateurs',
    ];

    protected $casts = [
        'population' => 'integer',
        'nb_communes' => 'integer',
        'nb_deputes' => 'integer',
        'nb_senateurs' => 'integer',
        'densite' => 'decimal:2',
        'taux_chomage' => 'decimal:2',
    ];

    // Relations
    public function communes(): HasMany
    {
        return $this->hasMany(InseeCommune::class, 'code_departement', 'code');
    }

    public function region()
    {
        return $this->belongsTo(InseeRegion::class, 'code_region', 'code');
    }

    // Scopes
    public function scopeMetropole($query)
    {
        return $query->where('code', '<', '97');
    }

    public function scopeOutreMer($query)
    {
        return $query->where('code', '>=', '97');
    }

    // Accessors
    public function getPopulationFormateAttribute(): string
    {
        if (!$this->population) return 'N/A';
        if ($this->population >= 1_000_000) {
            return number_format($this->population / 1_000_000, 1, ',', ' ') . 'M hab.';
        }
        return number_format($this->population, 0, ',', ' ') . ' hab.';
    }
}
