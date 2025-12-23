<?php

namespace App\Policies;

use App\Models\Candidate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CandidatePolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any candidates.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view candidates list
        return true;
    }

    /**
     * Determine if the user can view the candidate.
     */
    public function view(User $user, Candidate $candidate): bool
    {
        // Admin and Project Director can view all
        if ($user->isSuperAdmin() || $user->isProjectDirector()) {
            return true;
        }

        // Campus admin users can only view candidates from their campus
        if ($user->isCampusAdmin() && $user->campus_id) {
            return $candidate->campus_id === $user->campus_id;
        }

        // OEP users can only view candidates assigned to their OEP
        if ($user->isOep() && $user->oep_id) {
            return $candidate->oep_id === $user->oep_id;
        }

        // Visa Partner can view candidates they process
        if ($user->isVisaPartner() && $user->visa_partner_id) {
            return $candidate->visa_partner_id === $user->visa_partner_id;
        }

        return false;
    }

    /**
     * Determine if the user can create candidates.
     */
    public function create(User $user): bool
    {
        // Admin, Project Director, and campus admin users can create candidates
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin();
    }

    /**
     * Determine if the user can update the candidate.
     */
    public function update(User $user, Candidate $candidate): bool
    {
        // Admin and Project Director can update all
        if ($user->isSuperAdmin() || $user->isProjectDirector()) {
            return true;
        }

        // Campus admin users can only update candidates from their campus
        if ($user->isCampusAdmin() && $user->campus_id) {
            return $candidate->campus_id === $user->campus_id;
        }

        return false;
    }

    /**
     * Determine if the user can delete the candidate.
     */
    public function delete(User $user, Candidate $candidate): bool
    {
        // Only admin can delete candidates
        return $user->isSuperAdmin();
    }

    /**
     * Determine if the user can restore the candidate.
     */
    public function restore(User $user, Candidate $candidate): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine if the user can permanently delete the candidate.
     */
    public function forceDelete(User $user, Candidate $candidate): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine if the user can export candidates.
     */
    public function export(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin();
    }

    /**
     * Determine if the user can import candidates.
     */
    public function import(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isCampusAdmin();
    }
}
