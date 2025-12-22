<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VisaProcess;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy for visa processing operations
 *
 * Roles with access:
 * - super_admin/admin: Full access to all visa processes
 * - project_director: View all, limited modifications
 * - campus_admin: Access to their campus candidates only
 * - oep: Access to their assigned candidates
 * - visa_partner: Full access to visa processing (their specialty)
 * - instructor/trainer: View access only
 * - viewer: Read-only access
 */
class VisaProcessPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view visa process lists.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [
            User::ROLE_SUPER_ADMIN,
            User::ROLE_ADMIN,
            User::ROLE_PROJECT_DIRECTOR,
            User::ROLE_CAMPUS_ADMIN,
            User::ROLE_OEP,
            User::ROLE_VISA_PARTNER,
            User::ROLE_TRAINER,
            User::ROLE_INSTRUCTOR,
            User::ROLE_VIEWER,
        ]);
    }

    /**
     * Determine if the user can view specific visa process details.
     */
    public function view(User $user, VisaProcess $visaProcess = null): bool
    {
        // Super admins, project directors, and viewers can view all
        if ($user->isSuperAdmin() || $user->isProjectDirector() || $user->isViewer()) {
            return true;
        }

        // Visa partners can view all visa processes
        if ($user->isVisaPartner()) {
            return true;
        }

        // Campus admin can only view their campus
        if ($user->isCampusAdmin() && $user->campus_id) {
            if ($visaProcess && $visaProcess->candidate) {
                return $visaProcess->candidate->campus_id === $user->campus_id;
            }
            return true;
        }

        // OEP can view their assigned candidates
        if ($user->isOep() && $user->oep_id) {
            if ($visaProcess && $visaProcess->candidate) {
                return $visaProcess->candidate->oep_id === $user->oep_id;
            }
            return true;
        }

        // Trainers/Instructors can view
        if ($user->isTrainer()) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can create visa processes.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, [
            User::ROLE_SUPER_ADMIN,
            User::ROLE_ADMIN,
            User::ROLE_CAMPUS_ADMIN,
            User::ROLE_OEP,
            User::ROLE_VISA_PARTNER,
        ]);
    }

    /**
     * Determine if the user can update visa processes.
     */
    public function update(User $user, VisaProcess $visaProcess = null): bool
    {
        // Super admins and visa partners can update all
        if ($user->isSuperAdmin() || $user->isVisaPartner()) {
            return true;
        }

        // Campus admin can only update their campus
        if ($user->isCampusAdmin() && $user->campus_id) {
            if ($visaProcess && $visaProcess->candidate) {
                return $visaProcess->candidate->campus_id === $user->campus_id;
            }
            return true;
        }

        // OEP can update their assigned candidates
        if ($user->isOep() && $user->oep_id) {
            if ($visaProcess && $visaProcess->candidate) {
                return $visaProcess->candidate->oep_id === $user->oep_id;
            }
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can delete visa processes.
     */
    public function delete(User $user, VisaProcess $visaProcess = null): bool
    {
        // Only super admins can delete
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Campus admin can delete from their campus
        if ($user->isCampusAdmin() && $user->campus_id) {
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
        return in_array($user->role, [
            User::ROLE_SUPER_ADMIN,
            User::ROLE_ADMIN,
            User::ROLE_CAMPUS_ADMIN,
            User::ROLE_OEP,
            User::ROLE_VISA_PARTNER,
        ]);
    }

    /**
     * Determine if the user can view visa timeline.
     */
    public function viewTimeline(User $user): bool
    {
        return in_array($user->role, [
            User::ROLE_SUPER_ADMIN,
            User::ROLE_ADMIN,
            User::ROLE_PROJECT_DIRECTOR,
            User::ROLE_CAMPUS_ADMIN,
            User::ROLE_OEP,
            User::ROLE_VISA_PARTNER,
            User::ROLE_TRAINER,
            User::ROLE_INSTRUCTOR,
            User::ROLE_VIEWER,
        ]);
    }

    /**
     * Determine if the user can view reports.
     */
    public function viewReports(User $user): bool
    {
        return in_array($user->role, [
            User::ROLE_SUPER_ADMIN,
            User::ROLE_ADMIN,
            User::ROLE_PROJECT_DIRECTOR,
            User::ROLE_CAMPUS_ADMIN,
            User::ROLE_VISA_PARTNER,
            User::ROLE_TRAINER,
            User::ROLE_INSTRUCTOR,
            User::ROLE_VIEWER,
        ]);
    }
}
