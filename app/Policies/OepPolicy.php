<?php

namespace App\Policies;

use App\Models\Oep;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OepPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isOep() || $user->isViewer();
    }

    public function view(User $user, Oep $oep): bool
    {
        if ($user->isSuperAdmin() || $user->isProjectDirector() || $user->isViewer()) {
            return true;
        }

        // OEP user can only view their own OEP
        if ($user->isOep() && $user->oep_id === $oep->id) {
            return true;
        }

        return $user->isCampusAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, Oep $oep): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, Oep $oep): bool
    {
        return $user->isSuperAdmin();
    }

    public function toggleStatus(User $user, Oep $oep): bool
    {
        return $user->isSuperAdmin();
    }

    public function apiList(User $user): bool
    {
        // API list can be accessed by authenticated users who need dropdown data
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isOep() || $user->isViewer();
    }
}
