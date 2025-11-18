<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TexteLegislatifAN extends Model
{
    use HasFactory;

    protected $table = 'textes_legislatifs_an';
    protected $primaryKey = 'uid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'uid',
        'dossier_ref',
        'legislature',
        'type_texte',
        'numero',
        'titre',
        'date_depot',
    ];

    protected $casts = [
        'date_depot' => 'date',
        'legislature' => 'integer',
        'numero' => 'integer',
    ];

    /**
     * Relations
     */
    public function dossier(): BelongsTo
    {
        return $this->belongsTo(DossierLegislatifAN::class, 'dossier_ref', 'uid');
    }

    public function amendements(): HasMany
    {
        return $this->hasMany(AmendementAN::class, 'texte_legislatif_ref', 'uid');
    }

    /**
     * Scopes
     */
    public function scopeLegislature($query, int $legislature)
    {
        return $query->where('legislature', $legislature);
    }

    public function scopePropositionLoi($query)
    {
        return $query->where('type_texte', 'PION');
    }

    public function scopeProjetLoi($query)
    {
        return $query->where('type_texte', 'PRJL');
    }

    /**
     * Accessors
     */
    public function getNombreAmendementsAttribute(): int
    {
        return $this->amendements()->count();
    }

    public function getNombreAmendementsAdoptesAttribute(): int
    {
        return $this->amendements()
            ->where('etat_code', 'ADO')
            ->count();
    }

    public function getTauxAdoptionAmendementsAttribute(): float
    {
        $total = $this->nombre_amendements;
        if ($total === 0) {
            return 0.0;
        }
        return round(($this->nombre_amendements_adoptes / $total) * 100, 2);
    }
}

