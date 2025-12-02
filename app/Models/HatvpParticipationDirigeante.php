<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Participation aux organes dirigeants déclarée à la HATVP
 */
class HatvpParticipationDirigeante extends Model
{
    use HasFactory;

    protected $table = 'hatvp_participations_dirigeantes';

    protected $fillable = [
        'declaration_id',
        'nom_societe',
        'activite',
        'date_debut',
        'date_fin',
        'conservee',
        'commentaire',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'conservee' => 'boolean',
    ];

    public function declaration(): BelongsTo
    {
        return $this->belongsTo(HatvpDeclaration::class, 'declaration_id');
    }
}

