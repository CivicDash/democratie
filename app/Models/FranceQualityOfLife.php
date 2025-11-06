<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FranceQualityOfLife extends Model
{
    protected $table = 'france_quality_of_life';

    protected $fillable = [
        'year',
        // IDH
        'hdi_score',
        'hdi_world_rank',
        'hdi_life_expectancy',
        'hdi_education_index',
        'hdi_income_index',
        // Bonheur / Bien-être
        'happiness_score',
        'happiness_world_rank',
        'life_satisfaction',
        'work_life_balance',
        'social_connections',
        // Big Mac Index
        'big_mac_price_euros',
        'big_mac_index',
        'big_mac_ppp_rate',
        // Autres indicateurs
        'gini_coefficient',
        'disposable_income_euros',
        'housing_cost_percentage',
        'life_expectancy_at_birth',
        'sources',
        'notes',
    ];

    protected $casts = [
        'year' => 'integer',
        'hdi_score' => 'decimal:4',
        'hdi_world_rank' => 'integer',
        'hdi_life_expectancy' => 'decimal:2',
        'hdi_education_index' => 'decimal:4',
        'hdi_income_index' => 'decimal:4',
        'happiness_score' => 'decimal:3',
        'happiness_world_rank' => 'integer',
        'life_satisfaction' => 'decimal:2',
        'work_life_balance' => 'decimal:2',
        'social_connections' => 'decimal:2',
        'big_mac_price_euros' => 'decimal:2',
        'big_mac_index' => 'decimal:2',
        'big_mac_ppp_rate' => 'decimal:3',
        'gini_coefficient' => 'decimal:3',
        'disposable_income_euros' => 'decimal:2',
        'housing_cost_percentage' => 'decimal:2',
        'life_expectancy_at_birth' => 'decimal:2',
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
