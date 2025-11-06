<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FranceHealth extends Model
{
    protected $table = 'france_health';

    protected $fillable = [
        'year',
        'doctors_per_100k',
        'nurses_per_100k',
        'hospital_beds_per_1k',
        'medical_desert_population_percentage',
        'health_spending_per_capita_euros',
        'health_spending_gdp_percentage',
        'out_of_pocket_health_spending_percentage',
        'vaccination_rate_children',
        'flu_vaccination_rate_elderly',
        'cancer_screening_rate',
        'depression_rate',
        'psychiatrists_per_100k',
        'suicide_rate_per_100k',
        'smoking_rate',
        'alcohol_consumption_liters',
        'sources',
    ];

    protected $casts = [
        'year' => 'integer',
        'doctors_per_100k' => 'decimal:2',
        'nurses_per_100k' => 'decimal:2',
        'hospital_beds_per_1k' => 'decimal:2',
        'medical_desert_population_percentage' => 'decimal:2',
        'health_spending_per_capita_euros' => 'decimal:2',
        'health_spending_gdp_percentage' => 'decimal:2',
        'out_of_pocket_health_spending_percentage' => 'decimal:2',
        'vaccination_rate_children' => 'decimal:2',
        'flu_vaccination_rate_elderly' => 'decimal:2',
        'cancer_screening_rate' => 'decimal:2',
        'depression_rate' => 'decimal:2',
        'psychiatrists_per_100k' => 'decimal:2',
        'suicide_rate_per_100k' => 'integer',
        'smoking_rate' => 'decimal:2',
        'alcohol_consumption_liters' => 'decimal:2',
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
