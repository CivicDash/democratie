<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Document justificatif pour une candidature
 *
 * Peut être attaché à une ListeElectorale ou un CandidatMunicipal.
 */
class CandidatureDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'candidatures_documents';

    protected $fillable = [
        'uuid',
        'documentable_type',
        'documentable_id',
        'type',
        'nom_fichier',
        'chemin_fichier',
        'mime_type',
        'taille_octets',
        'description',
        'date_document',
        'numero_reference',
        'statut_verification',
        'commentaire_verification',
        'verified_at',
        'verified_by',
        'uploaded_by',
    ];

    protected $casts = [
        'date_document' => 'date',
        'verified_at' => 'datetime',
    ];

    // =========================================================================
    // BOOT
    // =========================================================================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($doc) {
            if (empty($doc->uuid)) {
                $doc->uuid = Str::uuid();
            }
        });
    }

    // =========================================================================
    // RELATIONS
    // =========================================================================

    /**
     * Entité parente (Liste ou Candidat)
     */
    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Utilisateur qui a uploadé
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Modérateur qui a vérifié
     */
    public function verificateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopeRecepisse($query)
    {
        return $query->where('type', 'recepisse_prefecture');
    }

    public function scopeValide($query)
    {
        return $query->where('statut_verification', 'valide');
    }

    public function scopeEnAttente($query)
    {
        return $query->where('statut_verification', 'en_attente');
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * URL du fichier
     */
    public function getUrlAttribute(): string
    {
        return asset('storage/'.$this->chemin_fichier);
    }

    /**
     * Taille formatée
     */
    public function getTailleFormateeAttribute(): string
    {
        $bytes = $this->taille_octets;

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2).' Mo';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2).' Ko';
        }

        return $bytes.' octets';
    }

    /**
     * Type formaté
     */
    public function getTypeFormateAttribute(): string
    {
        return match ($this->type) {
            'recepisse_prefecture' => 'Récépissé de dépôt en préfecture',
            'piece_identite' => 'Pièce d\'identité',
            'attestation_eligibilite' => 'Attestation d\'éligibilité',
            'declaration_candidature' => 'Déclaration de candidature',
            'photo_officielle' => 'Photo officielle',
            'programme_pdf' => 'Programme électoral (PDF)',
            'autre' => 'Autre document',
            default => $this->type,
        };
    }

    /**
     * Statut formaté
     */
    public function getStatutFormateAttribute(): string
    {
        return match ($this->statut_verification) {
            'en_attente' => 'En attente de vérification',
            'en_cours' => 'Vérification en cours',
            'valide' => 'Validé',
            'invalide' => 'Invalide',
            'expire' => 'Expiré',
            default => $this->statut_verification,
        };
    }

    /**
     * Couleur du statut
     */
    public function getStatutCouleurAttribute(): string
    {
        return match ($this->statut_verification) {
            'en_attente' => 'yellow',
            'en_cours' => 'blue',
            'valide' => 'green',
            'invalide' => 'red',
            'expire' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Est un document image
     */
    public function getEstImageAttribute(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Est un PDF
     */
    public function getEstPdfAttribute(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    // =========================================================================
    // MÉTHODES
    // =========================================================================

    /**
     * Valider le document
     */
    public function valider(User $moderateur, ?string $commentaire = null): bool
    {
        $this->statut_verification = 'valide';
        $this->verified_at = now();
        $this->verified_by = $moderateur->id;
        $this->commentaire_verification = $commentaire;

        return $this->save();
    }

    /**
     * Invalider le document
     */
    public function invalider(User $moderateur, string $raison): bool
    {
        $this->statut_verification = 'invalide';
        $this->verified_at = now();
        $this->verified_by = $moderateur->id;
        $this->commentaire_verification = $raison;

        return $this->save();
    }
}
