<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetProgramme extends Model
{
    protected $table = 'budget_programmes';

    protected $fillable = [
        'mission_id',
        'code',
        'libelle',
        'ministere',
        'annee',
        'type_loi',
        'credits_ae',
        'credits_cp',
        'credits_ae_prev',
        'credits_cp_prev',
        'evolution_pct',
    ];

    protected $casts = [
        'annee' => 'integer',
        'credits_ae' => 'decimal:2',
        'credits_cp' => 'decimal:2',
        'credits_ae_prev' => 'decimal:2',
        'credits_cp_prev' => 'decimal:2',
        'evolution_pct' => 'decimal:2',
    ];

    // Relations
    public function mission(): BelongsTo
    {
        return $this->belongsTo(BudgetMission::class, 'mission_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(BudgetAction::class, 'programme_id');
    }

    // Scopes
    public function scopeAnnee($query, int $annee)
    {
        return $query->where('annee', $annee);
    }

    public function scopeMinistere($query, string $ministere)
    {
        return $query->where('ministere', 'ILIKE', "%{$ministere}%");
    }

    // Accessors
    public function getCreditsAeFormateAttribute(): string
    {
        return $this->formatMontant($this->credits_ae);
    }

    public function getCreditsCpFormateAttribute(): string
    {
        return $this->formatMontant($this->credits_cp);
    }

    public function getEvolutionBadgeAttribute(): array
    {
        $pct = $this->evolution_pct;
        if ($pct === null) {
            return ['label' => 'N/A', 'color' => 'gray'];
        }
        if ($pct > 5) {
            return ['label' => '+' . number_format($pct, 1) . '%', 'color' => 'green'];
        }
        if ($pct < -5) {
            return ['label' => number_format($pct, 1) . '%', 'color' => 'red'];
        }
        return ['label' => ($pct >= 0 ? '+' : '') . number_format($pct, 1) . '%', 'color' => 'yellow'];
    }

    protected function formatMontant(?float $montant): string
    {
        if ($montant === null) return 'N/A';
        
        if ($montant >= 1_000_000_000) {
            return number_format($montant / 1_000_000_000, 2, ',', ' ') . ' Md€';
        }
        if ($montant >= 1_000_000) {
            return number_format($montant / 1_000_000, 1, ',', ' ') . ' M€';
        }
        return number_format($montant, 0, ',', ' ') . ' €';
    }
}
