<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Revenu annuel déclaré à la HATVP
 */
class HatvpRevenu extends Model
{
    use HasFactory;

    protected $table = 'hatvp_revenus';

    protected $fillable = [
        'declaration_id',
        'annee',
        'type_revenu',
        'montant_elu',
        'montant_conjoint',
        'brut_net',
    ];

    protected $casts = [
        'annee' => 'integer',
        'montant_elu' => 'decimal:2',
        'montant_conjoint' => 'decimal:2',
    ];

    public function declaration(): BelongsTo
    {
        return $this->belongsTo(HatvpDeclaration::class, 'declaration_id');
    }

    public function getTotalAttribute(): float
    {
        return ($this->montant_elu ?? 0) + ($this->montant_conjoint ?? 0);
    }

    public function getTypeRevenuLabelAttribute(): string
    {
        $labels = [
            'indemnites_elu' => 'Indemnités d\'élu',
            'traitements_salaires' => 'Traitements et salaires',
            'pensions_retraites' => 'Pensions et retraites',
            'revenus_professionnels' => 'Revenus professionnels (BNC, BIC, BA)',
            'revenus_capitaux_mobiliers' => 'Revenus de capitaux mobiliers',
            'revenus_fonciers' => 'Revenus fonciers',
            'autres_revenus' => 'Autres revenus',
            'plus_values_mobilieres' => 'Plus-values mobilières',
            'plus_values_immobilieres' => 'Plus-values immobilières',
        ];

        return $labels[$this->type_revenu] ?? $this->type_revenu;
    }
}
