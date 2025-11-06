<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FranceMigration extends Model
{
    protected $table = 'france_migration';

    protected $fillable = [
        'year',
        'immigration_total',
        'emigration_total',
        'net_migration',
        'immigration_by_origin',
        'emigration_by_destination',
        'asylum_requests',
        'asylum_granted',
    ];

    protected $casts = [
        'year' => 'integer',
        'immigration_total' => 'integer',
        'emigration_total' => 'integer',
        'net_migration' => 'integer',
        'immigration_by_origin' => 'array',
        'emigration_by_destination' => 'array',
        'asylum_requests' => 'integer',
        'asylum_granted' => 'integer',
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
