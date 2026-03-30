<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AffairesEnAttenteNotification extends Notification
{
    use Queueable;

    public function __construct(
        private int $enAttente,
        private int $contestees
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $parts = [];
        if ($this->enAttente > 0) {
            $parts[] = "{$this->enAttente} affaire(s) à valider";
        }
        if ($this->contestees > 0) {
            $parts[] = "{$this->contestees} contestation(s) urgente(s)";
        }

        return [
            'type' => 'affaires_moderation',
            'message' => implode(' — ', $parts),
            'en_attente' => $this->enAttente,
            'contestees' => $this->contestees,
            'url' => route('admin.affaires.index'),
        ];
    }
}
