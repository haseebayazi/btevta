<?php

namespace App\Policies;

use App\Models\PreDepartureDocument;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PreDepartureDocumentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view pre-departure documents
    }

    public function view(User $user, PreDepartureDocument $preDepartureDocument): bool
    {
        // Super admins, project directors, and viewers can view all
        if ($user->isSuperAdmin() || $user->isProjectDirector() || $user->isViewer()) {
            return true;
        }

        // Campus admins can view documents of candidates in their campus
        if ($user->isCampusAdmin()) {
            return $preDepartureDocument->candidate->campus_id === $user->campus_id;
        }

        // OEPs can view documents of their candidates
        if ($user->isOep()) {
            return $preDepartureDocument->candidate->oep_id === $user->oep_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isStaff();
    }

    public function update(User $user, PreDepartureDocument $preDepartureDocument): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isStaff();
    }

    public function delete(User $user, PreDepartureDocument $preDepartureDocument): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector();
    }
}
