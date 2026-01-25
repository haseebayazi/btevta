<?php

namespace App\Policies;

use App\Models\ComplaintEvidence;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ComplaintEvidencePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin();
    }

    public function view(User $user, ComplaintEvidence $evidence): bool
    {
        if ($user->isSuperAdmin() || $user->isProjectDirector()) {
            return true;
        }

        // Campus admin can view evidence for complaints from their campus
        if ($user->isCampusAdmin() && $evidence->complaint) {
            // Check direct campus_id on complaint first, then fall back to candidate's campus
            $complaintCampusId = $evidence->complaint->campus_id
                ?? $evidence->complaint->candidate?->campus_id;
            return $user->campus_id === $complaintCampusId;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin();
    }

    public function update(User $user, ComplaintEvidence $evidence): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector();
    }

    public function delete(User $user, ComplaintEvidence $evidence): bool
    {
        // Only super_admin (not regular admin) can delete evidence
        return $user->role === User::ROLE_SUPER_ADMIN;
    }

    public function download(User $user, ComplaintEvidence $evidence): bool
    {
        return $this->view($user, $evidence);
    }
}
