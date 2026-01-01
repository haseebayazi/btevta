<?php

namespace App\Policies;

use App\Models\VisaPartner;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class VisaPartnerPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isViewer();
    }

    public function view(User $user, VisaPartner $partner): bool
    {
        if ($user->isSuperAdmin() || $user->isProjectDirector() || $user->isViewer()) {
            return true;
        }

        // OEP users can view their assigned visa partners
        if ($user->isOep() && $user->visa_partner_id === $partner->id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, VisaPartner $partner): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, VisaPartner $partner): bool
    {
        return $user->isSuperAdmin();
    }

    public function toggleStatus(User $user, VisaPartner $partner): bool
    {
        return $user->isSuperAdmin();
    }
}
