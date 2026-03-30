<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InseeSalaire extends Model
{
    use HasFactory;

    protected $table = 'insee_salaires';

    protected $fillable = [
        'annee',
        'type',
        'categorie',
        'salaire_median',
        'salaire_moyen',
        'd1', 'd2', 'd3', 'd4', 'd5', 'd6', 'd7', 'd8', 'd9',
        'rapport_interdecile',
        'part_sous_smic',
        'source',
        'notes',
    ];

    protected $casts = [
        'annee' => 'integer',
        'salaire_median' => 'float',
        'salaire_moyen' => 'float',
        'd1' => 'float',
        'd2' => 'float',
        'd3' => 'float',
        'd4' => 'float',
        'd5' => 'float',
        'd6' => 'float',
        'd7' => 'float',
        'd8' => 'float',
        'd9' => 'float',
        'rapport_interdecile' => 'float',
        'part_sous_smic' => 'float',
    ];

    // ========================================================================
    // SCOPES
    // ========================================================================

    public function scopeGlobal($query)
    {
        return $query->where('type', 'global');
    }

    public function scopePrive($query)
    {
        return $query->where('type', 'prive');
    }

    public function scopePublic($query)
    {
        return $query->where('type', 'public');
    }

    public function scopeAnnee($query, int $annee)
    {
        return $query->where('annee', $annee);
    }

    // ========================================================================
    // ACCESSEURS
    // ========================================================================

    public function getSalaireMedianFormateAttribute(): string
    {
        return number_format($this->salaire_median ?? 0, 0, ',', ' ').' €';
    }

    public function getSalaireMoyenFormateAttribute(): string
    {
        return number_format($this->salaire_moyen ?? 0, 0, ',', ' ').' €';
    }

    public function getEcartMoyenMedianAttribute(): ?float
    {
        if (! $this->salaire_median || ! $this->salaire_moyen) {
            return null;
        }

        return round((($this->salaire_moyen - $this->salaire_median) / $this->salaire_median) * 100, 1);
    }

    // ========================================================================
    // MÉTHODES STATIQUES
    // ========================================================================

    /**
     * Récupère les dernières données disponibles
     */
    public static function dernieresDonnees(string $type = 'global'): ?self
    {
        return static::where('type', $type)
            ->orderByDesc('annee')
            ->first();
    }

    /**
     * Données par défaut INSEE (pour initialisation)
     * Source: INSEE - Salaires dans le secteur privé et les entreprises publiques
     * https://www.insee.fr/fr/statistiques/6436313
     */
    public static function donneesParDefaut(): array
    {
        return [
            // Données 2022 (dernières complètes INSEE)
            [
                'annee' => 2022,
                'type' => 'global',
                'categorie' => null,
                'salaire_median' => 2091,
                'salaire_moyen' => 2630,
                'd1' => 1398,
                'd5' => 2091,
                'd9' => 4162,
                'rapport_interdecile' => 2.98,
                'source' => 'INSEE - Salaires secteur privé 2022',
                'notes' => 'Salaires nets mensuels en EQTP',
            ],
            [
                'annee' => 2023,
                'type' => 'global',
                'categorie' => null,
                'salaire_median' => 2183,
                'salaire_moyen' => 2735,
                'd1' => 1450,
                'd5' => 2183,
                'd9' => 4350,
                'rapport_interdecile' => 3.00,
                'source' => 'INSEE - Estimation 2023',
                'notes' => 'Salaires nets mensuels en EQTP (estimation)',
            ],
            // Par catégorie socio-professionnelle 2022
            [
                'annee' => 2022,
                'type' => 'prive',
                'categorie' => 'cadres',
                'salaire_median' => 4240,
                'salaire_moyen' => 5370,
                'source' => 'INSEE',
            ],
            [
                'annee' => 2022,
                'type' => 'prive',
                'categorie' => 'professions_intermediaires',
                'salaire_median' => 2380,
                'salaire_moyen' => 2560,
                'source' => 'INSEE',
            ],
            [
                'annee' => 2022,
                'type' => 'prive',
                'categorie' => 'employes',
                'salaire_median' => 1660,
                'salaire_moyen' => 1790,
                'source' => 'INSEE',
            ],
            [
                'annee' => 2022,
                'type' => 'prive',
                'categorie' => 'ouvriers',
                'salaire_median' => 1790,
                'salaire_moyen' => 1890,
                'source' => 'INSEE',
            ],
            // Secteur public
            [
                'annee' => 2022,
                'type' => 'public',
                'categorie' => 'fonction_publique_etat',
                'salaire_median' => 2450,
                'salaire_moyen' => 2780,
                'source' => 'INSEE - Fonction publique',
            ],
            [
                'annee' => 2022,
                'type' => 'public',
                'categorie' => 'fonction_publique_territoriale',
                'salaire_median' => 1950,
                'salaire_moyen' => 2100,
                'source' => 'INSEE - Fonction publique',
            ],
            [
                'annee' => 2022,
                'type' => 'public',
                'categorie' => 'fonction_publique_hospitaliere',
                'salaire_median' => 2200,
                'salaire_moyen' => 2450,
                'source' => 'INSEE - Fonction publique',
            ],
        ];
    }
}
