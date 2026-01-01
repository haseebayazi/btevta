<?php

namespace App\Policies;

use App\Models\NextOfKin;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NextOfKinPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isViewer();
    }

    public function view(User $user, NextOfKin $nextOfKin): bool
    {
        if ($user->isSuperAdmin() || $user->isProjectDirector() || $user->isViewer()) {
            return true;
        }

        // Campus admin can view next of kin for candidates from their campus
        if ($user->isCampusAdmin() && $nextOfKin->candidate && $user->campus_id === $nextOfKin->candidate->campus_id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin();
    }

    public function update(User $user, NextOfKin $nextOfKin): bool
    {
        if ($user->isSuperAdmin() || $user->isProjectDirector()) {
            return true;
        }

        // Campus admin can update next of kin for candidates from their campus
        if ($user->isCampusAdmin() && $nextOfKin->candidate && $user->campus_id === $nextOfKin->candidate->campus_id) {
            return true;
        }

        return false;
    }

    public function delete(User $user, NextOfKin $nextOfKin): bool
    {
        return $user->isSuperAdmin();
    }
}
