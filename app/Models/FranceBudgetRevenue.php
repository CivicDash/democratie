<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FranceBudgetRevenue extends Model
{
    protected $table = 'france_budget_revenue';

    protected $fillable = [
        'year',
        'total_billions_euros',
        'tva_billions_euros',
        'income_tax_billions_euros',
        'corporate_tax_billions_euros',
        'property_tax_billions_euros',
        'housing_tax_billions_euros',
        'fuel_tax_billions_euros',
        'social_contributions_billions_euros',
        'other_taxes_billions_euros',
        'detailed_breakdown',
    ];

    protected $casts = [
        'year' => 'integer',
        'total_billions_euros' => 'decimal:2',
        'tva_billions_euros' => 'decimal:2',
        'income_tax_billions_euros' => 'decimal:2',
        'corporate_tax_billions_euros' => 'decimal:2',
        'property_tax_billions_euros' => 'decimal:2',
        'housing_tax_billions_euros' => 'decimal:2',
        'fuel_tax_billions_euros' => 'decimal:2',
        'social_contributions_billions_euros' => 'decimal:2',
        'other_taxes_billions_euros' => 'decimal:2',
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
