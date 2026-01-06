<?php

namespace App\Mail;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountSuspendedMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public int $days;
    public string $reason;
    public Carbon $endsAt;

    public function __construct(User $user, int $days, string $reason, Carbon $endsAt)
    {
        $this->user = $user;
        $this->days = $days;
        $this->reason = $reason;
        $this->endsAt = $endsAt;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "⚠️ Votre compte CivicDash a été suspendu",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.account-suspended',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
