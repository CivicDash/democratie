<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VoteResultMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;

    public string $voteTitle;

    public string $voteType; // 'scrutin_an', 'scrutin_senat', 'proposal'

    public string $result; // 'adopté', 'rejeté', 'en cours'

    public int $votesFor;

    public int $votesAgainst;

    public int $abstentions;

    public string $voteUrl;

    public ?string $eluPosition; // Position d'un élu suivi (optionnel)

    /**
     * Create a new message instance.
     */
    public function __construct(
        User $user,
        string $voteTitle,
        string $voteType,
        string $result,
        int $votesFor,
        int $votesAgainst,
        int $abstentions,
        string $voteUrl,
        ?string $eluPosition = null
    ) {
        $this->user = $user;
        $this->voteTitle = $voteTitle;
        $this->voteType = $voteType;
        $this->result = $result;
        $this->votesFor = $votesFor;
        $this->votesAgainst = $votesAgainst;
        $this->abstentions = $abstentions;
        $this->voteUrl = $voteUrl;
        $this->eluPosition = $eluPosition;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $resultEmoji = match ($this->result) {
            'adopté' => '✅',
            'rejeté' => '❌',
            default => '🗳️',
        };

        return new Envelope(
            subject: "{$resultEmoji} Résultat du vote : {$this->voteTitle}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.vote-result',
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
