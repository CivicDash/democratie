<?php

namespace App\Mail;

use App\Models\Topic;
use App\Models\TopicElu;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InterpellationNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public Topic $topic;
    public TopicElu $topicElu;
    public User $author;
    public string $eluName;
    public string $dashboardUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(Topic $topic, TopicElu $topicElu, User $author, string $eluName)
    {
        $this->topic = $topic;
        $this->topicElu = $topicElu;
        $this->author = $author;
        $this->eluName = $eluName;
        $this->dashboardUrl = route('elu.interpellations');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "🏛️ Nouvelle interpellation citoyenne sur CivicDash",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.interpellation-notification',
            with: [
                'topic' => $this->topic,
                'topicElu' => $this->topicElu,
                'author' => $this->author,
                'eluName' => $this->eluName,
                'dashboardUrl' => $this->dashboardUrl,
                'topicUrl' => route('participation.ideas.show', $this->topic->slug ?? $this->topic->id),
            ],
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
