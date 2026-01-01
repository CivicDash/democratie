<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ministere extends Model
{
    protected $table = 'ministeres';

    protected $fillable = [
        'gouvernement_id', 'nom', 'sigle', 'type',
        'rattachement', 'ordre', 'couleur', 'icone', 'actif',
    ];

    protected $casts = [
        'ordre' => 'integer',
        'actif' => 'boolean',
    ];

    // Relations
    public function gouvernement(): BelongsTo
    {
        return $this->belongsTo(Gouvernement::class);
    }

    public function ministres(): HasMany
    {
        return $this->hasMany(Ministre::class, 'ministere_id');
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    public function scopeMinisteres($query)
    {
        return $query->where('type', 'ministere');
    }

    public function scopeSecretariats($query)
    {
        return $query->where('type', 'secretariat_etat');
    }

    // Accessors
    public function getMinistreActuelAttribute(): ?Ministre
    {
        return $this->ministres()->where('actif', true)->first();
    }

    public function getTypeLibelleAttribute(): string
    {
        return match($this->type) {
            'ministere' => 'Ministère',
            'ministere_delegue' => 'Ministère délégué',
            'secretariat_etat' => 'Secrétariat d\'État',
            default => 'Autre',
        };
    }

    // Couleurs par défaut selon le domaine
    public static function getCouleurDefaut(string $nom): string
    {
        $couleurs = [
            'intérieur' => '#dc2626',
            'armées' => '#1e3a5f',
            'économie' => '#0891b2',
            'éducation' => '#2563eb',
            'justice' => '#78350f',
            'santé' => '#ec4899',
            'écologie' => '#059669',
            'culture' => '#8b5cf6',
            'travail' => '#f59e0b',
            'agriculture' => '#22c55e',
            'europe' => '#3b82f6',
            'outre-mer' => '#06b6d4',
            'sport' => '#84cc16',
        ];

        $nomLower = strtolower($nom);
        foreach ($couleurs as $key => $color) {
            if (str_contains($nomLower, $key)) {
                return $color;
            }
        }
        return '#6b7280';
    }
}
