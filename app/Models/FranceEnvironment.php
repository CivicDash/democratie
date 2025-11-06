<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FranceEnvironment extends Model
{
    protected $table = 'france_environment';

    protected $fillable = [
        'year',
        'co2_emissions_per_capita_tons',
        'total_co2_emissions_mt',
        'renewable_energy_percentage',
        'nuclear_energy_percentage',
        'pollution_days',
        'pm25_concentration',
        'air_quality_deaths',
        'waste_per_capita_kg',
        'recycling_rate',
        'plastic_recycling_rate',
        'protected_areas_percentage',
        'forest_coverage_percentage',
        'endangered_species',
        'water_quality_index',
        'water_consumption_per_capita_m3',
        'sources',
    ];

    protected $casts = [
        'year' => 'integer',
        'co2_emissions_per_capita_tons' => 'decimal:2',
        'total_co2_emissions_mt' => 'decimal:2',
        'renewable_energy_percentage' => 'decimal:2',
        'nuclear_energy_percentage' => 'decimal:2',
        'pollution_days' => 'integer',
        'pm25_concentration' => 'decimal:2',
        'air_quality_deaths' => 'integer',
        'waste_per_capita_kg' => 'decimal:2',
        'recycling_rate' => 'decimal:2',
        'plastic_recycling_rate' => 'decimal:2',
        'protected_areas_percentage' => 'decimal:2',
        'forest_coverage_percentage' => 'decimal:2',
        'endangered_species' => 'integer',
        'water_quality_index' => 'decimal:2',
        'water_consumption_per_capita_m3' => 'decimal:2',
    ];

    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function scopeLatestYears($query, int $count = 5)
    {
        return $query->orderBy('year', 'desc')->limit($count);
    }
}
