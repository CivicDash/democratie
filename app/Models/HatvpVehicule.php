<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Véhicule déclaré à la HATVP
 */
class HatvpVehicule extends Model
{
    use HasFactory;

    protected $table = 'hatvp_vehicules';

    protected $fillable = [
        'declaration_id',
        'nature',
        'marque',
        'annee_achat',
        'valeur_achat',
        'valeur',
        'conservee',
        'commentaire',
    ];

    protected $casts = [
        'annee_achat' => 'integer',
        'valeur_achat' => 'decimal:2',
        'valeur' => 'decimal:2',
        'conservee' => 'boolean',
    ];

    public function declaration(): BelongsTo
    {
        return $this->belongsTo(HatvpDeclaration::class, 'declaration_id');
    }

    public function getDescriptionAttribute(): string
    {
        return trim("{$this->marque} ({$this->annee_achat})");
    }
}
