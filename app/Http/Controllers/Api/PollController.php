<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PollOption;
use App\Models\PollVote;
use App\Models\Topic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Gestion des sondages (polls) sur les topics
 */
class PollController extends Controller
{
    /**
     * Voter sur un sondage
     */
    public function vote(Request $request, Topic $topic): JsonResponse
    {
        // Vérifier que c'est un sondage
        if ($topic->idea_type !== 'poll') {
            return response()->json([
                'success' => false,
                'message' => 'Ce sujet n\'est pas un sondage.',
            ], 400);
        }

        // Vérifier que le sondage est actif
        if (! $topic->isPollActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Ce sondage est terminé.',
            ], 400);
        }

        $validated = $request->validate([
            'option_ids' => ['required', 'array', 'min:1'],
            'option_ids.*' => ['required', 'integer', 'exists:poll_options,id'],
        ]);

        $userId = auth()->id();
        $optionIds = $validated['option_ids'];

        // Vérifier que les options appartiennent bien à ce topic
        $validOptions = $topic->pollOptions()->whereIn('id', $optionIds)->pluck('id')->toArray();

        if (count($validOptions) !== count($optionIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Options invalides.',
            ], 400);
        }

        // Pour les sondages à choix unique, ne garder que la première option
        if ($topic->poll_type === 'single' && count($optionIds) > 1) {
            $optionIds = [array_shift($optionIds)];
        }

        // Vérifier si l'utilisateur a déjà voté
        $existingVotes = PollVote::whereIn('poll_option_id', $topic->pollOptions()->pluck('id'))
            ->where('user_id', $userId)
            ->get();

        DB::beginTransaction();

        try {
            // Si l'utilisateur a déjà voté et qu'on permet le changement
            if ($existingVotes->isNotEmpty()) {
                if (! $topic->poll_allow_change_vote) {
                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' => 'Vous avez déjà voté et ne pouvez pas modifier votre vote.',
                    ], 400);
                }

                // Supprimer les anciens votes
                foreach ($existingVotes as $vote) {
                    $vote->option->decrementVotes();
                    $vote->delete();
                }
            }

            // Créer les nouveaux votes
            foreach ($optionIds as $optionId) {
                PollVote::createFromRequest($optionId, $userId, $request);
                PollOption::find($optionId)->incrementVotes();
            }

            DB::commit();

            // Recharger les options avec les nouveaux compteurs
            $topic->load('pollOptions');

            return response()->json([
                'success' => true,
                'message' => 'Votre vote a été enregistré.',
                'data' => [
                    'voted_options' => $optionIds,
                    'total_votes' => $topic->totalPollVotes(),
                    'options' => $topic->pollOptions->map(fn ($o) => [
                        'id' => $o->id,
                        'label' => $o->label,
                        'votes_count' => $o->votes_count,
                        'percentage' => $o->percentage,
                    ]),
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement du vote.',
            ], 500);
        }
    }

    /**
     * Obtenir les résultats d'un sondage
     */
    public function results(Topic $topic): JsonResponse
    {
        if ($topic->idea_type !== 'poll') {
            return response()->json([
                'success' => false,
                'message' => 'Ce sujet n\'est pas un sondage.',
            ], 400);
        }

        $userId = auth()->id();
        $userVotes = [];

        if ($userId) {
            $userVotes = PollVote::whereIn('poll_option_id', $topic->pollOptions()->pluck('id'))
                ->where('user_id', $userId)
                ->pluck('poll_option_id')
                ->toArray();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'topic_id' => $topic->id,
                'poll_type' => $topic->poll_type,
                'poll_ends_at' => $topic->poll_ends_at?->toIso8601String(),
                'is_active' => $topic->isPollActive(),
                'total_votes' => $topic->totalPollVotes(),
                'user_votes' => $userVotes,
                'options' => $topic->pollOptions->map(fn ($o) => [
                    'id' => $o->id,
                    'label' => $o->label,
                    'icon' => $o->icon,
                    'votes_count' => $o->votes_count,
                    'percentage' => $o->percentage,
                ]),
            ],
        ]);
    }
}
