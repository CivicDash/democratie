<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $dashboardUrl;
    public string $profileUrl;
    public string $discoverUrl;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->dashboardUrl = route('dashboard');
        $this->profileUrl = route('profile.edit');
        $this->discoverUrl = route('participation.ideas.index');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "🎉 Bienvenue sur CivicDash, {$this->user->name} !",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.welcome',
            with: [
                'user' => $this->user,
                'dashboardUrl' => $this->dashboardUrl,
                'profileUrl' => $this->profileUrl,
                'discoverUrl' => $this->discoverUrl,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
