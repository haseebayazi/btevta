<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VisaProcess;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy for visa processing operations
 *
 * CRITICAL: This policy was missing, causing ALL visa processing operations to fail.
 * Controller had authorization checks but no policy existed to evaluate them.
 */
class VisaProcessPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view visa process lists.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus_admin', 'instructor', 'viewer']);
    }

    /**
     * Determine if the user can view specific visa process details.
     */
    public function view(User $user, VisaProcess $visaProcess = null): bool
    {
        // Admins and viewers can view all
        if (in_array($user->role, ['admin', 'viewer'])) {
            return true;
        }

        // Campus admin can only view their campus
        if ($user->role === 'campus_admin' && $user->campus_id) {
            if ($visaProcess && $visaProcess->candidate) {
                return $visaProcess->candidate->campus_id === $user->campus_id;
            }
            return true; // Allow access to view form
        }

        // Instructors can view
        if ($user->role === 'instructor') {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can create visa processes.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus_admin']);
    }

    /**
     * Determine if the user can update visa processes.
     */
    public function update(User $user, VisaProcess $visaProcess = null): bool
    {
        // Admins can update all
        if ($user->role === 'admin') {
            return true;
        }

        // Campus admin can only update their campus
        if ($user->role === 'campus_admin' && $user->campus_id) {
            if ($visaProcess && $visaProcess->candidate) {
                return $visaProcess->candidate->campus_id === $user->campus_id;
            }
            return true; // Allow access to update form
        }

        return false;
    }

    /**
     * Determine if the user can delete visa processes.
     */
    public function delete(User $user, VisaProcess $visaProcess = null): bool
    {
        // Only admins can delete
        if ($user->role === 'admin') {
            return true;
        }

        // Campus admin can delete from their campus
        if ($user->role === 'campus_admin' && $user->campus_id) {
            if ($visaProcess && $visaProcess->candidate) {
                return $visaProcess->candidate->campus_id === $user->campus_id;
            }
        }

        return false;
    }

    /**
     * Determine if the user can complete visa processes.
     */
    public function complete(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus_admin']);
    }

    /**
     * Determine if the user can view visa timeline.
     */
    public function viewTimeline(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus_admin', 'instructor', 'viewer']);
    }

    /**
     * Determine if the user can view reports.
     */
    public function viewReports(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus_admin', 'instructor', 'viewer']);
    }
}
