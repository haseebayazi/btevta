<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Activitylog\Models\Activity;

class ActivityLogPolicy
{
    /**
     * Determine whether the user can view any activity logs.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can view the activity log.
     */
    public function view(User $user, Activity $activity): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can delete activity logs.
     */
    public function delete(User $user): bool
    {
        return $user->role === 'admin';
    }
}
