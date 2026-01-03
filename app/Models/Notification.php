<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'category',
        'title',
        'message',
        'icon',
        'link',
        'data',
        'priority',
        'read_at',
        'acknowledged_at',
        'actioned_at',
        'action_type',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'actioned_at' => 'datetime',
        'priority' => 'integer',
    ];

    /**
     * Utilisateur destinataire
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Catégories de notifications disponibles
     */
    public const CATEGORIES = [
        'interpellation' => [
            'label' => 'Interpellations',
            'description' => 'Quand quelqu\'un vous interpelle sur un sujet',
            'icon' => '📢',
        ],
        'response' => [
            'label' => 'Réponses',
            'description' => 'Quand un élu répond à une interpellation',
            'icon' => '💬',
        ],
        'mention' => [
            'label' => 'Mentions',
            'description' => 'Quand vous êtes mentionné dans un débat',
            'icon' => '@',
        ],
        'vote' => [
            'label' => 'Votes',
            'description' => 'Activité sur vos propositions (votes, seuils atteints)',
            'icon' => '🗳️',
        ],
        'comment' => [
            'label' => 'Commentaires',
            'description' => 'Nouveaux commentaires sur vos sujets',
            'icon' => '💭',
        ],
        'moderation' => [
            'label' => 'Modération',
            'description' => 'Actions de modération sur vos contenus',
            'icon' => '🛡️',
        ],
        'system' => [
            'label' => 'Système',
            'description' => 'Informations importantes du site',
            'icon' => '⚙️',
        ],
    ];

    /**
     * Canaux de notification
     */
    public const CHANNELS = [
        'site' => [
            'label' => 'Notifications site',
            'description' => 'Affichées dans le centre de notifications',
        ],
        'email' => [
            'label' => 'Notifications email',
            'description' => 'Envoyées par email',
        ],
    ];

    /**
     * Scope: non lues
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope: pour un utilisateur
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: lues
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope: non acquittées
     */
    public function scopeUnacknowledged($query)
    {
        return $query->whereNull('acknowledged_at');
    }

    /**
     * Scope: non traitées
     */
    public function scopePending($query)
    {
        return $query->whereNull('actioned_at');
    }

    /**
     * Scope: par catégorie
     */
    public function scopeOfCategory($query, string $category)
    {
        return $query->where('type', 'like', "%\\{$category}%");
    }

    /**
     * Marquer comme lue
     */
    public function markAsRead(): self
    {
        if (is_null($this->read_at)) {
            $this->update(['read_at' => now()]);
        }
        return $this;
    }

    /**
     * Marquer comme non lue
     */
    public function markAsUnread(): self
    {
        $this->update(['read_at' => null]);
        return $this;
    }

    /**
     * Acquitter la notification
     */
    public function acknowledge(): self
    {
        $this->update([
            'read_at' => $this->read_at ?? now(),
            'acknowledged_at' => now(),
        ]);
        return $this;
    }

    /**
     * Marquer comme traitée
     */
    public function markAsActioned(string $actionType = 'completed'): self
    {
        $this->update([
            'read_at' => $this->read_at ?? now(),
            'acknowledged_at' => $this->acknowledged_at ?? now(),
            'actioned_at' => now(),
            'action_type' => $actionType,
        ]);
        return $this;
    }

    /**
     * Vérifier si la notification est lue
     */
    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    /**
     * Vérifier si acquittée
     */
    public function isAcknowledged(): bool
    {
        return !is_null($this->acknowledged_at);
    }

    /**
     * Vérifier si traitée
     */
    public function isActioned(): bool
    {
        return !is_null($this->actioned_at);
    }

    /**
     * Obtenir le lien d'action
     */
    public function getActionUrlAttribute(): ?string
    {
        return $this->link ?? $this->data['action_url'] ?? null;
    }

    /**
     * Accesseur: time_ago
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }
}
