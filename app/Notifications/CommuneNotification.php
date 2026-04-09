<?php

namespace App\Notifications;

use App\Models\CommunePage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CommuneNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $type,
        private CommunePage $communePage,
        private mixed $sujet = null,
    ) {}

    public function via(object $notifiable): array
    {
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

        return match ($this->type) {
            'nouvel_article' => (new MailMessage)
                ->subject("Nouvelle actualite - {$communeNom}")
                ->greeting("Bonjour,")
                ->line("Une nouvelle actualite a ete publiee par la commune de {$communeNom}.")
                ->line("**{$this->sujet->titre}**")
                ->line($this->sujet->extrait_auto)
                ->action('Lire l\'article', url("/communes/{$this->communePage->code_insee}/actualites/{$this->sujet->slug}"))
                ->salutation('L\'equipe CivicDash'),

            'nouvel_evenement' => (new MailMessage)
                ->subject("Nouvel evenement - {$communeNom}")
                ->greeting("Bonjour,")
                ->line("Un nouvel evenement est prevu dans la commune de {$communeNom}.")
                ->line("**{$this->sujet->titre}**")
                ->line("Date : {$this->sujet->date_debut->format('d/m/Y a H:i')}")
                ->when($this->sujet->lieu_nom, fn ($m) => $m->line("Lieu : {$this->sujet->lieu_nom}"))
                ->action('Voir l\'evenement', url("/communes/{$this->communePage->code_insee}/evenements/{$this->sujet->slug}"))
                ->salutation('L\'equipe CivicDash'),

            'page_reclamee' => (new MailMessage)
                ->subject("Verification de commune - {$communeNom}")
                ->greeting("Bonjour,")
                ->line("Votre demande de reclamation pour la commune de {$communeNom} a ete recue.")
                ->line("Votre code de verification : **{$this->sujet}**")
                ->line("Ce code est valable 24 heures.")
                ->salutation('L\'equipe CivicDash'),

            'page_activee' => (new MailMessage)
                ->subject("Commune activee - {$communeNom}")
                ->greeting("Felicitations !")
                ->line("La page de la commune de {$communeNom} est maintenant active sur CivicDash.")
                ->line("Vous pouvez desormais publier des actualites, creer des evenements et personnaliser votre page.")
                ->action('Gerer ma commune', url("/communes/{$this->communePage->code_insee}/admin"))
                ->salutation('L\'equipe CivicDash'),

            default => (new MailMessage)
                ->subject("Notification - {$communeNom}")
                ->line("Notification de la commune de {$communeNom}.")
                ->salutation('L\'equipe CivicDash'),
        };
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->type,
            'commune_code_insee' => $this->communePage->code_insee,
            'commune_nom' => $this->communePage->ville?->nom,
            'sujet_type' => $this->sujet ? class_basename($this->sujet) : null,
            'sujet_id' => is_object($this->sujet) ? ($this->sujet->id ?? null) : null,
            'sujet_titre' => is_object($this->sujet) ? ($this->sujet->titre ?? null) : null,
        ];
    }
}
