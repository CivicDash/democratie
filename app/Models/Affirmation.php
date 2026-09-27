<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * Une affirmation entendue dans le débat public, confrontée aux données (« Ce qu'on
 * entend »). Le verdict ne porte que sur la partie mesurable ; la part de jugement est
 * laissée au lecteur, explicitement.
 */
class Affirmation extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Échelle du cadre éditorial, dans l'ordre de la jauge publique. `inverifiable` est
     * hors de l'échelle : ce n'est pas un degré d'accord, c'est l'absence de mesure.
     */
    public const VERDICTS = [
        'confirme' => 'Les données vont dans ce sens',
        'plutot_confirme' => 'Plutôt vrai, avec réserves',
        'nuance' => 'Vrai ou faux selon ce qu\'on mesure',
        'plutot_infirme' => 'Plutôt faux',
        'infirme' => 'Les données contredisent l\'affirmation',
        'inverifiable' => 'Aucune mesure fiable n\'existe',
    ];

    /** Regroupement du contrôle de symétrie (annexe C) — usage interne. */
    public const FAMILLES_VERDICT = [
        'confirme' => 'confirme', 'plutot_confirme' => 'confirme',
        'nuance' => 'nuance',
        'plutot_infirme' => 'infirme', 'infirme' => 'infirme',
        'inverifiable' => 'inverifiable',
    ];

    /** Coloration perçue dans le débat public. INTERNE : jamais exportée. */
    public const COLORATIONS = [
        'gauche' => 'Plutôt à gauche',
        'droite' => 'Plutôt à droite',
        'transversale' => 'Transversale',
    ];

    protected $fillable = [
        'uuid', 'election', 'slug', 'enonce', 'resume', 'theme_id', 'part_de_valeur',
        'derniere_verification', 'coloration_percue', 'statut_validation',
        'affiche_publiquement', 'valide_par', 'valide_at', 'commentaire_validation',
    ];

    protected $casts = [
        'part_de_valeur' => 'boolean',
        'affiche_publiquement' => 'boolean',
        'derniere_verification' => 'date',
        'valide_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $a) {
            $a->uuid ??= (string) \Illuminate\Support\Str::uuid();
        });
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(ProgrammeTheme::class, 'theme_id');
    }

    public function themesSecondaires(): BelongsToMany
    {
        return $this->belongsToMany(ProgrammeTheme::class, 'affirmation_theme', 'affirmation_id', 'theme_id');
    }

    public function verdicts(): HasMany
    {
        return $this->hasMany(AffirmationVerdict::class)->orderBy('ordre')->orderBy('id');
    }

    public function constats(): HasMany
    {
        return $this->hasMany(AffirmationConstat::class)->orderBy('ordre')->orderBy('id');
    }

    public function sources(): HasMany
    {
        return $this->hasMany(AffirmationSource::class)->orderBy('id');
    }

    public function graphiques(): HasMany
    {
        return $this->hasMany(AffirmationGraphique::class)->orderBy('ordre')->orderBy('id');
    }

    /**
     * Les phrases qui paraissent sur le site. Une fiche peut être publiée avant que tous ses
     * chiffres soient sourcés : les phrases non vérifiées restent masquées, et la fiche dit
     * combien il en reste (aSourcer). Les réserves, elles, doivent toutes être vérifiées
     * (ReglesAffirmation) : une fiche ne paraît jamais sans elles.
     */
    public function constatsAffiches(): Collection
    {
        return $this->constats->filter->estAffiche()->values();
    }

    /** Un graphique ne paraît qu'avec la phrase qui le porte. */
    public function graphiquesAffiches(): Collection
    {
        $affiches = $this->constatsAffiches()->pluck('id')->all();

        return $this->graphiques->filter(fn ($g) => in_array($g->constat_id, $affiches, true))->values();
    }

    /** @return array<string, int> phrases masquées, par section */
    public function aSourcer(): array
    {
        return $this->constats->reject->estAffiche()->countBy('section')->all();
    }

    public function scopePublie($query)
    {
        return $query->where('statut_validation', 'valide')->where('affiche_publiquement', true);
    }
}
