<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Recettes publiques (transparence)
 * 
 * @property int $id
 * @property int $year Année fiscale
 * @property string $scope national|region|dept
 * @property int|null $region_id
 * @property int|null $department_id
 * @property string $category Catégorie (TVA, IRPP, etc.)
 * @property float $amount Montant en euros
 * @property string|null $source Source des données
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class PublicRevenue extends Model
{
    use HasFactory;

    protected $table = 'public_revenue';

    protected $fillable = [
        'year',
        'scope',
        'region_id',
        'department_id',
        'category',
        'amount',
        'source',
    ];

    protected $casts = [
        'year' => 'integer',
        'amount' => 'decimal:2',
    ];

    /**
     * Région (si scope region ou dept)
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(TerritoryRegion::class, 'region_id');
    }

    /**
     * Département (si scope dept)
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(TerritoryDepartment::class, 'department_id');
    }

    /**
     * Scope: recettes d'une année
     */
    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Scope: recettes par scope
     */
    public function scopeByScope($query, string $scope)
    {
        return $query->where('scope', $scope);
    }

    /**
     * Scope: recettes nationales
     */
    public function scopeNational($query)
    {
        return $query->where('scope', 'national');
    }

    /**
     * Scope: recettes régionales
     */
    public function scopeRegional($query, ?int $regionId = null)
    {
        $query = $query->where('scope', 'region');
        
        if ($regionId) {
            $query->where('region_id', $regionId);
        }
        
        return $query;
    }

    /**
     * Scope: recettes départementales
     */
    public function scopeDepartmental($query, ?int $departmentId = null)
    {
        $query = $query->where('scope', 'dept');
        
        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }
        
        return $query;
    }

    /**
     * Scope: somme par catégorie
     */
    public function scopeSumByCategory($query)
    {
        return $query->selectRaw('category, SUM(amount) as total_amount')
            ->groupBy('category');
    }

    /**
     * Scope: total des recettes
     */
    public function scopeTotalRevenue($query)
    {
        return $query->sum('amount');
    }
}

