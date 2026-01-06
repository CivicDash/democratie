<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class DigestMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $period; // 'daily', 'weekly'
    public Carbon $startDate;
    public Carbon $endDate;
    public array $newVotes;
    public array $newInterpellations;
    public array $eluResponses;
    public array $popularTopics;
    public int $totalNotifications;
    public string $dashboardUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(
        User $user,
        string $period,
        Carbon $startDate,
        Carbon $endDate,
        array $newVotes = [],
        array $newInterpellations = [],
        array $eluResponses = [],
        array $popularTopics = [],
        int $totalNotifications = 0
    ) {
        $this->user = $user;
        $this->period = $period;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->newVotes = $newVotes;
        $this->newInterpellations = $newInterpellations;
        $this->eluResponses = $eluResponses;
        $this->popularTopics = $popularTopics;
        $this->totalNotifications = $totalNotifications;
        $this->dashboardUrl = route('dashboard');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $periodLabel = $this->period === 'daily' ? 'quotidien' : 'hebdomadaire';
        
        return new Envelope(
            subject: "📰 Votre récap {$periodLabel} CivicDash",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.digest',
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
