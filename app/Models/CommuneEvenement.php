<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

class CommuneEvenement extends Model
{
    use HasUuids, Searchable, SoftDeletes;

    protected $fillable = [
        'commune_page_id',
        'auteur_id',
        'titre',
        'slug',
        'description',
        'image_path',
        'lieu_nom',
        'lieu_adresse',
        'lieu_latitude',
        'lieu_longitude',
        'date_debut',
        'date_fin',
        'journee_entiere',
        'recurrence',
        'inscription_requise',
        'places_max',
        'inscrits_count',
        'inscription_limite',
        'inscription_infos',
        'categorie',
        'publie',
        'annule',
    ];

    protected $casts = [
        'lieu_latitude' => 'decimal:7',
        'lieu_longitude' => 'decimal:7',
        'date_debut' => 'datetime',
        'date_fin' => 'datetime',
        'journee_entiere' => 'boolean',
        'inscription_requise' => 'boolean',
        'places_max' => 'integer',
        'inscrits_count' => 'integer',
        'inscription_limite' => 'datetime',
        'publie' => 'boolean',
        'annule' => 'boolean',
    ];

    public const CATEGORIES = [
        'ceremonie' => 'Cérémonie',
        'culture' => 'Culture',
        'sport' => 'Sport',
        'marche' => 'Marché',
        'reunion' => 'Réunion publique',
        'atelier' => 'Atelier',
        'fete' => 'Fête',
        'environnement' => 'Environnement',
        'solidarite' => 'Solidarité',
        'autre' => 'Autre',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($evenement) {
            if (empty($evenement->slug)) {
                $evenement->slug = Str::slug($evenement->titre);
            }
        });
    }

    // ========================================================================
    // RELATIONS
    // ========================================================================

    public function communePage(): BelongsTo
    {
        return $this->belongsTo(CommunePage::class);
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auteur_id');
    }

    public function inscriptions(): HasMany
    {
        return $this->hasMany(CommuneEvenementInscription::class, 'evenement_id');
    }

    public function commentaires(): MorphMany
    {
        return $this->morphMany(CommuneCommentaire::class, 'commentable');
    }

    public function reactions(): MorphMany
    {
        return $this->morphMany(CommuneReaction::class, 'reactable');
    }

    // ========================================================================
    // SCOPES
    // ========================================================================

    public function scopePublies($query)
    {
        return $query->where('publie', true)->where('annule', false);
    }

    public function scopeAVenir($query)
    {
        return $query->where('date_debut', '>=', now());
    }

    public function scopePasses($query)
    {
        return $query->where('date_debut', '<', now());
    }

    public function scopeParCategorie($query, string $categorie)
    {
        return $query->where('categorie', $categorie);
    }

    public function scopeProchains($query)
    {
        return $query->aVenir()->orderBy('date_debut');
    }

    // ========================================================================
    // ACCESSEURS
    // ========================================================================

    public function getCategorieLabelAttribute(): string
    {
        return self::CATEGORIES[$this->categorie] ?? ucfirst($this->categorie);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? asset('storage/'.$this->image_path) : null;
    }

    public function getEstPasseAttribute(): bool
    {
        $fin = $this->date_fin ?? $this->date_debut;

        return $fin->isPast();
    }

    public function getEstCompletAttribute(): bool
    {
        if (! $this->inscription_requise || ! $this->places_max) {
            return false;
        }

        return $this->inscrits_count >= $this->places_max;
    }

    public function getPlacesRestantesAttribute(): ?int
    {
        if (! $this->places_max) {
            return null;
        }

        return max(0, $this->places_max - $this->inscrits_count);
    }

    public function getInscriptionOuverteAttribute(): bool
    {
        if (! $this->inscription_requise) {
            return false;
        }

        if ($this->est_complet) {
            return false;
        }

        if ($this->inscription_limite && $this->inscription_limite->isPast()) {
            return false;
        }

        return ! $this->est_passe && ! $this->annule;
    }

    public function getLieuCompletAttribute(): ?string
    {
        return implode(', ', array_filter([
            $this->lieu_nom,
            $this->lieu_adresse,
        ])) ?: null;
    }

    // ========================================================================
    // MÉTHODES
    // ========================================================================

    public function publier(): void
    {
        $this->update(['publie' => true]);
    }

    public function annuler(): void
    {
        $this->update(['annule' => true]);
    }

    public function inscrireUtilisateur(User $user, int $nbPersonnes = 1, ?string $commentaire = null): CommuneEvenementInscription
    {
        $statut = $this->est_complet ? 'liste_attente' : 'inscrit';

        $inscription = $this->inscriptions()->create([
            'user_id' => $user->id,
            'nb_personnes' => $nbPersonnes,
            'commentaire' => $commentaire,
            'statut' => $statut,
        ]);

        if ($statut === 'inscrit') {
            $this->increment('inscrits_count', $nbPersonnes);
        }

        return $inscription;
    }

    public function estInscrit(User $user): bool
    {
        return $this->inscriptions()
            ->where('user_id', $user->id)
            ->where('statut', '!=', 'annule')
            ->exists();
    }

    // ========================================================================
    // SCOUT / MEILISEARCH
    // ========================================================================

    public function shouldBeSearchable(): bool
    {
        return $this->publie && ! $this->annule;
    }

    public function toSearchableArray(): array
    {
        $page = $this->communePage;

        return [
            'id' => $this->id,
            'titre' => $this->titre,
            'description' => $this->description ? \Illuminate\Support\Str::limit(strip_tags($this->description), 300) : null,
            'categorie' => $this->categorie,
            'lieu_nom' => $this->lieu_nom,
            'commune_code_insee' => $page?->code_insee,
            'commune_nom' => $page?->ville?->nom,
            'date_debut' => $this->date_debut?->timestamp,
        ];
    }
}
