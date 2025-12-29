<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ThematiqueLoi extends Model
{
    protected $table = 'senat_dosleg_the';
    protected $primaryKey = 'thecle';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'thecle',
        'thelib',
        'theali',
    ];

    // ==========================================
    // RELATIONS
    // ==========================================

    public function lois(): BelongsToMany
    {
        return $this->belongsToMany(
            Loi::class,
            'senat_dosleg_loithe',
            'thecle',
            'loicod',
            'thecle',
            'loicod'
        );
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    public function getLibelleAttribute(): string
    {
        return trim($this->thelib ?? '');
    }

    /**
     * Catégorisation simplifiée pour l'affichage
     */
    public function getCategorieAttribute(): string
    {
        $libelle = strtolower($this->thelib ?? '');

        if (str_contains($libelle, 'budget') || str_contains($libelle, 'finance') || str_contains($libelle, 'fiscal')) {
            return 'budget';
        }
        if (str_contains($libelle, 'social') || str_contains($libelle, 'santé') || str_contains($libelle, 'retraite')) {
            return 'social';
        }
        if (str_contains($libelle, 'environnement') || str_contains($libelle, 'écologi') || str_contains($libelle, 'climat')) {
            return 'environnement';
        }
        if (str_contains($libelle, 'justice') || str_contains($libelle, 'pénal') || str_contains($libelle, 'droit')) {
            return 'justice';
        }
        if (str_contains($libelle, 'défense') || str_contains($libelle, 'sécurité') || str_contains($libelle, 'armée')) {
            return 'defense';
        }
        if (str_contains($libelle, 'éducation') || str_contains($libelle, 'recherche') || str_contains($libelle, 'université')) {
            return 'education';
        }
        if (str_contains($libelle, 'économi') || str_contains($libelle, 'entreprise') || str_contains($libelle, 'commerce')) {
            return 'economie';
        }
        if (str_contains($libelle, 'territoire') || str_contains($libelle, 'collectivité') || str_contains($libelle, 'local')) {
            return 'territoires';
        }

        return 'autre';
    }

    public function getCouleurAttribute(): string
    {
        return match ($this->categorie) {
            'budget' => '#10B981',      // Vert
            'social' => '#F59E0B',       // Orange
            'environnement' => '#22C55E', // Vert clair
            'justice' => '#8B5CF6',      // Violet
            'defense' => '#EF4444',       // Rouge
            'education' => '#3B82F6',     // Bleu
            'economie' => '#6366F1',      // Indigo
            'territoires' => '#14B8A6',   // Teal
            default => '#6B7280',         // Gris
        };
    }
}

