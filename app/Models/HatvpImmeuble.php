<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bien immobilier déclaré à la HATVP
 */
class HatvpImmeuble extends Model
{
    use HasFactory;

    protected $table = 'hatvp_immeubles';

    protected $fillable = [
        'declaration_id',
        'nature',
        'code_postal',
        'localite',
        'superficie_bati',
        'superficie_non_bati',
        'date_acquisition',
        'origine',
        'droit_reel',
        'quote_part',
        'prix_acquisition',
        'prix_travaux',
        'valeur_venale',
        'regime_juridique',
        'conservee',
        'commentaire',
    ];

    protected $casts = [
        'superficie_bati' => 'integer',
        'superficie_non_bati' => 'integer',
        'prix_acquisition' => 'decimal:2',
        'prix_travaux' => 'decimal:2',
        'valeur_venale' => 'decimal:2',
        'conservee' => 'boolean',
    ];

    public function declaration(): BelongsTo
    {
        return $this->belongsTo(HatvpDeclaration::class, 'declaration_id');
    }

    public function getSuperficieTotaleAttribute(): int
    {
        return ($this->superficie_bati ?? 0) + ($this->superficie_non_bati ?? 0);
    }

    public function getValeurTotaleAttribute(): float
    {
        return ($this->prix_acquisition ?? 0) + ($this->prix_travaux ?? 0);
    }
}

