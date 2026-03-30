<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Activité professionnelle (5 dernières années) déclarée à la HATVP
 */
class HatvpActiviteProfessionnelle extends Model
{
    use HasFactory;

    protected $table = 'hatvp_activites_professionnelles';

    protected $fillable = [
        'declaration_id',
        'employeur',
        'description',
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

    public function remunerations(): HasMany
    {
        return $this->hasMany(HatvpRemunerationActivitePro::class, 'activite_id');
    }

    public function getTotalRemunerationsAttribute(): float
    {
        return $this->remunerations->sum('montant') ?? 0;
    }

    public function getRemunerationsParAnneeAttribute(): array
    {
        return $this->remunerations
            ->sortByDesc('annee')
            ->pluck('montant', 'annee')
            ->toArray();
    }
}
