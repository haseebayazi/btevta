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
        return in_array($user->role, ['admin', 'campus_admin', 'instructor', 'viewer']);
    }

    /**
     * Determine if the user can view specific training details.
     */
    public function view(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus_admin', 'instructor', 'viewer']);
    }

    /**
     * Determine if the user can create training records.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus_admin']);
    }

    /**
     * Determine if the user can update training records.
     */
    public function update(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus_admin']);
    }

    /**
     * Determine if the user can delete training records.
     */
    public function delete(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine if the user can mark attendance.
     */
    public function markAttendance(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus_admin', 'instructor']);
    }

    /**
     * Determine if the user can view attendance.
     */
    public function viewAttendance(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus_admin', 'instructor', 'viewer']);
    }

    /**
     * Determine if the user can create assessments.
     */
    public function createAssessment(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus_admin', 'instructor']);
    }

    /**
     * Determine if the user can update assessments.
     */
    public function updateAssessment(User $user, TrainingAssessment $assessment = null): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        // Campus admin can update assessments from their campus
        if ($user->role === 'campus_admin' && $user->campus_id) {
            return true;
        }

        // Instructors can update their own assessments
        if ($user->role === 'instructor' && $assessment && $assessment->trainer_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can generate certificates.
     */
    public function generateCertificate(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus_admin']);
    }

    /**
     * Determine if the user can download certificates.
     */
    public function downloadCertificate(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus_admin', 'instructor', 'viewer']);
    }

    /**
     * Determine if the user can mark training as complete.
     */
    public function completeTraining(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus_admin']);
    }

    /**
     * Determine if the user can view attendance reports.
     */
    public function viewAttendanceReport(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus_admin', 'instructor', 'viewer']);
    }

    /**
     * Determine if the user can view assessment reports.
     */
    public function viewAssessmentReport(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus_admin', 'instructor', 'viewer']);
    }

    /**
     * Determine if the user can view batch performance.
     */
    public function viewBatchPerformance(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus_admin', 'instructor', 'viewer']);
    }
}
