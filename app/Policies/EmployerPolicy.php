<?php

namespace App\Policies;

use App\Models\Employer;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmployerPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view employers
    }

    public function view(User $user, Employer $employer): bool
    {
        return true; // All authenticated users can view
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isOep();
    }

    public function update(User $user, Employer $employer): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isOep();
    }

    public function delete(User $user, Employer $employer): bool
    {
        return $user->isSuperAdmin();
    }
}
