<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Élément de passif (dette) déclaré à la HATVP
 */
class HatvpPassif extends Model
{
    use HasFactory;

    protected $table = 'hatvp_passif';

    protected $fillable = [
        'declaration_id',
        'nom_creancier',
        'nature',
        'date_passif',
        'objet_dette',
        'montant',
        'duree',
        'restant_du',
        'mensualite',
        'conservee',
        'commentaire',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'restant_du' => 'decimal:2',
        'mensualite' => 'decimal:2',
        'conservee' => 'boolean',
    ];

    public function declaration(): BelongsTo
    {
        return $this->belongsTo(HatvpDeclaration::class, 'declaration_id');
    }

    public function getPourcentageRembourseAttribute(): float
    {
        if (!$this->montant || $this->montant == 0) {
            return 0;
        }
        
        $rembourse = $this->montant - ($this->restant_du ?? 0);
        return round(($rembourse / $this->montant) * 100, 1);
    }
}

