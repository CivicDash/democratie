<?php

namespace App\Observers;

use App\Models\Post;
use App\Services\HashtagParser;

/**
 * Observer Post - Auto-extraction hashtags
 */
class PostHashtagObserver
{
    /**
     * Handle the Post "created" event.
     */
    public function created(Post $post): void
    {
        $this->syncHashtags($post);
    }

    /**
     * Handle the Post "updated" event.
     */
    public function updated(Post $post): void
    {
        // Seulement si content a changé
        if ($post->isDirty('content')) {
            $this->syncHashtags($post);
        }
    }

    /**
     * Handle the Post "deleting" event.
     */
    public function deleting(Post $post): void
    {
        // Détacher hashtags (décrémente compteurs)
        $post->detachAllHashtags();
    }

    /**
     * Extrait et synchronise les hashtags
     */
    protected function syncHashtags(Post $post): void
    {
        $hashtags = HashtagParser::extract($post->content ?? '');
        $hashtags = HashtagParser::filter($hashtags); // Filtre blacklist
        
        $post->attachHashtags($hashtags);
    }
}
