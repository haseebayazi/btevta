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
        if ($user->isCampusAdmin() && $evidence->complaint && $evidence->complaint->candidate) {
            return $user->campus_id === $evidence->complaint->candidate->campus_id;
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
        return $user->isSuperAdmin();
    }

    public function download(User $user, ComplaintEvidence $evidence): bool
    {
        return $this->view($user, $evidence);
    }
}
