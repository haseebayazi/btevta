<?php

namespace App\Policies;

use App\Models\Batch;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BatchPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isTrainer() || $user->isViewer();
    }

    public function view(User $user, Batch $batch): bool
    {
        if ($user->isSuperAdmin() || $user->isProjectDirector() || $user->isViewer()) {
            return true;
        }

        if ($user->isCampusAdmin() && $user->campus_id) {
            return $batch->campus_id === $user->campus_id;
        }

        if ($user->isTrainer() && $user->campus_id) {
            return $batch->campus_id === $user->campus_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin();
    }

    public function update(User $user, Batch $batch): bool
    {
        if ($user->isSuperAdmin() || $user->isProjectDirector()) {
            return true;
        }

        if ($user->isCampusAdmin() && $user->campus_id) {
            return $batch->campus_id === $user->campus_id;
        }

        return false;
    }

    public function delete(User $user, Batch $batch): bool
    {
        return $user->isSuperAdmin();
    }

    public function changeStatus(User $user, Batch $batch): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin();
    }

    public function apiList(User $user): bool
    {
        // API list can be accessed by authenticated users who need dropdown data
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isTrainer() || $user->isViewer();
    }

    public function byCampus(User $user): bool
    {
        // API endpoint for batches by campus - needed for dropdown filtering
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isTrainer() || $user->isViewer();
    }
}
