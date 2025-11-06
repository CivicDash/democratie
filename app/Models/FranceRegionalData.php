<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FranceRegionalData extends Model
{
    protected $table = 'france_regional_data';

    protected $fillable = [
        'year',
        'region_code',
        'region_name',
        'population',
        'unemployment_rate',
        'gdp_billions_euros',
        'median_income_euros',
        'poverty_rate',
        'additional_data',
    ];

    protected $casts = [
        'year' => 'integer',
        'population' => 'integer',
        'unemployment_rate' => 'decimal:2',
        'gdp_billions_euros' => 'decimal:2',
        'median_income_euros' => 'decimal:2',
        'poverty_rate' => 'decimal:2',
        'additional_data' => 'array',
    ];

    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function scopeForRegion($query, string $regionCode)
    {
        return $query->where('region_code', $regionCode);
    }
}
