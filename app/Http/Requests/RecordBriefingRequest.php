<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordBriefingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Departure::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'candidate_id' => 'required|integer|exists:candidates,id',
            'briefing_date' => 'required|date|before_or_equal:today',
            'conducted_by' => 'nullable|integer|exists:users,id',
            'topics' => 'nullable|array',
            'topics.*' => 'string|max:255',
            'briefing_location' => 'nullable|string|max:255',
            'briefing_duration_minutes' => 'nullable|integer|min:15|max:480',
            'cultural_orientation' => 'nullable|boolean',
            'rights_explained' => 'nullable|boolean',
            'contract_reviewed' => 'nullable|boolean',
            'emergency_contacts_shared' => 'nullable|boolean',
            'attendee_signature' => 'nullable|string', // base64 signature
            'witness_name' => 'nullable|string|max:255',
            'witness_contact' => 'nullable|string|max:50',
            'remarks' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'candidate_id' => 'candidate',
            'briefing_date' => 'briefing date',
            'conducted_by' => 'conductor',
            'topics' => 'briefing topics',
            'briefing_location' => 'briefing location',
            'briefing_duration_minutes' => 'briefing duration',
            'cultural_orientation' => 'cultural orientation session',
            'rights_explained' => 'rights explanation',
            'contract_reviewed' => 'contract review',
            'emergency_contacts_shared' => 'emergency contacts',
            'witness_name' => 'witness name',
            'witness_contact' => 'witness contact',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'candidate_id.required' => 'Please select a candidate.',
            'briefing_date.required' => 'Please enter briefing date.',
            'briefing_date.before_or_equal' => 'Briefing date cannot be in the future.',
            'briefing_duration_minutes.min' => 'Briefing duration must be at least 15 minutes.',
            'briefing_duration_minutes.max' => 'Briefing duration cannot exceed 8 hours.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('topics') && is_string($this->topics)) {
            $this->merge([
                'topics' => explode(',', $this->topics),
            ]);
        }

        // Convert checkbox values to boolean
        $booleanFields = ['cultural_orientation', 'rights_explained', 'contract_reviewed', 'emergency_contacts_shared'];
        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $this->merge([
                    $field => filter_var($this->input($field), FILTER_VALIDATE_BOOLEAN),
                ]);
            }
        }
    }
}
