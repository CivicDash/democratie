<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Assurance vie déclarée à la HATVP
 */
class HatvpAssuranceVie extends Model
{
    use HasFactory;

    protected $table = 'hatvp_assurances_vie';

    protected $fillable = [
        'declaration_id',
        'souscripteur',
        'etablissement',
        'date_souscription',
        'valeur_rachat',
        'conservee',
        'commentaire',
    ];

    protected $casts = [
        'valeur_rachat' => 'decimal:2',
        'conservee' => 'boolean',
    ];

    public function declaration(): BelongsTo
    {
        return $this->belongsTo(HatvpDeclaration::class, 'declaration_id');
    }
}
