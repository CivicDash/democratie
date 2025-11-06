<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FranceEmploymentDetailed extends Model
{
    protected $table = 'france_employment_detailed';

    protected $fillable = [
        'year',
        'cdi_percentage',
        'cdd_percentage',
        'interim_percentage',
        'self_employed_percentage',
        'full_time_percentage',
        'part_time_percentage',
        'involuntary_part_time_percentage',
        'average_weekly_hours',
        'median_salary_private_sector',
        'median_salary_public_sector',
        'median_salary_agriculture',
        'median_salary_industry',
        'median_salary_construction',
        'median_salary_services',
        'median_salary_tech',
        'gender_pay_gap_percentage',
        'executive_worker_pay_ratio',
        'youth_unemployment_rate',
        'senior_unemployment_rate',
        'long_term_unemployment_rate',
        'workplace_accident_rate',
        'burnout_rate',
        'telework_percentage',
        'sources',
    ];

    protected $casts = [
        'year' => 'integer',
        'cdi_percentage' => 'decimal:2',
        'cdd_percentage' => 'decimal:2',
        'interim_percentage' => 'decimal:2',
        'self_employed_percentage' => 'decimal:2',
        'full_time_percentage' => 'decimal:2',
        'part_time_percentage' => 'decimal:2',
        'involuntary_part_time_percentage' => 'decimal:2',
        'average_weekly_hours' => 'decimal:2',
        'median_salary_private_sector' => 'decimal:2',
        'median_salary_public_sector' => 'decimal:2',
        'median_salary_agriculture' => 'decimal:2',
        'median_salary_industry' => 'decimal:2',
        'median_salary_construction' => 'decimal:2',
        'median_salary_services' => 'decimal:2',
        'median_salary_tech' => 'decimal:2',
        'gender_pay_gap_percentage' => 'decimal:2',
        'executive_worker_pay_ratio' => 'decimal:2',
        'youth_unemployment_rate' => 'decimal:2',
        'senior_unemployment_rate' => 'decimal:2',
        'long_term_unemployment_rate' => 'decimal:2',
        'workplace_accident_rate' => 'decimal:2',
        'burnout_rate' => 'decimal:2',
        'telework_percentage' => 'decimal:2',
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
