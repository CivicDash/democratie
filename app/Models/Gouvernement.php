<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gouvernement extends Model
{
    protected $table = 'gouvernements';

    protected $fillable = [
        'nom', 'slug', 'premier_ministre', 'president',
        'date_debut', 'date_fin', 'actif',
        'numero', 'suffixe', 'legislature', 'contexte', 'metadata',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'actif' => 'boolean',
        'metadata' => 'array',
    ];

    protected $appends = ['nom_complet', 'numero_ordinal'];

    // Relations
    public function ministeres(): HasMany
    {
        return $this->hasMany(Ministere::class, 'gouvernement_id');
    }

    public function ministres(): HasMany
    {
        return $this->hasMany(Ministre::class, 'gouvernement_id');
    }

    public function remaniements(): HasMany
    {
        return $this->hasMany(Remaniement::class, 'gouvernement_id');
    }

    // Nouvelle relation avec les postes ministériels
    public function postes(): HasMany
    {
        return $this->hasMany(PosteMinisteriel::class, 'gouvernement_id')
            ->orderBy('ordre');
    }

    public function postesActifs(): HasMany
    {
        return $this->hasMany(PosteMinisteriel::class, 'gouvernement_id')
            ->where('actif', true)
            ->orderBy('ordre');
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    // Accessors
    public function getDureeAttribute(): string
    {
        $debut = $this->date_debut;
        $fin = $this->date_fin ?? now();
        $jours = $debut->diffInDays($fin);

        if ($jours < 30) {
            return $jours.' jours';
        }
        if ($jours < 365) {
            return floor($jours / 30).' mois';
        }

        $annees = floor($jours / 365);
        $mois = floor(($jours % 365) / 30);

        return $annees.' an'.($annees > 1 ? 's' : '').($mois > 0 ? " et {$mois} mois" : '');
    }

    public function getNbMinistresAttribute(): int
    {
        return $this->ministres()->where('actif', true)->count();
    }

    public function getNbMinisteresAttribute(): int
    {
        return $this->ministeres()->where('actif', true)->count();
    }

    // Nom complet avec numéro : "48ème Gouvernement - Lecornu II"
    public function getNomCompletAttribute(): string
    {
        $parts = [];

        if ($this->numero) {
            $parts[] = $this->numero_ordinal.' Gouvernement';
        }

        $parts[] = $this->nom;

        if ($this->suffixe) {
            $parts[count($parts) - 1] .= ' '.$this->suffixe;
        }

        return implode(' - ', $parts);
    }

    // Numéro ordinal : 48 -> "48ème"
    public function getNumeroOrdinalAttribute(): ?string
    {
        if (! $this->numero) {
            return null;
        }

        if ($this->numero === 1) {
            return '1er';
        }

        return $this->numero.'ème';
    }

    // Gouvernement actuel
    public static function actuel(): ?self
    {
        return self::where('actif', true)->first();
    }

    // Liste des gouvernements de la Vème République
    public static function listeHistorique(): \Illuminate\Database\Eloquent\Collection
    {
        return self::orderBy('numero', 'desc')
            ->orderBy('date_debut', 'desc')
            ->get();
    }
}
