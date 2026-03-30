<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatsAffaireJudiciaire extends Model
{
    protected $table = 'stats_affaires_judiciaires';

    protected $fillable = [
        'scope', 'scope_value', 'data', 'calculated_at',
    ];

    protected $casts = [
        'data' => 'array',
        'calculated_at' => 'datetime',
    ];

    public function scopeGlobal($q)
    {
        return $q->where('scope', 'global')->whereNull('scope_value');
    }

    public function scopeParParti($q)
    {
        return $q->where('scope', 'parti');
    }

    public function scopeParTypeMandat($q)
    {
        return $q->where('scope', 'type_mandat');
    }

    public function scopeParTypeAffaire($q)
    {
        return $q->where('scope', 'type_affaire');
    }

    public function scopeForScope($q, string $scope, ?string $value = null)
    {
        $q->where('scope', $scope);
        if ($value !== null) {
            $q->where('scope_value', $value);
        } else {
            $q->whereNull('scope_value');
        }

        return $q;
    }
}
