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

class EluResponseMail extends Mailable
{
    use Queueable, SerializesModels;

    public Topic $topic;
    public TopicElu $topicElu;
    public User $citizen;
    public string $eluName;
    public string $eluFunction;
    public string $responseExcerpt;
    public string $topicUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(
        Topic $topic,
        TopicElu $topicElu,
        User $citizen,
        string $eluName,
        string $eluFunction,
        string $responseExcerpt
    ) {
        $this->topic = $topic;
        $this->topicElu = $topicElu;
        $this->citizen = $citizen;
        $this->eluName = $eluName;
        $this->eluFunction = $eluFunction;
        $this->responseExcerpt = $responseExcerpt;
        $this->topicUrl = route('participation.ideas.show', $topic->slug ?? $topic->id);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "💬 {$this->eluName} a répondu à votre interpellation",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.elu-response',
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
