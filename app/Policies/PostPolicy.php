<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PostPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any posts.
     */
    public function viewAny(?User $user): bool
    {
        // Tous peuvent voir les posts
        return true;
    }

    /**
     * Determine if the user can view the post.
     */
    public function view(?User $user, Post $post): bool
    {
        // Si le post est masqué, seuls modérateurs/admins peuvent voir
        if ($post->is_hidden) {
            return $user && $user->hasAnyRole(['moderator', 'admin']);
        }

        // Post visible par tous
        return true;
    }

    /**
     * Determine if the user can create posts.
     */
    public function create(User $user, ?Topic $topic = null): bool
    {
        // User doit avoir la permission
        if (!$user->hasPermissionTo('posts.create')) {
            return false;
        }

        // User ne doit pas être muted/banned
        if (!$user->canPost()) {
            return false;
        }

        // Si un topic est fourni, vérifier qu'il est ouvert
        if ($topic && $topic->status === 'closed') {
            // Seuls modérateurs/admins peuvent poster sur topic fermé
            return $user->hasAnyRole(['moderator', 'admin']);
        }

        return true;
    }

    /**
     * Determine if the user can reply to a post.
     */
    public function reply(User $user, Post $parentPost): bool
    {
        // Même logique que create, mais vérifier que le parent n'est pas masqué
        if ($parentPost->is_hidden) {
            return false;
        }

        return $this->create($user, $parentPost->topic);
    }

    /**
     * Determine if the user can update the post.
     */
    public function update(User $user, Post $post): bool
    {
        // L'auteur peut modifier son post (si pas masqué)
        if ($post->user_id === $user->id && !$post->is_hidden) {
            return $user->canPost();
        }

        // Modérateurs et admins peuvent toujours modifier
        return $user->hasAnyRole(['moderator', 'admin']);
    }

    /**
     * Determine if the user can delete the post.
     */
    public function delete(User $user, Post $post): bool
    {
        // L'auteur peut supprimer son post
        if ($post->user_id === $user->id) {
            return true;
        }

        // Modérateurs et admins peuvent supprimer
        return $user->hasPermissionTo('posts.delete');
    }

    /**
     * Determine if the user can hide the post (moderation).
     */
    public function hide(User $user, Post $post): bool
    {
        // Seuls modérateurs et admins peuvent masquer
        return $user->hasPermissionTo('posts.hide');
    }

    /**
     * Determine if the user can unhide the post.
     */
    public function unhide(User $user, Post $post): bool
    {
        // Seuls modérateurs et admins peuvent démasquer
        return $user->hasPermissionTo('posts.hide');
    }

    /**
     * Determine if the user can vote on the post.
     */
    public function vote(User $user, Post $post): bool
    {
        // User ne peut pas voter sur son propre post
        if ($post->user_id === $user->id) {
            return false;
        }

        // User doit avoir la permission
        if (!$user->hasPermissionTo('posts.vote')) {
            return false;
        }

        // User ne doit pas être muted/banned
        if ($user->isMuted() || $user->isBanned()) {
            return false;
        }

        // Post ne doit pas être masqué
        return !$post->is_hidden;
    }

    /**
     * Determine if the user can pin the post.
     */
    public function pin(User $user, Post $post): bool
    {
        // Seuls modérateurs, admins et l'auteur du topic peuvent épingler
        return $user->hasAnyRole(['moderator', 'admin']) ||
               $post->topic->author_id === $user->id;
    }

    /**
     * Determine if the user can mark the post as official.
     */
    public function markAsOfficial(User $user, Post $post): bool
    {
        // Seuls législateurs, state et admins peuvent marquer comme officiel
        return $user->hasAnyRole(['legislator', 'state', 'admin']);
    }

    /**
     * Determine if the user can report the post.
     */
    public function report(User $user, Post $post): bool
    {
        // User ne peut pas signaler son propre post
        if ($post->user_id === $user->id) {
            return false;
        }

        // User doit avoir la permission
        return $user->hasPermissionTo('reports.create') &&
               !$user->isBanned();
    }
}

