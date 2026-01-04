<?php

namespace App\Policies;

use App\Models\CampusEquipment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CampusEquipmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isViewer();
    }

    public function view(User $user, CampusEquipment $equipment): bool
    {
        if ($user->isSuperAdmin() || $user->isProjectDirector() || $user->isViewer()) {
            return true;
        }

        // Campus admin can only view equipment from their campus
        if ($user->isCampusAdmin() && $user->campus_id && $user->campus_id === $equipment->campus_id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin();
    }

    public function update(User $user, CampusEquipment $equipment): bool
    {
        if ($user->isSuperAdmin() || $user->isProjectDirector()) {
            return true;
        }

        // Campus admin can update their campus equipment
        if ($user->isCampusAdmin() && $user->campus_id && $user->campus_id === $equipment->campus_id) {
            return true;
        }

        return false;
    }

    public function delete(User $user, CampusEquipment $equipment): bool
    {
        return $user->isSuperAdmin();
    }

    public function logUsage(User $user, CampusEquipment $equipment): bool
    {
        if ($user->isSuperAdmin() || $user->isProjectDirector()) {
            return true;
        }

        // Campus admin and instructors can log usage for their campus
        if (($user->isCampusAdmin() || $user->isInstructor()) && $user->campus_id && $user->campus_id === $equipment->campus_id) {
            return true;
        }

        return false;
    }

    public function viewReports(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin();
    }
}
