<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SenateurMandat extends Model
{
    protected $table = 'senateurs_mandats';

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

    public function scopeSenateur($query)
    {
        return $query->where('type_mandat', 'SENATEUR');
    }

    public function scopeDepute($query)
    {
        return $query->where('type_mandat', 'DEPUTE');
    }

    public function scopeMunicipal($query)
    {
        return $query->where('type_mandat', 'MUNICIPAL');
    }

    public function getEstActifAttribute(): bool
    {
        return is_null($this->date_fin);
    }
}

