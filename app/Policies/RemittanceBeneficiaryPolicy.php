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
        // Allow admins, campus admins, OEPs
        return in_array($user->role, ['admin', 'campus_admin', 'oep']);
    }

    public function view(User $user, RemittanceBeneficiary $beneficiary): bool
    {
        // Admin can view all
        if ($user->role === 'admin') {
            return true;
        }

        // Campus admin can view beneficiaries from their campus
        if ($user->role === 'campus_admin' && $user->campus_id && $beneficiary->candidate) {
            return $beneficiary->candidate->campus_id === $user->campus_id;
        }

        // OEP can view beneficiaries from their candidates
        if ($user->role === 'oep' && $user->oep_id && $beneficiary->candidate) {
            return $beneficiary->candidate->oep_id === $user->oep_id;
        }

        // Candidates can view their own beneficiaries
        if ($user->role === 'candidate' && $beneficiary->candidate) {
            return $beneficiary->candidate->user_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        // Only admin and campus_admin can create beneficiaries
        return in_array($user->role, ['admin', 'campus_admin']);
    }

    public function update(User $user, RemittanceBeneficiary $beneficiary): bool
    {
        // Admin can update all
        if ($user->role === 'admin') {
            return true;
        }

        // Campus admin can update beneficiaries from their campus
        if ($user->role === 'campus_admin' && $user->campus_id && $beneficiary->candidate) {
            return $beneficiary->candidate->campus_id === $user->campus_id;
        }

        return false;
    }

    public function delete(User $user, RemittanceBeneficiary $beneficiary): bool
    {
        // Admin can delete all
        if ($user->role === 'admin') {
            return true;
        }

        // Campus admin can delete beneficiaries from their campus
        if ($user->role === 'campus_admin' && $user->campus_id && $beneficiary->candidate) {
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
