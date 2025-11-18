<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrganeAN extends Model
{
    use HasFactory;

    protected $table = 'organes_an';
    protected $primaryKey = 'uid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'uid',
        'code_type',
        'libelle',
        'libelle_abrege',
        'legislature',
        'date_debut',
        'date_fin',
        'regime',
        'site_internet',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'legislature' => 'integer',
    ];

    /**
     * Relations
     */
    public function mandats(): HasMany
    {
        return $this->hasMany(MandatAN::class, 'organe_ref', 'uid');
    }

    public function scrutins(): HasMany
    {
        return $this->hasMany(ScrutinAN::class, 'organe_ref', 'uid');
    }

    public function amendementsAuteurGroupe(): HasMany
    {
        return $this->hasMany(AmendementAN::class, 'auteur_groupe_ref', 'uid');
    }

    public function reunions(): HasMany
    {
        return $this->hasMany(ReunionAN::class, 'organe_ref', 'uid');
    }

    public function votesIndividuels(): HasMany
    {
        return $this->hasMany(VoteIndividuelAN::class, 'groupe_ref', 'uid');
    }

    /**
     * Scopes
     */
    public function scopeGroupesPolitiques($query)
    {
        return $query->where('code_type', 'GP');
    }

    public function scopeCommissionsPermanentes($query)
    {
        return $query->where('code_type', 'COMPER');
    }

    public function scopeDelegations($query)
    {
        return $query->where('code_type', 'DELEG');
    }

    public function scopeActifs($query)
    {
        return $query->whereNull('date_fin');
    }

    public function scopeLegislature($query, int $legislature)
    {
        return $query->where('legislature', $legislature);
    }

    /**
     * Accessors
     */
    public function getEstActifAttribute(): bool
    {
        return is_null($this->date_fin);
    }

    public function getNombresMembresAttribute(): int
    {
        return $this->mandats()
            ->whereNull('date_fin')
            ->distinct('acteur_ref')
            ->count('acteur_ref');
    }
}

