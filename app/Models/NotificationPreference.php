<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'notify_new_reply',
        'notify_new_vote_on_topic',
        'notify_legislative_vote_result',
        'notify_mention',
        'notify_vote_on_my_proposal',
        'notify_new_thematique_proposition',
        'notify_system_announcement',
        'notify_followed_topic_update',
        'notify_followed_legislation_update',
        'channel_in_app',
        'channel_email',
        'email_frequency',
        'quiet_hours_start',
        'quiet_hours_end',
        'group_similar_notifications',
    ];

    protected $casts = [
        'notify_new_reply' => 'boolean',
        'notify_new_vote_on_topic' => 'boolean',
        'notify_legislative_vote_result' => 'boolean',
        'notify_mention' => 'boolean',
        'notify_vote_on_my_proposal' => 'boolean',
        'notify_new_thematique_proposition' => 'boolean',
        'notify_system_announcement' => 'boolean',
        'notify_followed_topic_update' => 'boolean',
        'notify_followed_legislation_update' => 'boolean',
        'channel_in_app' => 'boolean',
        'channel_email' => 'boolean',
    ];

    /**
     * Relation : l'utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtenir les préférences d'un utilisateur (ou créer les valeurs par défaut)
     */
    public static function getForUser(int $userId): self
    {
        return self::firstOrCreate(['user_id' => $userId]);
    }

    /**
     * Vérifier si un type de notification est activé
     */
    public function isEnabled(string $type): bool
    {
        $field = 'notify_' . $type;
        return $this->$field ?? false;
    }

    /**
     * Vérifier si on est dans les heures calmes
     */
    public function isQuietHours(): bool
    {
        if (!$this->quiet_hours_start || !$this->quiet_hours_end) {
            return false;
        }

        $now = now()->format('H:i:s');
        $start = $this->quiet_hours_start;
        $end = $this->quiet_hours_end;

        // Gérer le cas où les heures calmes passent minuit
        if ($start < $end) {
            return $now >= $start && $now <= $end;
        } else {
            return $now >= $start || $now <= $end;
        }
    }

    /**
     * Vérifier si on peut envoyer une notification in-app
     */
    public function canSendInApp(): bool
    {
        return $this->channel_in_app && !$this->isQuietHours();
    }

    /**
     * Vérifier si on peut envoyer une notification par email
     */
    public function canSendEmail(): bool
    {
        return $this->channel_email && $this->email_frequency === 'instant' && !$this->isQuietHours();
    }

    /**
     * Obtenir toutes les préférences sous forme de tableau
     */
    public function toPreferencesArray(): array
    {
        return [
            'notifications' => [
                'new_reply' => $this->notify_new_reply,
                'new_vote_on_topic' => $this->notify_new_vote_on_topic,
                'legislative_vote_result' => $this->notify_legislative_vote_result,
                'mention' => $this->notify_mention,
                'vote_on_my_proposal' => $this->notify_vote_on_my_proposal,
                'new_thematique_proposition' => $this->notify_new_thematique_proposition,
                'system_announcement' => $this->notify_system_announcement,
                'followed_topic_update' => $this->notify_followed_topic_update,
                'followed_legislation_update' => $this->notify_followed_legislation_update,
            ],
            'channels' => [
                'in_app' => $this->channel_in_app,
                'email' => $this->channel_email,
            ],
            'email_frequency' => $this->email_frequency,
            'quiet_hours' => [
                'start' => $this->quiet_hours_start,
                'end' => $this->quiet_hours_end,
            ],
            'group_similar' => $this->group_similar_notifications,
        ];
    }
}
