<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Répartition budgétaire d'un citoyen
 * 
 * @property int $id
 * @property int $user_id
 * @property int $sector_id
 * @property float $percent Pourcentage alloué (0-100)
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class UserAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'sector_id',
        'percent',
    ];

    protected $casts = [
        'percent' => 'decimal:2',
    ];

    /**
     * Utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Secteur
     */
    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    /**
     * Valide que la somme des allocations d'un user = 100%
     */
    public static function validateUserTotal(int $userId): bool
    {
        $total = self::where('user_id', $userId)->sum('percent');
        return abs($total - 100.0) < 0.01; // Tolérance float
    }

    /**
     * Calcule la somme des allocations d'un user
     */
    public static function getUserTotal(int $userId): float
    {
        return (float) self::where('user_id', $userId)->sum('percent');
    }

    /**
     * Scope: allocations d'un user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: allocations d'un secteur
     */
    public function scopeForSector($query, int $sectorId)
    {
        return $query->where('sector_id', $sectorId);
    }

    /**
     * Scope: calcule la moyenne par secteur
     */
    public function scopeAverageBySector($query)
    {
        return $query->selectRaw('sector_id, AVG(percent) as avg_percent')
            ->groupBy('sector_id');
    }
}

