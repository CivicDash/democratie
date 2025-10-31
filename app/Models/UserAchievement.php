<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAchievement extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'achievement_id',
        'progress',
        'progress_target',
        'is_unlocked',
        'unlocked_at',
        'unlock_data',
        'is_notified',
        'is_shared',
    ];

    protected $casts = [
        'is_unlocked' => 'boolean',
        'unlocked_at' => 'datetime',
        'unlock_data' => 'array',
        'is_notified' => 'boolean',
        'is_shared' => 'boolean',
    ];

    /**
     * L'utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Le badge
     */
    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class);
    }

    /**
     * Mettre à jour la progression
     */
    public function updateProgress(int $value, ?array $data = null): bool
    {
        $this->progress = $value;
        
        // Débloquer si la cible est atteinte
        if ($value >= $this->progress_target && !$this->is_unlocked) {
            return $this->unlock($data);
        }
        
        return $this->save();
    }

    /**
     * Incrémenter la progression
     */
    public function incrementProgress(int $amount = 1, ?array $data = null): bool
    {
        return $this->updateProgress($this->progress + $amount, $data);
    }

    /**
     * Débloquer le badge
     */
    public function unlock(?array $data = null): bool
    {
        if ($this->is_unlocked) {
            return false; // Déjà débloqué
        }

        $this->is_unlocked = true;
        $this->unlocked_at = now();
        $this->progress = $this->progress_target;
        
        if ($data) {
            $this->unlock_data = array_merge($this->unlock_data ?? [], $data);
        }
        
        $saved = $this->save();
        
        if ($saved) {
            // Incrémenter le compteur global
            $this->achievement->incrementUnlockCount();
        }
        
        return $saved;
    }

    /**
     * Marquer comme notifié
     */
    public function markAsNotified(): bool
    {
        $this->is_notified = true;
        return $this->save();
    }

    /**
     * Marquer comme partagé
     */
    public function markAsShared(): bool
    {
        $this->is_shared = true;
        return $this->save();
    }

    /**
     * Obtenir le pourcentage de progression
     */
    public function getProgressPercentageAttribute(): int
    {
        if ($this->progress_target == 0) {
            return 100;
        }
        
        return min(100, (int)(($this->progress / $this->progress_target) * 100));
    }

    /**
     * Vérifier si le badge est proche d'être débloqué
     */
    public function isAlmostUnlocked(int $threshold = 80): bool
    {
        return !$this->is_unlocked && $this->progress_percentage >= $threshold;
    }

    /**
     * Scope : badges débloqués
     */
    public function scopeUnlocked($query)
    {
        return $query->where('is_unlocked', true);
    }

    /**
     * Scope : badges en cours
     */
    public function scopeInProgress($query)
    {
        return $query->where('is_unlocked', false)
            ->where('progress', '>', 0);
    }

    /**
     * Scope : badges non notifiés
     */
    public function scopeUnnotified($query)
    {
        return $query->where('is_unlocked', true)
            ->where('is_notified', false);
    }
}
