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
        return true;
    }

    public function view(User $user, Trade $trade): bool
    {
        return true;
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
}
