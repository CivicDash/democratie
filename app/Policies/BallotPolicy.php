<?php

namespace App\Policies;

use App\Models\BallotToken;
use App\Models\Topic;
use App\Models\TopicBallot;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BallotPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view ballot results.
     */
    public function viewResults(?User $user, Topic $topic): bool
    {
        // Vérifier que le topic a un scrutin
        if (!$topic->has_ballot) {
            return false;
        }

        // Résultats visibles après la deadline
        if ($topic->canRevealResults()) {
            return true;
        }

        // Avant deadline, seuls l'auteur et les admins peuvent voir
        if ($user) {
            return $topic->author_id === $user->id || $user->hasRole('admin');
        }

        return false;
    }

    /**
     * Determine if the user can vote on the ballot.
     */
    public function vote(User $user, Topic $topic): bool
    {
        // Vérifier que le topic a un scrutin
        if (!$topic->has_ballot) {
            return false;
        }

        // Vérifier que le scrutin est ouvert
        if (!$topic->isVotingOpen()) {
            return false;
        }

        // User doit avoir la permission
        if (!$user->hasPermissionTo('ballots.vote')) {
            return false;
        }

        // User ne doit pas être muted/banned
        if ($user->isMuted() || $user->isBanned()) {
            return false;
        }

        // User ne doit pas avoir déjà voté
        if ($user->hasVotedOn($topic)) {
            return false;
        }

        // User doit être éligible (scope territorial)
        return $this->isEligibleToVote($user, $topic);
    }

    /**
     * Determine if the user can request a ballot token.
     */
    public function requestToken(User $user, Topic $topic): bool
    {
        // Mêmes vérifications que vote
        if (!$this->vote($user, $topic)) {
            return false;
        }

        // Vérifier que le user n'a pas déjà un token pour ce topic
        $existingToken = BallotToken::where('user_id', $user->id)
            ->where('topic_id', $topic->id)
            ->first();

        return $existingToken === null;
    }

    /**
     * Determine if the user can create a ballot for a topic.
     */
    public function create(User $user, Topic $topic): bool
    {
        // User doit être l'auteur du topic ou avoir la permission
        if ($topic->author_id !== $user->id && !$user->hasPermissionTo('ballots.create')) {
            return false;
        }

        // Topic ne doit pas déjà avoir un scrutin
        if ($topic->has_ballot) {
            return false;
        }

        // Topic doit être open ou draft
        return in_array($topic->status, ['draft', 'open']);
    }

    /**
     * Determine if the user can update ballot configuration.
     */
    public function update(User $user, Topic $topic): bool
    {
        // User doit être l'auteur ou admin
        if ($topic->author_id !== $user->id && !$user->hasRole('admin')) {
            return false;
        }

        // Le scrutin ne doit pas avoir commencé
        if ($topic->voting_opens_at && $topic->voting_opens_at <= now()) {
            return false;
        }

        return true;
    }

    /**
     * Determine if the user can close the ballot early.
     */
    public function close(User $user, Topic $topic): bool
    {
        // Seul l'auteur ou admin peut fermer prématurément
        if ($topic->author_id !== $user->id && !$user->hasRole('admin')) {
            return false;
        }

        // Le scrutin doit être ouvert
        return $topic->isVotingOpen();
    }

    /**
     * Determine if the user can extend ballot deadline.
     */
    public function extend(User $user, Topic $topic): bool
    {
        // Seuls les admins peuvent étendre la deadline
        if (!$user->hasRole('admin')) {
            return false;
        }

        // Le scrutin doit être ouvert ou sur le point de fermer
        return $topic->voting_deadline_at >= now()->subHour();
    }

    /**
     * Determine if the user can view individual ballot votes (for audit).
     */
    public function viewVotes(User $user, Topic $topic): bool
    {
        // CRITIQUE : Même les admins ne peuvent pas voir qui a voté quoi
        // Ils peuvent seulement voir les votes chiffrés
        return $user->hasRole('admin') && $topic->canRevealResults();
    }

    /**
     * Determine if the user can export ballot results.
     */
    public function export(User $user, Topic $topic): bool
    {
        // State et admins peuvent exporter après deadline
        return $user->hasAnyRole(['state', 'admin']) && $topic->canRevealResults();
    }

    /**
     * Check if user is eligible to vote based on territorial scope.
     */
    protected function isEligibleToVote(User $user, Topic $topic): bool
    {
        // Si le topic est national, tous peuvent voter
        if ($topic->scope === 'national') {
            return true;
        }

        // User doit avoir un profil
        if (!$user->profile) {
            return false;
        }

        // Si régional, vérifier la région
        if ($topic->scope === 'region') {
            return $user->profile->region_id === $topic->region_id;
        }

        // Si départemental, vérifier le département
        if ($topic->scope === 'dept') {
            return $user->profile->department_id === $topic->department_id;
        }

        return false;
    }
}

