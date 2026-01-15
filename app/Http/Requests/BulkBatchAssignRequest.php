<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkBatchAssignRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
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
            'batch_id' => 'required|integer|exists:batches,id',
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
            'batch_id' => 'batch',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'candidate_ids.required' => 'Please select at least one candidate.',
            'candidate_ids.max' => 'Cannot assign more than 100 candidates at once.',
            'batch_id.required' => 'Please select a batch.',
            'batch_id.exists' => 'Selected batch does not exist.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $batch = \App\Models\Batch::find($this->batch_id);
            $candidateCount = count($this->candidate_ids ?? []);

            if ($batch && !$batch->canAddCandidates($candidateCount)) {
                $validator->errors()->add(
                    'batch_id',
                    "Batch capacity exceeded. Available slots: {$batch->available_slots}, Requested: {$candidateCount}"
                );
            }
        });
    }
}
