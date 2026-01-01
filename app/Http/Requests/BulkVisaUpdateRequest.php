<?php

namespace App\Http\Requests;

use App\Enums\VisaStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkVisaUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('bulkUpdate', \App\Models\VisaProcess::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'visa_process_ids' => 'required|array|min:1',
            'visa_process_ids.*' => 'required|integer|exists:visa_processes,id',
            'stage' => ['required', Rule::enum(VisaStage::class)],
            'stage_date' => 'nullable|date|before_or_equal:today',
            'stage_status' => 'nullable|in:pending,in_progress,completed,failed',
            'remarks' => 'nullable|string|max:500',
            'notify_candidates' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'visa_process_ids' => 'visa processes',
            'visa_process_ids.*' => 'visa process ID',
            'stage_date' => 'stage date',
            'stage_status' => 'stage status',
            'notify_candidates' => 'notification preference',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'visa_process_ids.required' => 'Please select at least one visa process.',
            'visa_process_ids.min' => 'Please select at least one visa process.',
            'stage.required' => 'Please select a visa stage.',
            'stage_date.before_or_equal' => 'Stage date cannot be in the future.',
        ];
    }
}
