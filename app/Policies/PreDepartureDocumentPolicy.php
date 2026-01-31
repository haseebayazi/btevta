<?php

namespace App\Policies;

use App\Models\Candidate;
use App\Models\PreDepartureDocument;
use App\Models\User;

class PreDepartureDocumentPolicy
{
    /**
     * Get the candidate for a document, handling null relationship case
     */
    protected function getCandidate(PreDepartureDocument $document): ?Candidate
    {
        $candidate = $document->candidate;
        if (!$candidate && $document->candidate_id) {
            $candidate = Candidate::find($document->candidate_id);
        }
        return $candidate;
    }

    /**
     * Determine if user can view any pre-departure documents for a candidate
     */
    public function viewAny(User $user, Candidate $candidate): bool
    {
        // Super Admin and Project Director can view all
        if ($user->hasAnyRole(['super_admin', 'project_director'])) {
            return true;
        }

        // Campus Admin can view their campus candidates
        if ($user->hasRole('campus_admin')) {
            return $candidate->campus_id === $user->campus_id;
        }

        // OEP can view their assigned candidates
        if ($user->hasRole('oep')) {
            return $candidate->oep_id === $user->id;
        }

        return false;
    }

    /**
     * Determine if user can view a specific document
     */
    public function view(User $user, PreDepartureDocument $document): bool
    {
        $candidate = $this->getCandidate($document);
        if (!$candidate) {
            return false;
        }

        return $this->viewAny($user, $candidate);
    }

    /**
     * Determine if user can create documents for a candidate
     */
    public function create(User $user, Candidate $candidate): bool
    {
        // Super Admin can always create
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Others can only create if candidate is in editable status
        $editableStatuses = ['new', 'listed', 'pre_departure_docs'];
        if (!in_array($candidate->status, $editableStatuses)) {
            return false;
        }

        // Campus Admin can create for their campus
        if ($user->hasRole('campus_admin')) {
            return $candidate->campus_id === $user->campus_id;
        }

        // OEP can create for their candidates
        if ($user->hasRole('oep')) {
            return $candidate->oep_id === $user->id;
        }

        return false;
    }

    /**
     * Determine if user can update a document
     */
    public function update(User $user, PreDepartureDocument $document): bool
    {
        // Super Admin can always update (with audit log)
        if ($user->hasRole('super_admin')) {
            return true;
        }

        $candidate = $this->getCandidate($document);
        if (!$candidate) {
            return false;
        }

        // Cannot update if candidate progressed past editable statuses
        $editableStatuses = ['new', 'listed', 'pre_departure_docs'];
        if (!in_array($candidate->status, $editableStatuses)) {
            return false;
        }

        // Cannot update verified documents (unless Super Admin)
        if ($document->isVerified()) {
            return false;
        }

        // Campus Admin can update their campus documents
        if ($user->hasRole('campus_admin')) {
            return $candidate->campus_id === $user->campus_id;
        }

        // OEP can update their candidates' documents
        if ($user->hasRole('oep')) {
            return $candidate->oep_id === $user->id;
        }

        return false;
    }

    /**
     * Determine if user can delete a document
     */
    public function delete(User $user, PreDepartureDocument $document): bool
    {
        // Super Admin can always delete
        if ($user->hasRole('super_admin')) {
            return true;
        }

        $candidate = $this->getCandidate($document);
        if (!$candidate) {
            return false;
        }

        // Cannot delete if candidate progressed past editable statuses
        $editableStatuses = ['new', 'listed', 'pre_departure_docs'];
        if (!in_array($candidate->status, $editableStatuses)) {
            return false;
        }

        // Campus Admin can delete their campus documents
        if ($user->hasRole('campus_admin')) {
            return $candidate->campus_id === $user->campus_id;
        }

        // OEP can delete their candidates' documents
        if ($user->hasRole('oep')) {
            return $candidate->oep_id === $user->id;
        }

        return false;
    }

    /**
     * Determine if user can verify documents
     */
    public function verify(User $user, PreDepartureDocument $document): bool
    {
        // Super Admin and Project Director can verify
        if ($user->hasAnyRole(['super_admin', 'project_director'])) {
            return true;
        }

        // Campus Admin can verify their campus documents
        if ($user->hasRole('campus_admin')) {
            $candidate = $this->getCandidate($document);
            return $candidate && $candidate->campus_id === $user->campus_id;
        }

        return false;
    }

    /**
     * Determine if user can reject documents
     */
    public function reject(User $user, PreDepartureDocument $document): bool
    {
        return $this->verify($user, $document);
    }
}
