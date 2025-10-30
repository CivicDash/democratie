<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modèle pour les propositions et projets de loi
 * 
 * @property int $id
 * @property string $source (assemblee|senat)
 * @property int $legislature
 * @property string $numero
 * @property string $titre
 * @property string|null $resume
 * @property string|null $texte_integral
 * @property string $statut
 * @property string|null $theme
 * @property \Carbon\Carbon|null $date_depot
 * @property \Carbon\Carbon|null $date_adoption
 * @property \Carbon\Carbon|null $date_promulgation
 * @property array|null $auteurs
 * @property array|null $etapes
 * @property array|null $votes_resultats
 * @property string|null $url_externe
 * @property string|null $url_pdf
 * @property \Carbon\Carbon|null $fetched_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class PropositionLoi extends Model
{
    use HasFactory;

    protected $table = 'propositions_loi';

    protected $fillable = [
        'source',
        'legislature',
        'numero',
        'titre',
        'resume',
        'texte_integral',
        'statut',
        'theme',
        'date_depot',
        'date_adoption',
        'date_promulgation',
        'auteurs',
        'etapes',
        'votes_resultats',
        'url_externe',
        'url_pdf',
        'fetched_at',
    ];

    protected $casts = [
        'legislature' => 'integer',
        'auteurs' => 'array',
        'etapes' => 'array',
        'votes_resultats' => 'array',
        'date_depot' => 'date',
        'date_adoption' => 'date',
        'date_promulgation' => 'date',
        'fetched_at' => 'datetime',
    ];

    // ========================================================================
    // RELATIONS
    // ========================================================================

    public function amendements(): HasMany
    {
        return $this->hasMany(Amendement::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(VoteLegislatif::class);
    }

    // ========================================================================
    // SCOPES
    // ========================================================================

    public function scopeAssemblee($query)
    {
        return $query->where('source', 'assemblee');
    }

    public function scopeSenat($query)
    {
        return $query->where('source', 'senat');
    }

    public function scopeLegislature($query, int $legislature)
    {
        return $query->where('legislature', $legislature);
    }

    public function scopeEnCours($query)
    {
        return $query->where('statut', 'en_cours');
    }

    public function scopeAdoptees($query)
    {
        return $query->whereIn('statut', ['adoptee', 'promulguee']);
    }

    public function scopeRejetees($query)
    {
        return $query->where('statut', 'rejetee');
    }

    public function scopeRecentes($query, int $days = 30)
    {
        return $query->where('date_depot', '>=', now()->subDays($days));
    }

    public function scopeByTheme($query, string $theme)
    {
        return $query->where('theme', 'like', "%{$theme}%");
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('titre', 'like', "%{$search}%")
              ->orWhere('resume', 'like', "%{$search}%")
              ->orWhere('numero', 'like', "%{$search}%");
        });
    }

    // ========================================================================
    // ACCESSEURS
    // ========================================================================

    public function getSourceLabelAttribute(): string
    {
        return match($this->source) {
            'assemblee' => 'Assemblée nationale',
            'senat' => 'Sénat',
            default => $this->source,
        };
    }

    public function getStatutLabelAttribute(): string
    {
        return match($this->statut) {
            'en_cours' => 'En cours',
            'adoptee' => 'Adoptée',
            'rejetee' => 'Rejetée',
            'promulguee' => 'Promulguée (loi)',
            'retiree' => 'Retirée',
            default => $this->statut,
        };
    }

    public function getStatutBadgeAttribute(): string
    {
        return match($this->statut) {
            'en_cours' => 'info',
            'adoptee' => 'success',
            'rejetee' => 'danger',
            'promulguee' => 'primary',
            'retiree' => 'secondary',
            default => 'secondary',
        };
    }

    public function getNbAmendementsAttribute(): int
    {
        return $this->amendements()->count();
    }

    public function getNbVotesAttribute(): int
    {
        return $this->votes()->count();
    }

    public function getEstAdopteeAttribute(): bool
    {
        return in_array($this->statut, ['adoptee', 'promulguee']);
    }

    public function getEstRecente($query, int $days = 7): bool
    {
        return $this->date_depot && $this->date_depot->diffInDays(now()) <= $days;
    }

    // ========================================================================
    // MÉTHODES
    // ========================================================================

    /**
     * Retourne la durée du processus législatif en jours
     */
    public function getDureeProcessus(): ?int
    {
        if (!$this->date_depot) {
            return null;
        }

        $dateFinale = $this->date_adoption ?? $this->date_promulgation ?? now();
        
        return $this->date_depot->diffInDays($dateFinale);
    }

    /**
     * Retourne l'auteur principal (premier auteur)
     */
    public function getAuteurPrincipal(): ?string
    {
        if (empty($this->auteurs) || !is_array($this->auteurs)) {
            return null;
        }

        $premier = $this->auteurs[0];
        
        return is_string($premier) ? $premier : ($premier['nom'] ?? null);
    }

    /**
     * Retourne le nombre d'auteurs
     */
    public function getNbAuteurs(): int
    {
        return is_array($this->auteurs) ? count($this->auteurs) : 0;
    }

    /**
     * Retourne l'étape actuelle du processus
     */
    public function getEtapeActuelle(): ?array
    {
        if (empty($this->etapes) || !is_array($this->etapes)) {
            return null;
        }

        // Dernière étape
        return end($this->etapes);
    }

    /**
     * Formate pour l'API
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'source' => $this->source,
            'source_label' => $this->source_label,
            'legislature' => $this->legislature,
            'numero' => $this->numero,
            'titre' => $this->titre,
            'resume' => $this->resume,
            'statut' => $this->statut,
            'statut_label' => $this->statut_label,
            'statut_badge' => $this->statut_badge,
            'theme' => $this->theme,
            'date_depot' => $this->date_depot?->format('Y-m-d'),
            'date_adoption' => $this->date_adoption?->format('Y-m-d'),
            'date_promulgation' => $this->date_promulgation?->format('Y-m-d'),
            'auteur_principal' => $this->getAuteurPrincipal(),
            'nb_auteurs' => $this->getNbAuteurs(),
            'nb_amendements' => $this->nb_amendements,
            'nb_votes' => $this->nb_votes,
            'duree_processus_jours' => $this->getDureeProcessus(),
            'etape_actuelle' => $this->getEtapeActuelle(),
            'url_externe' => $this->url_externe,
            'url_pdf' => $this->url_pdf,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}

