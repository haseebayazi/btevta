<?php

namespace App\Policies;

use App\Models\Correspondence;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CorrespondencePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isOep() || $user->isViewer();
    }

    public function view(User $user, Correspondence $correspondence): bool
    {
        // Admin and Project Director can view all
        if ($user->isSuperAdmin() || $user->isProjectDirector() || $user->isViewer()) {
            return true;
        }

        // Campus users can view correspondence related to their campus
        if ($user->isCampusAdmin() && $user->campus_id) {
            return $correspondence->campus_id === $user->campus_id;
        }

        // OEP users can view correspondence related to their OEP
        if ($user->isOep() && $user->oep_id) {
            return $correspondence->oep_id === $user->oep_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin();
    }

    public function update(User $user, Correspondence $correspondence): bool
    {
        // Admin and Project Director can update all
        if ($user->isSuperAdmin() || $user->isProjectDirector()) {
            return true;
        }

        // Campus users can update their own correspondence
        if ($user->isCampusAdmin() && $user->campus_id) {
            return $correspondence->campus_id === $user->campus_id;
        }

        return false;
    }

    public function delete(User $user, Correspondence $correspondence): bool
    {
        return $user->isSuperAdmin();
    }

    public function markReplied(User $user, Correspondence $correspondence): bool
    {
        return $this->update($user, $correspondence);
    }
}
