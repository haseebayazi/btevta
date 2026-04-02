<?php

namespace App\Policies;

use App\Models\PostDepartureDetail;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PostDepartureDetailPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin()
            || $user->isOep() || $user->isViewer();
    }

    public function view(User $user, PostDepartureDetail $detail): bool
    {
        if ($user->isSuperAdmin() || $user->isProjectDirector() || $user->isViewer()) {
            return true;
        }

        if ($user->isCampusAdmin() && $user->campus_id && $detail->candidate) {
            return $detail->candidate->campus_id === $user->campus_id;
        }

        if ($user->isOep() && $user->oep_id && $detail->candidate) {
            return $detail->candidate->oep_id === $user->oep_id;
        }

        return false;
    }

    public function update(User $user, PostDepartureDetail $detail): bool
    {
        if ($user->isSuperAdmin() || $user->isProjectDirector()) {
            return true;
        }

        if ($user->isCampusAdmin() && $user->campus_id && $detail->candidate) {
            return $detail->candidate->campus_id === $user->campus_id;
        }

        return false;
    }

    /**
     * Determine if the user can verify 90-day compliance.
     * Restricted to admins and project directors only.
     */
    public function verify(User $user, PostDepartureDetail $detail): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector();
    }
}
