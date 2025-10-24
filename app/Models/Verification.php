<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vérification d'un document par journaliste/ONG
 * 
 * @property int $id
 * @property int $document_id
 * @property int $verifier_id
 * @property string $status verified|rejected|needs_review
 * @property string|null $notes Commentaires du vérificateur
 * @property array|null $metadata Données supplémentaires
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Verification extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'verifier_id',
        'status',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Document vérifié
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Vérificateur (journalist/ong/admin)
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verifier_id');
    }

    /**
     * Vérifie si le document est vérifié
     */
    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }

    /**
     * Vérifie si le document est rejeté
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Vérifie si le document nécessite une review
     */
    public function needsReview(): bool
    {
        return $this->status === 'needs_review';
    }

    /**
     * Scope: vérifications approuvées
     */
    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    /**
     * Scope: vérifications rejetées
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope: vérifications nécessitant une review
     */
    public function scopeNeedsReview($query)
    {
        return $query->where('status', 'needs_review');
    }

    /**
     * Scope: vérifications par vérificateur
     */
    public function scopeByVerifier($query, int $verifierId)
    {
        return $query->where('verifier_id', $verifierId);
    }

    /**
     * Scope: vérifications d'un document
     */
    public function scopeForDocument($query, int $documentId)
    {
        return $query->where('document_id', $documentId);
    }
}

