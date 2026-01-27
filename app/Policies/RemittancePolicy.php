<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Remittance;

class RemittancePolicy
{
    /**
     * Determine if the user can view any remittances
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'project_director', 'campus_admin', 'staff']);
    }

    /**
     * Determine if the user can view the remittance
     */
    public function view(User $user, Remittance $remittance): bool
    {
        // Super admin and project director can view all
        if ($user->isSuperAdmin() || $user->isProjectDirector()) {
            return true;
        }

        // Campus admin can only view remittances from their campus
        if ($user->isCampusAdmin()) {
            return $remittance->campus_id === $user->campus_id;
        }

        // Staff can view remittances from their campus
        if ($user->hasRole('staff')) {
            return $remittance->campus_id === $user->campus_id;
        }

        // OEP can only view remittances for their assigned candidates
        if ($user->isOep()) {
            // Defensive: check candidate relation
            if ($remittance->candidate && $remittance->candidate->oep_id) {
                return $remittance->candidate->oep_id === $user->oep_id;
            }
            // If candidate relation not loaded, fallback to DB check
            return \App\Models\Candidate::where('id', $remittance->candidate_id)
                ->where('oep_id', $user->oep_id)
                ->exists();
        }

        return false;
    }

    /**
     * Determine if the user can create remittances
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'project_director', 'campus_admin', 'staff']);
    }

    /**
     * Determine if the user can update the remittance
     */
    public function update(User $user, Remittance $remittance): bool
    {
        // Cannot update if already verified
        if ($remittance->is_verified) {
            return false;
        }

        // Super admin and project director can update all
        if ($user->isSuperAdmin() || $user->isProjectDirector()) {
            return true;
        }

        // Campus admin can update remittances from their campus
        if ($user->isCampusAdmin()) {
            return $remittance->campus_id === $user->campus_id;
        }

        return false;
    }

    /**
     * Determine if the user can delete the remittance
     */
    public function delete(User $user, Remittance $remittance): bool
    {
        // Cannot delete if verified
        if ($remittance->is_verified) {
            return false;
        }

        // Only super admin and project director can delete
        return $user->isSuperAdmin() || $user->isProjectDirector();
    }

    /**
     * Determine if the user can verify the remittance
     */
    public function verify(User $user, Remittance $remittance): bool
    {
        // Super admin and project director can verify all
        if ($user->isSuperAdmin() || $user->isProjectDirector()) {
            return true;
        }

        // Campus admin can verify remittances from their campus
        if ($user->isCampusAdmin()) {
            return $remittance->campus_id === $user->campus_id;
        }

        return false;
    }

    /**
     * Determine if the user can download proof documents
     */
    public function downloadProof(User $user, Remittance $remittance): bool
    {
        return $this->view($user, $remittance);
    }
}
