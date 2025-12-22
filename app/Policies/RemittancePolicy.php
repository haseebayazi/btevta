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
        // Allow admins, project directors, campus admins, OEPs, and viewers
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isOep() || $user->isViewer();
    }

    public function view(User $user, Remittance $remittance): bool
    {
        // Admin and Project Director can view all
        if ($user->isSuperAdmin() || $user->isProjectDirector() || $user->isViewer()) {
            return true;
        }

        // Campus admin can view remittances from their campus
        if ($user->isCampusAdmin() && $user->campus_id && $remittance->candidate) {
            return $remittance->candidate->campus_id === $user->campus_id;
        }

        // OEP can view remittances from their candidates
        if ($user->isOep() && $user->oep_id && $remittance->candidate) {
            return $remittance->candidate->oep_id === $user->oep_id;
        }

        // Candidates can view their own remittances
        if ($user->isCandidate() && $remittance->candidate) {
            return $remittance->candidate->user_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        // Only admin, project director, and campus_admin can create remittances
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin();
    }

    public function update(User $user, Remittance $remittance): bool
    {
        // Admin and Project Director can update all
        if ($user->isSuperAdmin() || $user->isProjectDirector()) {
            return true;
        }

        // Campus admin can update remittances from their campus
        if ($user->isCampusAdmin() && $user->campus_id && $remittance->candidate) {
            return $remittance->candidate->campus_id === $user->campus_id;
        }

        return false;
    }

    public function delete(User $user, Remittance $remittance): bool
    {
        // Only admin can delete
        return $user->isSuperAdmin();
    }

    public function verify(User $user, Remittance $remittance): bool
    {
        // Only admin and project director can verify
        return $user->isSuperAdmin() || $user->isProjectDirector();
    }

    public function uploadReceipt(User $user, Remittance $remittance): bool
    {
        // Admin and Project Director can upload for any
        if ($user->isSuperAdmin() || $user->isProjectDirector()) {
            return true;
        }

        // Campus admin can upload for their campus
        if ($user->isCampusAdmin() && $user->campus_id && $remittance->candidate) {
            return $remittance->candidate->campus_id === $user->campus_id;
        }

        return false;
    }

    public function deleteReceipt(User $user): bool
    {
        // Only admin can delete receipts
        return $user->isSuperAdmin();
    }

    public function export(User $user): bool
    {
        // Admin, project director, and campus_admin can export
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin();
    }

    public function viewReports(User $user): bool
    {
        // Admin, project director, campus_admin, and viewer can view reports
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isViewer();
    }
}
