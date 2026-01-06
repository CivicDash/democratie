<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationEluMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $eluName;
    public string $eluType; // depute, senateur, maire
    public string $inviterName;
    public string $registerUrl;
    public ?string $personalMessage;

    public function __construct(
        string $eluName,
        string $eluType,
        string $inviterName,
        string $registerUrl,
        ?string $personalMessage = null
    ) {
        $this->eluName = $eluName;
        $this->eluType = $eluType;
        $this->inviterName = $inviterName;
        $this->registerUrl = $registerUrl;
        $this->personalMessage = $personalMessage;
    }

    public function envelope(): Envelope
    {
        $typeLabel = match($this->eluType) {
            'depute' => 'Député(e)',
            'senateur' => 'Sénateur/Sénatrice',
            'maire' => 'Maire',
            default => 'Élu(e)',
        };

        return new Envelope(
            subject: "🏛️ Invitation à rejoindre CivicDash - Plateforme de dialogue citoyen",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.invitation-elu',
            with: [
                'eluName' => $this->eluName,
                'eluType' => $this->eluType,
                'eluTypeLabel' => $this->getEluTypeLabel(),
                'inviterName' => $this->inviterName,
                'registerUrl' => $this->registerUrl,
                'personalMessage' => $this->personalMessage,
            ],
        );
    }

    private function getEluTypeLabel(): string
    {
        return match($this->eluType) {
            'depute' => 'Député(e)',
            'senateur' => 'Sénateur/Sénatrice',
            'maire' => 'Maire',
            default => 'Élu(e)',
        };
    }

    public function attachments(): array
    {
        return [];
    }
}
