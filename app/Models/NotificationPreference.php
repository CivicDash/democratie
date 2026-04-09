<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
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
        'group_similar_notifications' => 'boolean',
    ];

    /**
     * Mapping des catégories vers les champs de préférence
     */
    public const CATEGORY_MAPPING = [
        'interpellation' => 'notify_mention',
        'response' => 'notify_new_reply',
        'mention' => 'notify_mention',
        'vote' => 'notify_vote_on_my_proposal',
        'comment' => 'notify_new_reply',
        'moderation' => 'notify_system_announcement',
        'system' => 'notify_system_announcement',
    ];

    /**
     * Utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Vérifier si une catégorie est activée pour un canal
     */
    public function isEnabled(string $category, string $channel): bool
    {
        // Vérifier d'abord si le canal est activé
        if ($channel === 'site' && ! $this->channel_in_app) {
            return false;
        }
        if ($channel === 'email' && ! $this->channel_email) {
            return false;
        }

        // Vérifier la catégorie
        $field = self::CATEGORY_MAPPING[$category] ?? 'notify_system_announcement';

        return $this->{$field} ?? true;
    }

    /**
     * Obtenir ou créer les préférences par défaut
     */
    public static function getOrCreateForUser(int $userId): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            [
                'notify_new_reply' => true,
                'notify_new_vote_on_topic' => true,
                'notify_legislative_vote_result' => true,
                'notify_mention' => true,
                'notify_vote_on_my_proposal' => true,
                'notify_new_thematique_proposition' => false,
                'notify_system_announcement' => true,
                'notify_followed_topic_update' => true,
                'notify_followed_legislation_update' => true,
                'channel_in_app' => true,
                'channel_email' => true,
                'email_frequency' => 'instant',
                'group_similar_notifications' => false,
            ]
        );
    }
}
