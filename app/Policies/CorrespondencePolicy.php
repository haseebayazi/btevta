<?php

namespace App\Policies;

use App\Models\Correspondence;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CorrespondencePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Correspondence $correspondence): bool
    {
        // Admin can view all
        if ($user->role === 'admin') {
            return true;
        }

        // Campus users can view correspondence related to their campus
        if ($user->role === 'campus_admin' && $user->campus_id) {
            return $correspondence->campus_id === $user->campus_id;
        }

        // OEP users can view correspondence related to their OEP
        if ($user->role === 'oep' && $user->oep_id) {
            return $correspondence->oep_id === $user->oep_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'campus_admin']);
    }

    public function update(User $user, Correspondence $correspondence): bool
    {
        // Admin can update all
        if ($user->role === 'admin') {
            return true;
        }

        // Campus users can update their own correspondence
        if ($user->role === 'campus_admin' && $user->campus_id) {
            return $correspondence->campus_id === $user->campus_id;
        }

        return false;
    }

    public function delete(User $user, Correspondence $correspondence): bool
    {
        return $user->role === 'admin';
    }

    public function markReplied(User $user, Correspondence $correspondence): bool
    {
        return $this->update($user, $correspondence);
    }
}
