<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkCampusAssignRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('bulkAssign', \App\Models\Candidate::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'candidate_ids' => 'required|array|min:1',
            'candidate_ids.*' => 'required|integer|exists:candidates,id',
            'campus_id' => 'required|integer|exists:campuses,id',
            'transfer_date' => 'nullable|date',
            'reason' => 'nullable|string|max:500',
            'notify_candidates' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'candidate_ids' => 'candidates',
            'candidate_ids.*' => 'candidate ID',
            'campus_id' => 'campus',
            'transfer_date' => 'transfer date',
            'notify_candidates' => 'notification preference',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'candidate_ids.required' => 'Please select at least one candidate.',
            'candidate_ids.min' => 'Please select at least one candidate.',
            'campus_id.required' => 'Please select a campus.',
            'campus_id.exists' => 'The selected campus does not exist.',
        ];
    }
}
