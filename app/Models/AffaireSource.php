<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffaireSource extends Model
{
    protected $table = 'affaires_sources';

    protected $fillable = [
        'affaire_id', 'type_source', 'titre', 'url', 'media',
        'date_publication', 'auteur', 'extrait', 'archive_url',
        'fiabilite', 'verifie_par', 'verifie_at', 'commentaire_verification',
    ];

    protected $casts = [
        'date_publication' => 'date',
        'verifie_at' => 'datetime',
    ];

    public function affaire(): BelongsTo
    {
        return $this->belongsTo(AffaireJudiciaire::class, 'affaire_id');
    }

    public function verificateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verifie_par');
    }

    public function scopeVerifiees($q)
    {
        return $q->whereNotNull('verifie_par');
    }

    public function scopeHauteFiabilite($q)
    {
        return $q->where('fiabilite', 'haute');
    }

    public function scopeOfficielles($q)
    {
        return $q->whereIn('type_source', [
            'decision_justice', 'journal_officiel', 'rapport_officiel', 'hatvp_signalement', 'cnccfp',
        ]);
    }

    public function getFiabiliteLibelleAttribute(): string
    {
        return match ($this->fiabilite) {
            'haute' => 'Source officielle',
            'moyenne' => 'Presse nationale',
            'basse' => 'Autre source',
            default => $this->fiabilite,
        };
    }

    public function getTypeSourceLibelleAttribute(): string
    {
        return match ($this->type_source) {
            'article_presse' => 'Article de presse',
            'decision_justice' => 'Décision de justice',
            'journal_officiel' => 'Journal Officiel',
            'hatvp_signalement' => 'Signalement HATVP',
            'wikipedia' => 'Wikipedia',
            'wikidata' => 'Wikidata',
            'rapport_officiel' => 'Rapport officiel',
            'cnccfp' => 'CNCCFP',
            default => ucfirst(str_replace('_', ' ', $this->type_source)),
        };
    }

    public static function TYPES_SOURCE(): array
    {
        return [
            'article_presse', 'decision_justice', 'journal_officiel',
            'hatvp_signalement', 'wikipedia', 'wikidata', 'rapport_officiel', 'cnccfp',
        ];
    }
}
