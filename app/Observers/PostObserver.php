<?php

namespace App\Observers;

use App\Models\Post;
use App\Models\Achievement;
use App\Services\GamificationService;

class PostObserver
{
    public function __construct(
        private GamificationService $gamificationService
    ) {}

    /**
     * Après création d'un post
     */
    public function created(Post $post): void
    {
        if (!$post->user_id) {
            return;
        }

        // Déclencher l'événement de gamification
        $this->gamificationService->processEvent(
            $post->user,
            Achievement::TRIGGER_POST_CREATED,
            1,
            [
                'post_id' => $post->id,
                'topic_id' => $post->topic_id ?? null,
            ]
        );
    }
}
