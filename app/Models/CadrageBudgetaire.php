<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Cadrage budgétaire d'un candidat, ou ligne de référence officielle (sans candidat).
 *
 * Valider, publier, dépublier et supprimer passent par les actions génériques du
 * back-office (type `cadrage`) ; les règles de publication sont dans
 * ReglesCadrageBudgetaire, partagées avec le contrôle d'intégrité.
 */
class CadrageBudgetaire extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cadrages_budgetaires';

    public const NATURE_REFERENCE = 'reference_officielle';

    public const NATURES = [
        'premier_cadrage' => 'Premier cadrage',
        'budget_premiere_annee' => 'Budget de première année',
        'programme_chiffre_complet' => 'Programme chiffré complet',
        self::NATURE_REFERENCE => 'Référence officielle',
    ];

    protected $fillable = [
        'uuid', 'election', 'candidat_id', 'nature', 'titre', 'date_source',
        'ingestion_document_id', 'programme_document_id', 'rubriques_non_precisees',
        'note_methodologique', 'statut_validation', 'affiche_publiquement', 'valide_par', 'valide_at',
    ];

    protected $casts = [
        'date_source' => 'date',
        'rubriques_non_precisees' => 'array',
        'affiche_publiquement' => 'boolean',
        'valide_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $c) {
            if (empty($c->uuid)) {
                $c->uuid = (string) Str::uuid();
            }
        });
    }

    public function candidat(): BelongsTo
    {
        return $this->belongsTo(CandidatPresidentielle::class, 'candidat_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(IngestionDocument::class, 'ingestion_document_id');
    }

    public function programmeDocument(): BelongsTo
    {
        return $this->belongsTo(ProgrammeDocument::class, 'programme_document_id');
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(CadrageLigne::class, 'cadrage_id')->orderBy('ordre')->orderBy('id');
    }

    /** Le document source renseigné (prise de parole ou référentiel), s'il y en a un. */
    public function documentSource(): IngestionDocument|ProgrammeDocument|null
    {
        return $this->document ?? $this->programmeDocument;
    }

    public function estReference(): bool
    {
        return $this->candidat_id === null && $this->nature === self::NATURE_REFERENCE;
    }

    public function scopePublie($query)
    {
        return $query->where('statut_validation', 'valide')->where('affiche_publiquement', true);
    }

    public function scopeReference($query)
    {
        return $query->whereNull('candidat_id')->where('nature', self::NATURE_REFERENCE);
    }

    /** Ordre d'affichage et d'export : du plus récent au plus ancien, déterministe. */
    public function scopeChronologieInverse($query)
    {
        return $query->orderByDesc('date_source')->orderByDesc('id');
    }
}
