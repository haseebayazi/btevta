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
        return true;
    }

    public function view(User $user, Batch $batch): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'campus' && $user->campus_id) {
            return $batch->campus_id === $user->campus_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus']);
    }

    public function update(User $user, Batch $batch): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'campus' && $user->campus_id) {
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
        return in_array($user->role, ['admin', 'campus']);
    }
}
