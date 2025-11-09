<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Amendement parlementaire déposé par un député ou sénateur
 * 
 * @property int $id
 * @property int $depute_senateur_id
 * @property string $numero
 * @property string|null $numero_long
 * @property \Carbon\Carbon $date_depot
 * @property string|null $legislature
 * @property string|null $session
 * @property string|null $titre
 * @property string|null $expose
 * @property string|null $dispositif
 * @property string|null $sort
 * @property string|null $sujet
 * @property int|null $proposition_loi_id
 * @property string|null $texte_loi_reference
 * @property string|null $url_nosdeputes
 * @property string|null $url_assemblee
 * @property array|null $cosignataires
 * @property int $nombre_cosignataires
 * @property string|null $groupe_politique
 */
class AmendementParlementaire extends Model
{
    use HasFactory;

    protected $fillable = [
        'depute_senateur_id',
        'numero',
        'numero_long',
        'date_depot',
        'legislature',
        'session',
        'titre',
        'expose',
        'dispositif',
        'sort',
        'sujet',
        'proposition_loi_id',
        'texte_loi_reference',
        'url_nosdeputes',
        'url_assemblee',
        'cosignataires',
        'nombre_cosignataires',
        'groupe_politique',
    ];

    protected $casts = [
        'date_depot' => 'date',
        'cosignataires' => 'array',
        'nombre_cosignataires' => 'integer',
        'proposition_loi_id' => 'integer',
    ];

    // ========================================================================
    // RELATIONS
    // ========================================================================

    /**
     * Député ou sénateur auteur de l'amendement
     */
    public function deputeSenateur(): BelongsTo
    {
        return $this->belongsTo(DeputeSenateur::class, 'depute_senateur_id');
    }

    /**
     * Proposition de loi liée (si applicable)
     */
    public function propositionLoi(): BelongsTo
    {
        return $this->belongsTo(PropositionLoi::class, 'proposition_loi_id');
    }

    // ========================================================================
    // SCOPES
    // ========================================================================

    /**
     * Amendements adoptés
     */
    public function scopeAdopte($query)
    {
        return $query->where('sort', 'adopte');
    }

    /**
     * Amendements rejetés
     */
    public function scopeRejete($query)
    {
        return $query->where('sort', 'rejete');
    }

    /**
     * Amendements retirés
     */
    public function scopeRetire($query)
    {
        return $query->where('sort', 'retire');
    }

    /**
     * Amendements tombés
     */
    public function scopeTombe($query)
    {
        return $query->where('sort', 'tombe');
    }

    /**
     * Amendements non votés
     */
    public function scopeNonVote($query)
    {
        return $query->where('sort', 'non-vote');
    }

    /**
     * Amendements co-signés (avec au moins 1 co-signataire)
     */
    public function scopeCosigne($query)
    {
        return $query->where('nombre_cosignataires', '>', 0);
    }

    /**
     * Recherche full-text
     */
    public function scopeSearch($query, string $search)
    {
        if (empty($search)) {
            return $query;
        }

        return $query->whereRaw(
            "to_tsvector('french', COALESCE(titre, '') || ' ' || COALESCE(expose, '') || ' ' || COALESCE(dispositif, '')) @@ plainto_tsquery('french', ?)",
            [$search]
        );
    }

    // ========================================================================
    // ACCESSEURS
    // ========================================================================

    /**
     * Label du sort de l'amendement
     */
    public function getSortLabelAttribute(): ?string
    {
        if (!$this->sort) {
            return null;
        }

        return match($this->sort) {
            'adopte' => 'Adopté',
            'rejete' => 'Rejeté',
            'retire' => 'Retiré',
            'tombe' => 'Tombé',
            'non-vote' => 'Non voté',
            default => ucfirst($this->sort),
        };
    }

    /**
     * Couleur badge selon le sort
     */
    public function getSortColorAttribute(): string
    {
        return match($this->sort) {
            'adopte' => 'green',
            'rejete' => 'red',
            'retire' => 'orange',
            'tombe' => 'gray',
            'non-vote' => 'blue',
            default => 'gray',
        };
    }

    /**
     * Indique si l'amendement a été co-signé
     */
    public function getIsCosigneAttribute(): bool
    {
        return $this->nombre_cosignataires > 0;
    }

    /**
     * Longueur du texte de l'amendement (en mots)
     */
    public function getLongueurTexteAttribute(): int
    {
        $texteComplet = implode(' ', [
            $this->expose ?? '',
            $this->dispositif ?? '',
        ]);
        
        return str_word_count(strip_tags($texteComplet));
    }
}

