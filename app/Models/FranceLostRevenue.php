<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FranceLostRevenue extends Model
{
    protected $table = 'france_lost_revenue';

    protected $fillable = [
        'year',
        'vat_fraud_billions_euros',
        'income_tax_fraud_billions_euros',
        'corporate_tax_fraud_billions_euros',
        'social_fraud_billions_euros',
        'tax_evasion_billions_euros',
        'tax_optimization_billions_euros',
        'offshore_billions_euros',
        'total_lost_billions_euros',
        'sources',
        'notes',
    ];

    protected $casts = [
        'year' => 'integer',
        'vat_fraud_billions_euros' => 'decimal:2',
        'income_tax_fraud_billions_euros' => 'decimal:2',
        'corporate_tax_fraud_billions_euros' => 'decimal:2',
        'social_fraud_billions_euros' => 'decimal:2',
        'tax_evasion_billions_euros' => 'decimal:2',
        'tax_optimization_billions_euros' => 'decimal:2',
        'offshore_billions_euros' => 'decimal:2',
        'total_lost_billions_euros' => 'decimal:2',
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
