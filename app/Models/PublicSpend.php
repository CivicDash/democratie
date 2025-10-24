<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dépenses publiques par secteur
 * 
 * @property int $id
 * @property int $year Année fiscale
 * @property string $scope national|region|dept
 * @property int|null $region_id
 * @property int|null $department_id
 * @property int $sector_id
 * @property float $amount Montant dépensé en euros
 * @property string|null $program Programme spécifique
 * @property string|null $source Source des données
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class PublicSpend extends Model
{
    use HasFactory;

    protected $table = 'public_spend';

    protected $fillable = [
        'year',
        'scope',
        'region_id',
        'department_id',
        'sector_id',
        'amount',
        'program',
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
     * Secteur budgétaire
     */
    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    /**
     * Scope: dépenses d'une année
     */
    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Scope: dépenses par scope
     */
    public function scopeByScope($query, string $scope)
    {
        return $query->where('scope', $scope);
    }

    /**
     * Scope: dépenses nationales
     */
    public function scopeNational($query)
    {
        return $query->where('scope', 'national');
    }

    /**
     * Scope: dépenses régionales
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
     * Scope: dépenses départementales
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
     * Scope: dépenses d'un secteur
     */
    public function scopeForSector($query, int $sectorId)
    {
        return $query->where('sector_id', $sectorId);
    }

    /**
     * Scope: somme par secteur
     */
    public function scopeSumBySector($query)
    {
        return $query->selectRaw('sector_id, SUM(amount) as total_amount')
            ->groupBy('sector_id');
    }

    /**
     * Scope: total des dépenses
     */
    public function scopeTotalSpend($query)
    {
        return $query->sum('amount');
    }

    /**
     * Calcule le % de dépenses par secteur
     */
    public static function calculatePercentBySector(int $year, string $scope = 'national'): array
    {
        $total = self::forYear($year)->byScope($scope)->sum('amount');
        
        if ($total == 0) {
            return [];
        }

        $spendBySector = self::forYear($year)
            ->byScope($scope)
            ->sumBySector()
            ->get();

        $result = [];
        foreach ($spendBySector as $item) {
            $result[$item->sector_id] = round(($item->total_amount / $total) * 100, 2);
        }

        return $result;
    }
}

