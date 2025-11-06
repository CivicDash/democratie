<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FranceDepartmentalData extends Model
{
    protected $table = 'france_departmental_data';

    protected $fillable = [
        'year',
        'department_code',
        'department_name',
        'region_code',
        'population',
        'unemployment_rate',
        'median_income_euros',
        'poverty_rate',
        'additional_data',
    ];

    protected $casts = [
        'year' => 'integer',
        'population' => 'integer',
        'unemployment_rate' => 'decimal:2',
        'median_income_euros' => 'decimal:2',
        'poverty_rate' => 'decimal:2',
        'additional_data' => 'array',
    ];

    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function scopeForDepartment($query, string $departmentCode)
    {
        return $query->where('department_code', $departmentCode);
    }

    public function scopeForRegion($query, string $regionCode)
    {
        return $query->where('region_code', $regionCode);
    }
}
