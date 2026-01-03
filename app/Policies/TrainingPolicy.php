<?php

namespace App\Policies;

use App\Models\User;
use App\Models\TrainingAssessment;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy for training operations, attendance, and assessments
 */
class TrainingPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view training lists.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isTrainer() || $user->isViewer();
    }

    /**
     * Determine if the user can view specific training details.
     * SECURITY FIX: Added optional model parameter for campus-specific enforcement
     *
     * @param User $user The authenticated user
     * @param mixed $training Optional training model (TrainingClass, TrainingSchedule, etc.)
     */
    public function view(User $user, $training = null): bool
    {
        if ($user->isSuperAdmin() || $user->isProjectDirector() || $user->isViewer()) {
            return true;
        }

        // If no training model provided, fall back to role-based check
        if (!$training) {
            return $user->isCampusAdmin() || $user->isTrainer();
        }

        // Campus admin can only view training from their campus
        if ($user->isCampusAdmin() && $user->campus_id) {
            // AUDIT FIX: Removed fallback 'return true' - require campus_id check
            if (isset($training->campus_id)) {
                return $training->campus_id === $user->campus_id;
            }
            // If training doesn't have campus_id, deny access for security
            return false;
        }

        // Trainers can view trainings they're assigned to or from their campus
        if ($user->isTrainer()) {
            // Check if trainer is assigned to this training
            if (isset($training->trainer_id) && $training->trainer_id === $user->id) {
                return true;
            }
            // AUDIT FIX: Removed fallback 'return true' - require campus validation
            // Allow only if in same campus
            if ($user->campus_id && isset($training->campus_id)) {
                return $training->campus_id === $user->campus_id;
            }
            // If no campus assignment, deny access
            return false;
        }

        return false;
    }

    /**
     * Determine if the user can create training records.
     */
    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin();
    }

    /**
     * Determine if the user can update training records.
     */
    public function update(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin();
    }

    /**
     * Determine if the user can delete training records.
     */
    public function delete(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine if the user can restore soft-deleted training records.
     * AUDIT FIX: Added restore method for SoftDeletes support
     */
    public function restore(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector();
    }

    /**
     * Determine if the user can permanently delete training records.
     * AUDIT FIX: Added forceDelete method for SoftDeletes support
     */
    public function forceDelete(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine if the user can mark attendance.
     */
    public function markAttendance(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isTrainer();
    }

    /**
     * Determine if the user can view attendance.
     */
    public function viewAttendance(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isTrainer() || $user->isViewer();
    }

    /**
     * Determine if the user can create assessments.
     */
    public function createAssessment(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isTrainer();
    }

    /**
     * Determine if the user can update assessments.
     */
    public function updateAssessment(User $user, TrainingAssessment $assessment = null): bool
    {
        if ($user->isSuperAdmin() || $user->isProjectDirector()) {
            return true;
        }

        // AUDIT FIX: Campus admin can update assessments from their campus only
        // Previously returned true without campus validation
        if ($user->isCampusAdmin() && $user->campus_id) {
            if ($assessment && isset($assessment->campus_id)) {
                return $assessment->campus_id === $user->campus_id;
            }
            // Allow if no assessment provided (for create-like operations)
            return $assessment === null;
        }

        // Trainers can update their own assessments
        if ($user->isTrainer() && $assessment && $assessment->trainer_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can generate certificates.
     */
    public function generateCertificate(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin();
    }

    /**
     * Determine if the user can download certificates.
     */
    public function downloadCertificate(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isTrainer() || $user->isViewer();
    }

    /**
     * Determine if the user can mark training as complete.
     */
    public function completeTraining(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin();
    }

    /**
     * Determine if the user can view attendance reports.
     */
    public function viewAttendanceReport(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isTrainer() || $user->isViewer();
    }

    /**
     * Determine if the user can view assessment reports.
     */
    public function viewAssessmentReport(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isTrainer() || $user->isViewer();
    }

    /**
     * Determine if the user can view batch performance.
     */
    public function viewBatchPerformance(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isTrainer() || $user->isViewer();
    }
}
