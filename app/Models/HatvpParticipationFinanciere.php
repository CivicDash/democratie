<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Participation financière directe déclarée à la HATVP
 */
class HatvpParticipationFinanciere extends Model
{
    use HasFactory;

    protected $table = 'hatvp_participations_financieres';

    protected $fillable = [
        'declaration_id',
        'nom_societe',
        'evaluation',
        'capital_detenu',
        'nombre_parts',
        'commentaire',
    ];

    public function declaration(): BelongsTo
    {
        return $this->belongsTo(HatvpDeclaration::class, 'declaration_id');
    }
}
