<?php

namespace App\Policies;

use App\Models\Sanction;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SanctionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any sanctions.
     */
    public function viewAny(User $user): bool
    {
        // Modérateurs et admins peuvent voir toutes les sanctions
        // Les users peuvent voir leurs propres sanctions (via viewOwn)
        return $user->hasAnyRole(['moderator', 'admin']);
    }

    /**
     * Determine if the user can view the sanction.
     */
    public function view(User $user, Sanction $sanction): bool
    {
        // Le user sanctionné peut voir sa sanction
        if ($sanction->user_id === $user->id) {
            return true;
        }

        // Modérateurs et admins peuvent voir toutes les sanctions
        return $user->hasAnyRole(['moderator', 'admin']);
    }

    /**
     * Determine if the user can view their own sanctions.
     */
    public function viewOwn(User $user): bool
    {
        // Tous les users peuvent voir leurs propres sanctions
        return true;
    }

    /**
     * Determine if the user can create sanctions.
     */
    public function create(User $user, User $targetUser): bool
    {
        // Seuls modérateurs et admins peuvent créer des sanctions
        if (!$user->hasPermissionTo('sanctions.create')) {
            return false;
        }

        // Ne peut pas se sanctionner soi-même
        if ($user->id === $targetUser->id) {
            return false;
        }

        // Ne peut pas sanctionner un admin (sauf si on est admin)
        if ($targetUser->hasRole('admin') && !$user->hasRole('admin')) {
            return false;
        }

        // Modérateur ne peut pas sanctionner un autre modérateur
        if ($targetUser->hasRole('moderator') && $user->hasRole('moderator') && !$user->hasRole('admin')) {
            return false;
        }

        return true;
    }

    /**
     * Determine if the user can create a warning.
     */
    public function createWarning(User $user, User $targetUser): bool
    {
        return $this->create($user, $targetUser);
    }

    /**
     * Determine if the user can create a mute.
     */
    public function createMute(User $user, User $targetUser): bool
    {
        return $this->create($user, $targetUser);
    }

    /**
     * Determine if the user can create a ban.
     */
    public function createBan(User $user, User $targetUser): bool
    {
        // Seuls admins peuvent ban définitivement
        if (!$user->hasRole('admin')) {
            return false;
        }

        return $this->create($user, $targetUser);
    }

    /**
     * Determine if the user can update the sanction.
     */
    public function update(User $user, Sanction $sanction): bool
    {
        // Seul le modérateur qui a créé la sanction ou un admin peut la modifier
        if ($sanction->moderator_id === $user->id) {
            return true;
        }

        return $user->hasRole('admin');
    }

    /**
     * Determine if the user can revoke the sanction.
     */
    public function revoke(User $user, Sanction $sanction): bool
    {
        // La sanction doit être active
        if (!$sanction->is_active) {
            return false;
        }

        // Le modérateur qui a créé la sanction ou un admin peut la révoquer
        return $sanction->moderator_id === $user->id || $user->hasRole('admin');
    }

    /**
     * Determine if the user can delete the sanction.
     */
    public function delete(User $user, Sanction $sanction): bool
    {
        // Seuls admins peuvent supprimer (pour historique)
        return $user->hasRole('admin');
    }

    /**
     * Determine if the user can view sanction history for a user.
     */
    public function viewHistory(User $user, User $targetUser): bool
    {
        // User peut voir son propre historique
        if ($user->id === $targetUser->id) {
            return true;
        }

        // Modérateurs et admins peuvent voir l'historique
        return $user->hasAnyRole(['moderator', 'admin']);
    }
}

