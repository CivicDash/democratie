<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class MesureScrutinLien extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mesure_scrutin_liens';

    public const SCRUTIN_TYPES = ['scrutin_an', 'scrutin_senat', 'amendement_an', 'amendement_senat'];
    public const SENS_LIEN = ['coherent', 'contradictoire', 'contexte'];
    public const NIVEAUX = ['vote_personnel', 'position_groupe', 'absence'];

    protected $fillable = [
        'uuid', 'mesure_id', 'scrutin_type', 'scrutin_ref', 'sens_lien', 'niveau',
        'explication', 'scrutin_date', 'scrutin_intitule', 'scrutin_resultat', 'scrutin_url',
        'source_detection', 'detection_confidence',
        'statut_validation', 'affiche_publiquement', 'valide_par', 'valide_at',
    ];

    protected $casts = [
        'scrutin_date' => 'date',
        'affiche_publiquement' => 'boolean',
        'valide_at' => 'datetime',
        'detection_confidence' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $l) {
            if (empty($l->uuid)) {
                $l->uuid = (string) Str::uuid();
            }
        });
    }

    public function mesure(): BelongsTo
    {
        return $this->belongsTo(ProgrammeMesure::class, 'mesure_id');
    }

    /**
     * Publiable uniquement si validé, affiché ET explication rédigée
     * (règle bloquante §4 bis : jamais de lien sans explication humaine).
     */
    public function scopePublie($query)
    {
        return $query->where('statut_validation', 'valide')
            ->where('affiche_publiquement', true)
            ->whereNotNull('explication')
            ->where('explication', '!=', '');
    }

    public function estPubliable(): bool
    {
        return $this->statut_validation === 'valide'
            && $this->affiche_publiquement
            && filled($this->explication);
    }
}
