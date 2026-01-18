<?php

namespace App\Policies;

use App\Models\SuccessStory;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SuccessStoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view success stories
    }

    public function view(User $user, SuccessStory $successStory): bool
    {
        return true; // All authenticated users can view
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin() || $user->isStaff();
    }

    public function update(User $user, SuccessStory $successStory): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isCampusAdmin();
    }

    public function delete(User $user, SuccessStory $successStory): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector();
    }
}
