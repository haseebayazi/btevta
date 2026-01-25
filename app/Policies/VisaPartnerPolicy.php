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
        // Only super_admin (not regular admin) can create visa partners
        return $user->role === User::ROLE_SUPER_ADMIN;
    }

    public function update(User $user, VisaPartner $partner): bool
    {
        // Super admin and project director can update visa partners
        return $user->isSuperAdmin() || $user->isProjectDirector();
    }

    public function delete(User $user, VisaPartner $partner): bool
    {
        // Only super_admin (not regular admin) can delete visa partners
        return $user->role === User::ROLE_SUPER_ADMIN;
    }

    public function toggleStatus(User $user, VisaPartner $partner): bool
    {
        return $user->isSuperAdmin();
    }
}
