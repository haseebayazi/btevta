<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Activitylog\Models\Activity;

/**
 * Policy for activity log viewing permissions
 *
 * Roles with access:
 * - super_admin/admin: Full access to view and manage logs
 * - project_director: View-only access to activity logs
 */
class ActivityPolicy
{
    /**
     * Determine if the user can view any activity logs
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector();
    }

    /**
     * Determine if the user can view a specific activity log
     */
    public function view(User $user, Activity $activity): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector();
    }

    /**
     * Determine if the user can delete activity logs
     */
    public function delete(User $user): bool
    {
        // Only super admins can delete logs
        return $user->isSuperAdmin();
    }
}
