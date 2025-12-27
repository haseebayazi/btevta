<?php

namespace App\Policies;

use App\Models\CandidateScreening;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CandidateScreeningPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any candidate screenings.
     * SECURITY FIX: Added role-based restrictions instead of allowing all users
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() ||
               $user->isCampusAdmin() || $user->isStaff() ||
               $user->isTrainer() || $user->isViewer();
    }

    /**
     * Determine if the user can view the candidate screening.
     */
    public function view(User $user, CandidateScreening $screening): bool
    {
        // Admin and Project Director can view all
        if ($user->isSuperAdmin() || $user->isProjectDirector()) {
            return true;
        }

        // Campus admin users can only view screenings for their campus candidates
        if ($user->isCampusAdmin() && $user->campus_id) {
            return $screening->candidate &&
                   $screening->candidate->campus_id === $user->campus_id;
        }

        // Staff can view screenings they created
        if ($user->isStaff()) {
            return $screening->created_by === $user->id;
        }

        return false;
    }

    /**
     * Determine if the user can create candidate screenings.
     */
    public function create(User $user): bool
    {
        // Admin, Project Director, campus admin, and staff can create screenings
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isStaff();
    }

    /**
     * Determine if the user can update the candidate screening.
     */
    public function update(User $user, CandidateScreening $screening): bool
    {
        // Admin and Project Director can update all
        if ($user->isSuperAdmin() || $user->isProjectDirector()) {
            return true;
        }

        // Campus admin users can only update screenings for their campus candidates
        if ($user->isCampusAdmin() && $user->campus_id) {
            return $screening->candidate &&
                   $screening->candidate->campus_id === $user->campus_id;
        }

        // Staff can update screenings they created
        if ($user->isStaff()) {
            return $screening->created_by === $user->id;
        }

        return false;
    }

    /**
     * Determine if the user can delete the candidate screening.
     */
    public function delete(User $user, CandidateScreening $screening): bool
    {
        // Only admin can delete screenings
        return $user->isSuperAdmin();
    }

    /**
     * Determine if the user can restore the candidate screening.
     */
    public function restore(User $user, CandidateScreening $screening): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine if the user can permanently delete the candidate screening.
     */
    public function forceDelete(User $user, CandidateScreening $screening): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine if the user can export screenings.
     */
    public function export(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isStaff();
    }

    /**
     * Determine if the user can log calls for screenings.
     */
    public function logCall(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isStaff();
    }

    /**
     * Determine if the user can record screening outcomes.
     */
    public function recordOutcome(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isStaff();
    }
}
