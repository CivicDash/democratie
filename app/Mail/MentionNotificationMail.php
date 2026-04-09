<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MentionNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $mentionedUser;

    public User $author;

    public string $contentType;

    public string $contentTitle;

    public string $contentExcerpt;

    public string $contentUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(
        User $mentionedUser,
        User $author,
        string $contentType,
        string $contentTitle,
        string $contentExcerpt,
        string $contentUrl
    ) {
        $this->mentionedUser = $mentionedUser;
        $this->author = $author;
        $this->contentType = $contentType;
        $this->contentTitle = $contentTitle;
        $this->contentExcerpt = $contentExcerpt;
        $this->contentUrl = $contentUrl;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "💬 {$this->author->name} vous a mentionné sur CivicDash",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.mention-notification',
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
