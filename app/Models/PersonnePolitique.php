<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PersonnePolitique extends Model
{
    use HasFactory;

    protected $table = 'personnes_politiques';

    protected $fillable = [
        'slug',
        'civilite',
        'prenom',
        'nom',
        'date_naissance',
        'lieu_naissance',
        'date_deces',
        'profession',
        'biographie',
        'parti_politique',
        'nuance_politique',
        'photo_url',
        'photo_officielle_url',
        'wikipedia_url',
        'wikipedia_extract',
        'twitter_url',
        'facebook_url',
        'linkedin_url',
        'instagram_url',
        'mastodon_url',
        'bluesky_url',
        'youtube_url',
        'tiktok_url',
        'site_web',
        'url_hatvp',
        'hatvp_type_mandat',
        'uid_an',
        'uid_senat',
        'maire_id',
        'wikidata_id',
        'wikipedia_last_sync',
        'metadata',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'date_deces' => 'date',
        'wikipedia_last_sync' => 'datetime',
        'metadata' => 'array',
    ];

    protected $appends = ['nom_complet', 'age', 'photo'];

    /**
     * Boot du modèle
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($personne) {
            if (empty($personne->slug)) {
                $personne->slug = Str::slug($personne->prenom.'-'.$personne->nom);
            }
        });
    }

    /**
     * Relations
     */

    // Tous les postes occupés par cette personne
    public function postes(): HasMany
    {
        return $this->hasMany(PosteMinisteriel::class, 'personne_id')
            ->orderBy('date_debut', 'desc');
    }

    // Poste actuel
    public function posteActuel(): HasMany
    {
        return $this->hasMany(PosteMinisteriel::class, 'personne_id')
            ->where('actif', true);
    }

    // Lien vers le député (si applicable)
    public function depute(): BelongsTo
    {
        return $this->belongsTo(ActeurAN::class, 'uid_an', 'uid');
    }

    // Lien vers le sénateur (si applicable)
    public function senateur(): BelongsTo
    {
        return $this->belongsTo(Senateur::class, 'uid_senat', 'matricule');
    }

    // Lien vers le maire (si applicable)
    public function maire(): BelongsTo
    {
        return $this->belongsTo(Maire::class, 'maire_id');
    }

    public function affairesJudiciaires(): HasMany
    {
        return $this->hasMany(AffaireJudiciaire::class, 'personne_politique_id');
    }

    /** Candidatures présidentielle (une par élection). */
    public function candidaturesPresidentielle(): HasMany
    {
        return $this->hasMany(CandidatPresidentielle::class, 'personne_politique_id');
    }

    /** Événements de parcours (mandats, fonctions, postes, engagements). */
    public function parcoursEvenements(): HasMany
    {
        return $this->hasMany(ParcoursEvenement::class, 'personne_politique_id');
    }

    public function affairesPubliques(): HasMany
    {
        return $this->affairesJudiciaires()->publiques();
    }

    /**
     * Toutes les affaires publiques, toutes casquettes confondues
     * (personne_politique + depute + senateur + maire)
     */
    public function toutesAffairesPubliques()
    {
        return AffaireJudiciaire::publiques()
            ->where(function ($q) {
                $q->where('personne_politique_id', $this->id);

                if ($this->uid_an) {
                    $q->orWhere('acteur_an_uid', $this->uid_an);
                }
                if ($this->uid_senat) {
                    $q->orWhere('senateur_matricule', $this->uid_senat);
                }
                if ($this->maire_id) {
                    $q->orWhere('maire_id', $this->maire_id);
                }
            })
            ->orderByRaw("CASE statut_judiciaire
                WHEN 'condamne_definitif' THEN 1
                WHEN 'condamne_appel' THEN 2
                WHEN 'condamne_premiere_instance' THEN 3
                WHEN 'mis_en_examen' THEN 4
                ELSE 5 END");
    }

    /**
     * Declarations HATVP multi-criteres :
     * match par nom/prenom + par uid depute/senateur si applicable
     */
    public function declarationsHatvp()
    {
        return HatvpDeclaration::where(function ($q) {
            $q->where(function ($q2) {
                $q2->where('nom', 'ILIKE', $this->nom)
                    ->where('prenom', 'ILIKE', $this->prenom);
            });

            if ($this->uid_an) {
                $q->orWhere(function ($q2) {
                    $q2->where('parlementaire_type', 'depute')
                        ->where('parlementaire_id', $this->uid_an);
                });
            }

            if ($this->uid_senat) {
                $q->orWhere(function ($q2) {
                    $q2->where('parlementaire_type', 'senateur')
                        ->where('parlementaire_id', $this->uid_senat);
                });
            }
        })->orderByDesc('date_depot');
    }

    /**
     * Accessors
     */
    public function getNbAffairesAttribute(): int
    {
        return $this->toutesAffairesPubliques()->count();
    }

    public function getACondamnationDefinitiveAttribute(): bool
    {
        return $this->toutesAffairesPubliques()
            ->where('statut_judiciaire', 'condamne_definitif')
            ->exists();
    }

    public function getNomCompletAttribute(): string
    {
        $civilite = $this->civilite ? $this->civilite.' ' : '';

        return trim($civilite.$this->prenom.' '.$this->nom);
    }

    public function getAgeAttribute(): ?int
    {
        if (! $this->date_naissance) {
            return null;
        }
        $endDate = $this->date_deces ?? now();

        return (int) $this->date_naissance->diffInYears($endDate);
    }

    public function getPhotoAttribute(): ?string
    {
        // Priorité : photo officielle > photo URL > photo Wikipedia du député/sénateur
        return $this->photo_officielle_url
            ?? $this->photo_url
            ?? $this->depute?->photo_officielle
            ?? $this->senateur?->photo_wikipedia_url;
    }

    /**
     * Scopes
     */
    public function scopeActifs($query)
    {
        return $query->whereHas('posteActuel');
    }

    public function scopeAvecPoste($query, string $type)
    {
        return $query->whereHas('postes', fn ($q) => $q->where('type_fonction', $type));
    }

    /**
     * Méthodes
     */

    // Récupérer l'historique des postes formaté
    public function getHistoriquePostes(): array
    {
        return $this->postes->map(function ($poste) {
            $periode = $poste->date_debut->format('d/m/Y');
            $periode .= $poste->date_fin ? ' - '.$poste->date_fin->format('d/m/Y') : ' - en cours';

            return [
                'gouvernement' => $poste->gouvernement->nom_complet,
                'fonction' => $poste->fonction,
                'ministere' => $poste->ministere?->nom,
                'periode' => $periode,
                'duree' => $poste->duree_fonction,
            ];
        })->toArray();
    }

    // Créer ou trouver une personne à partir d'un nom
    public static function findOrCreateByName(string $prenom, string $nom, array $attributes = []): self
    {
        $slug = Str::slug($prenom.'-'.$nom);

        return static::firstOrCreate(
            ['slug' => $slug],
            array_merge([
                'prenom' => $prenom,
                'nom' => $nom,
            ], $attributes)
        );
    }
}
