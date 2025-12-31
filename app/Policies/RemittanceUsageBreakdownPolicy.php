<?php

namespace App\Policies;

use App\Models\RemittanceUsageBreakdown;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RemittanceUsageBreakdownPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isOep();
    }

    public function view(User $user, RemittanceUsageBreakdown $breakdown): bool
    {
        if ($user->isSuperAdmin() || $user->isProjectDirector()) {
            return true;
        }

        // OEP can view breakdowns for candidates assigned to them
        if ($user->isOep() && $breakdown->remittance && $breakdown->remittance->candidate) {
            return $breakdown->remittance->candidate->oep_id === $user->oep_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isOep();
    }

    public function update(User $user, RemittanceUsageBreakdown $breakdown): bool
    {
        if ($user->isSuperAdmin() || $user->isProjectDirector()) {
            return true;
        }

        // OEP can update breakdowns for candidates assigned to them
        if ($user->isOep() && $breakdown->remittance && $breakdown->remittance->candidate) {
            return $breakdown->remittance->candidate->oep_id === $user->oep_id;
        }

        return false;
    }

    public function delete(User $user, RemittanceUsageBreakdown $breakdown): bool
    {
        return $user->isSuperAdmin();
    }
}
