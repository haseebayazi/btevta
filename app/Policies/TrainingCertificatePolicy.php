<?php

namespace App\Policies;

use App\Models\TrainingCertificate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TrainingCertificatePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isViewer();
    }

    public function view(User $user, TrainingCertificate $certificate): bool
    {
        if ($user->isSuperAdmin() || $user->isProjectDirector() || $user->isViewer()) {
            return true;
        }

        // Campus admin can view certificates from their campus
        if ($user->isCampusAdmin() && $certificate->candidate && $user->campus_id === $certificate->candidate->campus_id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin();
    }

    public function update(User $user, TrainingCertificate $certificate): bool
    {
        // Certificates should generally not be updated, only reissued
        return $user->isSuperAdmin();
    }

    public function delete(User $user, TrainingCertificate $certificate): bool
    {
        // Certificates should not be deleted, only revoked
        return $user->isSuperAdmin();
    }

    public function download(User $user, TrainingCertificate $certificate): bool
    {
        if ($user->isSuperAdmin() || $user->isProjectDirector() || $user->isViewer()) {
            return true;
        }

        // Campus admin can download certificates from their campus
        if ($user->isCampusAdmin() && $certificate->candidate && $user->campus_id === $certificate->candidate->campus_id) {
            return true;
        }

        return false;
    }

    public function verify(User $user): bool
    {
        // Anyone can verify a certificate (public endpoint typically)
        return true;
    }
}
