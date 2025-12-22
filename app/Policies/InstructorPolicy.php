<?php

namespace App\Policies;

use App\Models\Instructor;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InstructorPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isTrainer() || $user->isViewer();
    }

    public function view(User $user, Instructor $instructor): bool
    {
        // Admin and Project Director can view all
        if ($user->isSuperAdmin() || $user->isProjectDirector() || $user->isViewer()) {
            return true;
        }

        // Campus users can view instructors from their campus
        if ($user->isCampusAdmin() && $user->campus_id) {
            return $instructor->campus_id === $user->campus_id;
        }

        // Trainers can view instructors from their campus
        if ($user->isTrainer() && $user->campus_id) {
            return $instructor->campus_id === $user->campus_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin();
    }

    public function update(User $user, Instructor $instructor): bool
    {
        // Admin and Project Director can update all
        if ($user->isSuperAdmin() || $user->isProjectDirector()) {
            return true;
        }

        // Campus users can update instructors from their campus
        if ($user->isCampusAdmin() && $user->campus_id) {
            return $instructor->campus_id === $user->campus_id;
        }

        return false;
    }

    public function delete(User $user, Instructor $instructor): bool
    {
        return $user->isSuperAdmin();
    }
}
