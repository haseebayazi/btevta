<?php

namespace App\Policies;

use App\Models\Instructor;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InstructorPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Instructor $instructor): bool
    {
        // Admin can view all
        if ($user->role === 'admin') {
            return true;
        }

        // Campus users can view instructors from their campus
        if ($user->role === 'campus_admin' && $user->campus_id) {
            return $instructor->campus_id === $user->campus_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus_admin']);
    }

    public function update(User $user, Instructor $instructor): bool
    {
        // Admin can update all
        if ($user->role === 'admin') {
            return true;
        }

        // Campus users can update instructors from their campus
        if ($user->role === 'campus_admin' && $user->campus_id) {
            return $instructor->campus_id === $user->campus_id;
        }

        return false;
    }

    public function delete(User $user, Instructor $instructor): bool
    {
        return $user->role === 'admin';
    }
}
