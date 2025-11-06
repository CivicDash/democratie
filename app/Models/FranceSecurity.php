<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FranceSecurity extends Model
{
    protected $table = 'france_security';

    protected $fillable = [
        'year',
        'crime_rate_per_1000',
        'total_crimes',
        'violent_crimes',
        'property_crimes',
        'homicides',
        'feminicides', // 💜 Indicateur crucial
        'domestic_violence_reports',
        'sexual_assault_reports',
        'rape_reports',
        'feeling_safe_percentage',
        'feeling_safe_night_percentage',
        'prison_population',
        'prison_occupancy_rate',
        'recidivism_rate',
        'police_per_100k',
        'police_budget_billions_euros',
        'sources',
    ];

    protected $casts = [
        'year' => 'integer',
        'crime_rate_per_1000' => 'decimal:2',
        'total_crimes' => 'integer',
        'violent_crimes' => 'integer',
        'property_crimes' => 'integer',
        'homicides' => 'integer',
        'feminicides' => 'integer',
        'domestic_violence_reports' => 'integer',
        'sexual_assault_reports' => 'integer',
        'rape_reports' => 'integer',
        'feeling_safe_percentage' => 'decimal:2',
        'feeling_safe_night_percentage' => 'decimal:2',
        'prison_population' => 'integer',
        'prison_occupancy_rate' => 'decimal:2',
        'recidivism_rate' => 'decimal:2',
        'police_per_100k' => 'decimal:2',
        'police_budget_billions_euros' => 'decimal:2',
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
