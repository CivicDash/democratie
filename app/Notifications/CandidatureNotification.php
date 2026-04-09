<?php

namespace App\Notifications;

use App\Models\ListeElectorale;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification pour les événements liés aux candidatures municipales
 */
class CandidatureNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ListeElectorale $liste,
        public string $type,
        public ?string $message = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return match ($this->type) {
            'liste_creee' => $this->listeCreee($notifiable),
            'liste_soumise' => $this->listeSoumise($notifiable),
            'liste_validee' => $this->listeValidee($notifiable),
            'liste_rejetee' => $this->listeRejetee($notifiable),
            'documents_demandes' => $this->documentsDemandes($notifiable),
            'document_valide' => $this->documentValide($notifiable),
            'document_invalide' => $this->documentInvalide($notifiable),
            'rappel_depot' => $this->rappelDepot($notifiable),
            default => $this->defaultNotification($notifiable),
        };
    }

    private function listeCreee(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("✅ Liste \"{$this->liste->nom_liste}\" créée - CivicDash")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Votre liste **{$this->liste->nom_liste}** pour la commune de **{$this->liste->commune_nom}** a été créée avec succès !")
            ->line('**Prochaines étapes :**')
            ->line('1. Ajoutez vos candidats (tête de liste + colistiers)')
            ->line('2. Uploadez votre récépissé de dépôt en préfecture')
            ->line('3. Complétez votre programme')
            ->line('4. Soumettez pour validation')
            ->action('Compléter ma liste', route('elections.municipales.espace-candidat.edit-liste', $this->liste->uuid))
            ->line('📅 **Rappel :** La date limite de dépôt en préfecture est le **27 février 2026** à 18h00.')
            ->salutation('Bonne campagne !');
    }

    private function listeSoumise(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('📤 Liste soumise pour validation - CivicDash')
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Votre liste **{$this->liste->nom_liste}** a été soumise pour validation.")
            ->line('Notre équipe de modération va examiner vos documents sous 24-48h.')
            ->line('Vous recevrez un email dès que la validation sera effectuée.')
            ->action('Suivre ma candidature', route('elections.municipales.espace-candidat.edit-liste', $this->liste->uuid))
            ->salutation('À très bientôt !');
    }

    private function listeValidee(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('🎉 Félicitations ! Votre liste est validée - CivicDash')
            ->greeting("Bonjour {$notifiable->name},")
            ->line("**Excellente nouvelle !** Votre liste **{$this->liste->nom_liste}** est maintenant **validée** et visible publiquement sur CivicDash.")
            ->line("Les électeurs de **{$this->liste->commune_nom}** peuvent désormais consulter votre profil et votre programme.")
            ->action('Voir ma liste publique', route('elections.municipales.liste', $this->liste->uuid))
            ->line('**Conseil :** Partagez le lien de votre profil sur vos réseaux sociaux pour gagner en visibilité !')
            ->salutation('Bonne campagne et bonne chance pour les élections du 15 mars 2026 ! 🗳️');
    }

    private function listeRejetee(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('❌ Candidature non validée - CivicDash')
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Nous sommes au regret de vous informer que votre liste **{$this->liste->nom_liste}** n'a pas pu être validée.")
            ->line('**Motif :**')
            ->line($this->message ?? $this->liste->motif_rejet ?? 'Non spécifié')
            ->line("Si vous pensez qu'il s'agit d'une erreur, vous pouvez nous contacter à l'adresse support@civicdash.fr.")
            ->salutation('Cordialement,');
    }

    private function documentsDemandes(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('📋 Documents supplémentaires requis - CivicDash')
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Votre liste **{$this->liste->nom_liste}** nécessite des documents supplémentaires pour être validée.")
            ->line('**Détails de la demande :**')
            ->line($this->message ?? 'Merci de compléter votre dossier.')
            ->action('Ajouter les documents', route('elections.municipales.espace-candidat.edit-liste', $this->liste->uuid))
            ->line('Une fois les documents ajoutés, votre dossier sera réexaminé automatiquement.')
            ->salutation('Merci de votre compréhension.');
    }

    private function documentValide(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('✅ Document validé - CivicDash')
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Un document de votre liste **{$this->liste->nom_liste}** a été validé.")
            ->action('Voir le statut de ma candidature', route('elections.municipales.espace-candidat.edit-liste', $this->liste->uuid))
            ->salutation('À bientôt !');
    }

    private function documentInvalide(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('⚠️ Document non valide - Action requise')
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Un document de votre liste **{$this->liste->nom_liste}** n'a pas pu être validé.")
            ->line('**Raison :**')
            ->line($this->message ?? 'Document non conforme.')
            ->action('Ajouter un nouveau document', route('elections.municipales.espace-candidat.edit-liste', $this->liste->uuid))
            ->salutation('Merci de votre réactivité.');
    }

    private function rappelDepot(object $notifiable): MailMessage
    {
        $joursRestants = now()->diffInDays(\Carbon\Carbon::create(2026, 2, 27));

        return (new MailMessage)
            ->subject("⏰ Rappel : J-{$joursRestants} avant la date limite de dépôt")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Il ne reste plus que **{$joursRestants} jours** avant la date limite de dépôt des candidatures en préfecture (27 février 2026 à 18h00).")
            ->line("**Votre liste \"{$this->liste->nom_liste}\" :**")
            ->line("- Statut : {$this->liste->statut_formate}")
            ->line("- Candidats : {$this->liste->nombre_candidats}")
            ->action('Finaliser ma candidature', route('elections.municipales.espace-candidat.edit-liste', $this->liste->uuid))
            ->line("N'oubliez pas de déposer votre dossier en préfecture avant la date limite !")
            ->salutation('Bonne chance !');
    }

    private function defaultNotification(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Notification - CivicDash')
            ->greeting("Bonjour {$notifiable->name},")
            ->line($this->message ?? 'Vous avez une nouvelle notification concernant votre candidature.')
            ->action('Voir ma candidature', route('elections.municipales.espace-candidat.index'))
            ->salutation('Cordialement,');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->type,
            'liste_id' => $this->liste->id,
            'liste_uuid' => $this->liste->uuid,
            'liste_nom' => $this->liste->nom_liste,
            'commune_nom' => $this->liste->commune_nom,
            'message' => $this->message,
        ];
    }
}
