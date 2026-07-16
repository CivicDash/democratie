<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Argument = FAIT SOURCÉ AUTONOME (plan §4, v1.2). Il ne porte plus ni mesure_id ni sens :
 * il est réutilisable sur plusieurs mesures de plusieurs candidats, dans des sens opposés.
 * Le sens (pour|contre) et le lien à une mesure vivent dans `argument_mesure_liens`.
 * Peut appartenir à une `controverse` (question de fond regroupant plusieurs arguments).
 */
class Argument extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'arguments';

    public const TYPES = [
        'chiffrage', 'precedent_historique', 'avis_institution',
        'etude', 'comparaison_internationale', 'faisabilite_juridique',
    ];

    protected $fillable = [
        'uuid', 'controverse_id', 'titre', 'contenu', 'type_argument',
        'statut_validation', 'affiche_publiquement', 'ordre',
        'valide_par', 'valide_at', 'commentaire_validation',
    ];

    protected $casts = [
        'affiche_publiquement' => 'boolean',
        'valide_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $a) {
            if (empty($a->uuid)) {
                $a->uuid = (string) Str::uuid();
            }
        });
    }

    public function controverse(): BelongsTo
    {
        return $this->belongsTo(Controverse::class, 'controverse_id');
    }

    public function liens(): HasMany
    {
        return $this->hasMany(ArgumentMesureLien::class, 'argument_id');
    }

    public function mesures(): BelongsToMany
    {
        return $this->belongsToMany(ProgrammeMesure::class, 'argument_mesure_liens', 'argument_id', 'mesure_id')
            ->withPivot(['id', 'sens', 'note_contextuelle', 'statut_validation', 'affiche_publiquement'])
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }

    public function sources(): HasMany
    {
        return $this->hasMany(ArgumentSource::class, 'argument_id');
    }

    public function scopePublie($query)
    {
        return $query->where('statut_validation', 'valide')->where('affiche_publiquement', true);
    }

    /** Le fait est validé (une seule validation : le sens « contre » relève de la liaison). */
    public function estValide(): bool
    {
        return $this->statut_validation === 'valide' && $this->valide_par !== null;
    }

    /** Au moins une source de fiabilité haute ou moyenne (exigence de publication). */
    public function aSourceFiable(): bool
    {
        return $this->sources->contains(fn ($s) => in_array($s->fiabilite, ['haute', 'moyenne'], true));
    }
}
