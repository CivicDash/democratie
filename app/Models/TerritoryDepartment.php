<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Département français (INSEE)
 * 
 * @property int $id
 * @property string $code Code INSEE (ex: 75, 2A)
 * @property string $name Nom du département
 * @property int $region_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class TerritoryDepartment extends Model
{
    use HasFactory;

    protected $table = 'territories_departments';

    protected $fillable = [
        'code',
        'name',
        'region_id',
    ];

    /**
     * Région parente
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(TerritoryRegion::class, 'region_id');
    }

    /**
     * Profils citoyens de ce département
     */
    public function profiles(): HasMany
    {
        return $this->hasMany(Profile::class, 'department_id');
    }

    /**
     * Topics scopés à ce département
     */
    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class, 'department_id');
    }

    /**
     * Recettes publiques de ce département
     */
    public function publicRevenue(): HasMany
    {
        return $this->hasMany(PublicRevenue::class, 'department_id');
    }

    /**
     * Dépenses publiques de ce département
     */
    public function publicSpend(): HasMany
    {
        return $this->hasMany(PublicSpend::class, 'department_id');
    }

    /**
     * Scope: rechercher par code
     */
    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }

    /**
     * Scope: départements d'une région
     */
    public function scopeInRegion($query, int $regionId)
    {
        return $query->where('region_id', $regionId);
    }
}

