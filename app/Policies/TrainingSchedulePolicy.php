<?php

namespace App\Policies;

use App\Models\TrainingSchedule;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TrainingSchedulePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isInstructor() || $user->isViewer();
    }

    public function view(User $user, TrainingSchedule $schedule): bool
    {
        if ($user->isSuperAdmin() || $user->isProjectDirector() || $user->isViewer()) {
            return true;
        }

        // Campus admin can view schedules from their campus
        if ($user->isCampusAdmin() && $schedule->trainingClass && $user->campus_id === $schedule->trainingClass->campus_id) {
            return true;
        }

        // Instructor can view schedules they're assigned to
        if ($user->isInstructor() && $schedule->instructor_id === $user->id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin();
    }

    public function update(User $user, TrainingSchedule $schedule): bool
    {
        if ($user->isSuperAdmin() || $user->isProjectDirector()) {
            return true;
        }

        // Campus admin can update schedules from their campus
        if ($user->isCampusAdmin() && $schedule->trainingClass && $user->campus_id === $schedule->trainingClass->campus_id) {
            return true;
        }

        return false;
    }

    public function delete(User $user, TrainingSchedule $schedule): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector();
    }
}
