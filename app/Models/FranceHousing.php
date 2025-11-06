<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FranceHousing extends Model
{
    protected $table = 'france_housing';

    protected $fillable = [
        'year',
        'homeownership_rate',
        'rental_rate',
        'social_housing_rate',
        'average_price_per_sqm_euros',
        'paris_price_per_sqm_euros',
        'rent_to_income_ratio',
        'homeless_people',
        'poorly_housed_people',
        'overcrowding_rate',
        'energy_poverty_rate',
        'new_housing_units',
        'vacant_housing_rate',
        'sources',
    ];

    protected $casts = [
        'year' => 'integer',
        'homeownership_rate' => 'decimal:2',
        'rental_rate' => 'decimal:2',
        'social_housing_rate' => 'decimal:2',
        'average_price_per_sqm_euros' => 'decimal:2',
        'paris_price_per_sqm_euros' => 'decimal:2',
        'rent_to_income_ratio' => 'decimal:2',
        'homeless_people' => 'integer',
        'poorly_housed_people' => 'integer',
        'overcrowding_rate' => 'decimal:2',
        'energy_poverty_rate' => 'decimal:2',
        'new_housing_units' => 'integer',
        'vacant_housing_rate' => 'decimal:2',
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
