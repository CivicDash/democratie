<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SenateurCommission extends Model
{
    protected $table = 'senateurs_commissions';

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

    public function getEstPresidentAttribute(): bool
    {
        return str_contains(strtolower($this->fonction ?? ''), 'président');
    }
}

