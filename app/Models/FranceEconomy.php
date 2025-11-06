<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FranceEconomy extends Model
{
    protected $table = 'france_economy';

    protected $fillable = [
        'year',
        'quarter',
        'gdp_billions_euros',
        'gdp_growth_rate',
        'unemployment_rate',
        'inflation_rate',
        'public_debt_billions_euros',
        'public_debt_gdp_percentage',
        'trade_balance_billions_euros',
        'exports_billions_euros',
        'imports_billions_euros',
        'gdp_per_capita_euros',
        'food_inflation_rate',
        'energy_inflation_rate',
        'services_inflation_rate',
    ];

    protected $casts = [
        'year' => 'integer',
        'quarter' => 'integer',
        'gdp_billions_euros' => 'decimal:2',
        'gdp_growth_rate' => 'decimal:2',
        'unemployment_rate' => 'decimal:2',
        'inflation_rate' => 'decimal:2',
        'public_debt_billions_euros' => 'decimal:2',
        'public_debt_gdp_percentage' => 'decimal:2',
        'trade_balance_billions_euros' => 'decimal:2',
        'exports_billions_euros' => 'decimal:2',
        'imports_billions_euros' => 'decimal:2',
        'gdp_per_capita_euros' => 'decimal:2',
        'food_inflation_rate' => 'decimal:2',
        'energy_inflation_rate' => 'decimal:2',
        'services_inflation_rate' => 'decimal:2',
    ];

    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function scopeAnnual($query)
    {
        return $query->whereNull('quarter');
    }

    public function scopeQuarterly($query)
    {
        return $query->whereNotNull('quarter');
    }

    public function scopeLatestYears($query, int $count = 5)
    {
        return $query->whereNull('quarter')->orderBy('year', 'desc')->limit($count);
    }
}
