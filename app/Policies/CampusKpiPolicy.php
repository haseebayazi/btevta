<?php

namespace App\Policies;

use App\Models\CampusKpi;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CampusKpiPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isViewer();
    }

    public function view(User $user, CampusKpi $kpi): bool
    {
        if ($user->isSuperAdmin() || $user->isProjectDirector() || $user->isViewer()) {
            return true;
        }

        // Campus admin can view KPIs from their campus
        if ($user->isCampusAdmin() && $user->campus_id && $user->campus_id === $kpi->campus_id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector();
    }

    public function update(User $user, CampusKpi $kpi): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector();
    }

    public function delete(User $user, CampusKpi $kpi): bool
    {
        return $user->isSuperAdmin();
    }

    public function viewReports(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin();
    }
}
