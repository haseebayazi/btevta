<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy for data import permissions
 */
class ImportPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can import candidates.
     */
    public function importCandidates(User $user): bool
    {
        // Only admin and campus_admin can import
        return in_array($user->role, ['admin', 'campus_admin']);
    }

    /**
     * Determine if the user can view import history.
     */
    public function viewImportHistory(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus_admin', 'viewer']);
    }

    /**
     * Determine if the user can download import templates.
     */
    public function downloadTemplate(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus_admin']);
    }
}
