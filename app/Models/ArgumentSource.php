<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArgumentSource extends Model
{
    use HasFactory;

    protected $table = 'argument_sources';

    public const TYPES_SOURCE = [
        'rapport_officiel', 'etude_academique', 'insee', 'cour_des_comptes',
        'conseil_constitutionnel', 'ocde_eurostat', 'presse_nationale', 'fact_checking',
    ];

    public const FIABILITES = ['haute', 'moyenne', 'basse'];

    protected $fillable = [
        'argument_id', 'type_source', 'titre', 'url', 'media', 'date_publication',
        'auteur', 'extrait', 'archive_url', 'fiabilite',
        'verifie_par', 'verifie_at', 'commentaire_verification',
    ];

    protected $casts = [
        'date_publication' => 'date',
        'verifie_at' => 'datetime',
    ];

    public function argument(): BelongsTo
    {
        return $this->belongsTo(Argument::class, 'argument_id');
    }

    public function scopeFiable($query)
    {
        return $query->whereIn('fiabilite', ['haute', 'moyenne']);
    }
}
