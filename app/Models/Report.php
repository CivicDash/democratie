<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Signalement de contenu
 * 
 * @property int $id
 * @property int $reporter_id
 * @property string $reportable_type
 * @property int $reportable_id
 * @property string $reason spam|harassment|misinformation|off_topic|inappropriate|other
 * @property string|null $description
 * @property string $status pending|reviewing|resolved|dismissed
 * @property int|null $moderator_id
 * @property string|null $moderator_notes
 * @property \Illuminate\Support\Carbon|null $resolved_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'reporter_id',
        'reportable_type',
        'reportable_id',
        'reason',
        'description',
        'status',
        'moderator_id',
        'moderator_notes',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    /**
     * Utilisateur qui a signalé
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /**
     * Modérateur assigné
     */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    /**
     * Contenu signalé (polymorphic)
     */
    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Assigne un modérateur
     */
    public function assignModerator(User $moderator): void
    {
        $this->update([
            'moderator_id' => $moderator->id,
            'status' => 'reviewing',
        ]);
    }

    /**
     * Résout le signalement
     */
    public function resolve(string $notes = null): void
    {
        $this->update([
            'status' => 'resolved',
            'moderator_notes' => $notes,
            'resolved_at' => now(),
        ]);
    }

    /**
     * Rejette le signalement
     */
    public function dismiss(string $notes = null): void
    {
        $this->update([
            'status' => 'dismissed',
            'moderator_notes' => $notes,
            'resolved_at' => now(),
        ]);
    }

    /**
     * Scope: signalements en attente
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: signalements en cours de review
     */
    public function scopeReviewing($query)
    {
        return $query->where('status', 'reviewing');
    }

    /**
     * Scope: signalements résolus
     */
    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    /**
     * Scope: signalements rejetés
     */
    public function scopeDismissed($query)
    {
        return $query->where('status', 'dismissed');
    }

    /**
     * Scope: signalements par raison
     */
    public function scopeByReason($query, string $reason)
    {
        return $query->where('reason', $reason);
    }

    /**
     * Scope: signalements d'un modérateur
     */
    public function scopeByModerator($query, int $moderatorId)
    {
        return $query->where('moderator_id', $moderatorId);
    }

    /**
     * Scope: signalements sans modérateur
     */
    public function scopeUnassigned($query)
    {
        return $query->whereNull('moderator_id');
    }
}

