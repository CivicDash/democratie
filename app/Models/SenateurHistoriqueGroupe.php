<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SenateurHistoriqueGroupe extends Model
{
    protected $table = 'senateurs_historique_groupes';

    protected $fillable = [
        'matricule',
        'groupe_politique',
        'type_appartenance',
        'date_debut',
        'date_fin',
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
}

