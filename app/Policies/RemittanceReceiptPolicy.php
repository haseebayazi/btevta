<?php

namespace App\Policies;

use App\Models\RemittanceReceipt;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RemittanceReceiptPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isOep();
    }

    public function view(User $user, RemittanceReceipt $receipt): bool
    {
        if ($user->isSuperAdmin() || $user->isProjectDirector()) {
            return true;
        }

        // OEP can view receipts for candidates assigned to them
        if ($user->isOep() && $receipt->remittance && $receipt->remittance->candidate) {
            return $receipt->remittance->candidate->oep_id === $user->oep_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector() || $user->isOep();
    }

    public function update(User $user, RemittanceReceipt $receipt): bool
    {
        return $user->isSuperAdmin() || $user->isProjectDirector();
    }

    public function delete(User $user, RemittanceReceipt $receipt): bool
    {
        return $user->isSuperAdmin();
    }

    public function download(User $user, RemittanceReceipt $receipt): bool
    {
        return $this->view($user, $receipt);
    }
}
