<?php

namespace App\Notifications;

use App\Models\CommunePage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CommuneBroadcastNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private CommunePage $communePage,
        private string $sujet,
        private string $contenu,
        private string $type,
        private string $cible,
    ) {}

    public function via(object $notifiable): array
    {
        if ($this->cible === 'email_only') {
            return ['mail'];
        }
        if ($this->cible === 'app_only') {
            return ['database'];
        }

        $channels = ['database'];

        $abonnement = $this->communePage->abonnements()
            ->where('user_id', $notifiable->id)
            ->first();

        if ($abonnement?->notif_email) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $communeNom = $this->communePage->ville?->nom ?? 'votre commune';
        $icon = match ($this->type) {
            'urgence' => '🚨',
            'alerte' => '⚠️',
            'evenement' => '📅',
            default => 'ℹ️',
        };

        return (new MailMessage)
            ->subject("{$icon} {$this->sujet} - {$communeNom}")
            ->greeting('Bonjour,')
            ->line("Message de la commune de {$communeNom} :")
            ->line("**{$this->sujet}**")
            ->line($this->contenu)
            ->action('Voir la commune', url("/commune-hub/{$this->communePage->code_insee}"))
            ->salutation('L\'equipe CivicDash');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'broadcast_'.$this->type,
            'commune_code_insee' => $this->communePage->code_insee,
            'commune_nom' => $this->communePage->ville?->nom,
            'sujet' => $this->sujet,
            'contenu' => $this->contenu,
        ];
    }
}
