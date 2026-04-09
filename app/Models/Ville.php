<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

/**
 * Entité Ville - Agrégation par code INSEE
 * Centralise les données démographiques, économiques et politiques
 */
class Ville extends Model
{
    use Searchable;

    protected $fillable = [
        'code_insee',
        'nom',
        'slug',
        'code_postal_principal',
        'codes_postaux',
        'departement_code',
        'departement_nom',
        'region_code',
        'region_nom',
        'circonscription',
        'epci_code',
        'epci_nom',
        'latitude',
        'longitude',
        'est_prefecture',
        'est_sous_prefecture',
        'est_chef_lieu_region',
        'arrondissement_municipal',
        'ville_parent_insee',
        'population',
        'superficie_km2',
        'densite',
        'maire_actuel_id',
        'wikipedia_url',
        'site_officiel',
        'blason_url',
        'altitude_min',
        'altitude_max',
    ];

    protected $casts = [
        'codes_postaux' => 'array',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'population' => 'integer',
        'superficie_km2' => 'decimal:2',
        'densite' => 'decimal:2',
        'est_prefecture' => 'boolean',
        'est_sous_prefecture' => 'boolean',
        'est_chef_lieu_region' => 'boolean',
        'arrondissement_municipal' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ville) {
            if (empty($ville->slug)) {
                $ville->slug = Str::slug($ville->nom.'-'.$ville->code_insee);
            }
            // Calculer la densité
            if ($ville->population && $ville->superficie_km2 > 0) {
                $ville->densite = round($ville->population / $ville->superficie_km2, 2);
            }
        });

        static::updating(function ($ville) {
            if ($ville->population && $ville->superficie_km2 > 0) {
                $ville->densite = round($ville->population / $ville->superficie_km2, 2);
            }
        });
    }

    // ========================================================================
    // RELATIONS
    // ========================================================================

    /**
     * Maire actuel
     */
    public function maireActuel(): BelongsTo
    {
        return $this->belongsTo(Maire::class, 'maire_actuel_id');
    }

    /**
     * Historique des mandats de maires
     */
    public function mandatsMaires(): HasMany
    {
        return $this->hasMany(MaireMandat::class)->orderByDesc('date_debut');
    }

    /**
     * Mandat actuel
     */
    public function mandatActuel(): HasOne
    {
        return $this->hasOne(MaireMandat::class)->where('est_actuel', true);
    }

    /**
     * Historique de population
     */
    public function historiquePopulation(): HasMany
    {
        return $this->hasMany(VillePopulation::class)->orderByDesc('annee');
    }

    /**
     * Statistiques pré-calculées
     */
    public function stats(): HasOne
    {
        return $this->hasOne(VilleStats::class)->whereNull('annee');
    }

    /**
     * Statistiques par année
     */
    public function statsParAnnee(): HasMany
    {
        return $this->hasMany(VilleStats::class)->whereNotNull('annee')->orderByDesc('annee');
    }

    /**
     * Budgets (via commune_budgets)
     */
    public function budgets(): HasMany
    {
        return $this->hasMany(CommuneBudget::class, 'insee_code', 'code_insee')->orderByDesc('annee');
    }

    /**
     * Ville parente (pour arrondissements)
     */
    public function villeParent(): BelongsTo
    {
        return $this->belongsTo(Ville::class, 'ville_parent_insee', 'code_insee');
    }

    /**
     * Arrondissements (pour Paris, Lyon, Marseille)
     */
    public function arrondissements(): HasMany
    {
        return $this->hasMany(Ville::class, 'ville_parent_insee', 'code_insee');
    }

    /**
     * Résultats élections municipales
     */
    public function resultatsMunicipaux(): HasMany
    {
        return $this->hasMany(ResultatMunicipal::class);
    }

    /**
     * Page commune (hub citoyen v2.0)
     */
    public function communePage(): HasOne
    {
        return $this->hasOne(CommunePage::class);
    }

    // ========================================================================
    // SCOPES
    // ========================================================================

    public function scopeSearch($query, string $term)
    {
        return $query->where('nom', 'ILIKE', "%{$term}%")
            ->orWhere('code_postal_principal', 'LIKE', "{$term}%")
            ->orWhere('code_insee', $term);
    }

    public function scopeByDepartement($query, string $code)
    {
        return $query->where('departement_code', $code);
    }

    public function scopeByRegion($query, string $code)
    {
        return $query->where('region_code', $code);
    }

    public function scopePrefectures($query)
    {
        return $query->where('est_prefecture', true);
    }

    public function scopeGrandesVilles($query, int $minPopulation = 50000)
    {
        return $query->where('population', '>=', $minPopulation);
    }

    // ========================================================================
    // ACCESSEURS
    // ========================================================================

    public function getPopulationFormateAttribute(): string
    {
        if (! $this->population) {
            return 'N/A';
        }

        return number_format($this->population, 0, ',', ' ').' hab.';
    }

    public function getDensiteFormateAttribute(): string
    {
        if (! $this->densite) {
            return 'N/A';
        }

        return number_format($this->densite, 0, ',', ' ').' hab/km²';
    }

    public function getSuperficieFormateAttribute(): string
    {
        if (! $this->superficie_km2) {
            return 'N/A';
        }

        return number_format($this->superficie_km2, 2, ',', ' ').' km²';
    }

    public function getUrlAttribute(): string
    {
        return route('villes.show', $this->slug);
    }

    public function getNomCompletAttribute(): string
    {
        $nom = $this->nom;
        if ($this->arrondissement_municipal && $this->villeParent) {
            $nom = $this->villeParent->nom.' - '.$this->nom;
        }

        return $nom;
    }

    /**
     * URL Wikipedia générée si non définie
     */
    public function getWikipediaUrlFormateAttribute(): string
    {
        if ($this->wikipedia_url) {
            return $this->wikipedia_url;
        }

        // Générer l'URL Wikipedia automatiquement
        $nom = str_replace(' ', '_', $this->nom);

        return "https://fr.wikipedia.org/wiki/{$nom}";
    }

    /**
     * Altitude formatée
     */
    public function getAltitudeFormateAttribute(): ?string
    {
        if ($this->altitude_min === null && $this->altitude_max === null) {
            return null;
        }

        if ($this->altitude_min === $this->altitude_max) {
            return $this->altitude_min.' m';
        }

        return ($this->altitude_min ?? '?').' - '.($this->altitude_max ?? '?').' m';
    }

    // ========================================================================
    // MEILISEARCH
    // ========================================================================

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'code_insee' => $this->code_insee,
            'nom' => $this->nom,
            'slug' => $this->slug,
            'code_postal' => $this->code_postal_principal,
            'codes_postaux' => implode(' ', $this->codes_postaux ?? []),
            'departement' => $this->departement_nom,
            'region' => $this->region_nom,
            'population' => $this->population,
            'est_prefecture' => $this->est_prefecture,
        ];
    }

    public function searchableAs(): string
    {
        return 'villes';
    }
}
