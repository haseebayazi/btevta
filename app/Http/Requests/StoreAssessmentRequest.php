<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssessmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\TrainingAssessment::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'candidate_id' => 'required|integer|exists:candidates,id',
            'batch_id' => 'nullable|integer|exists:batches,id',
            'assessment_type' => 'required|in:initial,midterm,practical,final',
            'assessment_date' => 'required|date|before_or_equal:today',
            'theoretical_score' => 'nullable|numeric|min:0|max:100',
            'practical_score' => 'nullable|numeric|min:0|max:100',
            'total_score' => 'required|numeric|min:0|max:100',
            'max_score' => 'nullable|numeric|min:1|max:100',
            'pass_score' => 'nullable|numeric|min:0|max:100',
            'result' => 'nullable|in:pass,fail,pending',
            'assessment_location' => 'nullable|string|max:255',
            'remedial_needed' => 'nullable|boolean',
            'remarks' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Auto-calculate result if not provided
        if (!$this->has('result') && $this->has('total_score')) {
            $passScore = $this->pass_score ?? 60;
            $this->merge([
                'result' => $this->total_score >= $passScore ? 'pass' : 'fail',
            ]);
        }
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'candidate_id' => 'candidate',
            'batch_id' => 'batch',
            'assessment_type' => 'assessment type',
            'assessment_date' => 'assessment date',
            'theoretical_score' => 'theoretical score',
            'practical_score' => 'practical score',
            'total_score' => 'total score',
            'max_score' => 'maximum score',
            'pass_score' => 'passing score',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'candidate_id.required' => 'Please select a candidate.',
            'assessment_type.required' => 'Please select an assessment type.',
            'assessment_date.before_or_equal' => 'Assessment date cannot be in the future.',
            'total_score.required' => 'Please enter the total score.',
            'total_score.min' => 'Score cannot be negative.',
            'total_score.max' => 'Score cannot exceed 100.',
        ];
    }
}
