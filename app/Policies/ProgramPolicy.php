<?php

namespace App\Policies;

use App\Models\Program;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProgramPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view programs
    }

    public function view(User $user, Program $program): bool
    {
        return true; // All authenticated users can view programs
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector();
    }

    public function update(User $user, Program $program): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector();
    }

    public function delete(User $user, Program $program): bool
    {
        return $user->isSuperAdmin();
    }
}
