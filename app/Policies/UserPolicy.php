<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function view(User $user, User $model): bool
    {
        // Users can view their own profile or admin can view all
        return $user->id === $model->id || $user->role === 'admin';
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, User $model): bool
    {
        // Users can update their own profile or admin can update all
        return $user->id === $model->id || $user->role === 'admin';
    }

    public function delete(User $user, User $model): bool
    {
        // Only admin can delete, but not themselves
        return $user->role === 'admin' && $user->id !== $model->id;
    }

    public function toggleStatus(User $user, User $model): bool
    {
        // Only admin can toggle status, but not their own
        return $user->role === 'admin' && $user->id !== $model->id;
    }

    public function resetPassword(User $user, User $model): bool
    {
        return $user->role === 'admin' && $user->id !== $model->id;
    }
}
