<?php

namespace App\Models;

use App\Support\Locuteur;
use App\Support\Porteur;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class IngestionProposition extends Model
{
    use HasFactory;

    protected $table = 'ingestion_propositions';

    public const TYPES = ['mesure', 'position', 'revirement', 'declaration'];
    public const STATUTS = ['detecte', 'validee', 'rattachee', 'rejetee'];

    /**
     * Seuls statuts affichés publiquement pour le candidat et son équipe : une proposition
     * détectée n'a été relue par personne.
     */
    public const STATUTS_PUBLICS = ['validee', 'rattachee'];

    protected $fillable = [
        'uuid', 'document_id', 'candidat_slug', 'candidat_id', 'theme_slug', 'theme_id',
        'locuteur', 'role_locuteur', 'qualite_locuteur',
        'type', 'resume_propose', 'citation_verbatim', 'timestamp_ou_paragraphe',
        'source_url', 'confiance', 'verbatim_verifie', 'statut', 'affiche_publiquement', 'mesure_id',
        'valide_par', 'valide_at', 'raw_llm_output',
    ];

    protected $casts = [
        'confiance' => 'decimal:2',
        'verbatim_verifie' => 'boolean',
        'affiche_publiquement' => 'boolean',
        'valide_at' => 'datetime',
        'raw_llm_output' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $p) {
            if (empty($p->uuid)) {
                $p->uuid = (string) Str::uuid();
            }
        });
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(IngestionDocument::class, 'document_id');
    }

    public function candidat(): BelongsTo
    {
        return $this->belongsTo(CandidatPresidentielle::class, 'candidat_id');
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(ProgrammeTheme::class, 'theme_id');
    }

    public function mesure(): BelongsTo
    {
        return $this->belongsTo(ProgrammeMesure::class, 'mesure_id');
    }

    public function scopeEnAttente($query)
    {
        return $query->where('statut', 'detecte');
    }

    /*
    |--------------------------------------------------------------------------
    | Qui parle ? Le critère est écrit ici, et seulement ici.
    |--------------------------------------------------------------------------
    | « Entourage » = la parole d'un parti : un locuteur de qualité `parti`, ou n'importe
    | quelle proposition d'un document porté par un parti ou un groupe parlementaire.
    | L'équipe de campagne n'en fait PAS partie : elle parle pour le candidat.
    */

    /** Le candidat et son équipe de campagne : ce qui peut paraître sur sa fiche. */
    public function scopeDuCandidat($query)
    {
        return $query
            ->where(fn ($q) => $q->whereNull('qualite_locuteur')->orWhere('qualite_locuteur', '!=', Locuteur::PARTI))
            ->whereDoesntHave('document', fn ($d) => $d->whereIn('porteur_type', Porteur::ENTOURAGE));
    }

    /** La parole d'un parti : jamais une mesure, seulement « Autour du candidat ». */
    public function scopeEntourage($query)
    {
        return $query->where(fn ($q) => $q->where('qualite_locuteur', Locuteur::PARTI)
            ->orWhereHas('document', fn ($d) => $d->whereIn('porteur_type', Porteur::ENTOURAGE)));
    }

    /** Statuts relus : seuls ceux-là paraissent pour le candidat et son équipe. */
    public function scopePublique($query)
    {
        return $query->whereIn('statut', self::STATUTS_PUBLICS);
    }

    /** Publiées dans « Autour du candidat ». */
    public function scopePublieAutour($query)
    {
        return $query->entourage()->where('statut', 'validee')->where('affiche_publiquement', true);
    }

    public function estEntourage(): bool
    {
        return $this->qualite_locuteur === Locuteur::PARTI
            || Porteur::estEntourage($this->document?->porteur_type);
    }

    public function estDeLEquipe(): bool
    {
        return $this->qualite_locuteur === Locuteur::EQUIPE;
    }

    /** Champs d'attribution, tels qu'ils partent dans l'export. */
    public function attributionExport(): array
    {
        return [
            'locuteur' => $this->locuteur,
            'role_locuteur' => $this->role_locuteur,
            'qualite_locuteur' => $this->qualite_locuteur,
        ];
    }
}
