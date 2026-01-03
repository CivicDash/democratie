<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Liaison entre un Topic et un Élu (député, sénateur, maire)
 * 
 * Permet les interpellations citoyennes vers les élus
 */
class TopicElu extends Model
{
    use HasFactory;

    protected $table = 'topic_elus';

    protected $fillable = [
        'topic_id',
        'elu_type',
        'elu_id',
        'is_interpellation',
        'response_status',
        'notified_at',
        'email_sent_at',
        'viewed_at',
        'answered_at',
        'response_content',
    ];

    protected $casts = [
        'is_interpellation' => 'boolean',
        'notified_at' => 'datetime',
        'email_sent_at' => 'datetime',
        'viewed_at' => 'datetime',
        'answered_at' => 'datetime',
    ];

    // =========================================================================
    // RELATIONS
    // =========================================================================

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Récupérer l'élu associé (polymorphe manuel)
     */
    public function getEluAttribute(): ?Model
    {
        return match ($this->elu_type) {
            'depute' => ActeurAN::where('uid', $this->elu_id)->first(),
            'senateur' => Senateur::find($this->elu_id),
            'maire' => Maire::find($this->elu_id),
            default => null,
        };
    }

    /**
     * Nom complet de l'élu
     */
    public function getEluNomAttribute(): ?string
    {
        $elu = $this->elu;
        if (!$elu) return null;

        return match ($this->elu_type) {
            'depute' => $elu->nom_complet ?? $elu->prenom . ' ' . $elu->nom,
            'senateur' => $elu->nom_complet ?? $elu->prenom . ' ' . $elu->nom,
            'maire' => $elu->nom_complet ?? $elu->prenom . ' ' . $elu->nom,
            default => null,
        };
    }

    /**
     * Photo de l'élu
     */
    public function getEluPhotoAttribute(): ?string
    {
        $elu = $this->elu;
        if (!$elu) return null;

        return match ($this->elu_type) {
            'depute' => $elu->photo_url,
            'senateur' => $elu->photo_url,
            'maire' => $elu->photo_url,
            default => null,
        };
    }

    /**
     * Label du type d'élu
     */
    public function getEluTypeLabelAttribute(): string
    {
        return match ($this->elu_type) {
            'depute' => 'Député(e)',
            'senateur' => 'Sénateur/trice',
            'maire' => 'Maire',
            default => 'Élu(e)',
        };
    }

    /**
     * URL du profil de l'élu
     */
    public function getEluUrlAttribute(): ?string
    {
        return match ($this->elu_type) {
            'depute' => route('representants.deputes.show', $this->elu_id),
            'senateur' => route('representants.senateurs.show', $this->elu_id),
            'maire' => null, // TODO: route maire
            default => null,
        };
    }

    /**
     * Statut formaté
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->response_status) {
            'pending' => '⏳ En attente',
            'viewed' => '👁️ Consulté',
            'answered' => '✅ Répondu',
            'declined' => '❌ Décliné',
            default => 'Inconnu',
        };
    }

    /**
     * Couleur du statut
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->response_status) {
            'pending' => 'amber',
            'viewed' => 'sky',
            'answered' => 'emerald',
            'declined' => 'rose',
            default => 'slate',
        };
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopeInterpellations($query)
    {
        return $query->where('is_interpellation', true);
    }

    public function scopePending($query)
    {
        return $query->where('response_status', 'pending');
    }

    public function scopeAnswered($query)
    {
        return $query->where('response_status', 'answered');
    }

    public function scopeForElu($query, string $type, string $id)
    {
        return $query->where('elu_type', $type)->where('elu_id', $id);
    }

    // =========================================================================
    // ACTIONS
    // =========================================================================

    /**
     * Marquer comme vu
     */
    public function markAsViewed(): self
    {
        if ($this->response_status === 'pending') {
            $this->update([
                'response_status' => 'viewed',
                'viewed_at' => now(),
            ]);
        }
        return $this;
    }

    /**
     * Répondre à l'interpellation
     */
    public function answer(string $content): self
    {
        $this->update([
            'response_status' => 'answered',
            'answered_at' => now(),
            'response_content' => $content,
        ]);
        return $this;
    }

    /**
     * Décliner l'interpellation
     */
    public function decline(): self
    {
        $this->update([
            'response_status' => 'declined',
        ]);
        return $this;
    }
}
