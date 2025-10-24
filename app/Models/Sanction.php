<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Sanction (avertissement, mute, ban)
 * 
 * @property int $id
 * @property int $user_id
 * @property int $moderator_id
 * @property int|null $report_id
 * @property string $type warning|mute|ban
 * @property string $reason
 * @property \Illuminate\Support\Carbon $starts_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Sanction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'moderator_id',
        'report_id',
        'type',
        'reason',
        'starts_at',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Utilisateur sanctionné
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Modérateur qui a sanctionné
     */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    /**
     * Signalement à l'origine (si applicable)
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    /**
     * Vérifie si la sanction est expirée
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false; // Permanent
        }

        return now()->gt($this->expires_at);
    }

    /**
     * Vérifie si la sanction est permanente
     */
    public function isPermanent(): bool
    {
        return $this->expires_at === null;
    }

    /**
     * Vérifie si la sanction est en cours
     */
    public function isActive(): bool
    {
        return $this->is_active && !$this->isExpired();
    }

    /**
     * Révoque la sanction
     */
    public function revoke(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Scope: sanctions actives
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope: sanctions expirées
     */
    public function scopeExpired($query)
    {
        return $query->where('is_active', true)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }

    /**
     * Scope: sanctions permanentes
     */
    public function scopePermanent($query)
    {
        return $query->whereNull('expires_at');
    }

    /**
     * Scope: sanctions par type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: warnings
     */
    public function scopeWarnings($query)
    {
        return $query->where('type', 'warning');
    }

    /**
     * Scope: mutes
     */
    public function scopeMutes($query)
    {
        return $query->where('type', 'mute');
    }

    /**
     * Scope: bans
     */
    public function scopeBans($query)
    {
        return $query->where('type', 'ban');
    }
}

