<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountBannedMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;

    public string $reason;

    public string $appealEmail = 'bannissement@civis-consilium.eu';

    public function __construct(User $user, string $reason)
    {
        $this->user = $user;
        $this->reason = $reason;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🚫 Votre compte CivicDash a été banni',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.account-banned',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
