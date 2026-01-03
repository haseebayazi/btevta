<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreComplaintRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * AUDIT FIX: Changed from simple auth()->check() to proper role-based authorization.
     * Previously any authenticated user could create complaints.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (!$user) {
            return false;
        }

        // Check if user has permission to create complaints
        // Super admins, project directors, campus admins, and OEPs can create complaints
        return $user->isSuperAdmin() ||
               $user->isProjectDirector() ||
               $user->isCampusAdmin() ||
               $user->isOep() ||
               $user->isViewer(); // Viewers can file complaints on behalf of candidates
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'candidate_id' => 'required|exists:candidates,id',
            'campus_id' => 'nullable|exists:campuses,id',
            'oep_id' => 'nullable|exists:oeps,id',
            'complaint_category' => 'required|in:screening,training,visa,salary,conduct,accommodation,other',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,normal,high,urgent',
            'assigned_to' => 'nullable|exists:users,id',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'complaint_category' => 'category',
            'assigned_to' => 'assignee',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'candidate_id.required' => 'Please select a candidate.',
            'complaint_category.required' => 'Please select a complaint category.',
            'subject.required' => 'Please enter a subject.',
            'description.required' => 'Please enter a description.',
        ];
    }
}
