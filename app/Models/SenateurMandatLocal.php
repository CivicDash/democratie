<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SenateurMandatLocal extends Model
{
    protected $table = 'senateurs_mandats_locaux';

    protected $fillable = [
        'senateur_matricule',
        'type_mandat',
        'fonction',
        'collectivite',
        'code_collectivite',
        'date_debut',
        'date_fin',
        'en_cours',
        'details',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'en_cours' => 'boolean',
        'details' => 'array',
    ];

    /**
     * Relation : Mandat appartient à un sénateur
     */
    public function senateur(): BelongsTo
    {
        return $this->belongsTo(Senateur::class, 'senateur_matricule', 'matricule');
    }

    /**
     * Scope : Mandats en cours
     */
    public function scopeEnCours($query)
    {
        return $query->where('en_cours', true);
    }

    /**
     * Scope : Par type de mandat
     */
    public function scopeType($query, string $type)
    {
        return $query->where('type_mandat', $type);
    }

    /**
     * Accesseur : Libellé du type de mandat
     */
    public function getTypeLibelleAttribute(): string
    {
        return match ($this->type_mandat) {
            'MUNICIPAL' => 'Mandat municipal',
            'DEPARTEMENTAL' => 'Mandat départemental',
            'REGIONAL' => 'Mandat régional',
            'EUROPEEN' => 'Mandat européen',
            'DEPUTE' => 'Ancien député',
            default => $this->type_mandat,
        };
    }

    /**
     * Accesseur : Période formatée
     */
    public function getPeriodeAttribute(): string
    {
        $debut = $this->date_debut?->format('Y') ?? '?';
        $fin = $this->en_cours ? 'Aujourd\'hui' : ($this->date_fin?->format('Y') ?? '?');
        
        return "{$debut} - {$fin}";
    }
}

