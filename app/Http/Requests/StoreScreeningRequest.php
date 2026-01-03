<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreScreeningRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * AUDIT FIX: Changed from simple auth()->check() to proper role-based authorization.
     * Previously any authenticated user could create screenings.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (!$user) {
            return false;
        }

        // Only authorized staff can create screening records
        return $user->isSuperAdmin() ||
               $user->isProjectDirector() ||
               $user->isCampusAdmin() ||
               $user->isOep();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'candidate_id' => 'required|exists:candidates,id',
            'screening_type' => 'required|string|in:desk,call,physical,document,medical',
            'screened_at' => 'required|date',
            'call_duration' => 'nullable|integer|min:1',
            'status' => 'required|in:pending,in_progress,passed,failed,deferred,cancelled',
            'remarks' => 'nullable|string|max:1000',
            'evidence_path' => 'nullable|string',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'screened_at' => 'screening date',
            'call_duration' => 'call duration',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'candidate_id.required' => 'Please select a candidate.',
            'screening_type.required' => 'Please select a screening type.',
            'status.required' => 'Please select a status.',
        ];
    }
}
