<?php

namespace App\Policies;

use App\Models\Undertaking;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UndertakingPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isViewer();
    }

    public function view(User $user, Undertaking $undertaking): bool
    {
        if ($user->isSuperAdmin() || $user->isProjectDirector() || $user->isViewer()) {
            return true;
        }

        // Campus admin can view undertakings for candidates from their campus
        if ($user->isCampusAdmin() && $undertaking->candidate && $user->campus_id === $undertaking->candidate->campus_id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin();
    }

    public function update(User $user, Undertaking $undertaking): bool
    {
        // Undertakings should generally not be updated after signing
        return $user->isSuperAdmin();
    }

    public function delete(User $user, Undertaking $undertaking): bool
    {
        return $user->isSuperAdmin();
    }

    public function download(User $user, Undertaking $undertaking): bool
    {
        return $this->view($user, $undertaking);
    }
}
