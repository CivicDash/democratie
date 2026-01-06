<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserMentionMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $mentionedUser;
    public User $author;
    public string $contentType; // topic, comment, post
    public string $contentTitle;
    public string $contentExcerpt;
    public string $contentUrl;

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

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "💬 {$this->author->name} vous a mentionné sur CivicDash",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.user-mention',
            with: [
                'mentionedUser' => $this->mentionedUser,
                'author' => $this->author,
                'contentType' => $this->contentType,
                'contentTypeLabel' => $this->getContentTypeLabel(),
                'contentTitle' => $this->contentTitle,
                'contentExcerpt' => $this->contentExcerpt,
                'contentUrl' => $this->contentUrl,
            ],
        );
    }

    private function getContentTypeLabel(): string
    {
        return match($this->contentType) {
            'topic' => 'une discussion',
            'comment' => 'un commentaire',
            'post' => 'une publication',
            'idea' => 'une idée citoyenne',
            default => 'un contenu',
        };
    }

    public function attachments(): array
    {
        return [];
    }
}
