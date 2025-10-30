<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modèle pour les budgets des communes françaises
 * 
 * @property int $id
 * @property string $code_insee
 * @property string $nom_commune
 * @property int $annee
 * @property int $population
 * @property int $budget_total Montant en centimes
 * @property int $recettes_fonctionnement Montant en centimes
 * @property int $depenses_fonctionnement Montant en centimes
 * @property int $recettes_investissement Montant en centimes
 * @property int $depenses_investissement Montant en centimes
 * @property int $dette Montant en centimes
 * @property float $depenses_par_habitant Montant en euros
 * @property array|null $sections
 * @property string $source
 * @property \Carbon\Carbon|null $fetched_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class CommuneBudget extends Model
{
    use HasFactory;

    protected $table = 'commune_budgets';

    protected $fillable = [
        'code_insee',
        'nom_commune',
        'annee',
        'population',
        'budget_total',
        'recettes_fonctionnement',
        'depenses_fonctionnement',
        'recettes_investissement',
        'depenses_investissement',
        'dette',
        'depenses_par_habitant',
        'sections',
        'source',
        'fetched_at',
    ];

    protected $casts = [
        'annee' => 'integer',
        'population' => 'integer',
        'budget_total' => 'integer',
        'recettes_fonctionnement' => 'integer',
        'depenses_fonctionnement' => 'integer',
        'recettes_investissement' => 'integer',
        'depenses_investissement' => 'integer',
        'dette' => 'integer',
        'depenses_par_habitant' => 'decimal:2',
        'sections' => 'array',
        'fetched_at' => 'datetime',
    ];

    /**
     * Accesseurs pour convertir les centimes en euros
     */
    public function getBudgetTotalEurosAttribute(): float
    {
        return $this->budget_total / 100;
    }

    public function getRecettesFonctionnementEurosAttribute(): float
    {
        return $this->recettes_fonctionnement / 100;
    }

    public function getDepensesFonctionnementEurosAttribute(): float
    {
        return $this->depenses_fonctionnement / 100;
    }

    public function getRecettesInvestissementEurosAttribute(): float
    {
        return $this->recettes_investissement / 100;
    }

    public function getDepensesInvestissementEurosAttribute(): float
    {
        return $this->depenses_investissement / 100;
    }

    public function getDetteEurosAttribute(): float
    {
        return $this->dette / 100;
    }

    /**
     * Calcule le taux d'endettement
     */
    public function getTauxEndettementAttribute(): float
    {
        if ($this->budget_total === 0) {
            return 0;
        }

        return round(($this->dette / $this->budget_total) * 100, 2);
    }

    /**
     * Calcule l'épargne brute
     */
    public function getEpargneBruteAttribute(): int
    {
        return $this->recettes_fonctionnement - $this->depenses_fonctionnement;
    }

    /**
     * Calcule l'épargne nette
     */
    public function getEpargneNetteAttribute(): int
    {
        return $this->epargne_brute - ($this->dette > 0 ? (int)($this->dette * 0.05) : 0); // Approximation
    }

    /**
     * Scope pour filtrer par code INSEE
     */
    public function scopeForCommune($query, string $codeInsee)
    {
        return $query->where('code_insee', $codeInsee);
    }

    /**
     * Scope pour filtrer par année
     */
    public function scopeForYear($query, int $annee)
    {
        return $query->where('annee', $annee);
    }

    /**
     * Scope pour les communes récentes (données de moins de X jours)
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('fetched_at', '>=', now()->subDays($days));
    }

    /**
     * Scope pour les grandes villes (>= X habitants)
     */
    public function scopeBigCities($query, int $minPopulation = 100000)
    {
        return $query->where('population', '>=', $minPopulation);
    }

    /**
     * Scope pour trier par budget décroissant
     */
    public function scopeOrderByBudget($query, string $direction = 'desc')
    {
        return $query->orderBy('budget_total', $direction);
    }

    /**
     * Scope pour trier par population décroissante
     */
    public function scopeOrderByPopulation($query, string $direction = 'desc')
    {
        return $query->orderBy('population', $direction);
    }

    /**
     * Récupère le budget le plus récent pour une commune
     */
    public static function getLatestForCommune(string $codeInsee): ?self
    {
        return static::forCommune($codeInsee)
            ->orderBy('annee', 'desc')
            ->first();
    }

    /**
     * Compare avec une autre commune
     */
    public function compareWith(CommuneBudget $other): array
    {
        return [
            'population' => [
                'this' => $this->population,
                'other' => $other->population,
                'ratio' => $other->population > 0 ? round($this->population / $other->population, 2) : 0,
            ],
            'budget_total' => [
                'this' => $this->budget_total,
                'other' => $other->budget_total,
                'ratio' => $other->budget_total > 0 ? round($this->budget_total / $other->budget_total, 2) : 0,
            ],
            'depenses_par_habitant' => [
                'this' => $this->depenses_par_habitant,
                'other' => $other->depenses_par_habitant,
                'difference' => round($this->depenses_par_habitant - $other->depenses_par_habitant, 2),
            ],
            'taux_endettement' => [
                'this' => $this->taux_endettement,
                'other' => $other->taux_endettement,
                'difference' => round($this->taux_endettement - $other->taux_endettement, 2),
            ],
        ];
    }

    /**
     * Formate un montant en euros lisible
     */
    public static function formatMontant(int $centimes): string
    {
        $euros = $centimes / 100;
        
        if ($euros >= 1000000) {
            return number_format($euros / 1000000, 2, ',', ' ') . ' M€';
        } elseif ($euros >= 1000) {
            return number_format($euros / 1000, 0, ',', ' ') . ' k€';
        } else {
            return number_format($euros, 2, ',', ' ') . ' €';
        }
    }
}

