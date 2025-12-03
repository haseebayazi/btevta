<?php

namespace App\Policies;

use App\Models\Remittance;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RemittancePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        // Allow admins, campus admins, OEPs, and viewers
        return in_array($user->role, ['admin', 'campus_admin', 'oep', 'viewer']);
    }

    public function view(User $user, Remittance $remittance): bool
    {
        // Admin can view all
        if ($user->role === 'admin') {
            return true;
        }

        // Campus admin can view remittances from their campus
        if ($user->role === 'campus_admin' && $user->campus_id && $remittance->candidate) {
            return $remittance->candidate->campus_id === $user->campus_id;
        }

        // OEP can view remittances from their candidates
        if ($user->role === 'oep' && $user->oep_id && $remittance->candidate) {
            return $remittance->candidate->oep_id === $user->oep_id;
        }

        // Candidates can view their own remittances
        if ($user->role === 'candidate' && $remittance->candidate) {
            return $remittance->candidate->user_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        // Only admin and campus_admin can create remittances
        return in_array($user->role, ['admin', 'campus_admin']);
    }

    public function update(User $user, Remittance $remittance): bool
    {
        // Admin can update all
        if ($user->role === 'admin') {
            return true;
        }

        // Campus admin can update remittances from their campus
        if ($user->role === 'campus_admin' && $user->campus_id && $remittance->candidate) {
            return $remittance->candidate->campus_id === $user->campus_id;
        }

        return false;
    }

    public function delete(User $user, Remittance $remittance): bool
    {
        // Only admin can delete
        return $user->role === 'admin';
    }

    public function verify(User $user, Remittance $remittance): bool
    {
        // Only admin can verify
        return $user->role === 'admin';
    }

    public function uploadReceipt(User $user, Remittance $remittance): bool
    {
        // Admin can upload for any
        if ($user->role === 'admin') {
            return true;
        }

        // Campus admin can upload for their campus
        if ($user->role === 'campus_admin' && $user->campus_id && $remittance->candidate) {
            return $remittance->candidate->campus_id === $user->campus_id;
        }

        return false;
    }

    public function deleteReceipt(User $user): bool
    {
        // Only admin can delete receipts
        return $user->role === 'admin';
    }

    public function export(User $user): bool
    {
        // Only admin and campus_admin can export
        return in_array($user->role, ['admin', 'campus_admin']);
    }
}
