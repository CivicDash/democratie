<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SenateurMandat extends Model
{
    protected $table = 'senateurs_mandats';

    protected $fillable = [
        'matricule',
        'type_mandat',
        'circonscription',
        'date_debut',
        'date_fin',
        'motif_fin',
        'numero_mandat',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'numero_mandat' => 'integer',
    ];

    public function senateur(): BelongsTo
    {
        return $this->belongsTo(Senateur::class, 'matricule', 'matricule');
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

