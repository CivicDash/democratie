<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FrenchPostalCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'postal_code',
        'city_name',
        'department_code',
        'department_name',
        'region_code',
        'region_name',
        'circonscription',
        'latitude',
        'longitude',
        'insee_code',
        'population',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'population' => 'integer',
    ];

    /**
     * Recherche par code postal
     */
    public function scopeByPostalCode($query, string $postalCode)
    {
        return $query->where('postal_code', $postalCode);
    }

    /**
     * Recherche par nom de ville (insensible à la casse)
     */
    public function scopeByCity($query, string $cityName)
    {
        return $query->where('city_name', 'ILIKE', "%{$cityName}%");
    }

    /**
     * Recherche par département
     */
    public function scopeByDepartment($query, string $departmentCode)
    {
        return $query->where('department_code', $departmentCode);
    }

    /**
     * Recherche par circonscription
     */
    public function scopeByCirconscription($query, string $circonscription)
    {
        return $query->where('circonscription', $circonscription);
    }

    /**
     * Autocomplétion : recherche par code postal OU nom de ville
     */
    public function scopeAutocomplete($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('postal_code', 'LIKE', "{$search}%")
              ->orWhere('city_name', 'ILIKE', "%{$search}%");
        });
    }

    /**
     * Obtenir le label complet pour l'affichage
     */
    public function getFullLabelAttribute(): string
    {
        return "{$this->postal_code} - {$this->city_name} ({$this->department_name})";
    }

    /**
     * Obtenir le label court pour l'affichage
     */
    public function getShortLabelAttribute(): string
    {
        return "{$this->postal_code} - {$this->city_name}";
    }
}

