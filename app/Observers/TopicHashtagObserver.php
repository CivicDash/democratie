<?php

namespace App\Observers;

use App\Models\Topic;
use App\Services\HashtagParser;

/**
 * Observer Topic - Auto-extraction hashtags
 */
class TopicHashtagObserver
{
    /**
     * Handle the Topic "created" event.
     */
    public function created(Topic $topic): void
    {
        $this->syncHashtags($topic);
    }

    /**
     * Handle the Topic "updated" event.
     */
    public function updated(Topic $topic): void
    {
        // Seulement si title ou description a changé
        if ($topic->isDirty(['title', 'description'])) {
            $this->syncHashtags($topic);
        }
    }

    /**
     * Handle the Topic "deleting" event.
     */
    public function deleting(Topic $topic): void
    {
        // Détacher hashtags (décrémente compteurs)
        $topic->detachAllHashtags();
    }

    /**
     * Extrait et synchronise les hashtags
     */
    protected function syncHashtags(Topic $topic): void
    {
        // Extraire depuis title + description
        $content = ($topic->title ?? '') . ' ' . ($topic->description ?? '');
        
        $hashtags = HashtagParser::extract($content);
        $hashtags = HashtagParser::filter($hashtags); // Filtre blacklist
        
        $topic->attachHashtags($hashtags);
    }
}
