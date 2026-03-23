<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EluFollower extends Model
{
    protected $fillable = [
        'user_id',
        'elu_type',
        'elu_id',
        'elu_nom',
        'elu_photo_url',
        'elu_groupe',
        'elu_circonscription',
        'notify_votes',
        'notify_interventions',
        'notify_amendements',
        'notify_propositions',
        'notify_rapports',
        'notify_commissions',
        'notify_actualites',
        'notify_site',
        'notify_email',
        'email_frequency',
        'followed_at',
        'last_activity_notified_at',
        'notifications_received',
    ];

    protected $casts = [
        'notify_votes' => 'boolean',
        'notify_interventions' => 'boolean',
        'notify_amendements' => 'boolean',
        'notify_propositions' => 'boolean',
        'notify_rapports' => 'boolean',
        'notify_commissions' => 'boolean',
        'notify_actualites' => 'boolean',
        'notify_site' => 'boolean',
        'notify_email' => 'boolean',
        'followed_at' => 'datetime',
        'last_activity_notified_at' => 'datetime',
        'notifications_received' => 'integer',
    ];

    /**
     * Types d'élus supportés
     */
    public const ELU_TYPES = [
        'depute' => [
            'label' => 'Député(e)',
            'icon' => '🔵',
            'color' => 'blue',
        ],
        'senateur' => [
            'label' => 'Sénateur/Sénatrice',
            'icon' => '🔴',
            'color' => 'red',
        ],
        'maire' => [
            'label' => 'Maire',
            'icon' => '🏛️',
            'color' => 'amber',
        ],
        'ministre' => [
            'label' => 'Ministre',
            'icon' => '⚜️',
            'color' => 'purple',
        ],
    ];

    /**
     * Types d'activités notifiables
     */
    public const ACTIVITY_TYPES = [
        'votes' => [
            'label' => 'Votes en séance',
            'description' => 'Scrutins publics à l\'Assemblée ou au Sénat',
            'icon' => '🗳️',
        ],
        'interventions' => [
            'label' => 'Interventions',
            'description' => 'Questions au gouvernement, débats',
            'icon' => '🎤',
        ],
        'amendements' => [
            'label' => 'Amendements',
            'description' => 'Amendements déposés ou co-signés',
            'icon' => '📝',
        ],
        'propositions' => [
            'label' => 'Propositions de loi',
            'description' => 'Propositions de loi déposées',
            'icon' => '📜',
        ],
        'rapports' => [
            'label' => 'Rapports',
            'description' => 'Rapports parlementaires',
            'icon' => '📊',
        ],
        'commissions' => [
            'label' => 'Commissions',
            'description' => 'Activité en commission',
            'icon' => '👥',
        ],
        'actualites' => [
            'label' => 'Actualités',
            'description' => 'Changements de fonction, groupe politique',
            'icon' => '📰',
        ],
    ];

    /**
     * L'utilisateur qui suit
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Notifications d'activités envoyées
     */
    public function activityNotifications(): HasMany
    {
        return $this->hasMany(EluActivityNotification::class);
    }

    /**
     * Obtenir l'élu suivi (polymorphique)
     */
    public function getElu(): ?Model
    {
        return match($this->elu_type) {
            'depute' => ActeurAN::find($this->elu_id),
            'senateur' => Senateur::find($this->elu_id),
            'maire' => Maire::find($this->elu_id),
            'ministre' => PoliticalPerson::find($this->elu_id),
            default => null,
        };
    }

    /**
     * URL du profil de l'élu
     */
    public function getEluUrlAttribute(): ?string
    {
        return match($this->elu_type) {
            'depute' => route('representants.deputes.show', $this->elu_id),
            'senateur' => route('representants.senateurs.show', $this->elu_id),
            'maire' => route('elus.public-profile', ['type' => 'maire', 'ref' => $this->elu_id]),
            'ministre' => route('gouvernement.ministre', $this->elu_id),
            default => null,
        };
    }

    /**
     * Label du type d'élu
     */
    public function getEluTypeLabelAttribute(): string
    {
        return self::ELU_TYPES[$this->elu_type]['label'] ?? 'Élu(e)';
    }

    /**
     * Vérifier si un type d'activité doit être notifié
     */
    public function shouldNotify(string $activityType): bool
    {
        $field = "notify_{$activityType}";
        return $this->$field ?? false;
    }

    /**
     * Vérifier si une activité a déjà été notifiée
     */
    public function wasNotified(string $activityType, string $activityId): bool
    {
        return $this->activityNotifications()
            ->where('activity_type', $activityType)
            ->where('activity_id', $activityId)
            ->exists();
    }

    /**
     * Marquer une activité comme notifiée
     */
    public function markAsNotified(string $activityType, string $activityId, ?int $notificationId = null, bool $emailSent = false): EluActivityNotification
    {
        $notification = $this->activityNotifications()->create([
            'activity_type' => $activityType,
            'activity_id' => $activityId,
            'notification_id' => $notificationId,
            'email_sent' => $emailSent,
        ]);

        $this->increment('notifications_received');
        $this->update(['last_activity_notified_at' => now()]);

        return $notification;
    }

    /**
     * Scope: suivis d'un utilisateur
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: followers d'un élu
     */
    public function scopeForElu($query, string $eluType, string $eluId)
    {
        return $query->where('elu_type', $eluType)->where('elu_id', $eluId);
    }

    /**
     * Scope: avec notifications email activées
     */
    public function scopeWithEmailNotifications($query)
    {
        return $query->where('notify_email', true);
    }

    /**
     * Scope: intéressés par un type d'activité
     */
    public function scopeInterestedIn($query, string $activityType)
    {
        $field = "notify_{$activityType}";
        return $query->where($field, true);
    }
}
