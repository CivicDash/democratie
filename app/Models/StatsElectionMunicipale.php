<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatsElectionMunicipale extends Model
{
    protected $table = 'stats_elections_municipales';

    protected $fillable = [
        'annee',
        'scope',
        'scope_code',
        'data',
        'calculated_at',
    ];

    protected $casts = [
        'annee' => 'integer',
        'data' => 'array',
        'calculated_at' => 'datetime',
    ];

    // ========================================================================
    // SCOPES
    // ========================================================================

    public function scopeNational($query, int $annee = 2026)
    {
        return $query->where('annee', $annee)
            ->where('scope', 'national')
            ->whereNull('scope_code');
    }

    public function scopeByScope($query, string $scope, ?string $code = null, int $annee = 2026)
    {
        $query->where('annee', $annee)->where('scope', $scope);

        if ($code !== null) {
            $query->where('scope_code', $code);
        }

        return $query;
    }

    public function scopeDepartement($query, string $code, int $annee = 2026)
    {
        return $query->where('annee', $annee)
            ->where('scope', 'departement')
            ->where('scope_code', $code);
    }

    // ========================================================================
    // ACCESSEURS
    // ========================================================================

    public function getParticipationAttribute(): ?array
    {
        return $this->data['participation'] ?? null;
    }

    public function getCommunesAttribute(): ?array
    {
        return $this->data['communes'] ?? null;
    }

    public function getNuancesAttribute(): ?array
    {
        return $this->data['nuances'] ?? null;
    }

    public function getPariteMairesAttribute(): ?array
    {
        return $this->data['parite_maires'] ?? null;
    }

    public function getRenouvellementAttribute(): ?array
    {
        return $this->data['renouvellement'] ?? null;
    }
}
