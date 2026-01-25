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
        if ($user->isCampusAdmin() && $update->complaint) {
            // Check direct campus_id on complaint first, then fall back to candidate's campus
            $complaintCampusId = $update->complaint->campus_id
                ?? $update->complaint->candidate?->campus_id;
            if ($user->campus_id === $complaintCampusId) {
                return true;
            }
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
        // Only super_admin can update complaint updates (for corrections)
        return $user->role === User::ROLE_SUPER_ADMIN;
    }

    public function delete(User $user, ComplaintUpdate $update): bool
    {
        // Only super_admin (not regular admin) can delete updates
        return $user->role === User::ROLE_SUPER_ADMIN;
    }
}
