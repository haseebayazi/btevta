<?php

namespace App\Policies;

use App\Models\CompanySwitchLog;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CompanySwitchLogPolicy
{
    use HandlesAuthorization;

    public function view(User $user, CompanySwitchLog $switch): bool
    {
        if ($user->isSuperAdmin() || $user->isProjectDirector() || $user->isViewer()) {
            return true;
        }

        if ($user->isCampusAdmin() && $user->campus_id && $switch->candidate) {
            return $switch->candidate->campus_id === $user->campus_id;
        }

        if ($user->isOep() && $user->oep_id && $switch->candidate) {
            return $switch->candidate->oep_id === $user->oep_id;
        }

        return false;
    }

    /**
     * Determine if the user can approve a company switch.
     * Only admins and project directors can approve.
     */
    public function approve(User $user, CompanySwitchLog $switch): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector();
    }

    /**
     * Determine if the user can mark a switch as complete.
     * Admins, project directors, and campus admins can complete switches.
     */
    public function complete(User $user, CompanySwitchLog $switch): bool
    {
        if ($user->isSuperAdmin() || $user->isProjectDirector()) {
            return true;
        }

        if ($user->isCampusAdmin() && $user->campus_id && $switch->candidate) {
            return $switch->candidate->campus_id === $user->campus_id;
        }

        return false;
    }

    /**
     * Determine if the user can reject a company switch.
     */
    public function reject(User $user, CompanySwitchLog $switch): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector();
    }
}
