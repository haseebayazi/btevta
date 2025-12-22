<?php

namespace App\Policies;

use App\Models\RemittanceAlert;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RemittanceAlertPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        // Admin, project director, campus_admin, and OEP can view alerts
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isOep();
    }

    public function view(User $user, RemittanceAlert $alert): bool
    {
        // Admin and Project Director can view all
        if ($user->isSuperAdmin() || $user->isProjectDirector()) {
            return true;
        }

        // Campus admin can view alerts from their campus
        if ($user->isCampusAdmin() && $user->campus_id && $alert->candidate) {
            return $alert->candidate->campus_id === $user->campus_id;
        }

        // OEP can view alerts from their candidates
        if ($user->isOep() && $user->oep_id && $alert->candidate) {
            return $alert->candidate->oep_id === $user->oep_id;
        }

        return false;
    }

    public function markAsRead(User $user, RemittanceAlert $alert): bool
    {
        // Same as view permission
        return $this->view($user, $alert);
    }

    public function markAllAsRead(User $user): bool
    {
        // Same as viewAny permission
        return $this->viewAny($user);
    }

    public function resolve(User $user, RemittanceAlert $alert): bool
    {
        // Admin and Project Director can resolve all
        if ($user->isSuperAdmin() || $user->isProjectDirector()) {
            return true;
        }

        // Campus admin can resolve alerts from their campus
        if ($user->isCampusAdmin() && $user->campus_id && $alert->candidate) {
            return $alert->candidate->campus_id === $user->campus_id;
        }

        return false;
    }

    public function dismiss(User $user, RemittanceAlert $alert): bool
    {
        // Same as resolve permission
        return $this->resolve($user, $alert);
    }

    public function bulkAction(User $user): bool
    {
        // Admin, project director, and campus_admin can perform bulk actions
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin();
    }

    public function generateAlerts(User $user): bool
    {
        // Only admin and project director can manually generate alerts
        return $user->isSuperAdmin() || $user->isProjectDirector();
    }

    public function autoResolve(User $user): bool
    {
        // Only admin can auto-resolve alerts
        return $user->isSuperAdmin();
    }

    public function getUnreadCount(User $user): bool
    {
        // Same as viewAny permission
        return $this->viewAny($user);
    }
}
