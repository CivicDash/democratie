<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegalReference extends Model
{
    use HasFactory;

    protected $fillable = [
        'proposition_loi_id',
        'reference_text',
        'code_name',
        'legifrance_id',
        'article_current_text',
        'article_proposed_text',
        'context_description',
        'position_start',
        'position_end',
        'matched_text',
        'article_type',
        'jurisprudence_count',
        'related_articles_count',
        'is_range',
        'range_start',
        'range_end',
        'last_synced_at',
        'sync_success',
        'sync_error',
    ];

    protected $casts = [
        'article_current_text' => 'array',
        'article_proposed_text' => 'array',
        'is_range' => 'boolean',
        'sync_success' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Proposition de loi associée
     */
    public function propositionLoi(): BelongsTo
    {
        return $this->belongsTo(PropositionLoi::class);
    }

    /**
     * Jurisprudences liées
     */
    public function jurisprudences(): HasMany
    {
        return $this->hasMany(JurisprudenceLink::class);
    }

    /**
     * Obtenir le label du type d'article
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->article_type) {
            'legislative' => 'Législatif (Loi)',
            'regulatory' => 'Réglementaire',
            'decree' => 'Décret',
            'order' => 'Arrêté',
            default => 'Inconnu',
        };
    }

    /**
     * Obtenir l'URL Légifrance de l'article
     */
    public function getLegifranceUrlAttribute(): ?string
    {
        if (!$this->legifrance_id) {
            return null;
        }
        
        return "https://www.legifrance.gouv.fr/codes/article_lc/{$this->legifrance_id}";
    }

    /**
     * Vérifier si une synchronisation est nécessaire
     */
    public function needsSync(): bool
    {
        if (!$this->sync_success) {
            return true;
        }
        
        if (!$this->last_synced_at) {
            return true;
        }
        
        // Re-sync après 7 jours
        return $this->last_synced_at->diffInDays(now()) > 7;
    }

    /**
     * Marquer comme synchronisé
     */
    public function markSynced(bool $success = true, ?string $error = null): void
    {
        $this->update([
            'last_synced_at' => now(),
            'sync_success' => $success,
            'sync_error' => $error,
        ]);
    }

    /**
     * Scope: Références synchronisées avec succès
     */
    public function scopeSynced($query)
    {
        return $query->where('sync_success', true);
    }

    /**
     * Scope: Références nécessitant une synchro
     */
    public function scopeNeedsSync($query)
    {
        return $query->where(function ($q) {
            $q->where('sync_success', false)
              ->orWhere('last_synced_at', '<', now()->subDays(7))
              ->orWhereNull('last_synced_at');
        });
    }

    /**
     * Scope: Par proposition
     */
    public function scopeForProposition($query, int $propositionId)
    {
        return $query->where('proposition_loi_id', $propositionId);
    }

    /**
     * Scope: Avec jurisprudence
     */
    public function scopeWithJurisprudence($query)
    {
        return $query->where('jurisprudence_count', '>', 0);
    }
}
