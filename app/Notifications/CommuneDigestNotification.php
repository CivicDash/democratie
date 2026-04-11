<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CommuneDigestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private array $digestData,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $totalArticles = collect($this->digestData)->sum(fn ($d) => count($d['articles']));
        $totalEvents = collect($this->digestData)->sum(fn ($d) => count($d['evenements']));
        $communeCount = count($this->digestData);

        $mail = (new MailMessage)
            ->subject("Votre digest hebdomadaire CivicDash - {$communeCount} commune(s)")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Voici votre recapitulatif de la semaine pour vos communes suivies.");

        foreach ($this->digestData as $data) {
            $mail->line("---");
            $mail->line("**{$data['commune_nom']}**");

            if (! empty($data['articles'])) {
                $mail->line(count($data['articles'])." nouvel(s) article(s) :");
                foreach ($data['articles'] as $article) {
                    $mail->line("- [{$article['titre']}]({$article['url']})");
                }
            }

            if (! empty($data['evenements'])) {
                $mail->line(count($data['evenements'])." evenement(s) a venir :");
                foreach ($data['evenements'] as $event) {
                    $lieu = $event['lieu'] ? " - {$event['lieu']}" : '';
                    $mail->line("- {$event['date']}{$lieu} : [{$event['titre']}]({$event['url']})");
                }
            }

            if ($data['forum_nouveaux'] > 0) {
                $mail->line("{$data['forum_nouveaux']} nouveau(x) sujet(s) sur le forum.");
            }
        }

        $mail->line("---");
        $mail->action('Voir mes communes', url('/'));
        $mail->salutation("L'equipe CivicDash");

        return $mail;
    }
}
