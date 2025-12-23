<?php

namespace App\Policies;

use App\Models\RemittanceBeneficiary;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RemittanceBeneficiaryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        // Allow admins, project directors, campus admins, OEPs
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isOep();
    }

    public function view(User $user, RemittanceBeneficiary $beneficiary): bool
    {
        // Admin and Project Director can view all
        if ($user->isSuperAdmin() || $user->isProjectDirector()) {
            return true;
        }

        // Campus admin can view beneficiaries from their campus
        if ($user->isCampusAdmin() && $user->campus_id && $beneficiary->candidate) {
            return $beneficiary->candidate->campus_id === $user->campus_id;
        }

        // OEP can view beneficiaries from their candidates
        if ($user->isOep() && $user->oep_id && $beneficiary->candidate) {
            return $beneficiary->candidate->oep_id === $user->oep_id;
        }

        // Candidates can view their own beneficiaries
        if ($user->isCandidate() && $beneficiary->candidate) {
            return $beneficiary->candidate->user_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        // Only admin, project director, and campus_admin can create beneficiaries
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin();
    }

    public function update(User $user, RemittanceBeneficiary $beneficiary): bool
    {
        // Admin and Project Director can update all
        if ($user->isSuperAdmin() || $user->isProjectDirector()) {
            return true;
        }

        // Campus admin can update beneficiaries from their campus
        if ($user->isCampusAdmin() && $user->campus_id && $beneficiary->candidate) {
            return $beneficiary->candidate->campus_id === $user->campus_id;
        }

        return false;
    }

    public function delete(User $user, RemittanceBeneficiary $beneficiary): bool
    {
        // Admin and Project Director can delete all
        if ($user->isSuperAdmin() || $user->isProjectDirector()) {
            return true;
        }

        // Campus admin can delete beneficiaries from their campus
        if ($user->isCampusAdmin() && $user->campus_id && $beneficiary->candidate) {
            return $beneficiary->candidate->campus_id === $user->campus_id;
        }

        return false;
    }

    public function setPrimary(User $user, RemittanceBeneficiary $beneficiary): bool
    {
        // Same as update permission
        return $this->update($user, $beneficiary);
    }
}
