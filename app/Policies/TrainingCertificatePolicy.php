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

    /**
     * Determine if the user can verify certificates.
     *
     * AUDIT FIX: Explicit documentation of public verification.
     * Certificate verification is intentionally open to authenticated users
     * as it validates certificate authenticity (similar to public QR verification).
     * This allows employers, institutions, or anyone with the certificate
     * to verify its authenticity.
     *
     * Note: Actual verification happens via public routes with signed URLs
     * for unauthenticated access. This method covers authenticated verification.
     */
    public function verify(User $user): bool
    {
        // All authenticated users can verify certificates
        // This is by design for certificate authenticity checks
        return $user !== null;
    }

    /**
     * Determine if the user can revoke a certificate.
     */
    public function revoke(User $user, TrainingCertificate $certificate): bool
    {
        // Only super admins can revoke certificates
        return $user->isSuperAdmin();
    }

    /**
     * Determine if the user can reissue a certificate.
     */
    public function reissue(User $user, TrainingCertificate $certificate): bool
    {
        if ($user->isSuperAdmin() || $user->isProjectDirector()) {
            return true;
        }

        // Campus admin can reissue certificates from their campus
        if ($user->isCampusAdmin() && $certificate->candidate && $user->campus_id === $certificate->candidate->campus_id) {
            return true;
        }

        return false;
    }
}
