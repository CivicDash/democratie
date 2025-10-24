<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Région française (INSEE)
 * 
 * @property int $id
 * @property string $code Code INSEE (ex: 11, 93)
 * @property string $name Nom de la région
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class TerritoryRegion extends Model
{
    use HasFactory;

    protected $table = 'territories_regions';

    protected $fillable = [
        'code',
        'name',
    ];

    /**
     * Départements de cette région
     */
    public function departments(): HasMany
    {
        return $this->hasMany(TerritoryDepartment::class, 'region_id');
    }

    /**
     * Profils citoyens de cette région
     */
    public function profiles(): HasMany
    {
        return $this->hasMany(Profile::class, 'region_id');
    }

    /**
     * Topics scopés à cette région
     */
    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class, 'region_id');
    }

    /**
     * Recettes publiques de cette région
     */
    public function publicRevenue(): HasMany
    {
        return $this->hasMany(PublicRevenue::class, 'region_id');
    }

    /**
     * Dépenses publiques de cette région
     */
    public function publicSpend(): HasMany
    {
        return $this->hasMany(PublicSpend::class, 'region_id');
    }

    /**
     * Scope: rechercher par code
     */
    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }
}

