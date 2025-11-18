<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DossierLegislatifAN extends Model
{
    use HasFactory;

    protected $table = 'dossiers_legislatifs_an';
    protected $primaryKey = 'uid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'uid',
        'legislature',
        'numero',
        'titre',
        'date_creation',
    ];

    protected $casts = [
        'date_creation' => 'date',
        'legislature' => 'integer',
        'numero' => 'integer',
    ];

    /**
     * Relations
     */
    public function textesLegislatifs(): HasMany
    {
        return $this->hasMany(TexteLegislatifAN::class, 'dossier_ref', 'uid');
    }

    /**
     * Scopes
     */
    public function scopeLegislature($query, int $legislature)
    {
        return $query->where('legislature', $legislature);
    }

    /**
     * Accessors
     */
    public function getNombreTextesAttribute(): int
    {
        return $this->textesLegislatifs()->count();
    }

    public function getNombreAmendementsAttribute(): int
    {
        return AmendementAN::whereHas('texteLegislatif', function ($q) {
            $q->where('dossier_ref', $this->uid);
        })->count();
    }
}

