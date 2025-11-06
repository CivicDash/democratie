<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FranceBudgetSpending extends Model
{
    protected $table = 'france_budget_spending';

    protected $fillable = [
        'year',
        'total_billions_euros',
        'health_billions_euros',
        'education_billions_euros',
        'security_defense_billions_euros',
        'justice_billions_euros',
        'social_welfare_billions_euros',
        'unemployment_billions_euros',
        'pensions_billions_euros',
        'business_subsidies_billions_euros',
        'infrastructure_billions_euros',
        'environment_billions_euros',
        'culture_billions_euros',
        'debt_interest_billions_euros',
        'other_spending_billions_euros',
        'detailed_breakdown',
    ];

    protected $casts = [
        'year' => 'integer',
        'total_billions_euros' => 'decimal:2',
        'health_billions_euros' => 'decimal:2',
        'education_billions_euros' => 'decimal:2',
        'security_defense_billions_euros' => 'decimal:2',
        'justice_billions_euros' => 'decimal:2',
        'social_welfare_billions_euros' => 'decimal:2',
        'unemployment_billions_euros' => 'decimal:2',
        'pensions_billions_euros' => 'decimal:2',
        'business_subsidies_billions_euros' => 'decimal:2',
        'infrastructure_billions_euros' => 'decimal:2',
        'environment_billions_euros' => 'decimal:2',
        'culture_billions_euros' => 'decimal:2',
        'debt_interest_billions_euros' => 'decimal:2',
        'other_spending_billions_euros' => 'decimal:2',
        'detailed_breakdown' => 'array',
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
