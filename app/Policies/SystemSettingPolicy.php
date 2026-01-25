<?php

namespace App\Policies;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SystemSettingPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, SystemSetting $setting): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        // Only super_admin (not regular admin) can create settings
        return $user->role === User::ROLE_SUPER_ADMIN;
    }

    public function update(User $user, SystemSetting $setting): bool
    {
        // Only super_admin (not regular admin) can update settings
        return $user->role === User::ROLE_SUPER_ADMIN;
    }

    public function delete(User $user, SystemSetting $setting): bool
    {
        // Only super_admin (not regular admin) can delete settings
        return $user->role === User::ROLE_SUPER_ADMIN;
    }
}
