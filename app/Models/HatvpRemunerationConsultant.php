<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rémunération annuelle d'une activité de consultant HATVP
 */
class HatvpRemunerationConsultant extends Model
{
    use HasFactory;

    protected $table = 'hatvp_remunerations_consultant';

    protected $fillable = [
        'activite_id',
        'annee',
        'montant',
        'brut_net',
    ];

    protected $casts = [
        'annee' => 'integer',
        'montant' => 'decimal:2',
    ];

    public function activite(): BelongsTo
    {
        return $this->belongsTo(HatvpActiviteConsultant::class, 'activite_id');
    }
}

