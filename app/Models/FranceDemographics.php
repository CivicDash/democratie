<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FranceDemographics extends Model
{
    protected $table = 'france_demographics';

    protected $fillable = [
        'year',
        'population_total',
        'population_by_age_group',
        'population_by_gender',
        'birth_rate',
        'death_rate',
        'life_expectancy_male',
        'life_expectancy_female',
        'median_salary_euros',
    ];

    protected $casts = [
        'year' => 'integer',
        'population_total' => 'integer',
        'population_by_age_group' => 'array',
        'population_by_gender' => 'array',
        'birth_rate' => 'decimal:2',
        'death_rate' => 'decimal:2',
        'life_expectancy_male' => 'decimal:2',
        'life_expectancy_female' => 'decimal:2',
        'median_salary_euros' => 'decimal:2',
    ];

    /**
     * Scope pour récupérer les données d'une année spécifique
     */
    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Scope pour récupérer les dernières années
     */
    public function scopeLatestYears($query, int $count = 5)
    {
        return $query->orderBy('year', 'desc')->limit($count);
    }
}
