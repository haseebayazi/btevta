<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector();
    }

    public function view(User $user, User $model): bool
    {
        // Users can view their own profile or admin can view all
        return $user->id === $model->id || $user->isSuperAdmin() || $user->isProjectDirector();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, User $model): bool
    {
        // Users can update their own profile or admin can update all
        return $user->id === $model->id || $user->isSuperAdmin();
    }

    public function delete(User $user, User $model): bool
    {
        // Only admin can delete, but not themselves
        return $user->isSuperAdmin() && $user->id !== $model->id;
    }

    public function toggleStatus(User $user, User $model): bool
    {
        // Only admin can toggle status, but not their own
        return $user->isSuperAdmin() && $user->id !== $model->id;
    }

    public function resetPassword(User $user, User $model): bool
    {
        return $user->isSuperAdmin() && $user->id !== $model->id;
    }

    public function manageSettings(User $user): bool
    {
        // Only admin can manage system settings
        return $user->isSuperAdmin();
    }

    public function viewAuditLogs(User $user): bool
    {
        // Only admin can view audit logs
        return $user->isSuperAdmin() || $user->isProjectDirector();
    }

    public function globalSearch(User $user): bool
    {
        // All authenticated users can use global search
        // Authorization is then applied per entity type in the service
        return true;
    }
}
