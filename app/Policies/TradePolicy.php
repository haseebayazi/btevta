<?php

namespace App\Policies;

use App\Models\Trade;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TradePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        // FIXED: Was allowing ALL users - should restrict to specific roles
        return in_array($user->role, ['admin', 'campus_admin', 'viewer']);
    }

    public function view(User $user, Trade $trade): bool
    {
        // FIXED: Was allowing ALL users - should restrict to specific roles
        return in_array($user->role, ['admin', 'campus_admin', 'viewer']);
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, Trade $trade): bool
    {
        return $user->role === 'admin';
    }

    public function delete(User $user, Trade $trade): bool
    {
        return $user->role === 'admin';
    }

    public function toggleStatus(User $user, Trade $trade): bool
    {
        return $user->role === 'admin';
    }

    public function apiList(User $user): bool
    {
        // API list can be accessed by authenticated users who need dropdown data
        return in_array($user->role, ['admin', 'campus_admin', 'viewer']);
    }
}
