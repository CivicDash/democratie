<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetAnnuel extends Model
{
    protected $table = 'budget_annuel';

    protected $fillable = [
        'annee',
        'recettes_nettes',
        'depenses_nettes',
        'deficit',
        'dette_publique',
        'pib',
        'deficit_pib_pct',
        'dette_pib_pct',
        'metadata',
    ];

    protected $casts = [
        'annee' => 'integer',
        'recettes_nettes' => 'decimal:2',
        'depenses_nettes' => 'decimal:2',
        'deficit' => 'decimal:2',
        'dette_publique' => 'decimal:2',
        'pib' => 'decimal:2',
        'deficit_pib_pct' => 'decimal:2',
        'dette_pib_pct' => 'decimal:2',
        'metadata' => 'array',
    ];

    // Accessors formatés
    public function getRecettesFormateAttribute(): string
    {
        return $this->formatMdEuros($this->recettes_nettes);
    }

    public function getDepensesFormateAttribute(): string
    {
        return $this->formatMdEuros($this->depenses_nettes);
    }

    public function getDeficitFormateAttribute(): string
    {
        return $this->formatMdEuros($this->deficit);
    }

    public function getDetteFormateAttribute(): string
    {
        return $this->formatMdEuros($this->dette_publique);
    }

    public function getPibFormateAttribute(): string
    {
        return $this->formatMdEuros($this->pib);
    }

    protected function formatMdEuros(?float $montant): string
    {
        if ($montant === null) return 'N/A';
        return number_format($montant / 1_000_000_000, 1, ',', ' ') . ' Md€';
    }

    // Indicateurs
    public function getSanteIndicateurAttribute(): string
    {
        $deficit_pct = abs($this->deficit_pib_pct ?? 0);
        
        if ($deficit_pct <= 3) return '🟢 Conforme Maastricht';
        if ($deficit_pct <= 5) return '🟡 Déficit modéré';
        if ($deficit_pct <= 7) return '🟠 Déficit élevé';
        return '🔴 Déficit critique';
    }

    public function getDetteIndicateurAttribute(): string
    {
        $dette_pct = $this->dette_pib_pct ?? 0;
        
        if ($dette_pct <= 60) return '🟢 Conforme Maastricht';
        if ($dette_pct <= 90) return '🟡 Dette modérée';
        if ($dette_pct <= 110) return '🟠 Dette élevée';
        return '🔴 Dette très élevée';
    }
}
