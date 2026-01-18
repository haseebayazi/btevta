<?php

namespace App\Policies;

use App\Models\DocumentChecklist;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DocumentChecklistPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view document checklists
    }

    public function view(User $user, DocumentChecklist $documentChecklist): bool
    {
        return true; // All authenticated users can view
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector();
    }

    public function update(User $user, DocumentChecklist $documentChecklist): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector();
    }

    public function delete(User $user, DocumentChecklist $documentChecklist): bool
    {
        return $user->isSuperAdmin();
    }
}
