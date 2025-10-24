<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Topic;
use App\Services\BallotService;
use App\Http\Requests\Vote\RequestBallotTokenRequest;
use App\Http\Requests\Vote\CastVoteRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VoteController extends Controller
{
    public function __construct(
        protected BallotService $ballotService
    ) {}

    /**
     * Page de vote
     */
    public function show(Topic $topic): Response
    {
        abort_unless($topic->ballot_type, 404, 'Ce topic n\'a pas de scrutin.');

        $hasVoted = auth()->check() 
            ? $this->ballotService->hasUserVoted($topic, auth()->user())
            : false;

        $results = $this->ballotService->getResults($topic);

        return Inertia::render('Vote/Show', [
            'topic' => $topic->load(['author', 'region', 'department']),
            'ballotOptions' => $topic->ballot_options,
            'hasVoted' => $hasVoted,
            'results' => $results,
        ]);
    }

    /**
     * Afficher les résultats
     */
    public function results(Topic $topic): Response
    {
        abort_unless($topic->ballot_type, 404);

        $results = $this->ballotService->getResults($topic);

        return Inertia::render('Vote/Results', [
            'topic' => $topic,
            'results' => $results,
        ]);
    }

    /**
     * Demander un jeton de vote
     */
    public function requestToken(RequestBallotTokenRequest $request, Topic $topic)
    {
        $token = $this->ballotService->requestBallotToken($topic, $request->user());

        return back()->with([
            'success' => 'Jeton de vote généré avec succès !',
            'token' => $token,
        ]);
    }

    /**
     * Voter
     */
    public function cast(CastVoteRequest $request, Topic $topic)
    {
        $this->ballotService->castVote(
            $topic,
            $request->validated()['token'],
            $request->validated()['ballot_choice']
        );

        return back()->with('success', 'Vote enregistré avec succès !');
    }
}

