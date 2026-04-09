<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Mandat électif déclaré à la HATVP
 */
class HatvpMandatElectif extends Model
{
    use HasFactory;

    protected $table = 'hatvp_mandats_electifs';

    protected $fillable = [
        'declaration_id',
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

    /**
     * Rémunérations polymorphiques
     */
    public function remunerations(): MorphMany
    {
        return $this->morphMany(HatvpRemuneration::class, 'remuneratable');
    }

    public function getEstActifAttribute(): bool
    {
        return $this->conservee && is_null($this->date_fin);
    }

    public function getTotalRemunerationsAttribute(): float
    {
        return $this->remunerations->sum('montant') ?? 0;
    }

    /**
     * Rémunérations par année
     */
    public function getRemunerationsParAnneeAttribute(): array
    {
        return $this->remunerations
            ->sortByDesc('annee')
            ->mapWithKeys(fn ($r) => [$r->annee => $r->montant])
            ->toArray();
    }
}
