<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy for report viewing and generation permissions
 *
 * Roles with access:
 * - super_admin/admin: Full access to all reports
 * - project_director: Full access to all reports (oversight role)
 * - campus_admin: Access to their campus reports
 * - viewer: Read-only access to most reports
 */
class ReportPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view reports.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isViewer();
    }

    /**
     * Determine if the user can view candidate reports.
     */
    public function viewCandidateReport(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isViewer();
    }

    /**
     * Determine if the user can view campus-wise reports.
     */
    public function viewCampusWiseReport(User $user): bool
    {
        // Campus-wide reports require higher privileges
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isViewer();
    }

    /**
     * Determine if the user can view departure reports.
     */
    public function viewDepartureReport(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isViewer();
    }

    /**
     * Determine if the user can view financial reports.
     */
    public function viewFinancialReport(User $user): bool
    {
        // Financial reports require super admin or project director
        return $user->isSuperAdmin() || $user->isProjectDirector();
    }

    /**
     * Determine if the user can view trade-wise reports.
     */
    public function viewTradeWiseReport(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isViewer();
    }

    /**
     * Determine if the user can view monthly reports.
     */
    public function viewMonthlyReport(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isViewer();
    }

    /**
     * Determine if the user can view screening reports.
     */
    public function viewScreeningReport(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isViewer();
    }

    /**
     * Determine if the user can view training reports.
     */
    public function viewTrainingReport(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isViewer() || $user->isTrainer();
    }

    /**
     * Determine if the user can view visa reports.
     */
    public function viewVisaReport(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isViewer() || $user->isVisaPartner();
    }

    /**
     * Determine if the user can export reports.
     */
    public function exportReport(User $user): bool
    {
        // Export requires management-level access
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin();
    }
}
