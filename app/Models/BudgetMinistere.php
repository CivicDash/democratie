<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetMinistere extends Model
{
    protected $table = 'budget_ministeres';

    protected $fillable = [
        'code',
        'nom',
        'sigle',
        'annee',
        'type_loi',
        'budget_ae',
        'budget_cp',
        'budget_general',
        'budgets_annexes',
        'comptes_affectation_speciale',
        'comptes_concours_financiers',
        'budget_total',
        'effectifs_etpt',
        'nb_programmes',
        'couleur',
        'source',
    ];

    protected $casts = [
        'annee' => 'integer',
        'budget_ae' => 'decimal:2',
        'budget_cp' => 'decimal:2',
        'budget_general' => 'decimal:2',
        'budgets_annexes' => 'decimal:2',
        'comptes_affectation_speciale' => 'decimal:2',
        'comptes_concours_financiers' => 'decimal:2',
        'budget_total' => 'decimal:2',
        'effectifs_etpt' => 'integer',
        'nb_programmes' => 'integer',
    ];

    // Scopes
    public function scopeAnnee($query, int $annee)
    {
        return $query->where('annee', $annee);
    }

    // Accessors
    public function getBudgetFormateAttribute(): string
    {
        $montant = $this->budget_cp ?? $this->budget_ae;
        if ($montant === null) return 'N/A';
        
        if ($montant >= 1_000_000_000) {
            return number_format($montant / 1_000_000_000, 2, ',', ' ') . ' Md€';
        }
        return number_format($montant / 1_000_000, 1, ',', ' ') . ' M€';
    }

    public function getEffectifsFormateAttribute(): string
    {
        if (!$this->effectifs_etpt) return 'N/A';
        return number_format($this->effectifs_etpt, 0, ',', ' ') . ' ETPT';
    }

    // Couleurs par ministère
    public static function getCouleur(string $nom): string
    {
        $couleurs = [
            'interieur' => '#dc2626',
            'defense' => '#1e3a5f',
            'economie' => '#0891b2',
            'education' => '#2563eb',
            'justice' => '#78350f',
            'sante' => '#ec4899',
            'ecologie' => '#059669',
            'culture' => '#8b5cf6',
            'travail' => '#f59e0b',
            'agriculture' => '#22c55e',
            'affaires_etrangeres' => '#3b82f6',
            'premier_ministre' => '#1f2937',
        ];

        $nomNorm = strtolower(str_replace([' ', '-', "'", 'é', 'è', 'ê'], ['_', '_', '', 'e', 'e', 'e'], $nom));
        
        foreach ($couleurs as $key => $color) {
            if (str_contains($nomNorm, $key)) {
                return $color;
            }
        }
        
        return '#6b7280';
    }
}
