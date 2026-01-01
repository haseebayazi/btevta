<?php

namespace App\Policies;

use App\Models\RegistrationDocument;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RegistrationDocumentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isViewer();
    }

    public function view(User $user, RegistrationDocument $document): bool
    {
        if ($user->isSuperAdmin() || $user->isProjectDirector() || $user->isViewer()) {
            return true;
        }

        // Campus admin can view documents for candidates from their campus
        if ($user->isCampusAdmin() && $document->candidate && $user->campus_id === $document->candidate->campus_id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin();
    }

    public function update(User $user, RegistrationDocument $document): bool
    {
        if ($user->isSuperAdmin() || $user->isProjectDirector()) {
            return true;
        }

        // Campus admin can update documents for candidates from their campus
        if ($user->isCampusAdmin() && $document->candidate && $user->campus_id === $document->candidate->campus_id) {
            return true;
        }

        return false;
    }

    public function delete(User $user, RegistrationDocument $document): bool
    {
        return $user->isSuperAdmin();
    }

    public function download(User $user, RegistrationDocument $document): bool
    {
        return $this->view($user, $document);
    }

    public function verify(User $user, RegistrationDocument $document): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector();
    }
}
