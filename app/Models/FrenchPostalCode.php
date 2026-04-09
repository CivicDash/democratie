<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        // Nouvelles colonnes
        'epci_code',
        'epci_nom',
        'superficie',
        'est_chef_lieu_dep',
        'est_chef_lieu_region',
        'zone_montagne',
        'zone_rurale',
        'outre_mer',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'population' => 'integer',
        'superficie' => 'decimal:2',
        'est_chef_lieu_dep' => 'boolean',
        'est_chef_lieu_region' => 'boolean',
        'zone_montagne' => 'boolean',
        'zone_rurale' => 'boolean',
        'outre_mer' => 'boolean',
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

    // ========================================================================
    // RELATIONS
    // ========================================================================

    /**
     * Budgets de la commune (via insee_code)
     */
    public function budgets(): HasMany
    {
        return $this->hasMany(CommuneBudget::class, 'insee_code', 'insee_code')
            ->orderByDesc('annee');
    }

    /**
     * Dernier budget disponible
     */
    public function dernierBudget(): HasOne
    {
        return $this->hasOne(CommuneBudget::class, 'insee_code', 'insee_code')
            ->latestOfMany('annee');
    }

    /**
     * Le maire de la commune (via code INSEE)
     */
    public function maire()
    {
        return Maire::where('code_commune', $this->insee_code)->first();
    }

    // ========================================================================
    // SCOPES SUPPLÉMENTAIRES
    // ========================================================================

    /**
     * Filtre par EPCI
     */
    public function scopeByEpci($query, string $epciCode)
    {
        return $query->where('epci_code', $epciCode);
    }

    /**
     * Communes chef-lieu de département
     */
    public function scopeChefLieuDep($query)
    {
        return $query->where('est_chef_lieu_dep', true);
    }

    /**
     * Communes outre-mer
     */
    public function scopeOutreMer($query)
    {
        return $query->where('outre_mer', true);
    }

    /**
     * Communes avec population
     */
    public function scopeWithPopulation($query)
    {
        return $query->whereNotNull('population')->where('population', '>', 0);
    }

    // ========================================================================
    // ACCESSEURS
    // ========================================================================

    /**
     * Population formatée
     */
    public function getPopulationFormattedAttribute(): string
    {
        if (! $this->population) {
            return 'N/A';
        }

        return number_format($this->population, 0, ',', ' ').' hab.';
    }

    /**
     * Densité de population (hab/km²)
     */
    public function getDensiteAttribute(): ?float
    {
        if (! $this->population || ! $this->superficie) {
            return null;
        }

        return round($this->population / $this->superficie, 1);
    }

    /**
     * Nom normalisé (sans numéro d'arrondissement)
     */
    public function getNomCommuneAttribute(): string
    {
        // "PARIS 01" → "Paris"
        // "LYON 03" → "Lyon"
        $nom = preg_replace('/\s+\d{2}$/', '', $this->city_name);

        return ucwords(strtolower($nom));
    }
}
