<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Activitylog\Models\Activity;

class ActivityPolicy
{
    /**
     * Determine if the user can view any activity logs
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'admin']);
    }

    /**
     * Determine if the user can view a specific activity log
     */
    public function view(User $user, Activity $activity): bool
    {
        return in_array($user->role, ['super_admin', 'admin']);
    }

    /**
     * Determine if the user can delete activity logs
     */
    public function delete(User $user): bool
    {
        return $user->role === 'super_admin';
    }
}
