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
        // FIXED: Was allowing ALL users - should restrict to specific roles
        return in_array($user->role, ['admin', 'campus_admin', 'viewer']);
    }

    public function view(User $user, DocumentArchive $document): bool
    {
        // Admin can view all
        if ($user->role === 'admin') {
            return true;
        }

        // Campus users can view documents from their campus
        if ($user->role === 'campus_admin' && $user->campus_id && $document->candidate) {
            return $document->candidate->campus_id === $user->campus_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus_admin']);
    }

    public function update(User $user, DocumentArchive $document): bool
    {
        // Admin can update all
        if ($user->role === 'admin') {
            return true;
        }

        // Campus users can update documents from their campus
        if ($user->role === 'campus_admin' && $user->campus_id && $document->candidate) {
            return $document->candidate->campus_id === $user->campus_id;
        }

        return false;
    }

    public function delete(User $user, DocumentArchive $document): bool
    {
        return $user->role === 'admin';
    }

    public function download(User $user, DocumentArchive $document): bool
    {
        return $this->view($user, $document);
    }

    public function archive(User $user, DocumentArchive $document): bool
    {
        return $user->role === 'admin';
    }

    public function restore(User $user, DocumentArchive $document): bool
    {
        return $user->role === 'admin';
    }
}
