<?php

namespace App\Policies;

use App\Models\TrainingAssessment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TrainingAssessmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isInstructor() || $user->isViewer();
    }

    public function view(User $user, TrainingAssessment $assessment): bool
    {
        if ($user->isSuperAdmin() || $user->isProjectDirector() || $user->isViewer()) {
            return true;
        }

        // Campus admin can view assessments from their campus
        if ($user->isCampusAdmin() && $assessment->candidate && $user->campus_id === $assessment->candidate->campus_id) {
            return true;
        }

        // Instructor can view their own assessments
        if ($user->isInstructor() && $assessment->trainer_id === $user->id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isInstructor();
    }

    public function update(User $user, TrainingAssessment $assessment): bool
    {
        if ($user->isSuperAdmin() || $user->isProjectDirector()) {
            return true;
        }

        // Campus admin can update assessments from their campus
        if ($user->isCampusAdmin() && $assessment->candidate && $user->campus_id === $assessment->candidate->campus_id) {
            return true;
        }

        // Instructor can update their own assessments
        if ($user->isInstructor() && $assessment->trainer_id === $user->id) {
            return true;
        }

        return false;
    }

    public function delete(User $user, TrainingAssessment $assessment): bool
    {
        return $user->isSuperAdmin();
    }
}
