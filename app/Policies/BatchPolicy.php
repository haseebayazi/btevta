<?php

namespace App\Policies;

use App\Models\Batch;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BatchPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        // FIXED: Was allowing ALL users - should restrict to specific roles
        return in_array($user->role, ['admin', 'campus_admin', 'viewer']);
    }

    public function view(User $user, Batch $batch): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'campus_admin' && $user->campus_id) {
            return $batch->campus_id === $user->campus_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus_admin']);
    }

    public function update(User $user, Batch $batch): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'campus_admin' && $user->campus_id) {
            return $batch->campus_id === $user->campus_id;
        }

        return false;
    }

    public function delete(User $user, Batch $batch): bool
    {
        return $user->role === 'admin';
    }

    public function changeStatus(User $user, Batch $batch): bool
    {
        return in_array($user->role, ['admin', 'campus_admin']);
    }

    public function apiList(User $user): bool
    {
        // API list can be accessed by authenticated users who need dropdown data
        return in_array($user->role, ['admin', 'campus_admin', 'viewer']);
    }

    public function byCampus(User $user): bool
    {
        // API endpoint for batches by campus - needed for dropdown filtering
        return in_array($user->role, ['admin', 'campus_admin', 'viewer']);
    }
}
