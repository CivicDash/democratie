<?php

namespace App\Services;

use App\Models\BallotToken;
use App\Models\Topic;
use App\Models\TopicBallot;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Service CRITIQUE pour gérer le vote anonyme
 * 
 * Ce service garantit qu'aucun lien entre l'identité de l'utilisateur
 * et son vote ne peut être établi.
 */
class BallotService
{
    public function __construct(
        protected CacheService $cacheService
    ) {}
    /**
     * Génère un token de vote unique pour un utilisateur sur un topic.
     * 
     * @throws RuntimeException Si le user a déjà un token ou ne peut pas voter
     */
    public function generateToken(User $user, Topic $topic): BallotToken
    {
        // Vérifier que le user peut voter
        if (!$user->can('vote', $topic)) {
            throw new RuntimeException('User cannot vote on this topic.');
        }

        // Vérifier qu'il n'a pas déjà un token
        $existingToken = BallotToken::where('user_id', $user->id)
            ->where('topic_id', $topic->id)
            ->first();

        if ($existingToken) {
            throw new RuntimeException('User already has a token for this topic.');
        }

        return DB::transaction(function () use ($user, $topic) {
            // Générer un token unique (SHA512)
            $tokenValue = hash('sha512', Str::random(128) . time() . $user->id);
            $tokenHash = hash('sha512', $tokenValue);

            // Créer le token
            $token = BallotToken::create([
                'user_id' => $user->id,
                'topic_id' => $topic->id,
                'token' => $tokenValue,
                'token_hash' => $tokenHash,
                'expires_at' => $topic->voting_deadline_at,
            ]);

            return $token;
        });
    }

    /**
     * Caste un vote de manière anonyme.
     * 
     * CRITIQUE : Cette méthode ne stocke AUCUNE référence au user_id
     * dans la table topic_ballots.
     * 
     * @param string $tokenValue Le token de vote (fourni par l'utilisateur)
     * @param array $vote Les données du vote (ex: ['choice' => 'yes'])
     * 
     * @throws RuntimeException Si le token est invalide ou expiré
     */
    public function castVote(string $tokenValue, array $vote): TopicBallot
    {
        return DB::transaction(function () use ($tokenValue, $vote) {
            // Récupérer le token par son hash
            $tokenHash = hash('sha512', $tokenValue);
            $ballotToken = BallotToken::where('token_hash', $tokenHash)->first();

            if (!$ballotToken) {
                throw new RuntimeException('Invalid ballot token.');
            }

            // Vérifier que le token est valide
            if (!$ballotToken->isValid()) {
                throw new RuntimeException('Ballot token is expired or already consumed.');
            }

            // Vérifier que le scrutin est ouvert
            $topic = $ballotToken->topic;
            if (!$topic->isVotingOpen()) {
                throw new RuntimeException('Voting is not open for this topic.');
            }

            // Chiffrer le vote
            $encryptedVote = Crypt::encryptString(json_encode($vote));

            // Générer un hash unique pour le vote (différent du token)
            $voteHash = hash('sha512', $encryptedVote . time() . Str::random(64));

            // Créer le bulletin SANS user_id
            $ballot = TopicBallot::create([
                'topic_id' => $topic->id,
                'vote_hash' => $voteHash,
                'encrypted_vote' => $encryptedVote,
            ]);

            // Consommer le token
            $ballotToken->consume();

            // CRITIQUE : À ce stade, il est impossible de lier le ballot au user
            // Le token est consommé mais le ballot ne contient aucune référence à l'utilisateur

            // Invalider le cache des résultats
            $this->cacheService->invalidateVoteResults($topic->id);

            return $ballot;
        });
    }

    /**
     * Vérifie si un utilisateur a déjà voté sur un topic.
     */
    public function hasUserVoted(User $user, Topic $topic): bool
    {
        $token = BallotToken::where('user_id', $user->id)
            ->where('topic_id', $topic->id)
            ->first();

        return $token && $token->consumed;
    }

    /**
     * Compte le nombre de votes pour un topic.
     */
    public function countVotes(Topic $topic): int
    {
        return TopicBallot::where('topic_id', $topic->id)->count();
    }

    /**
     * Calcule les résultats d'un scrutin.
     * 
     * @throws RuntimeException Si les résultats ne peuvent pas encore être révélés
     */
    public function calculateResults(Topic $topic): array
    {
        if (!$topic->canRevealResults()) {
            throw new RuntimeException('Results cannot be revealed yet.');
        }

        // Vérifier le cache d'abord
        $cached = $this->cacheService->getVoteResults($topic->id);
        if ($cached !== null) {
            return $cached;
        }

        $ballots = TopicBallot::where('topic_id', $topic->id)->get();
        $results = [];

        foreach ($ballots as $ballot) {
            try {
                // Déchiffrer le vote
                $decryptedVote = json_decode(
                    Crypt::decryptString($ballot->encrypted_vote),
                    true
                );

                $choice = $decryptedVote['choice'] ?? 'unknown';

                if (!isset($results[$choice])) {
                    $results[$choice] = 0;
                }

                $results[$choice]++;
            } catch (\Exception $e) {
                // Vote corrompu, ignorer
                continue;
            }
        }

        $data = [
            'total_votes' => $ballots->count(),
            'results' => $results,
            'revealed_at' => now(),
        ];

        // Mettre en cache les résultats
        $this->cacheService->cacheVoteResults($topic->id, $data);

        return $data;
    }

    /**
     * Vérifie l'intégrité d'un scrutin.
     * 
     * Vérifie que :
     * - Tous les votes ont un hash unique
     * - Aucun vote ne contient de user_id
     * - Le nombre de tokens consommés correspond au nombre de votes
     */
    public function verifyIntegrity(Topic $topic): array
    {
        $ballots = TopicBallot::where('topic_id', $topic->id)->get();
        $consumedTokens = BallotToken::where('topic_id', $topic->id)
            ->where('consumed', true)
            ->count();

        $issues = [];

        // Vérifier que le nombre de votes correspond aux tokens consommés
        if ($ballots->count() !== $consumedTokens) {
            $issues[] = "Mismatch: {$ballots->count()} votes but {$consumedTokens} consumed tokens.";
        }

        // Vérifier l'unicité des hashes
        $hashes = $ballots->pluck('vote_hash')->toArray();
        if (count($hashes) !== count(array_unique($hashes))) {
            $issues[] = 'Duplicate vote hashes detected.';
        }

        // Vérifier qu'aucun ballot ne contient de user_id
        foreach ($ballots as $ballot) {
            if (isset($ballot->user_id) || array_key_exists('user_id', $ballot->getAttributes())) {
                $issues[] = 'CRITICAL: Ballot contains user_id field.';
                break;
            }
        }

        return [
            'valid' => empty($issues),
            'total_votes' => $ballots->count(),
            'consumed_tokens' => $consumedTokens,
            'issues' => $issues,
        ];
    }

    /**
     * Obtient le token de vote d'un utilisateur pour un topic (si il existe).
     */
    public function getUserToken(User $user, Topic $topic): ?BallotToken
    {
        return BallotToken::where('user_id', $user->id)
            ->where('topic_id', $topic->id)
            ->first();
    }

    /**
     * Révoque tous les tokens non consommés d'un scrutin (fermeture anticipée).
     */
    public function revokeUnconsumedTokens(Topic $topic): int
    {
        return BallotToken::where('topic_id', $topic->id)
            ->where('consumed', false)
            ->update(['expires_at' => now()]);
    }

    /**
     * Exporte les résultats d'un scrutin (format anonyme).
     */
    public function exportResults(Topic $topic): array
    {
        $results = $this->calculateResults($topic);
        $integrity = $this->verifyIntegrity($topic);

        return [
            'topic' => [
                'id' => $topic->id,
                'title' => $topic->title,
                'type' => $topic->type,
                'scope' => $topic->scope,
            ],
            'voting_period' => [
                'opened_at' => $topic->voting_opens_at?->toIso8601String(),
                'closed_at' => $topic->voting_deadline_at?->toIso8601String(),
            ],
            'results' => $results,
            'integrity_check' => $integrity,
            'exported_at' => now()->toIso8601String(),
        ];
    }
}

