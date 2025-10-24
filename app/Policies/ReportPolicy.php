<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReportPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any reports.
     */
    public function viewAny(User $user): bool
    {
        // Seuls modérateurs et admins peuvent voir les rapports
        return $user->hasPermissionTo('reports.review');
    }

    /**
     * Determine if the user can view the report.
     */
    public function view(User $user, Report $report): bool
    {
        // Le créateur du rapport peut le voir
        if ($report->reporter_id === $user->id) {
            return true;
        }

        // Les modérateurs et admins peuvent voir tous les rapports
        return $user->hasPermissionTo('reports.review');
    }

    /**
     * Determine if the user can create reports.
     */
    public function create(User $user): bool
    {
        // Tous les utilisateurs non bannis peuvent créer des rapports
        return $user->hasPermissionTo('reports.create') && !$user->isBanned();
    }

    /**
     * Determine if the user can review the report.
     */
    public function review(User $user, Report $report): bool
    {
        // Seuls modérateurs et admins peuvent reviewer
        if (!$user->hasPermissionTo('reports.review')) {
            return false;
        }

        // Le rapport doit être pending ou reviewing
        return in_array($report->status, ['pending', 'reviewing']);
    }

    /**
     * Determine if the user can resolve the report.
     */
    public function resolve(User $user, Report $report): bool
    {
        // Seuls modérateurs et admins peuvent résoudre
        if (!$user->hasPermissionTo('reports.resolve')) {
            return false;
        }

        // Le rapport doit être en reviewing
        return $report->status === 'reviewing';
    }

    /**
     * Determine if the user can reject the report.
     */
    public function reject(User $user, Report $report): bool
    {
        // Même logique que resolve
        return $this->resolve($user, $report);
    }

    /**
     * Determine if the user can update the report.
     */
    public function update(User $user, Report $report): bool
    {
        // Seuls modérateurs/admins peuvent mettre à jour
        // Le créateur peut mettre à jour seulement si pending
        if ($report->reporter_id === $user->id && $report->status === 'pending') {
            return true;
        }

        return $user->hasPermissionTo('reports.review');
    }

    /**
     * Determine if the user can delete the report.
     */
    public function delete(User $user, Report $report): bool
    {
        // Le créateur peut supprimer si pending
        if ($report->reporter_id === $user->id && $report->status === 'pending') {
            return true;
        }

        // Admins peuvent supprimer
        return $user->hasRole('admin');
    }

    /**
     * Determine if the user can assign the report to themselves.
     */
    public function assign(User $user, Report $report): bool
    {
        // Modérateurs et admins peuvent s'assigner des rapports pending
        return $user->hasPermissionTo('reports.review') && 
               $report->status === 'pending';
    }

    /**
     * Determine if the user can add notes to the report.
     */
    public function addNotes(User $user, Report $report): bool
    {
        // Modérateurs assignés ou admins peuvent ajouter des notes
        return $user->hasPermissionTo('reports.review') &&
               ($report->moderator_id === $user->id || $user->hasRole('admin'));
    }
}

