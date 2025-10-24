<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vote\CastVoteRequest;
use App\Http\Requests\Vote\RequestBallotTokenRequest;
use App\Models\Topic;
use App\Services\BallotService;
use Illuminate\Http\JsonResponse;

class VoteController extends Controller
{
    public function __construct(
        protected BallotService $ballotService
    ) {}

    /**
     * Request a ballot token for voting.
     */
    public function requestToken(RequestBallotTokenRequest $request, Topic $topic): JsonResponse
    {
        try {
            $token = $this->ballotService->generateToken(
                $request->user(),
                $topic
            );

            return response()->json([
                'message' => 'Token de vote généré avec succès.',
                'token' => $token->token,
                'expires_at' => $token->expires_at,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Cast a vote anonymously.
     */
    public function castVote(CastVoteRequest $request, Topic $topic): JsonResponse
    {
        try {
            $ballot = $this->ballotService->castVote(
                $request->token,
                $request->vote
            );

            return response()->json([
                'message' => 'Vote enregistré avec succès.',
                'ballot_id' => $ballot->id,
                'voted_at' => $ballot->created_at,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get ballot results (after deadline).
     */
    public function results(Topic $topic): JsonResponse
    {
        if (!auth()->check()) {
            $user = null;
        } else {
            $user = auth()->user();
        }

        // Vérifier l'autorisation via la policy
        if (!$user || !$user->can('viewResults', $topic)) {
            if (!$topic->canRevealResults()) {
                return response()->json([
                    'message' => 'Les résultats ne peuvent pas encore être révélés.',
                ], 403);
            }
        }

        try {
            $results = $this->ballotService->calculateResults($topic);

            return response()->json($results);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Check if user has voted on a topic.
     */
    public function hasVoted(Topic $topic): JsonResponse
    {
        $hasVoted = $this->ballotService->hasUserVoted(
            auth()->user(),
            $topic
        );

        return response()->json([
            'has_voted' => $hasVoted,
        ]);
    }

    /**
     * Get vote count for a topic.
     */
    public function count(Topic $topic): JsonResponse
    {
        $count = $this->ballotService->countVotes($topic);

        return response()->json([
            'total_votes' => $count,
        ]);
    }

    /**
     * Verify ballot integrity (admin only).
     */
    public function verifyIntegrity(Topic $topic): JsonResponse
    {
        $this->authorize('viewVotes', $topic);

        $integrity = $this->ballotService->verifyIntegrity($topic);

        return response()->json($integrity);
    }

    /**
     * Export ballot results (admin/state only).
     */
    public function export(Topic $topic): JsonResponse
    {
        $this->authorize('export', $topic);

        $export = $this->ballotService->exportResults($topic);

        return response()->json($export);
    }
}

