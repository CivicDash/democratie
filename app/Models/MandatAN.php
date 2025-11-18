<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MandatAN extends Model
{
    use HasFactory;

    protected $table = 'mandats_an';
    protected $primaryKey = 'uid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'uid',
        'acteur_ref',
        'organe_ref',
        'legislature',
        'type_organe',
        'date_debut',
        'date_fin',
        'code_qualite',
        'libelle_qualite',
        'preseance',
        'nomination_principale',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'legislature' => 'integer',
        'preseance' => 'integer',
        'nomination_principale' => 'boolean',
    ];

    /**
     * Relations
     */
    public function acteur(): BelongsTo
    {
        return $this->belongsTo(ActeurAN::class, 'acteur_ref', 'uid');
    }

    public function organe(): BelongsTo
    {
        return $this->belongsTo(OrganeAN::class, 'organe_ref', 'uid');
    }

    /**
     * Scopes
     */
    public function scopeActifs($query)
    {
        return $query->whereNull('date_fin');
    }

    public function scopeLegislature($query, int $legislature)
    {
        return $query->where('legislature', $legislature);
    }

    public function scopeTypeOrgane($query, string $type)
    {
        return $query->where('type_organe', $type);
    }

    public function scopeAssemblee($query)
    {
        return $query->where('type_organe', 'ASSEMBLEE');
    }

    public function scopeGroupePolitique($query)
    {
        return $query->where('type_organe', 'GP');
    }

    public function scopeCommission($query)
    {
        return $query->whereIn('type_organe', ['COMPER', 'DELEG']);
    }

    /**
     * Accessors
     */
    public function getEstActifAttribute(): bool
    {
        return is_null($this->date_fin);
    }

    public function getEstPresidentAttribute(): bool
    {
        return str_contains(strtolower($this->code_qualite), 'president');
    }
}

