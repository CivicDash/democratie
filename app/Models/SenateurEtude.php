<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SenateurEtude extends Model
{
    protected $table = 'senateurs_etudes';

    protected $fillable = [
        'senateur_matricule',
        'etablissement',
        'diplome',
        'niveau',
        'domaine',
        'annee',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    /**
     * Relation : Étude appartient à un sénateur
     */
    public function senateur(): BelongsTo
    {
        return $this->belongsTo(Senateur::class, 'senateur_matricule', 'matricule');
    }

    /**
     * Accesseur : Libellé complet
     */
    public function getLibelleCompletAttribute(): string
    {
        $parts = array_filter([
            $this->diplome,
            $this->domaine,
            $this->etablissement,
            $this->annee ? "({$this->annee})" : null,
        ]);

        return implode(' - ', $parts) ?: 'Formation non précisée';
    }
}
