<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SenateurEtude extends Model
{
    protected $table = 'senateurs_etudes';

    protected $fillable = [
        'matricule',
        'diplome',
        'etablissement',
        'annee_obtention',
    ];

    protected $casts = [
        'annee_obtention' => 'integer',
    ];

    public function senateur(): BelongsTo
    {
        return $this->belongsTo(Senateur::class, 'matricule', 'matricule');
    }
}

