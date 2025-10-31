<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'icon',
        'link',
        'data',
        'read_at',
        'priority',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Types de notifications disponibles
     */
    public const TYPE_NEW_THEMATIQUE = 'new_thematique';
    public const TYPE_NEW_GROUPE = 'new_groupe';
    public const TYPE_NEW_VOTE = 'new_vote';
    public const TYPE_NEW_LEGISLATION = 'new_legislation';
    public const TYPE_VOTE_RESULT = 'vote_result';
    public const TYPE_SYSTEM = 'system';
    public const TYPE_ALERT = 'alert';
    
    // Nouveaux types
    public const TYPE_NEW_REPLY = 'new_reply'; // Nouvelle réponse sur conversation suivie
    public const TYPE_NEW_VOTE_ON_TOPIC = 'new_vote_on_topic'; // Nouveau vote sur sujet d'intérêt
    public const TYPE_LEGISLATIVE_VOTE_RESULT = 'legislative_vote_result'; // Résultat vote législatif suivi
    public const TYPE_MENTION = 'mention'; // Mention dans un commentaire
    public const TYPE_VOTE_ON_MY_PROPOSAL = 'vote_on_my_proposal'; // Vote sur ma proposition citoyenne
    public const TYPE_NEW_THEMATIQUE_PROPOSITION = 'new_thematique_proposition'; // Nouvelle proposition dans thématique suivie
    public const TYPE_FOLLOWED_TOPIC_UPDATE = 'followed_topic_update'; // Mise à jour sujet suivi
    public const TYPE_FOLLOWED_LEGISLATION_UPDATE = 'followed_legislation_update'; // Mise à jour législation suivie

    /**
     * Priorités
     */
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    /**
     * Relation : l'utilisateur qui reçoit la notification
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Marquer comme lue
     */
    public function markAsRead(): void
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Marquer comme non lue
     */
    public function markAsUnread(): void
    {
        $this->update(['read_at' => null]);
    }

    /**
     * Vérifier si la notification est lue
     */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Vérifier si la notification est non lue
     */
    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    /**
     * Scope : notifications non lues
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope : notifications lues
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope : par type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope : par priorité
     */
    public function scopeWithPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope : récentes (dernières 30 jours)
     */
    public function scopeRecent($query)
    {
        return $query->where('created_at', '>=', now()->subDays(30));
    }

    /**
     * Obtenir l'icône par défaut selon le type
     */
    public function getDefaultIcon(): string
    {
        return match($this->type) {
            self::TYPE_NEW_THEMATIQUE => '🏷️',
            self::TYPE_NEW_GROUPE => '🏛️',
            self::TYPE_NEW_VOTE => '🗳️',
            self::TYPE_NEW_LEGISLATION => '📜',
            self::TYPE_VOTE_RESULT => '📊',
            self::TYPE_ALERT => '⚠️',
            self::TYPE_SYSTEM => '⚙️',
            // Nouveaux types
            self::TYPE_NEW_REPLY => '💬',
            self::TYPE_NEW_VOTE_ON_TOPIC => '👍',
            self::TYPE_LEGISLATIVE_VOTE_RESULT => '🏛️',
            self::TYPE_MENTION => '👤',
            self::TYPE_VOTE_ON_MY_PROPOSAL => '⭐',
            self::TYPE_NEW_THEMATIQUE_PROPOSITION => '📢',
            self::TYPE_FOLLOWED_TOPIC_UPDATE => '🔔',
            self::TYPE_FOLLOWED_LEGISLATION_UPDATE => '📋',
            default => '🔔',
        };
    }

    /**
     * Obtenir la couleur selon la priorité
     */
    public function getPriorityColor(): string
    {
        return match($this->priority) {
            self::PRIORITY_LOW => 'gray',
            self::PRIORITY_NORMAL => 'blue',
            self::PRIORITY_HIGH => 'orange',
            self::PRIORITY_URGENT => 'red',
            default => 'blue',
        };
    }
}
