<?php

namespace App\Policies;

use App\Models\Departure;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DeparturePolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any departures.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view departures list
        return in_array($user->role, ['admin', 'campus_admin', 'viewer']);
    }

    /**
     * Determine if the user can view a specific departure.
     */
    public function view(User $user, Departure $departure): bool
    {
        // Admin can view all
        if ($user->role === 'admin') {
            return true;
        }

        // Campus admin can view departures from their campus
        if ($user->role === 'campus_admin' && $user->campus_id && $departure->candidate) {
            return $departure->candidate->campus_id === $user->campus_id;
        }

        // Viewers can view all
        if ($user->role === 'viewer') {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can create departures.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus_admin']);
    }

    /**
     * Determine if the user can update a departure.
     */
    public function update(User $user, Departure $departure): bool
    {
        // Admin can update all
        if ($user->role === 'admin') {
            return true;
        }

        // Campus admin can update departures from their campus
        if ($user->role === 'campus_admin' && $user->campus_id && $departure->candidate) {
            return $departure->candidate->campus_id === $user->campus_id;
        }

        return false;
    }

    /**
     * Determine if the user can delete a departure.
     */
    public function delete(User $user, Departure $departure): bool
    {
        // Only admin can delete
        return $user->role === 'admin';
    }

    /**
     * Determine if the user can record briefing.
     */
    public function recordBriefing(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus_admin']);
    }

    /**
     * Determine if the user can record departure.
     */
    public function recordDeparture(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus_admin']);
    }

    /**
     * Determine if the user can record iqama.
     */
    public function recordIqama(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus_admin']);
    }

    /**
     * Determine if the user can record absher.
     */
    public function recordAbsher(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus_admin']);
    }

    /**
     * Determine if the user can record WPS/QIWA.
     */
    public function recordWps(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus_admin']);
    }

    /**
     * Determine if the user can record first salary.
     */
    public function recordFirstSalary(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus_admin']);
    }

    /**
     * Determine if the user can record 90-day compliance.
     */
    public function record90DayCompliance(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus_admin']);
    }

    /**
     * Determine if the user can report issues.
     */
    public function reportIssue(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus_admin']);
    }

    /**
     * Determine if the user can update issues.
     */
    public function updateIssue(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus_admin']);
    }

    /**
     * Determine if the user can view timeline.
     */
    public function viewTimeline(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus_admin', 'viewer']);
    }

    /**
     * Determine if the user can view compliance reports.
     */
    public function viewComplianceReport(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus_admin', 'viewer']);
    }

    /**
     * Determine if the user can view tracking reports.
     */
    public function viewTrackingReports(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus_admin', 'viewer']);
    }

    /**
     * Determine if the user can mark candidate as returned.
     */
    public function markReturned(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus_admin']);
    }
}
