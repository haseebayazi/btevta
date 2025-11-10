<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy for report viewing and generation permissions
 */
class ReportPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view reports.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view reports
        return in_array($user->role, ['admin', 'campus_admin', 'viewer']);
    }

    /**
     * Determine if the user can view candidate reports.
     */
    public function viewCandidateReport(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus_admin', 'viewer']);
    }

    /**
     * Determine if the user can view campus-wise reports.
     */
    public function viewCampusWiseReport(User $user): bool
    {
        return in_array($user->role, ['admin', 'viewer']);
    }

    /**
     * Determine if the user can view departure reports.
     */
    public function viewDepartureReport(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus_admin', 'viewer']);
    }

    /**
     * Determine if the user can view financial reports.
     */
    public function viewFinancialReport(User $user): bool
    {
        // Only admin can view financial reports
        return $user->role === 'admin';
    }

    /**
     * Determine if the user can view trade-wise reports.
     */
    public function viewTradeWiseReport(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus_admin', 'viewer']);
    }

    /**
     * Determine if the user can view monthly reports.
     */
    public function viewMonthlyReport(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus_admin', 'viewer']);
    }

    /**
     * Determine if the user can view screening reports.
     */
    public function viewScreeningReport(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus_admin', 'viewer']);
    }

    /**
     * Determine if the user can view training reports.
     */
    public function viewTrainingReport(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus_admin', 'viewer']);
    }

    /**
     * Determine if the user can view visa reports.
     */
    public function viewVisaReport(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus_admin', 'viewer']);
    }

    /**
     * Determine if the user can export reports.
     */
    public function exportReport(User $user): bool
    {
        // Admin and campus_admin can export
        return in_array($user->role, ['admin', 'campus_admin']);
    }
}
