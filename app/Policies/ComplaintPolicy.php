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
        // FIXED: Was allowing ALL users - should restrict to specific roles
        return in_array($user->role, ['admin', 'campus_admin', 'viewer']);
    }

    public function view(User $user, Complaint $complaint): bool
    {
        // Admin can view all
        if ($user->role === 'admin') {
            return true;
        }

        // Assigned user can view
        if ($complaint->assigned_to === $user->id) {
            return true;
        }

        // Campus users can view complaints related to their candidates
        if ($user->role === 'campus_admin' && $user->campus_id && $complaint->candidate) {
            return $complaint->candidate->campus_id === $user->campus_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        // FIXED: Was allowing ALL users - should restrict to specific roles
        // Only admin and campus_admin can create complaints, not viewers or instructors
        return in_array($user->role, ['admin', 'campus_admin']);
    }

    public function update(User $user, Complaint $complaint): bool
    {
        // Admin can update all
        if ($user->role === 'admin') {
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
        return $user->role === 'admin';
    }

    public function assign(User $user, Complaint $complaint): bool
    {
        return $user->role === 'admin';
    }

    public function resolve(User $user, Complaint $complaint): bool
    {
        return $user->role === 'admin' || $complaint->assigned_to === $user->id;
    }

    public function escalate(User $user, Complaint $complaint): bool
    {
        return $user->role === 'admin' || $complaint->assigned_to === $user->id;
    }

    public function close(User $user, Complaint $complaint): bool
    {
        return $user->role === 'admin';
    }

    public function reopen(User $user, Complaint $complaint): bool
    {
        return $user->role === 'admin';
    }
}
