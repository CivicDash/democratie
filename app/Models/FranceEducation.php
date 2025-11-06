<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FranceEducation extends Model
{
    protected $table = 'france_education';

    protected $fillable = [
        'year',
        'illiteracy_rate',
        'numeracy_rate',
        'no_diploma_percentage',
        'brevet_percentage',
        'cap_bep_percentage',
        'bac_percentage',
        'bac_plus_2_percentage',
        'bac_plus_3_percentage',
        'bac_plus_5_percentage',
        'bac_plus_8_percentage',
        'school_enrollment_rate',
        'bac_success_rate',
        'dropout_rate',
        'neet_rate',
        'university_students',
        'higher_education_access_rate',
        'sources',
    ];

    protected $casts = [
        'year' => 'integer',
        'illiteracy_rate' => 'decimal:2',
        'numeracy_rate' => 'decimal:2',
        'no_diploma_percentage' => 'decimal:2',
        'brevet_percentage' => 'decimal:2',
        'cap_bep_percentage' => 'decimal:2',
        'bac_percentage' => 'decimal:2',
        'bac_plus_2_percentage' => 'decimal:2',
        'bac_plus_3_percentage' => 'decimal:2',
        'bac_plus_5_percentage' => 'decimal:2',
        'bac_plus_8_percentage' => 'decimal:2',
        'school_enrollment_rate' => 'decimal:2',
        'bac_success_rate' => 'decimal:2',
        'dropout_rate' => 'decimal:2',
        'neet_rate' => 'decimal:2',
        'university_students' => 'integer',
        'higher_education_access_rate' => 'decimal:2',
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
