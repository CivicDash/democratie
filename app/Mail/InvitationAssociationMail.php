<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationAssociationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $memberName;

    public string $associationName;

    public string $inviterName;

    public string $registerUrl;

    public ?string $personalMessage;

    /**
     * Create a new message instance.
     */
    public function __construct(
        string $memberName,
        string $associationName,
        string $inviterName,
        string $registerUrl,
        ?string $personalMessage = null
    ) {
        $this->memberName = $memberName;
        $this->associationName = $associationName;
        $this->inviterName = $inviterName;
        $this->registerUrl = $registerUrl;
        $this->personalMessage = $personalMessage;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "🤝 Invitation à rejoindre CivicDash — {$this->associationName}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.invitation-association',
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
