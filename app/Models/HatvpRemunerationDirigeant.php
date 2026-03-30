<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rémunération annuelle d'une participation dirigeante HATVP
 */
class HatvpRemunerationDirigeant extends Model
{
    use HasFactory;

    protected $table = 'hatvp_remunerations_dirigeant';

    protected $fillable = [
        'participation_id',
        'annee',
        'montant',
        'brut_net',
    ];

    protected $casts = [
        'annee' => 'integer',
        'montant' => 'decimal:2',
    ];

    public function participation(): BelongsTo
    {
        return $this->belongsTo(HatvpParticipationDirigeante::class, 'participation_id');
    }
}
