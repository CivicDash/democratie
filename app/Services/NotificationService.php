<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Models\UserFollow;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Envoyer une notification à un utilisateur
     */
    public function send(
        User $user,
        string $type,
        string $title,
        ?string $message = null,
        ?string $link = null,
        ?array $data = null,
        string $priority = Notification::PRIORITY_NORMAL,
        ?string $icon = null
    ): Notification {
        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'icon' => $icon,
            'link' => $link,
            'data' => $data,
            'priority' => $priority,
        ]);

        Log::info("Notification envoyée", [
            'user_id' => $user->id,
            'type' => $type,
            'notification_id' => $notification->id,
        ]);

        return $notification;
    }

    /**
     * Envoyer une notification à plusieurs utilisateurs
     */
    public function sendToMany(
        Collection $users,
        string $type,
        string $title,
        ?string $message = null,
        ?string $link = null,
        ?array $data = null,
        string $priority = Notification::PRIORITY_NORMAL,
        ?string $icon = null
    ): int {
        $count = 0;

        foreach ($users as $user) {
            $this->send($user, $type, $title, $message, $link, $data, $priority, $icon);
            $count++;
        }

        Log::info("Notifications envoyées en masse", [
            'count' => $count,
            'type' => $type,
        ]);

        return $count;
    }

    /**
     * Envoyer une notification pour une nouvelle thématique
     */
    public function notifyNewThematique(User $user, array $thematiqueData): Notification
    {
        return $this->send(
            user: $user,
            type: Notification::TYPE_NEW_THEMATIQUE,
            title: "Nouvelle thématique : {$thematiqueData['nom']}",
            message: $thematiqueData['description'] ?? null,
            link: "/thematiques/{$thematiqueData['code']}",
            data: $thematiqueData,
            priority: Notification::PRIORITY_NORMAL,
            icon: '🏷️'
        );
    }

    /**
     * Envoyer une notification pour un nouveau groupe parlementaire
     */
    public function notifyNewGroupe(User $user, array $groupeData): Notification
    {
        return $this->send(
            user: $user,
            type: Notification::TYPE_NEW_GROUPE,
            title: "Nouveau groupe : {$groupeData['nom']}",
            message: "{$groupeData['nombre_membres']} membres · {$groupeData['position_politique']}",
            link: "/groupes/{$groupeData['id']}",
            data: $groupeData,
            priority: Notification::PRIORITY_NORMAL,
            icon: '🏛️'
        );
    }

    /**
     * Envoyer une notification pour une nouvelle législation
     */
    public function notifyNewLegislation(User $user, array $legislationData): Notification
    {
        return $this->send(
            user: $user,
            type: Notification::TYPE_NEW_LEGISLATION,
            title: "Nouvelle proposition : {$legislationData['titre']}",
            message: "Source : {$legislationData['source']}",
            link: "/legislation/{$legislationData['id']}",
            data: $legislationData,
            priority: Notification::PRIORITY_NORMAL,
            icon: '📜'
        );
    }

    /**
     * Envoyer une notification pour un résultat de vote
     */
    public function notifyVoteResult(User $user, array $voteData): Notification
    {
        $result = $voteData['resultat'] ?? 'Inconnu';
        $icon = $result === 'adopté' ? '✅' : ($result === 'rejeté' ? '❌' : '📊');

        return $this->send(
            user: $user,
            type: Notification::TYPE_VOTE_RESULT,
            title: "Résultat de vote : {$result}",
            message: $voteData['titre'] ?? null,
            link: "/legislation/{$voteData['proposition_id']}",
            data: $voteData,
            priority: Notification::PRIORITY_HIGH,
            icon: $icon
        );
    }

    /**
     * Envoyer une alerte système
     */
    public function sendAlert(
        User $user,
        string $title,
        string $message,
        string $priority = Notification::PRIORITY_HIGH,
        ?string $link = null
    ): Notification {
        return $this->send(
            user: $user,
            type: Notification::TYPE_ALERT,
            title: $title,
            message: $message,
            link: $link,
            priority: $priority,
            icon: '⚠️'
        );
    }

    /**
     * Marquer toutes les notifications d'un utilisateur comme lues
     */
    public function markAllAsRead(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Marquer une notification comme lue
     */
    public function markAsRead(int $notificationId, User $user): bool
    {
        $notification = Notification::where('id', $notificationId)
            ->where('user_id', $user->id)
            ->first();

        if ($notification) {
            $notification->markAsRead();
            return true;
        }

        return false;
    }

    /**
     * Supprimer les anciennes notifications (> 90 jours)
     */
    public function cleanOldNotifications(int $days = 90): int
    {
        return Notification::where('created_at', '<', now()->subDays($days))
            ->delete();
    }

    /**
     * Obtenir les statistiques de notifications d'un utilisateur
     */
    public function getStats(User $user): array
    {
        $notifications = Notification::where('user_id', $user->id);

        return [
            'total' => $notifications->count(),
            'unread' => $notifications->clone()->unread()->count(),
            'read' => $notifications->clone()->read()->count(),
            'by_type' => $notifications->clone()
                ->selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray(),
            'by_priority' => $notifications->clone()
                ->selectRaw('priority, COUNT(*) as count')
                ->groupBy('priority')
                ->pluck('count', 'priority')
                ->toArray(),
        ];
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * NOUVELLES MÉTHODES DE NOTIFICATION INTELLIGENTES
     * ═══════════════════════════════════════════════════════════════════
     */

    /**
     * Envoyer une notification avec vérification des préférences
     */
    private function sendWithPreferences(
        User $user,
        string $preferenceKey,
        string $type,
        string $title,
        ?string $message = null,
        ?string $link = null,
        ?array $data = null,
        string $priority = Notification::PRIORITY_NORMAL,
        ?string $icon = null
    ): ?Notification {
        $preferences = NotificationPreference::getForUser($user->id);

        // Vérifier si ce type de notification est activé
        if (!$preferences->isEnabled($preferenceKey)) {
            return null;
        }

        // Vérifier les heures calmes
        if (!$preferences->canSendInApp()) {
            return null;
        }

        return $this->send($user, $type, $title, $message, $link, $data, $priority, $icon);
    }

    /**
     * Notifier d'une nouvelle réponse sur une conversation suivie
     */
    public function notifyNewReply(User $recipientUser, array $postData, User $authorUser): ?Notification
    {
        return $this->sendWithPreferences(
            user: $recipientUser,
            preferenceKey: 'new_reply',
            type: Notification::TYPE_NEW_REPLY,
            title: "Nouvelle réponse de {$authorUser->name}",
            message: substr($postData['content'] ?? '', 0, 100) . '...',
            link: "/topics/{$postData['topic_id']}/posts/{$postData['id']}",
            data: [
                'post_id' => $postData['id'],
                'topic_id' => $postData['topic_id'],
                'author_id' => $authorUser->id,
                'author_name' => $authorUser->name,
            ],
            priority: Notification::PRIORITY_NORMAL,
            icon: '💬'
        );
    }

    /**
     * Notifier les followers qu'il y a une nouvelle réponse
     */
    public function notifyFollowersNewReply(int $topicId, array $postData, User $authorUser): int
    {
        $followers = UserFollow::getFollowers('App\\Models\\Topic', $topicId);
        $count = 0;

        foreach ($followers as $follow) {
            // Ne pas notifier l'auteur
            if ($follow->user_id === $authorUser->id) {
                continue;
            }

            // Vérifier le cooldown pour éviter le spam
            if (!$follow->canNotify(5)) {
                continue;
            }

            $notification = $this->notifyNewReply($follow->user, $postData, $authorUser);
            if ($notification) {
                $follow->markNotified();
                $count++;
            }
        }

        return $count;
    }

    /**
     * Notifier d'un nouveau vote sur un sujet d'intérêt
     */
    public function notifyNewVoteOnTopic(User $recipientUser, array $topicData, User $voterUser): ?Notification
    {
        return $this->sendWithPreferences(
            user: $recipientUser,
            preferenceKey: 'new_vote_on_topic',
            type: Notification::TYPE_NEW_VOTE_ON_TOPIC,
            title: "{$voterUser->name} a voté sur un sujet que vous suivez",
            message: $topicData['title'] ?? '',
            link: "/topics/{$topicData['id']}",
            data: [
                'topic_id' => $topicData['id'],
                'voter_id' => $voterUser->id,
                'vote_type' => $topicData['vote_type'] ?? 'pour',
            ],
            priority: Notification::PRIORITY_LOW,
            icon: '👍'
        );
    }

    /**
     * Notifier les followers d'un nouveau vote sur un topic
     */
    public function notifyFollowersNewVoteOnTopic(int $topicId, array $topicData, User $voterUser): int
    {
        $followers = UserFollow::getFollowers('App\\Models\\Topic', $topicId);
        $count = 0;

        foreach ($followers as $follow) {
            if ($follow->user_id === $voterUser->id) {
                continue;
            }

            if (!$follow->canNotify(30)) { // Cooldown de 30 minutes pour les votes
                continue;
            }

            $notification = $this->notifyNewVoteOnTopic($follow->user, $topicData, $voterUser);
            if ($notification) {
                $follow->markNotified();
                $count++;
            }
        }

        return $count;
    }

    /**
     * Notifier du résultat d'un vote législatif suivi
     */
    public function notifyLegislativeVoteResult(User $recipientUser, array $propositionData, array $voteResult): ?Notification
    {
        $resultat = $voteResult['resultat'] ?? 'Inconnu';
        $icon = $resultat === 'adopté' ? '✅' : ($resultat === 'rejeté' ? '❌' : '📊');

        return $this->sendWithPreferences(
            user: $recipientUser,
            preferenceKey: 'legislative_vote_result',
            type: Notification::TYPE_LEGISLATIVE_VOTE_RESULT,
            title: "Résultat du vote : {$resultat}",
            message: $propositionData['titre'] ?? '',
            link: "/legislation/{$propositionData['id']}",
            data: [
                'proposition_id' => $propositionData['id'],
                'resultat' => $resultat,
                'pour' => $voteResult['pour'] ?? 0,
                'contre' => $voteResult['contre'] ?? 0,
                'abstention' => $voteResult['abstention'] ?? 0,
            ],
            priority: Notification::PRIORITY_HIGH,
            icon: $icon
        );
    }

    /**
     * Notifier les followers d'un résultat de vote législatif
     */
    public function notifyFollowersLegislativeVoteResult(int $propositionId, array $propositionData, array $voteResult): int
    {
        $followers = UserFollow::getFollowers('App\\Models\\PropositionLoi', $propositionId);
        $count = 0;

        foreach ($followers as $follow) {
            $notification = $this->notifyLegislativeVoteResult($follow->user, $propositionData, $voteResult);
            if ($notification) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Notifier d'une mention dans un commentaire
     */
    public function notifyMention(User $mentionedUser, array $postData, User $authorUser): ?Notification
    {
        return $this->sendWithPreferences(
            user: $mentionedUser,
            preferenceKey: 'mention',
            type: Notification::TYPE_MENTION,
            title: "{$authorUser->name} vous a mentionné",
            message: substr($postData['content'] ?? '', 0, 100) . '...',
            link: "/topics/{$postData['topic_id']}/posts/{$postData['id']}",
            data: [
                'post_id' => $postData['id'],
                'topic_id' => $postData['topic_id'],
                'author_id' => $authorUser->id,
                'author_name' => $authorUser->name,
            ],
            priority: Notification::PRIORITY_NORMAL,
            icon: '👤'
        );
    }

    /**
     * Notifier le créateur qu'il y a un vote sur sa proposition citoyenne
     */
    public function notifyVoteOnMyProposal(User $proposalOwner, array $topicData, User $voterUser, string $voteType): ?Notification
    {
        $icon = $voteType === 'pour' ? '👍' : ($voteType === 'contre' ? '👎' : '🤔');

        return $this->sendWithPreferences(
            user: $proposalOwner,
            preferenceKey: 'vote_on_my_proposal',
            type: Notification::TYPE_VOTE_ON_MY_PROPOSAL,
            title: "{$voterUser->name} a voté {$voteType} votre proposition",
            message: $topicData['title'] ?? '',
            link: "/topics/{$topicData['id']}",
            data: [
                'topic_id' => $topicData['id'],
                'voter_id' => $voterUser->id,
                'vote_type' => $voteType,
            ],
            priority: Notification::PRIORITY_LOW,
            icon: $icon
        );
    }

    /**
     * Notifier d'une nouvelle proposition dans une thématique suivie
     */
    public function notifyNewThematiqueProposition(User $recipientUser, array $propositionData, string $thematiqueCode): ?Notification
    {
        return $this->sendWithPreferences(
            user: $recipientUser,
            preferenceKey: 'new_thematique_proposition',
            type: Notification::TYPE_NEW_THEMATIQUE_PROPOSITION,
            title: "Nouvelle proposition dans {$thematiqueCode}",
            message: $propositionData['titre'] ?? '',
            link: "/legislation/{$propositionData['id']}",
            data: [
                'proposition_id' => $propositionData['id'],
                'thematique_code' => $thematiqueCode,
            ],
            priority: Notification::PRIORITY_LOW,
            icon: '📢'
        );
    }

    /**
     * Acquittement automatique des notifications quand l'utilisateur visite une page
     */
    public function autoMarkReadByLink(User $user, string $link): int
    {
        return Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->where('link', 'like', "%{$link}%")
            ->update(['read_at' => now()]);
    }

    /**
     * Grouper les notifications similaires
     */
    public function groupSimilarNotifications(User $user): array
    {
        $notifications = Notification::where('user_id', $user->id)
            ->unread()
            ->orderBy('created_at', 'desc')
            ->get();

        $grouped = [];

        foreach ($notifications as $notification) {
            $key = $notification->type . '_' . ($notification->data['topic_id'] ?? $notification->data['proposition_id'] ?? 'general');
            
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'type' => $notification->type,
                    'count' => 0,
                    'latest' => null,
                    'notifications' => [],
                ];
            }

            $grouped[$key]['count']++;
            $grouped[$key]['notifications'][] = $notification;
            
            if (!$grouped[$key]['latest'] || $notification->created_at > $grouped[$key]['latest']->created_at) {
                $grouped[$key]['latest'] = $notification;
            }
        }

        return array_values($grouped);
    }
}


