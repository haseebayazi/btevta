<?php

namespace App\Policies;

use App\Models\Campus;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CampusPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isViewer();
    }

    public function view(User $user, Campus $campus): bool
    {
        if ($user->isSuperAdmin() || $user->isProjectDirector() || $user->isViewer()) {
            return true;
        }

        // Campus admin can only view their own campus
        if ($user->isCampusAdmin() && $user->campus_id && $user->campus_id === $campus->id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, Campus $campus): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, Campus $campus): bool
    {
        return $user->isSuperAdmin();
    }

    public function toggleStatus(User $user, Campus $campus): bool
    {
        return $user->isSuperAdmin();
    }

    public function apiList(User $user): bool
    {
        // API list can be accessed by authenticated users who need dropdown data
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isViewer();
    }
}
