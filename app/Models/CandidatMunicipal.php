<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Candidat municipal individuel
 *
 * Représente une personne candidate sur une liste électorale.
 */
class CandidatMunicipal extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'candidats_municipaux';

    protected $fillable = [
        'uuid',
        'liste_id',
        'user_id',
        'civilite',
        'nom',
        'prenom',
        'nom_usage',
        'date_naissance',
        'lieu_naissance',
        'profession',
        'position',
        'est_tete_de_liste',
        'fonction_visee',
        'photo_path',
        'biographie',
        'parcours',
        'engagements',
        'email',
        'telephone',
        'facebook_url',
        'twitter_url',
        'instagram_url',
        'linkedin_url',
        'statut',
        'source',
        'sexe',
        'sortant',
        'elu',
        'maire_id',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'est_tete_de_liste' => 'boolean',
        'parcours' => 'array',
        'engagements' => 'array',
        'sortant' => 'boolean',
        'elu' => 'boolean',
    ];

    // =========================================================================
    // BOOT
    // =========================================================================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($candidat) {
            if (empty($candidat->uuid)) {
                $candidat->uuid = Str::uuid();
            }
        });
    }

    // =========================================================================
    // RELATIONS
    // =========================================================================

    /**
     * Liste électorale
     */
    public function liste(): BelongsTo
    {
        return $this->belongsTo(ListeElectorale::class, 'liste_id');
    }

    /**
     * Compte utilisateur lié
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Documents du candidat
     */
    public function documents(): MorphMany
    {
        return $this->morphMany(CandidatureDocument::class, 'documentable');
    }

    /**
     * Logs de modération
     */
    public function moderationLogs(): MorphMany
    {
        return $this->morphMany(CandidatureModerationLog::class, 'moderatable');
    }

    /**
     * Fiche Maire liée (si candidat est un maire sortant ou nouveau)
     */
    public function maire(): BelongsTo
    {
        return $this->belongsTo(Maire::class);
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopeTeteDeListe($query)
    {
        return $query->where('est_tete_de_liste', true);
    }

    public function scopeActif($query)
    {
        return $query->where('statut', 'actif');
    }

    public function scopeElus($query)
    {
        return $query->where('elu', true);
    }

    public function scopeSortants($query)
    {
        return $query->where('sortant', true);
    }

    public function scopeOfficiels($query)
    {
        return $query->where('source', 'datagouv');
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Nom complet
     */
    public function getNomCompletAttribute(): string
    {
        $nom = $this->nom_usage ?? $this->nom;

        return trim("{$this->prenom} {$nom}");
    }

    /**
     * Nom complet avec civilité
     */
    public function getNomCompletCiviliteAttribute(): string
    {
        $civilite = $this->civilite ? "{$this->civilite} " : '';

        return $civilite.$this->nom_complet;
    }

    /**
     * Âge du candidat
     */
    public function getAgeAttribute(): ?int
    {
        if (! $this->date_naissance) {
            return null;
        }

        return $this->date_naissance->age;
    }

    /**
     * URL de la photo
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo_path) {
            return null;
        }

        return asset('storage/'.$this->photo_path);
    }

    /**
     * Photo par défaut (initiales)
     */
    public function getInitialesAttribute(): string
    {
        $prenom = mb_substr($this->prenom, 0, 1);
        $nom = mb_substr($this->nom, 0, 1);

        return mb_strtoupper("{$prenom}{$nom}");
    }

    /**
     * Label de la fonction visée
     */
    public function getFonctionLabelAttribute(): string
    {
        if ($this->fonction_visee) {
            return $this->fonction_visee;
        }

        if ($this->est_tete_de_liste) {
            return 'Candidat(e) au poste de Maire';
        }

        return "Colistier(ère) - Position {$this->position}";
    }

    /**
     * Réseaux sociaux non vides
     */
    public function getReseauxSociauxAttribute(): array
    {
        $reseaux = [];

        if ($this->facebook_url) {
            $reseaux['facebook'] = $this->facebook_url;
        }
        if ($this->twitter_url) {
            $reseaux['twitter'] = $this->twitter_url;
        }
        if ($this->instagram_url) {
            $reseaux['instagram'] = $this->instagram_url;
        }
        if ($this->linkedin_url) {
            $reseaux['linkedin'] = $this->linkedin_url;
        }

        return $reseaux;
    }

    /**
     * Est éligible (18 ans minimum, français ou UE)
     */
    public function getEstEligibleAttribute(): bool
    {
        if (! $this->date_naissance) {
            return true; // On ne peut pas vérifier
        }

        // Doit avoir 18 ans au jour du premier tour (15 mars 2026)
        $dateElection = \Carbon\Carbon::create(2026, 3, 15);

        return $this->date_naissance->diffInYears($dateElection) >= 18;
    }
}
