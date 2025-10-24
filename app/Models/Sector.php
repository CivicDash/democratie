<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Secteur budgétaire (éducation, santé, etc.)
 * 
 * @property int $id
 * @property string $code Code unique (EDU, HEALTH, etc.)
 * @property string $name Nom du secteur
 * @property string|null $description
 * @property string|null $icon Nom d'icône
 * @property string|null $color Couleur hex
 * @property float $min_percent % minimum allouable
 * @property float $max_percent % maximum allouable
 * @property int $display_order Ordre d'affichage
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Sector extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'icon',
        'color',
        'min_percent',
        'max_percent',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'min_percent' => 'decimal:2',
        'max_percent' => 'decimal:2',
        'display_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Allocations des utilisateurs pour ce secteur
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(UserAllocation::class);
    }

    /**
     * Dépenses publiques de ce secteur
     */
    public function publicSpend(): HasMany
    {
        return $this->hasMany(PublicSpend::class);
    }

    /**
     * Vérifie si un pourcentage est dans les limites
     */
    public function isPercentValid(float $percent): bool
    {
        return $percent >= $this->min_percent && $percent <= $this->max_percent;
    }

    /**
     * Calcule la moyenne des allocations citoyennes
     */
    public function averageAllocation(): float
    {
        return $this->allocations()->avg('percent') ?? 0.0;
    }

    /**
     * Scope: secteurs actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: tri par ordre d'affichage
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }

    /**
     * Scope: recherche par code
     */
    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }
}

