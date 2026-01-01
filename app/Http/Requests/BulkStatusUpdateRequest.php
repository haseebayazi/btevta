<?php

namespace App\Http\Requests;

use App\Enums\CandidateStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkStatusUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only admins can perform bulk operations
        return $this->user()->isSuperAdmin() || $this->user()->isProjectDirector() || $this->user()->isCampusAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'candidate_ids' => 'required|array|min:1|max:100',
            'candidate_ids.*' => 'required|integer|exists:candidates,id',
            'status' => ['required', Rule::in(array_column(CandidateStatus::cases(), 'value'))],
            'remarks' => 'nullable|string|max:500',
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
            'candidate_ids.max' => 'Cannot update more than 100 candidates at once.',
            'status.required' => 'Please select a status.',
            'status.in' => 'Invalid status selected.',
        ];
    }
}
