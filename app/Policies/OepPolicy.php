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
        // FIXED: Was allowing ALL users - should restrict to specific roles
        return in_array($user->role, ['admin', 'campus_admin', 'viewer']);
    }

    public function view(User $user, Oep $oep): bool
    {
        // FIXED: Was allowing ALL users - should restrict to specific roles
        return in_array($user->role, ['admin', 'campus_admin', 'viewer']);
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

    public function apiList(User $user): bool
    {
        // API list can be accessed by authenticated users who need dropdown data
        return in_array($user->role, ['admin', 'campus_admin', 'viewer']);
    }
}
