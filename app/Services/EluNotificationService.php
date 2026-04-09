<?php

namespace App\Services;

use App\Mail\InterpellationNotificationMail;
use App\Models\ActeurAN;
use App\Models\Maire;
use App\Models\Notification;
use App\Models\Senateur;
use App\Models\Topic;
use App\Models\TopicElu;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EluNotificationService
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Notifier un élu qu'il a été interpellé
     */
    public function notifyInterpellation(Topic $topic, TopicElu $topicElu): void
    {
        // Récupérer les informations de l'élu
        $eluInfo = $this->getEluInfo($topicElu->elu_type, $topicElu->elu_id);

        if (! $eluInfo) {
            Log::warning('Élu non trouvé pour notification', [
                'elu_type' => $topicElu->elu_type,
                'elu_id' => $topicElu->elu_id,
            ]);

            return;
        }

        // Chercher si l'élu a un compte utilisateur
        $eluUser = $this->findEluUser($topicElu->elu_type, $topicElu->elu_id);

        // Auteur du topic
        $author = $topic->author;

        // 1. Notification in-app si l'élu a un compte
        if ($eluUser) {
            $this->notificationService->notify(
                $eluUser,
                'interpellation',
                '📣 Nouvelle interpellation citoyenne',
                "Vous avez été interpellé par {$author->name} : \"{$topic->title}\"",
                route('elu.interpellations.show', $topicElu->id),
                '📣',
                [
                    'topic_id' => $topic->id,
                    'topic_elu_id' => $topicElu->id,
                    'author_id' => $author->id,
                ]
            );

            // 2. Email si l'élu accepte les emails
            $this->sendInterpellationEmail($eluUser, $topic, $topicElu, $author, $eluInfo['name']);
        }

        // 3. Mettre à jour le statut de l'interpellation
        $topicElu->update([
            'notified_at' => now(),
            'response_status' => 'notified',
        ]);

        Log::info("Notification d'interpellation envoyée", [
            'topic_id' => $topic->id,
            'elu_type' => $topicElu->elu_type,
            'elu_id' => $topicElu->elu_id,
            'has_account' => $eluUser !== null,
        ]);
    }

    /**
     * Notifier un élu qu'il a été mentionné (@depute:, @senateur:, etc.)
     */
    public function notifyMention(
        User $author,
        string $content,
        string $eluType,
        string $eluId,
        string $contextUrl,
        string $contextTitle
    ): void {
        $eluUser = $this->findEluUser($eluType, $eluId);

        if (! $eluUser) {
            return; // Pas de compte, pas de notification
        }

        $eluInfo = $this->getEluInfo($eluType, $eluId);

        $this->notificationService->notify(
            $eluUser,
            'mention',
            '@ Vous avez été mentionné',
            "{$author->name} vous a mentionné dans \"{$contextTitle}\"",
            $contextUrl,
            '@',
            [
                'author_id' => $author->id,
                'elu_type' => $eluType,
                'elu_id' => $eluId,
            ]
        );
    }

    /**
     * Notifier les élus mentionnés dans un contenu
     */
    public function notifyMentionsInContent(
        User $author,
        string $content,
        string $contextUrl,
        string $contextTitle
    ): int {
        $moderationService = app(ContentModerationService::class);
        $references = $moderationService->extractReferences($content);

        $eluTypes = ['depute', 'senateur', 'maire'];
        $notified = 0;

        foreach ($references as $ref) {
            if (in_array($ref['type'], $eluTypes) && $ref['exists']) {
                $this->notifyMention(
                    $author,
                    $content,
                    $ref['type'],
                    $ref['identifier'],
                    $contextUrl,
                    $contextTitle
                );
                $notified++;
            }
        }

        return $notified;
    }

    /**
     * Envoyer l'email d'interpellation
     */
    protected function sendInterpellationEmail(
        User $eluUser,
        Topic $topic,
        TopicElu $topicElu,
        User $author,
        string $eluName
    ): bool {
        // Vérifier les préférences email
        if (! $this->notificationService->isChannelEnabled($eluUser, 'email', 'interpellation')) {
            return false;
        }

        try {
            Mail::to($eluUser->email)
                ->queue(new InterpellationNotificationMail($topic, $topicElu, $author, $eluName));

            return true;
        } catch (\Exception $e) {
            Log::error('Erreur envoi email interpellation', [
                'user_id' => $eluUser->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Trouver le compte utilisateur d'un élu
     */
    protected function findEluUser(string $eluType, string $eluId): ?User
    {
        return User::where('elu_type', $eluType)
            ->where('elu_ref', $eluId)
            ->where('is_verified_elu', true)
            ->first();
    }

    /**
     * Obtenir les informations d'un élu
     */
    protected function getEluInfo(string $eluType, string $eluId): ?array
    {
        return match ($eluType) {
            'depute' => $this->getDeputeInfo($eluId),
            'senateur' => $this->getSenateurInfo($eluId),
            'maire' => $this->getMaireInfo($eluId),
            default => null,
        };
    }

    protected function getDeputeInfo(string $uid): ?array
    {
        $depute = ActeurAN::where('uid', $uid)->first(['uid', 'prenom', 'nom', 'slug']);

        if (! $depute) {
            return null;
        }

        return [
            'name' => $depute->prenom.' '.$depute->nom,
            'url' => route('representants.deputes.show', $depute->slug ?? $uid),
        ];
    }

    protected function getSenateurInfo(string $matricule): ?array
    {
        $senateur = Senateur::where('matricule', $matricule)->first(['matricule', 'prenom', 'nom']);

        if (! $senateur) {
            return null;
        }

        return [
            'name' => $senateur->prenom.' '.$senateur->nom,
            'url' => route('representants.senateurs.show', $matricule),
        ];
    }

    protected function getMaireInfo(string $id): ?array
    {
        $maire = Maire::find($id, ['id', 'prenom', 'nom', 'commune']);

        if (! $maire) {
            return null;
        }

        return [
            'name' => $maire->prenom.' '.$maire->nom.' (Maire de '.$maire->commune.')',
            'url' => route('representants.maires.show', $id),
        ];
    }

    /**
     * Notifier tous les élus d'un topic
     */
    public function notifyAllElusForTopic(Topic $topic): int
    {
        $notified = 0;

        foreach ($topic->elus as $topicElu) {
            if ($topicElu->is_interpellation && ! $topicElu->notified_at) {
                $this->notifyInterpellation($topic, $topicElu);
                $notified++;
            }
        }

        return $notified;
    }
}
