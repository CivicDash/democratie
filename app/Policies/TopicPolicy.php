<?php

namespace App\Policies;

use App\Models\Topic;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TopicPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any topics.
     */
    public function viewAny(?User $user): bool
    {
        // Tous les utilisateurs (même non authentifiés) peuvent voir les topics
        return true;
    }

    /**
     * Determine if the user can view the topic.
     */
    public function view(?User $user, Topic $topic): bool
    {
        // Tous peuvent voir un topic ouvert
        if ($topic->status === 'open') {
            return true;
        }

        // Seul l'auteur et les admins/modos peuvent voir les drafts
        if ($topic->status === 'draft') {
            return $user && (
                $topic->author_id === $user->id ||
                $user->hasAnyRole(['admin', 'moderator'])
            );
        }

        // Les topics fermés et archivés sont visibles par tous
        return true;
    }

    /**
     * Determine if the user can create topics.
     */
    public function create(User $user): bool
    {
        // Citoyens et législateurs peuvent créer des topics
        return $user->hasAnyPermission(['topics.create']) &&
               !$user->isMuted() &&
               !$user->isBanned();
    }

    /**
     * Determine if the user can create a bill topic.
     */
    public function createBill(User $user): bool
    {
        // Seuls les législateurs peuvent créer des bills
        return $user->hasPermissionTo('topics.bill') && !$user->isMuted() && !$user->isBanned();
    }

    /**
     * Determine if the user can update the topic.
     */
    public function update(User $user, Topic $topic): bool
    {
        // L'auteur peut modifier son topic (si draft ou open)
        if ($topic->author_id === $user->id && in_array($topic->status, ['draft', 'open'])) {
            return !$user->isMuted() && !$user->isBanned();
        }

        // Admin et modérateurs peuvent toujours modifier
        return $user->hasAnyRole(['admin', 'moderator']);
    }

    /**
     * Determine if the user can delete the topic.
     */
    public function delete(User $user, Topic $topic): bool
    {
        // L'auteur peut supprimer son topic draft
        if ($topic->author_id === $user->id && $topic->status === 'draft') {
            return true;
        }

        // Admin peut supprimer
        return $user->hasRole('admin');
    }

    /**
     * Determine if the user can close the topic.
     */
    public function close(User $user, Topic $topic): bool
    {
        // L'auteur, modérateurs et admins peuvent fermer
        return $topic->author_id === $user->id ||
               $user->hasPermissionTo('topics.close');
    }

    /**
     * Determine if the user can pin the topic.
     */
    public function pin(User $user, Topic $topic): bool
    {
        // Seuls modérateurs et admins peuvent épingler
        return $user->hasPermissionTo('topics.pin');
    }

    /**
     * Determine if the user can create a ballot for the topic.
     */
    public function createBallot(User $user, Topic $topic): bool
    {
        // L'auteur du topic peut créer un scrutin
        // OU les législateurs/admins
        return ($topic->author_id === $user->id || $user->hasPermissionTo('ballots.create')) &&
               !$topic->has_ballot; // Pas déjà de scrutin
    }

    /**
     * Determine if the user can vote on the topic.
     */
    public function vote(User $user, Topic $topic): bool
    {
        // Vérifier que le topic a un scrutin et qu'il est ouvert
        if (!$topic->has_ballot || !$topic->isVotingOpen()) {
            return false;
        }

        // User doit avoir la permission de voter
        if (!$user->hasPermissionTo('ballots.vote')) {
            return false;
        }

        // User ne doit pas être muted/banned
        if ($user->isMuted() || $user->isBanned()) {
            return false;
        }

        // User ne doit pas avoir déjà voté
        return !$user->hasVotedOn($topic);
    }

    /**
     * Determine if the user can view ballot results.
     */
    public function viewResults(?User $user, Topic $topic): bool
    {
        // Résultats visibles seulement après la deadline
        if (!$topic->canRevealResults()) {
            // Sauf pour admins et l'auteur
            return $user && (
                $user->hasRole('admin') ||
                $topic->author_id === $user->id
            );
        }

        // Après deadline, visible par tous
        return true;
    }

    /**
     * Determine if the user can reply to the topic.
     */
    public function reply(User $user, Topic $topic): bool
    {
        // Le topic doit être ouvert
        if ($topic->status !== 'open') {
            return false;
        }

        // User doit avoir la permission de créer des posts
        if (!$user->hasPermissionTo('create_posts')) {
            return false;
        }

        // User ne doit pas être muted/banned
        return !$user->isMuted() && !$user->isBanned();
    }

    /**
     * Determine if the user can archive the topic.
     */
    public function archive(User $user, Topic $topic): bool
    {
        // Modérateurs et admins peuvent archiver
        return $user->hasAnyRole(['moderator', 'admin']);
    }
}

