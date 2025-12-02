<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rémunération annuelle d'un mandat électif HATVP
 */
class HatvpRemuneration extends Model
{
    use HasFactory;

    protected $table = 'hatvp_remunerations';

    protected $fillable = [
        'mandat_id',
        'annee',
        'montant',
        'brut_net',
    ];

    protected $casts = [
        'annee' => 'integer',
        'montant' => 'decimal:2',
    ];

    public function mandat(): BelongsTo
    {
        return $this->belongsTo(HatvpMandatElectif::class, 'mandat_id');
    }
}

