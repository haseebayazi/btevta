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
        // Only admin, campus_admin, and OEP can view alerts
        return in_array($user->role, ['admin', 'campus_admin', 'oep']);
    }

    public function view(User $user, RemittanceAlert $alert): bool
    {
        // Admin can view all
        if ($user->role === 'admin') {
            return true;
        }

        // Campus admin can view alerts from their campus
        if ($user->role === 'campus_admin' && $user->campus_id && $alert->candidate) {
            return $alert->candidate->campus_id === $user->campus_id;
        }

        // OEP can view alerts from their candidates
        if ($user->role === 'oep' && $user->oep_id && $alert->candidate) {
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
        // Admin can resolve all
        if ($user->role === 'admin') {
            return true;
        }

        // Campus admin can resolve alerts from their campus
        if ($user->role === 'campus_admin' && $user->campus_id && $alert->candidate) {
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
        // Only admin and campus_admin can perform bulk actions
        return in_array($user->role, ['admin', 'campus_admin']);
    }

    public function generateAlerts(User $user): bool
    {
        // Only admin can manually generate alerts
        return $user->role === 'admin';
    }

    public function autoResolve(User $user): bool
    {
        // Only admin can auto-resolve alerts
        return $user->role === 'admin';
    }

    public function getUnreadCount(User $user): bool
    {
        // Same as viewAny permission
        return $this->viewAny($user);
    }
}
