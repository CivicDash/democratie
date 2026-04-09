<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Liste électorale pour les élections municipales
 *
 * Une liste regroupe plusieurs candidats qui se présentent ensemble
 * sous une même étiquette/bannière.
 */
class ListeElectorale extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'listes_electorales';

    protected $fillable = [
        'uuid',
        'commune_code_insee',
        'commune_nom',
        'departement_code',
        'nom_liste',
        'nuance_politique',
        'parti_principal',
        'slogan',
        'description',
        'logo_path',
        'couleur_principale',
        'email_contact',
        'telephone_contact',
        'site_web',
        'facebook_url',
        'twitter_url',
        'instagram_url',
        'youtube_url',
        'tiktok_url',
        'programme_pdf_path',
        'resume_programme',
        'statut',
        'motif_rejet',
        'validated_at',
        'validated_by',
        'created_by',
        'source',
        'numero_panneau',
        'tour',
        'libelle_abrege',
        'libelle_etendu',
        'liste_t1_id',
        'liste_civicdash_id',
    ];

    protected $casts = [
        'validated_at' => 'datetime',
    ];

    // =========================================================================
    // BOOT
    // =========================================================================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($liste) {
            if (empty($liste->uuid)) {
                $liste->uuid = Str::uuid();
            }
        });
    }

    // =========================================================================
    // RELATIONS
    // =========================================================================

    /**
     * Candidats de la liste
     */
    public function candidats(): HasMany
    {
        return $this->hasMany(CandidatMunicipal::class, 'liste_id')->orderBy('position');
    }

    /**
     * Tête de liste
     */
    public function teteDeListe(): BelongsTo
    {
        return $this->hasOne(CandidatMunicipal::class, 'liste_id')
            ->where('est_tete_de_liste', true);
    }

    /**
     * Créateur de la liste
     */
    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Validateur de la liste
     */
    public function validateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /**
     * Documents justificatifs
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
     * Commune associée
     */
    public function commune(): BelongsTo
    {
        return $this->belongsTo(FrenchPostalCode::class, 'commune_code_insee', 'insee_code');
    }

    /**
     * Résultats électoraux liés à cette liste
     */
    public function resultats(): HasMany
    {
        return $this->hasMany(ResultatListeMunicipale::class, 'liste_id');
    }

    /**
     * Liste T1 d'origine (pour les fusions T2)
     */
    public function listeT1(): BelongsTo
    {
        return $this->belongsTo(ListeElectorale::class, 'liste_t1_id');
    }

    /**
     * Listes T2 issues de cette liste T1
     */
    public function listesT2(): HasMany
    {
        return $this->hasMany(ListeElectorale::class, 'liste_t1_id');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopeValide($query)
    {
        return $query->where('statut', 'valide');
    }

    public function scopeEnAttente($query)
    {
        return $query->where('statut', 'en_attente');
    }

    public function scopeEnVerification($query)
    {
        return $query->where('statut', 'en_verification');
    }

    public function scopePourCommune($query, string $codeInsee)
    {
        return $query->where('commune_code_insee', $codeInsee);
    }

    public function scopePourDepartement($query, string $codeDept)
    {
        return $query->where('departement_code', $codeDept);
    }

    public function scopeOfficielles($query)
    {
        return $query->where('source', 'datagouv');
    }

    public function scopeCivicdash($query)
    {
        return $query->where('source', 'civicdash');
    }

    public function scopeTour($query, int $tour)
    {
        return $query->where('tour', $tour);
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * URL du logo
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        return asset('storage/'.$this->logo_path);
    }

    /**
     * URL du programme PDF
     */
    public function getProgrammePdfUrlAttribute(): ?string
    {
        if (! $this->programme_pdf_path) {
            return null;
        }

        return asset('storage/'.$this->programme_pdf_path);
    }

    /**
     * Nombre de candidats
     */
    public function getNombreCandidatsAttribute(): int
    {
        return $this->candidats()->count();
    }

    /**
     * Est validée
     */
    public function getEstValideeAttribute(): bool
    {
        return $this->statut === 'valide';
    }

    /**
     * Peut être modifiée
     */
    public function getPeutEtreModifieeAttribute(): bool
    {
        return in_array($this->statut, ['brouillon', 'documents_requis']);
    }

    /**
     * Statut formaté pour affichage
     */
    public function getStatutFormateAttribute(): string
    {
        return match ($this->statut) {
            'brouillon' => 'Brouillon',
            'en_attente' => 'En attente de validation',
            'documents_requis' => 'Documents requis',
            'en_verification' => 'En cours de vérification',
            'valide' => 'Validée',
            'rejete' => 'Rejetée',
            'suspendu' => 'Suspendue',
            default => $this->statut,
        };
    }

    /**
     * Couleur du badge statut
     */
    public function getStatutCouleurAttribute(): string
    {
        return match ($this->statut) {
            'brouillon' => 'gray',
            'en_attente' => 'yellow',
            'documents_requis' => 'orange',
            'en_verification' => 'blue',
            'valide' => 'green',
            'rejete' => 'red',
            'suspendu' => 'red',
            default => 'gray',
        };
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
        if ($this->youtube_url) {
            $reseaux['youtube'] = $this->youtube_url;
        }
        if ($this->tiktok_url) {
            $reseaux['tiktok'] = $this->tiktok_url;
        }

        return $reseaux;
    }

    // =========================================================================
    // MÉTHODES
    // =========================================================================

    /**
     * Soumettre la liste pour validation
     */
    public function soumettre(): bool
    {
        if ($this->statut !== 'brouillon' && $this->statut !== 'documents_requis') {
            return false;
        }

        $this->statut = 'en_attente';
        $this->save();

        $this->logModeration('soumission', 'brouillon', 'en_attente');

        return true;
    }

    /**
     * Valider la liste
     */
    public function valider(User $moderateur, ?string $commentaire = null): bool
    {
        $ancienStatut = $this->statut;

        $this->statut = 'valide';
        $this->validated_at = now();
        $this->validated_by = $moderateur->id;
        $this->save();

        $this->logModeration('validation', $ancienStatut, 'valide', $commentaire, $moderateur);

        return true;
    }

    /**
     * Rejeter la liste
     */
    public function rejeter(User $moderateur, string $motif): bool
    {
        $ancienStatut = $this->statut;

        $this->statut = 'rejete';
        $this->motif_rejet = $motif;
        $this->save();

        $this->logModeration('rejet', $ancienStatut, 'rejete', $motif, $moderateur);

        return true;
    }

    /**
     * Demander des documents supplémentaires
     */
    public function demanderDocuments(User $moderateur, string $commentaire): bool
    {
        $ancienStatut = $this->statut;

        $this->statut = 'documents_requis';
        $this->save();

        $this->logModeration('demande_documents', $ancienStatut, 'documents_requis', $commentaire, $moderateur);

        return true;
    }

    /**
     * Logger une action de modération
     */
    protected function logModeration(
        string $action,
        ?string $ancienStatut = null,
        ?string $nouveauStatut = null,
        ?string $commentaire = null,
        ?User $moderateur = null
    ): void {
        $this->moderationLogs()->create([
            'action' => $action,
            'ancien_statut' => $ancienStatut,
            'nouveau_statut' => $nouveauStatut,
            'commentaire' => $commentaire,
            'moderator_id' => $moderateur?->id ?? auth()->id(),
        ]);
    }
}
