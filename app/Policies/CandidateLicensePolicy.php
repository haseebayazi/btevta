<?php

namespace App\Policies;

use App\Models\Candidate;
use App\Models\CandidateLicense;
use App\Models\User;

class CandidateLicensePolicy
{
    /**
     * Determine if user can view any licenses for a candidate
     */
    public function viewAny(User $user, Candidate $candidate): bool
    {
        // Super Admin and Project Director can view all
        if ($user->hasRole(['super_admin', 'project_director'])) {
            return true;
        }

        // Campus Admin can view their campus candidates
        if ($user->hasRole('campus_admin')) {
            return $candidate->campus_id === $user->campus_id;
        }

        // OEP can view their assigned candidates
        if ($user->hasRole('oep')) {
            return $candidate->oep_id === $user->id;
        }

        return false;
    }

    /**
     * Determine if user can view a specific license
     */
    public function view(User $user, CandidateLicense $license): bool
    {
        return $this->viewAny($user, $license->candidate);
    }

    /**
     * Determine if user can create licenses for a candidate
     */
    public function create(User $user, Candidate $candidate): bool
    {
        // Super Admin can always create
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Others can only create if candidate is in 'new' status
        if ($candidate->status !== 'new') {
            return false;
        }

        // Campus Admin can create for their campus
        if ($user->hasRole('campus_admin')) {
            return $candidate->campus_id === $user->campus_id;
        }

        // OEP can create for their candidates
        if ($user->hasRole('oep')) {
            return $candidate->oep_id === $user->id;
        }

        return false;
    }

    /**
     * Determine if user can update a license
     */
    public function update(User $user, CandidateLicense $license): bool
    {
        // Super Admin can always update
        if ($user->hasRole('super_admin')) {
            return true;
        }

        $candidate = $license->candidate;

        // Cannot update if candidate progressed past 'new' status
        if ($candidate->status !== 'new') {
            return false;
        }

        // Campus Admin can update their campus licenses
        if ($user->hasRole('campus_admin')) {
            return $candidate->campus_id === $user->campus_id;
        }

        // OEP can update their candidates' licenses
        if ($user->hasRole('oep')) {
            return $candidate->oep_id === $user->id;
        }

        return false;
    }

    /**
     * Determine if user can delete a license
     */
    public function delete(User $user, CandidateLicense $license): bool
    {
        // Super Admin can always delete
        if ($user->hasRole('super_admin')) {
            return true;
        }

        $candidate = $license->candidate;

        // Cannot delete if candidate progressed past 'new' status
        if ($candidate->status !== 'new') {
            return false;
        }

        // Campus Admin can delete their campus licenses
        if ($user->hasRole('campus_admin')) {
            return $candidate->campus_id === $user->campus_id;
        }

        // OEP can delete their candidates' licenses
        if ($user->hasRole('oep')) {
            return $candidate->oep_id === $user->id;
        }

        return false;
    }
}
