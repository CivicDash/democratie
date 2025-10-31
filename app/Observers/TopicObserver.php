<?php

namespace App\Observers;

use App\Models\Topic;
use App\Models\Achievement;
use App\Services\GamificationService;

class TopicObserver
{
    public function __construct(
        private GamificationService $gamificationService
    ) {}

    /**
     * Après création d'un topic
     */
    public function created(Topic $topic): void
    {
        if (!$topic->user_id) {
            return;
        }

        // Déclencher l'événement de gamification
        $this->gamificationService->processEvent(
            $topic->user,
            Achievement::TRIGGER_TOPIC_CREATED,
            1,
            [
                'topic_id' => $topic->id,
                'category' => $topic->category ?? null,
            ]
        );
    }
}
