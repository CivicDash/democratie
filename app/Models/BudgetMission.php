<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetMission extends Model
{
    protected $table = 'budget_missions';

    protected $fillable = [
        'code',
        'libelle',
        'annee',
        'type_loi',
        'credits_ae',
        'credits_cp',
        'nb_programmes',
    ];

    protected $casts = [
        'annee' => 'integer',
        'credits_ae' => 'decimal:2',
        'credits_cp' => 'decimal:2',
        'nb_programmes' => 'integer',
    ];

    // Relations
    public function programmes(): HasMany
    {
        return $this->hasMany(BudgetProgramme::class, 'mission_id');
    }

    // Scopes
    public function scopeAnnee($query, int $annee)
    {
        return $query->where('annee', $annee);
    }

    public function scopePlf($query)
    {
        return $query->where('type_loi', 'plf');
    }

    public function scopeLfi($query)
    {
        return $query->where('type_loi', 'lfi');
    }

    // Accessors
    public function getCreditsAeFormateAttribute(): string
    {
        return $this->formatMontant($this->credits_ae);
    }

    public function getCreditsAeMdAttribute(): float
    {
        return round(($this->credits_ae ?? 0) / 1_000_000, 2);
    }

    public function getCreditsCpFormateAttribute(): string
    {
        return $this->formatMontant($this->credits_cp);
    }

    public function getCreditsCpMdAttribute(): float
    {
        return round(($this->credits_cp ?? 0) / 1_000_000, 2);
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

    // Couleurs par mission (pour graphiques)
    public static function getCouleurMission(string $code): string
    {
        $couleurs = [
            'defense' => '#1e3a5f',
            'enseignement_scolaire' => '#2563eb',
            'recherche' => '#7c3aed',
            'ecologie' => '#059669',
            'securites' => '#dc2626',
            'justice' => '#78350f',
            'solidarite' => '#ec4899',
            'travail' => '#f59e0b',
            'economie' => '#0891b2',
            'culture' => '#8b5cf6',
            'sport' => '#84cc16',
            'agriculture' => '#22c55e',
            'outre_mer' => '#06b6d4',
            'action_exterieure' => '#3b82f6',
        ];

        $codeNorm = strtolower(str_replace([' ', '-', "'"], '_', $code));
        return $couleurs[$codeNorm] ?? '#6b7280';
    }
}
