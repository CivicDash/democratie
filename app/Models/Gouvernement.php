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
        'numero', 'legislature', 'contexte', 'metadata',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'actif' => 'boolean',
        'metadata' => 'array',
    ];

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
        
        if ($jours < 30) return $jours . ' jours';
        if ($jours < 365) return floor($jours / 30) . ' mois';
        
        $annees = floor($jours / 365);
        $mois = floor(($jours % 365) / 30);
        return $annees . ' an' . ($annees > 1 ? 's' : '') . ($mois > 0 ? " et {$mois} mois" : '');
    }

    public function getNbMinistresAttribute(): int
    {
        return $this->ministres()->where('actif', true)->count();
    }

    public function getNbMinisteresAttribute(): int
    {
        return $this->ministeres()->where('actif', true)->count();
    }

    // Gouvernement actuel
    public static function actuel(): ?self
    {
        return self::where('actif', true)->first();
    }
}
