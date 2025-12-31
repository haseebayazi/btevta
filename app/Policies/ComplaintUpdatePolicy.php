<?php

namespace App\Policies;

use App\Models\ComplaintUpdate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ComplaintUpdatePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin();
    }

    public function view(User $user, ComplaintUpdate $update): bool
    {
        if ($user->isSuperAdmin() || $user->isProjectDirector()) {
            return true;
        }

        // Campus admin can view updates for complaints from their campus
        if ($user->isCampusAdmin() && $update->complaint && $update->complaint->candidate) {
            return $user->campus_id === $update->complaint->candidate->campus_id;
        }

        // User can view their own updates
        if ($update->user_id === $user->id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin();
    }

    public function update(User $user, ComplaintUpdate $update): bool
    {
        // Updates are immutable audit records
        return false;
    }

    public function delete(User $user, ComplaintUpdate $update): bool
    {
        // Updates should not be deleted - they are audit records
        return $user->isSuperAdmin();
    }
}
