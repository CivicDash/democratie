<?php

namespace App\Notifications;

use App\Models\Post;
use App\Models\Topic;
use App\Models\UserMention;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserMentionedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected UserMention $mention
    ) {}

    public function via($notifiable): array
    {
        $channels = ['database'];
        
        // Email si l'utilisateur a activé les notifications email
        if ($this->shouldSendEmail($notifiable)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    protected function shouldSendEmail($notifiable): bool
    {
        // Vérifier les préférences utilisateur
        $prefs = $notifiable->notificationPreferences ?? null;
        if ($prefs && !$prefs->mention_email) {
            return false;
        }
        return true;
    }

    public function toMail($notifiable): MailMessage
    {
        $author = $this->mention->author;
        $contentType = $this->getContentType();
        $contentUrl = $this->getContentUrl();

        return (new MailMessage)
            ->subject("💬 {$author->name} vous a mentionné sur CivicDash")
            ->greeting("Bonjour {$notifiable->name} !")
            ->line("{$author->name} vous a mentionné dans {$contentType}.")
            ->line($this->getExcerpt())
            ->action('Voir le contenu', $contentUrl)
            ->line('Merci de participer à la vie démocratique !');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'mention',
            'mention_id' => $this->mention->id,
            'author_id' => $this->mention->mentioned_by,
            'author_name' => $this->mention->author->name ?? 'Utilisateur',
            'content_type' => $this->getContentType(),
            'content_url' => $this->getContentUrl(),
            'excerpt' => $this->getExcerpt(),
        ];
    }

    protected function getContentType(): string
    {
        $mentionable = $this->mention->mentionable;

        return match (true) {
            $mentionable instanceof Topic => 'un sujet de discussion',
            $mentionable instanceof Post => 'un commentaire',
            default => 'un contenu',
        };
    }

    protected function getContentUrl(): string
    {
        $mentionable = $this->mention->mentionable;

        if ($mentionable instanceof Topic) {
            return route('participation.ideas.show', $mentionable->slug ?? $mentionable->id);
        }

        if ($mentionable instanceof Post) {
            $topic = $mentionable->topic;
            return route('participation.ideas.show', $topic->slug ?? $topic->id) . "#post-{$mentionable->id}";
        }

        return route('dashboard');
    }

    protected function getExcerpt(): string
    {
        $mentionable = $this->mention->mentionable;
        $content = '';

        if ($mentionable instanceof Topic) {
            $content = $mentionable->description ?? $mentionable->title ?? '';
        } elseif ($mentionable instanceof Post) {
            $content = $mentionable->content ?? '';
        }

        return \Illuminate\Support\Str::limit(strip_tags($content), 150);
    }
}
