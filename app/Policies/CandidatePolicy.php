<?php

namespace App\Policies;

use App\Models\Candidate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CandidatePolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     * Inactive users are denied all actions.
     */
    public function before(User $user, string $ability): ?bool
    {
        // Inactive users cannot perform any actions
        if (!$user->is_active) {
            return false;
        }

        return null; // Continue to specific policy methods
    }

    /**
     * Determine if the user can view any candidates.
     * SECURITY FIX: Added role-based restrictions instead of allowing all users
     */
    public function viewAny(User $user): bool
    {
        if (!$user->is_active) {
            return false;
        }

        return $user->isSuperAdmin() || $user->isProjectDirector() ||
               $user->isCampusAdmin() || $user->isOep() ||
               $user->isVisaPartner() || $user->isStaff() ||
               $user->isTrainer() || $user->isViewer();
    }

    /**
     * Determine if the user can view the candidate.
     */
    public function view(User $user, Candidate $candidate): bool
    {
        if (!$user->is_active) {
            return false;
        }

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

        // Visa Partner can view candidates through visa process relationship
        // Note: Visa partners access candidates via VisaProcess model, not directly
        if ($user->isVisaPartner() && $user->visa_partner_id) {
            // Check if this candidate has a visa process associated with this visa partner
            return $candidate->visaProcess()
                ->where('visa_partner_id', $user->visa_partner_id)
                ->exists();
        }

        // Trainers/Instructors can view candidates in their batches
        if ($user->isTrainer() || $user->isInstructor()) {
            return $candidate->batch && $candidate->batch->trainer_id === $user->id;
        }

        // Viewers can view all candidates (read-only access)
        if ($user->isViewer()) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can create candidates.
     */
    public function create(User $user): bool
    {
        if (!$user->is_active) {
            return false;
        }

        // Admin, Project Director, and campus admin users can create candidates
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin();
    }

    /**
     * Determine if the user can update the candidate.
     */
    public function update(User $user, Candidate $candidate): bool
    {
        if (!$user->is_active) {
            return false;
        }

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
        if (!$user->is_active) {
            return false;
        }

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

    /**
     * Determine if the user can perform bulk status updates.
     * AUDIT FIX: Added proper policy method for bulk operations
     */
    public function bulkUpdateStatus(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin();
    }

    /**
     * Determine if the user can perform bulk batch assignments.
     * AUDIT FIX: Added proper policy method for bulk operations
     */
    public function bulkAssignBatch(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin();
    }

    /**
     * Determine if the user can perform bulk campus assignments.
     * AUDIT FIX: Added proper policy method for bulk operations
     */
    public function bulkAssignCampus(User $user): bool
    {
        // Only super admin and project director can reassign across campuses
        return $user->isSuperAdmin() || $user->isProjectDirector();
    }

    /**
     * Determine if the user can perform bulk delete operations.
     * AUDIT FIX: Added proper policy method for bulk operations
     */
    public function bulkDelete(User $user): bool
    {
        // Only super admin can bulk delete
        return $user->isSuperAdmin();
    }

    /**
     * Determine if the user can send bulk notifications.
     * AUDIT FIX: Added proper policy method for bulk operations
     */
    public function bulkNotify(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin();
    }

    /**
     * Determine if the user can update departure information for a candidate.
     * OEPs can update post-departure info for their assigned candidates.
     */
    public function updateDepartureInfo(User $user, Candidate $candidate): bool
    {
        // Admin and Project Director can update all
        if ($user->isSuperAdmin() || $user->isProjectDirector()) {
            return true;
        }

        // OEP users can update departure info for candidates assigned to them
        if ($user->isOep() && $user->oep_id) {
            return $candidate->oep_id === $user->oep_id;
        }

        // Campus admin can update for their campus candidates
        if ($user->isCampusAdmin() && $user->campus_id) {
            return $candidate->campus_id === $user->campus_id;
        }

        return false;
    }
}
