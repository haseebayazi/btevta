<?php

namespace App\Policies;

use App\Models\TrainingAttendance;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TrainingAttendancePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isInstructor() || $user->isViewer();
    }

    public function view(User $user, TrainingAttendance $attendance): bool
    {
        if ($user->isSuperAdmin() || $user->isProjectDirector() || $user->isViewer()) {
            return true;
        }

        // Campus admin can view attendance from their campus
        if ($user->isCampusAdmin() && $attendance->candidate && $user->campus_id === $attendance->candidate->campus_id) {
            return true;
        }

        // Instructor can view their recorded attendance
        if ($user->isInstructor() && $attendance->trainer_id === $user->id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isInstructor();
    }

    public function update(User $user, TrainingAttendance $attendance): bool
    {
        if ($user->isSuperAdmin() || $user->isProjectDirector()) {
            return true;
        }

        // Campus admin can update attendance from their campus
        if ($user->isCampusAdmin() && $attendance->candidate && $user->campus_id === $attendance->candidate->campus_id) {
            return true;
        }

        // Instructor can update their recorded attendance (same day only)
        if ($user->isInstructor() && $attendance->trainer_id === $user->id) {
            return $attendance->date->isToday();
        }

        return false;
    }

    public function delete(User $user, TrainingAttendance $attendance): bool
    {
        return $user->isSuperAdmin();
    }

    public function bulkRecord(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isInstructor();
    }
}
