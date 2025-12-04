<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RemittanceReportPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view remittance reports
     */
    public function viewAny(User $user): bool
    {
        // Only admin, campus_admin, and OEP can view reports
        // Viewers excluded as reports contain sensitive analytics
        return in_array($user->role, ['admin', 'campus_admin', 'oep']);
    }

    /**
     * Determine if user can view the dashboard
     */
    public function viewDashboard(User $user): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine if user can view monthly reports
     */
    public function viewMonthly(User $user): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine if user can view purpose analysis
     */
    public function viewPurposeAnalysis(User $user): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine if user can view beneficiary reports
     */
    public function viewBeneficiary(User $user): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine if user can view proof compliance reports
     */
    public function viewCompliance(User $user): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine if user can view impact analytics
     */
    public function viewImpact(User $user): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine if user can export reports
     */
    public function export(User $user): bool
    {
        // Only admin and campus_admin can export sensitive financial data
        return in_array($user->role, ['admin', 'campus_admin']);
    }
}
