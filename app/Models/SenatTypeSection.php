<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Type de section de discussion au Sénat
 * 
 * Exemples: article, amendement, motion, question, etc.
 */
class SenatTypeSection extends Model
{
    protected $table = 'senat_types_section';
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'code',
        'libelle',
    ];

    /**
     * Libellés lisibles pour les types courants
     */
    public function getLibelleFormatAttribute(): string
    {
        return $this->libelle ?: match($this->code) {
            'ART' => 'Article',
            'AMD' => 'Amendement',
            'MOT' => 'Motion',
            'DG' => 'Discussion générale',
            'EXP' => 'Explications de vote',
            'ORG' => 'Organisation des débats',
            'RG' => 'Règlement',
            'QG' => 'Questions au gouvernement',
            'QE' => 'Questions écrites',
            'QO' => 'Questions orales',
            default => $this->code,
        };
    }
}
