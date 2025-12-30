<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CitizenLawStats;
use App\Models\CitizenLawVote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CitizenVoteController extends Controller
{
    /**
     * Vote sur une loi
     * 
     * POST /api/lois/{loiCod}/vote
     * Body: { "vote": 1 } ou { "vote": -1 }
     */
    public function voteLoi(Request $request, string $loiCod): JsonResponse
    {
        $request->validate([
            'vote' => 'required|integer|in:-1,1',
        ]);

        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'error' => 'Vous devez être connecté pour voter',
            ], 401);
        }

        // Enregistrer le vote
        $vote = CitizenLawVote::castVote(
            $user->id,
            $loiCod,
            $request->vote
        );

        // Recalculer les stats
        $stats = CitizenLawStats::recalculateForLoi($loiCod);

        return response()->json([
            'success' => true,
            'message' => 'Vote enregistré',
            'vote' => $vote->vote,
            'stats' => [
                'pour' => $stats->votes_pour,
                'contre' => $stats->votes_contre,
                'total' => $stats->total_votes,
                'pct_pour' => $stats->pct_pour,
                'pct_contre' => $stats->pct_contre,
            ],
        ]);
    }

    /**
     * Supprime son vote sur une loi
     * 
     * DELETE /api/lois/{loiCod}/vote
     */
    public function removeVoteLoi(Request $request, string $loiCod): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'error' => 'Vous devez être connecté',
            ], 401);
        }

        $deleted = CitizenLawVote::where('user_id', $user->id)
            ->where('loi_cod', $loiCod)
            ->delete();

        if ($deleted) {
            // Recalculer les stats
            $stats = CitizenLawStats::recalculateForLoi($loiCod);

            return response()->json([
                'success' => true,
                'message' => 'Vote supprimé',
                'stats' => [
                    'pour' => $stats->votes_pour,
                    'contre' => $stats->votes_contre,
                    'total' => $stats->total_votes,
                    'pct_pour' => $stats->pct_pour,
                    'pct_contre' => $stats->pct_contre,
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Aucun vote à supprimer',
        ], 404);
    }

    /**
     * Récupère les stats de vote pour une loi
     * 
     * GET /api/lois/{loiCod}/votes
     */
    public function getLoiVotes(Request $request, string $loiCod): JsonResponse
    {
        $stats = CitizenLawStats::getForLoi($loiCod);
        
        $userVote = null;
        if (Auth::check()) {
            $userVote = CitizenLawVote::getUserVote(Auth::id(), $loiCod);
        }

        return response()->json([
            'stats' => $stats,
            'user_vote' => $userVote,
        ]);
    }

    /**
     * Historique des votes de l'utilisateur connecté
     * 
     * GET /api/mes-votes
     */
    public function mesVotes(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        $votes = CitizenLawVote::where('user_id', $user->id)
            ->with('loi:loicod,tit,etaloi')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($votes);
    }
}
