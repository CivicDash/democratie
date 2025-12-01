<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SenateurHistoriqueGroupe extends Model
{
    protected $table = 'senateurs_historique_groupes';

    // Vue SQL - pas de fillable nécessaire
    protected $fillable = [];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'actif' => 'boolean',
    ];

    public function senateur(): BelongsTo
    {
        // La vue utilise senateur_matricule
        return $this->belongsTo(Senateur::class, 'senateur_matricule', 'matricule');
    }

    public function scopeActifs($query)
    {
        return $query->whereNull('date_fin');
    }
}

