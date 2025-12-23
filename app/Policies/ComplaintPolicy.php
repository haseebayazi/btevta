<?php

namespace App\Policies;

use App\Models\Complaint;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ComplaintPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isViewer();
    }

    public function view(User $user, Complaint $complaint): bool
    {
        // Admin and Project Director can view all
        if ($user->isSuperAdmin() || $user->isProjectDirector() || $user->isViewer()) {
            return true;
        }

        // Assigned user can view
        if ($complaint->assigned_to === $user->id) {
            return true;
        }

        // Campus users can view complaints related to their candidates
        if ($user->isCampusAdmin() && $user->campus_id && $complaint->candidate) {
            return $complaint->candidate->campus_id === $user->campus_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        // Only admin, project director, and campus_admin can create complaints
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin();
    }

    public function update(User $user, Complaint $complaint): bool
    {
        // Admin and Project Director can update all
        if ($user->isSuperAdmin() || $user->isProjectDirector()) {
            return true;
        }

        // Assigned user can update
        if ($complaint->assigned_to === $user->id) {
            return true;
        }

        return false;
    }

    public function delete(User $user, Complaint $complaint): bool
    {
        return $user->isSuperAdmin();
    }

    public function assign(User $user, Complaint $complaint): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector();
    }

    public function resolve(User $user, Complaint $complaint): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $complaint->assigned_to === $user->id;
    }

    public function escalate(User $user, Complaint $complaint): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $complaint->assigned_to === $user->id;
    }

    public function close(User $user, Complaint $complaint): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector();
    }

    public function reopen(User $user, Complaint $complaint): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector();
    }
}
