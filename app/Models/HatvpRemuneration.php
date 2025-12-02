<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Rémunération annuelle polymorphique HATVP
 * 
 * Peut être liée à :
 * - HatvpMandatElectif
 * - HatvpActiviteProfessionnelle
 * - HatvpActiviteConsultant
 * - HatvpParticipationDirigeante
 */
class HatvpRemuneration extends Model
{
    use HasFactory;

    protected $table = 'hatvp_remunerations';

    protected $fillable = [
        'remuneratable_type',
        'remuneratable_id',
        'annee',
        'montant',
        'brut_net',
    ];

    protected $casts = [
        'annee' => 'integer',
        'montant' => 'decimal:2',
    ];

    /**
     * Relation polymorphique vers l'entité rémunérée
     */
    public function remuneratable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Formatte le montant pour l'affichage
     */
    public function getMontantFormateAttribute(): string
    {
        if ($this->montant === null) {
            return '-';
        }
        return number_format($this->montant, 0, ',', ' ') . ' €';
    }
}

