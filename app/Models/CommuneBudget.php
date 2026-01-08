<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Budget annuel d'une commune (données OFGL)
 * Lié à french_postal_codes via insee_code
 */
class CommuneBudget extends Model
{
    protected $fillable = [
        'insee_code',
        'annee',
        // Fonctionnement
        'recettes_fonctionnement',
        'depenses_fonctionnement',
        // Investissement
        'recettes_investissement',
        'depenses_investissement',
        // Dette
        'encours_dette',
        'annuite_dette',
        // Ratios
        'capacite_autofinancement',
        'euros_par_habitant',
        // Détails recettes
        'impots_locaux',
        'dotations_subventions',
        // Détails dépenses
        'charges_personnel',
        'achats_services',
        // Soldes
        'epargne_brute',
        // Population
        'population',
        'source',
    ];

    protected $casts = [
        'annee' => 'integer',
        'recettes_fonctionnement' => 'decimal:2',
        'depenses_fonctionnement' => 'decimal:2',
        'recettes_investissement' => 'decimal:2',
        'depenses_investissement' => 'decimal:2',
        'encours_dette' => 'decimal:2',
        'annuite_dette' => 'decimal:2',
        'capacite_autofinancement' => 'decimal:2',
        'euros_par_habitant' => 'decimal:2',
        'impots_locaux' => 'decimal:2',
        'dotations_subventions' => 'decimal:2',
        'charges_personnel' => 'decimal:2',
        'achats_services' => 'decimal:2',
        'epargne_brute' => 'decimal:2',
        'population' => 'integer',
    ];

    /**
     * La commune associée (via insee_code)
     */
    public function commune(): BelongsTo
    {
        return $this->belongsTo(FrenchPostalCode::class, 'insee_code', 'insee_code');
    }

    /**
     * Total des recettes
     */
    public function getTotalRecettesAttribute(): float
    {
        return ($this->recettes_fonctionnement ?? 0) + ($this->recettes_investissement ?? 0);
    }

    /**
     * Total des dépenses
     */
    public function getTotalDepensesAttribute(): float
    {
        return ($this->depenses_fonctionnement ?? 0) + ($this->depenses_investissement ?? 0);
    }

    /**
     * Solde budgétaire
     */
    public function getSoldeAttribute(): float
    {
        return $this->total_recettes - $this->total_depenses;
    }

    /**
     * Taux d'endettement (dette / recettes)
     */
    public function getTauxEndettementAttribute(): ?float
    {
        if (!$this->encours_dette || !$this->recettes_fonctionnement) {
            return null;
        }
        return round(($this->encours_dette / $this->recettes_fonctionnement) * 100, 1);
    }

    /**
     * Accesseur : recettes_fonctionnement formatées
     */
    public function getRecettesFonctionnementFormateAttribute(): string
    {
        return self::formatMontant($this->recettes_fonctionnement);
    }

    /**
     * Accesseur : dépenses_fonctionnement formatées
     */
    public function getDepensesFonctionnementFormateAttribute(): string
    {
        return self::formatMontant($this->depenses_fonctionnement);
    }

    /**
     * Accesseur : encours_dette formaté
     */
    public function getEncoursDetteFormateAttribute(): string
    {
        return self::formatMontant($this->encours_dette);
    }

    /**
     * Scope par année
     */
    public function scopeAnnee($query, int $annee)
    {
        return $query->where('annee', $annee);
    }

    /**
     * Scope dernière année disponible
     */
    public function scopeDerniereAnnee($query)
    {
        return $query->orderByDesc('annee')->limit(1);
    }

    /**
     * Formater un montant en euros
     */
    public static function formatMontant(?float $montant): string
    {
        if ($montant === null) return 'N/A';
        
        if (abs($montant) >= 1_000_000_000) {
            return number_format($montant / 1_000_000_000, 2, ',', ' ') . ' Md€';
        }
        if (abs($montant) >= 1_000_000) {
            return number_format($montant / 1_000_000, 2, ',', ' ') . ' M€';
        }
        if (abs($montant) >= 1_000) {
            return number_format($montant / 1_000, 1, ',', ' ') . ' k€';
        }
        return number_format($montant, 0, ',', ' ') . ' €';
    }
}
