<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SenateurCommission extends Model
{
    protected $table = 'senateurs_commissions';

    protected $fillable = [
        'matricule',
        'commission',
        'date_debut',
        'date_fin',
        'fonction',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
    ];

    public function senateur(): BelongsTo
    {
        return $this->belongsTo(Senateur::class, 'matricule', 'matricule');
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

