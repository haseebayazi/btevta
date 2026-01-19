<?php

namespace App\Policies;

use App\Models\ImplementingPartner;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ImplementingPartnerPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view implementing partners
    }

    public function view(User $user, ImplementingPartner $implementingPartner): bool
    {
        return true; // All authenticated users can view
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector();
    }

    public function update(User $user, ImplementingPartner $implementingPartner): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector();
    }

    public function delete(User $user, ImplementingPartner $implementingPartner): bool
    {
        return $user->isSuperAdmin();
    }
}
