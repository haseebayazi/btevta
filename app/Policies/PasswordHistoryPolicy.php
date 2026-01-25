<?php

namespace App\Policies;

use App\Models\PasswordHistory;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PasswordHistoryPolicy
{
    use HandlesAuthorization;

    /**
     * Password history should only be accessible by the system
     * and super admins for audit purposes.
     */
    public function viewAny(User $user): bool
    {
        // Only super_admin (not regular admin) can view password history
        return $user->role === User::ROLE_SUPER_ADMIN;
    }

    public function view(User $user, PasswordHistory $history): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Users can view their own password history
        return $history->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        // Password history is created by the system only
        return false;
    }

    public function update(User $user, PasswordHistory $history): bool
    {
        // Password history should never be updated
        return false;
    }

    public function delete(User $user, PasswordHistory $history): bool
    {
        // Password history should never be deleted
        return false;
    }
}
