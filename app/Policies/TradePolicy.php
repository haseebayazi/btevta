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
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isTrainer() || $user->isViewer();
    }

    public function view(User $user, Trade $trade): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isTrainer() || $user->isViewer();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, Trade $trade): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, Trade $trade): bool
    {
        return $user->isSuperAdmin();
    }

    public function toggleStatus(User $user, Trade $trade): bool
    {
        return $user->isSuperAdmin();
    }

    public function apiList(User $user): bool
    {
        // API list can be accessed by authenticated users who need dropdown data
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isTrainer() || $user->isViewer();
    }
}
