<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActeurAN extends Model
{
    use HasFactory;

    protected $table = 'acteurs_an';
    protected $primaryKey = 'uid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'uid',
        'civilite',
        'prenom',
        'nom',
        'trigramme',
        'date_naissance',
        'ville_naissance',
        'departement_naissance',
        'pays_naissance',
        'profession',
        'categorie_socio_pro',
        'url_hatvp',
        'wikipedia_url',
        'photo_wikipedia_url',
        'wikipedia_extract',
        'wikipedia_last_sync',
        'adresses',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'wikipedia_last_sync' => 'datetime',
        'adresses' => 'array',
    ];

    /**
     * Relations
     */
    public function mandats(): HasMany
    {
        return $this->hasMany(MandatAN::class, 'acteur_ref', 'uid');
    }

    public function votesIndividuels(): HasMany
    {
        return $this->hasMany(VoteIndividuelAN::class, 'acteur_ref', 'uid');
    }

    public function amendementsAuteur(): HasMany
    {
        return $this->hasMany(AmendementAN::class, 'auteur_acteur_ref', 'uid');
    }

    public function deports(): HasMany
    {
        return $this->hasMany(DeportAN::class, 'acteur_ref', 'uid');
    }

    /**
     * Scopes
     */
    public function scopeDeputes($query)
    {
        // Députés actifs (avec mandat ASSEMBLEE en cours)
        return $query->whereHas('mandats', function ($q) {
            $q->where('type_organe', 'ASSEMBLEE')
              ->whereNull('date_fin');
        });
    }

    /**
     * Accessors
     */
    public function getNomCompletAttribute(): string
    {
        return trim("{$this->civilite} {$this->prenom} {$this->nom}");
    }

    /**
     * Récupère le groupe politique actuel (via mandat GP actif)
     */
    public function getGroupePolitiqueActuelAttribute()
    {
        $mandatGP = $this->mandats()
            ->where('type_organe', 'GP')
            ->whereNull('date_fin')
            ->with('organe')
            ->first();

        return $mandatGP ? $mandatGP->organe : null;
    }

    /**
     * Récupère les commissions actuelles
     */
    public function getCommissionsActuellesAttribute()
    {
        return $this->mandats()
            ->whereIn('type_organe', ['COMPER', 'DELEG'])
            ->whereNull('date_fin')
            ->with('organe')
            ->get()
            ->pluck('organe');
    }
}

