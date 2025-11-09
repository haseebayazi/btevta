<?php

namespace App\Policies;

use App\Models\TrainingClass;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TrainingClassPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, TrainingClass $class): bool
    {
        // Admin can view all
        if ($user->role === 'admin') {
            return true;
        }

        // Campus users can view classes from their campus
        if ($user->role === 'campus' && $user->campus_id) {
            return $class->campus_id === $user->campus_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus']);
    }

    public function update(User $user, TrainingClass $class): bool
    {
        // Admin can update all
        if ($user->role === 'admin') {
            return true;
        }

        // Campus users can update classes from their campus
        if ($user->role === 'campus' && $user->campus_id) {
            return $class->campus_id === $user->campus_id;
        }

        return false;
    }

    public function delete(User $user, TrainingClass $class): bool
    {
        return $user->role === 'admin';
    }

    public function assignCandidates(User $user, TrainingClass $class): bool
    {
        return $this->update($user, $class);
    }

    public function removeCandidate(User $user, TrainingClass $class): bool
    {
        return $this->update($user, $class);
    }
}
