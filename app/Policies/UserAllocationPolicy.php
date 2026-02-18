<?php

namespace App\Policies;

use App\Models\Sector;
use App\Models\User;
use App\Models\UserAllocation;
use Illuminate\Auth\Access\HandlesAuthorization;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class UserAllocationPolicy
{
    use HandlesAuthorization;

    private function canAllocateBudget(User $user): bool
    {
        try {
            return $user->hasPermissionTo('budget.allocate');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    /**
     * Determine if the user can view any allocations.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can view the allocation.
     */
    public function view(User $user, UserAllocation $allocation): bool
    {
        if ($allocation->user_id === $user->id) {
            return true;
        }

        return $user->hasAnyRole(['state', 'admin']);
    }

    /**
     * Determine if the user can view their own allocations.
     */
    public function viewOwn(User $user): bool
    {
        return $this->canAllocateBudget($user);
    }

    /**
     * Determine if the user can create allocations.
     */
    public function create(User $user): bool
    {
        if (!$this->canAllocateBudget($user)) {
            return false;
        }

        if ($user->isBanned()) {
            return false;
        }

        return $user->profile !== null;
    }

    /**
     * Determine if the user can allocate to a specific sector.
     */
    public function allocateToSector(User $user, Sector $sector): bool
    {
        return $this->create($user);
    }

    /**
     * Determine if the user can update the allocation.
     */
    public function update(User $user, UserAllocation $allocation): bool
    {
        // User peut uniquement mettre à jour ses propres allocations
        if ($allocation->user_id !== $user->id) {
            return false;
        }

        // Vérifier les permissions de base
        return $this->create($user);
    }

    /**
     * Determine if the user can delete the allocation.
     */
    public function delete(User $user, UserAllocation $allocation): bool
    {
        // User peut supprimer ses propres allocations
        if ($allocation->user_id !== $user->id) {
            return false;
        }

        return $this->create($user);
    }

    /**
     * Determine if the user can reset all their allocations.
     */
    public function resetAll(User $user): bool
    {
        // User peut réinitialiser ses allocations
        return $this->create($user);
    }

    /**
     * Determine if the user can view aggregated budget results.
     */
    public function viewAggregated(?User $user): bool
    {
        // Tous (même non authentifiés) peuvent voir les résultats agrégés
        return true;
    }

    /**
     * Determine if the user can export allocations data.
     */
    public function export(User $user): bool
    {
        // Seuls state et admins peuvent exporter
        return $user->hasAnyRole(['state', 'admin']);
    }
}

