<?php

namespace App\Observers;

use App\Models\Vote;
use App\Models\Achievement;
use App\Services\GamificationService;

class VoteObserver
{
    public function __construct(
        private GamificationService $gamificationService
    ) {}

    /**
     * Après création d'un vote
     */
    public function created(Vote $vote): void
    {
        if (!$vote->user_id) {
            return;
        }

        // Déclencher l'événement de gamification
        $this->gamificationService->processEvent(
            $vote->user,
            Achievement::TRIGGER_VOTE_COUNT,
            1,
            ['vote_id' => $vote->id]
        );
    }
}
