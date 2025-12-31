<?php

namespace App\Policies;

use App\Models\EquipmentUsageLog;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EquipmentUsageLogPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin();
    }

    public function view(User $user, EquipmentUsageLog $log): bool
    {
        if ($user->isSuperAdmin() || $user->isProjectDirector()) {
            return true;
        }

        // Campus admin can view logs from their campus equipment
        if ($user->isCampusAdmin() && $log->equipment && $user->campus_id === $log->equipment->campus_id) {
            return true;
        }

        // User can view their own usage logs
        if ($log->user_id === $user->id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isInstructor();
    }

    public function update(User $user, EquipmentUsageLog $log): bool
    {
        if ($user->isSuperAdmin() || $user->isProjectDirector()) {
            return true;
        }

        // Campus admin can update logs from their campus
        if ($user->isCampusAdmin() && $log->equipment && $user->campus_id === $log->equipment->campus_id) {
            return true;
        }

        // User can update their own incomplete logs (end usage)
        if ($log->user_id === $user->id && !$log->end_time) {
            return true;
        }

        return false;
    }

    public function delete(User $user, EquipmentUsageLog $log): bool
    {
        return $user->isSuperAdmin();
    }
}
