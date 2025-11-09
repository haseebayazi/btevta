<?php

namespace App\Policies;

use App\Models\Oep;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OepPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Oep $oep): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, Oep $oep): bool
    {
        return $user->role === 'admin';
    }

    public function delete(User $user, Oep $oep): bool
    {
        return $user->role === 'admin';
    }

    public function toggleStatus(User $user, Oep $oep): bool
    {
        return $user->role === 'admin';
    }
}
