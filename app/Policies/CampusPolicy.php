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
        return true;
    }

    public function view(User $user, Campus $campus): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, Campus $campus): bool
    {
        return $user->role === 'admin';
    }

    public function delete(User $user, Campus $campus): bool
    {
        return $user->role === 'admin';
    }

    public function toggleStatus(User $user, Campus $campus): bool
    {
        return $user->role === 'admin';
    }
}
