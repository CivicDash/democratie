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
        return $user->hasPermissionTo('view_reports');
    }

    /**
     * Determine if the user can view the report.
     */
    public function view(User $user, Report $report): bool
    {
        if ($report->reporter_id === $user->id) {
            return true;
        }

        return $user->hasPermissionTo('view_reports');
    }

    /**
     * Determine if the user can create reports.
     */
    public function create(User $user): bool
    {
        return !$user->isMuted() && !$user->isBanned();
    }

    /**
     * Determine if the user can review the report.
     */
    public function review(User $user, Report $report): bool
    {
        if (!$user->hasPermissionTo('handle_reports')) {
            return false;
        }

        return in_array($report->status, ['pending', 'reviewing']);
    }

    /**
     * Determine if the user can resolve the report.
     */
    public function resolve(User $user, Report $report): bool
    {
        if (!$user->hasPermissionTo('handle_reports')) {
            return false;
        }

        return $report->status === 'reviewing';
    }

    /**
     * Determine if the user can reject the report.
     */
    public function reject(User $user, Report $report): bool
    {
        return $this->resolve($user, $report);
    }

    /**
     * Determine if the user can update the report.
     */
    public function update(User $user, Report $report): bool
    {
        if ($report->reporter_id === $user->id && $report->status === 'pending') {
            return true;
        }

        return $user->hasPermissionTo('handle_reports');
    }

    /**
     * Determine if the user can delete the report.
     */
    public function delete(User $user, Report $report): bool
    {
        if ($report->reporter_id === $user->id && $report->status === 'pending') {
            return true;
        }

        return $user->hasRole('admin');
    }

    /**
     * Determine if the user can assign the report to themselves.
     */
    public function assign(User $user, Report $report): bool
    {
        return $user->hasPermissionTo('handle_reports') &&
               $report->status === 'pending';
    }

    /**
     * Determine if the user can add notes to the report.
     */
    public function addNotes(User $user, Report $report): bool
    {
        return $user->hasPermissionTo('handle_reports') &&
               ($report->moderator_id === $user->id || $user->hasRole('admin'));
    }
}

