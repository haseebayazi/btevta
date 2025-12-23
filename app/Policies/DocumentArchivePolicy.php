<?php

namespace App\Policies;

use App\Models\DocumentArchive;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DocumentArchivePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isViewer();
    }

    public function view(User $user, DocumentArchive $document): bool
    {
        // Admin, Project Director, and Viewer can view all
        if ($user->isSuperAdmin() || $user->isProjectDirector() || $user->isViewer()) {
            return true;
        }

        // Campus users can view documents from their campus
        if ($user->isCampusAdmin() && $user->campus_id && $document->candidate) {
            return $document->candidate->campus_id === $user->campus_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin();
    }

    public function update(User $user, DocumentArchive $document): bool
    {
        // Admin and Project Director can update all
        if ($user->isSuperAdmin() || $user->isProjectDirector()) {
            return true;
        }

        // Campus users can update documents from their campus
        if ($user->isCampusAdmin() && $user->campus_id && $document->candidate) {
            return $document->candidate->campus_id === $user->campus_id;
        }

        return false;
    }

    public function delete(User $user, DocumentArchive $document): bool
    {
        return $user->isSuperAdmin();
    }

    public function download(User $user, DocumentArchive $document): bool
    {
        return $this->view($user, $document);
    }

    public function archive(User $user, DocumentArchive $document): bool
    {
        return $user->isSuperAdmin();
    }

    public function restore(User $user, DocumentArchive $document): bool
    {
        return $user->isSuperAdmin();
    }
}
