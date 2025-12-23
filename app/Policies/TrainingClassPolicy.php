<?php

namespace App\Policies;

use App\Models\TrainingClass;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TrainingClassPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isTrainer() || $user->isViewer();
    }

    public function view(User $user, TrainingClass $class): bool
    {
        // Admin, Project Director, and Viewer can view all
        if ($user->isSuperAdmin() || $user->isProjectDirector() || $user->isViewer()) {
            return true;
        }

        // Campus users can view classes from their campus
        if ($user->isCampusAdmin() && $user->campus_id) {
            return $class->campus_id === $user->campus_id;
        }

        // Trainers can view classes from their campus
        if ($user->isTrainer() && $user->campus_id) {
            return $class->campus_id === $user->campus_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin();
    }

    public function update(User $user, TrainingClass $class): bool
    {
        // Admin and Project Director can update all
        if ($user->isSuperAdmin() || $user->isProjectDirector()) {
            return true;
        }

        // Campus users can update classes from their campus
        if ($user->isCampusAdmin() && $user->campus_id) {
            return $class->campus_id === $user->campus_id;
        }

        return false;
    }

    public function delete(User $user, TrainingClass $class): bool
    {
        return $user->isSuperAdmin();
    }

    public function assignCandidates(User $user, TrainingClass $class): bool
    {
        return $this->update($user, $class);
    }

    public function removeCandidate(User $user, TrainingClass $class): bool
    {
        return $this->update($user, $class);
    }
}
