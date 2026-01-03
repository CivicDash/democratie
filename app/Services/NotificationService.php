<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationEmail;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NotificationService
{
    /**
     * Envoyer une notification à un utilisateur
     */
    public function notify(
        User $user,
        string $category,
        string $title,
        string $message,
        ?string $actionUrl = null,
        ?string $icon = null,
        array $extraData = []
    ): ?Notification {
        // Vérifier si l'utilisateur accepte les notifications site pour cette catégorie
        $siteEnabled = $this->isChannelEnabled($user, 'site', $category);
        $emailEnabled = $this->isChannelEnabled($user, 'email', $category);

        if (!$siteEnabled && !$emailEnabled) {
            return null; // L'utilisateur a tout désactivé pour cette catégorie
        }

        $notification = null;

        // Créer la notification in-app si activée
        if ($siteEnabled) {
            $notification = Notification::create([
                'user_id' => $user->id,
                'type' => $this->getCategoryNotificationType($category),
                'category' => $category,
                'title' => $title,
                'message' => $message,
                'icon' => $icon ?? $this->getDefaultIcon($category),
                'link' => $actionUrl,
                'data' => $extraData,
                'priority' => $this->getPriority($category),
            ]);
        }

        // Programmer l'email si activé
        if ($emailEnabled) {
            $this->queueEmail($user, $notification, $category, $title, $message, $actionUrl);
        }

        return $notification;
    }

    /**
     * Envoyer une notification à plusieurs utilisateurs
     */
    public function notifyMany(
        array $users,
        string $category,
        string $title,
        string $message,
        ?string $actionUrl = null,
        ?string $icon = null,
        array $extraData = []
    ): int {
        $count = 0;
        
        foreach ($users as $user) {
            if ($this->notify($user, $category, $title, $message, $actionUrl, $icon, $extraData)) {
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * Vérifier si un canal est activé pour une catégorie
     */
    public function isChannelEnabled(User $user, string $channel, string $category): bool
    {
        $preference = NotificationPreference::getOrCreateForUser($user->id);
        return $preference->isEnabled($category, $channel);
    }

    /**
     * Mettre à jour les préférences de notification
     */
    public function updatePreferences(User $user, array $preferences): void
    {
        $pref = NotificationPreference::getOrCreateForUser($user->id);
        
        foreach ($preferences as $p) {
            $channel = $p['channel'] ?? null;
            $category = $p['category'] ?? null;
            $enabled = $p['enabled'] ?? true;

            // Mettre à jour le canal
            if ($channel === 'site') {
                $pref->channel_in_app = $enabled;
            } elseif ($channel === 'email') {
                $pref->channel_email = $enabled;
            }

            // Mettre à jour la catégorie
            if ($category && isset(NotificationPreference::CATEGORY_MAPPING[$category])) {
                $field = NotificationPreference::CATEGORY_MAPPING[$category];
                $pref->{$field} = $enabled;
            }
        }
        
        $pref->save();
    }

    /**
     * Obtenir toutes les préférences d'un utilisateur
     */
    public function getPreferences(User $user): array
    {
        $pref = NotificationPreference::getOrCreateForUser($user->id);
        $preferences = [];

        foreach (Notification::CATEGORIES as $category => $info) {
            foreach (Notification::CHANNELS as $channel => $channelInfo) {
                $preferences[] = [
                    'channel' => $channel,
                    'channel_label' => $channelInfo['label'],
                    'category' => $category,
                    'category_label' => $info['label'],
                    'category_description' => $info['description'],
                    'icon' => $info['icon'],
                    'enabled' => $pref->isEnabled($category, $channel),
                ];
            }
        }

        return $preferences;
    }

    /**
     * Obtenir les notifications d'un utilisateur
     */
    public function getNotifications(User $user, int $limit = 50, bool $unreadOnly = false): \Illuminate\Support\Collection
    {
        $query = Notification::forUser($user->id)
            ->orderByDesc('created_at');

        if ($unreadOnly) {
            $query->unread();
        }

        return $query->limit($limit)->get();
    }

    /**
     * Compter les notifications non lues
     */
    public function getUnreadCount(User $user): int
    {
        return Notification::forUser($user->id)
            ->unread()
            ->count();
    }

    /**
     * Marquer toutes comme lues
     */
    public function markAllAsRead(User $user): int
    {
        return Notification::forUser($user->id)
            ->unread()
            ->update(['read_at' => now()]);
    }

    /**
     * Marquer une notification comme lue
     */
    public function markAsRead(Notification $notification): Notification
    {
        return $notification->markAsRead();
    }

    /**
     * Acquitter une notification
     */
    public function acknowledge(Notification $notification): Notification
    {
        return $notification->acknowledge();
    }

    /**
     * Marquer comme traitée
     */
    public function markAsActioned(Notification $notification, string $actionType = 'completed'): Notification
    {
        return $notification->markAsActioned($actionType);
    }

    /**
     * Supprimer les anciennes notifications (>30 jours, lues)
     */
    public function cleanupOldNotifications(int $daysOld = 30): int
    {
        return Notification::where('created_at', '<', now()->subDays($daysOld))
            ->whereNotNull('read_at')
            ->delete();
    }

    /**
     * Obtenir la priorité d'une catégorie
     */
    protected function getPriority(string $category): int
    {
        $priorities = [
            'system' => 5,
            'moderation' => 4,
            'interpellation' => 3,
            'response' => 3,
            'mention' => 2,
            'vote' => 1,
            'comment' => 1,
        ];

        return $priorities[$category] ?? 1;
    }

    /**
     * Mettre en file d'attente un email
     */
    protected function queueEmail(
        User $user,
        ?Notification $notification,
        string $category,
        string $title,
        string $message,
        ?string $actionUrl
    ): NotificationEmail {
        return NotificationEmail::create([
            'user_id' => $user->id,
            'notification_id' => $notification?->id,
            'type' => $category,
            'status' => 'pending',
        ]);
    }

    /**
     * Obtenir le type de notification basé sur la catégorie
     */
    protected function getCategoryNotificationType(string $category): string
    {
        return 'App\\Notifications\\' . Str::studly($category) . 'Notification';
    }

    /**
     * Obtenir l'icône par défaut pour une catégorie
     */
    protected function getDefaultIcon(string $category): string
    {
        return Notification::CATEGORIES[$category]['icon'] ?? '🔔';
    }


    /**
     * Obtenir les statistiques de notifications pour un utilisateur
     */
    public function getStats(User $user): array
    {
        $base = Notification::forUser($user->id);

        return [
            'total' => (clone $base)->count(),
            'unread' => (clone $base)->unread()->count(),
            'unacknowledged' => (clone $base)->unacknowledged()->count(),
            'pending' => (clone $base)->pending()->count(),
            'this_week' => (clone $base)->where('created_at', '>=', now()->startOfWeek())->count(),
        ];
    }
}
