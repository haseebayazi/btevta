<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordComplianceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', \App\Models\Departure::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'candidate_id' => 'required|integer|exists:candidates,id',
            'compliance_date' => 'required|date',
            'is_compliant' => 'required|boolean',

            // Employment verification
            'employer_verified' => 'nullable|boolean',
            'employer_name_confirmed' => 'nullable|string|max:255',
            'work_location' => 'nullable|string|max:500',

            // Salary verification
            'salary_verified' => 'nullable|boolean',
            'salary_amount' => 'nullable|numeric|min:0',
            'salary_currency' => 'nullable|string|max:10',
            'salary_payment_method' => 'nullable|in:bank_transfer,cash,wps',
            'first_salary_date' => 'nullable|date',
            'salary_proof_path' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',

            // Accommodation verification
            'accommodation_verified' => 'nullable|boolean',
            'accommodation_type' => 'nullable|in:employer_provided,self_arranged,shared',
            'accommodation_address' => 'nullable|string|max:500',
            'accommodation_quality' => 'nullable|in:good,acceptable,poor',

            // Health and safety
            'health_status' => 'nullable|in:good,minor_issues,major_issues',
            'health_issues' => 'nullable|string|max:1000',
            'safety_concerns' => 'nullable|boolean',
            'safety_concerns_details' => 'nullable|string|max:1000|required_if:safety_concerns,true',

            // Communication
            'contact_method' => 'nullable|in:phone,whatsapp,email,video_call,visit',
            'contact_date' => 'nullable|date',
            'contacted_by' => 'nullable|integer|exists:users,id',
            'family_contact_maintained' => 'nullable|boolean',

            // Issues and follow-up
            'issues_reported' => 'nullable|array',
            'issues_reported.*' => 'string|max:500',
            'requires_follow_up' => 'nullable|boolean',
            'follow_up_date' => 'nullable|date|after:today|required_if:requires_follow_up,true',
            'follow_up_reason' => 'nullable|string|max:500',

            // Overall assessment
            'overall_assessment' => 'nullable|in:excellent,satisfactory,concerning,critical',
            'recommendations' => 'nullable|string|max:1000',
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
            'compliance_date' => 'compliance date',
            'is_compliant' => 'compliance status',
            'employer_verified' => 'employer verification',
            'employer_name_confirmed' => 'confirmed employer name',
            'salary_verified' => 'salary verification',
            'salary_amount' => 'salary amount',
            'salary_payment_method' => 'salary payment method',
            'first_salary_date' => 'first salary date',
            'salary_proof_path' => 'salary proof',
            'accommodation_verified' => 'accommodation verification',
            'accommodation_type' => 'accommodation type',
            'accommodation_quality' => 'accommodation quality',
            'health_status' => 'health status',
            'health_issues' => 'health issues',
            'safety_concerns' => 'safety concerns',
            'safety_concerns_details' => 'safety concerns details',
            'contact_method' => 'contact method',
            'contact_date' => 'contact date',
            'contacted_by' => 'contacted by',
            'family_contact_maintained' => 'family contact',
            'issues_reported' => 'reported issues',
            'requires_follow_up' => 'follow-up required',
            'follow_up_date' => 'follow-up date',
            'overall_assessment' => 'overall assessment',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'candidate_id.required' => 'Please select a candidate.',
            'compliance_date.required' => 'Please enter compliance check date.',
            'is_compliant.required' => 'Please indicate compliance status.',
            'safety_concerns_details.required_if' => 'Please provide details when safety concerns are reported.',
            'follow_up_date.required_if' => 'Follow-up date is required when follow-up is needed.',
            'follow_up_date.after' => 'Follow-up date must be in the future.',
            'salary_proof_path.max' => 'Salary proof file size cannot exceed 5MB.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert boolean fields
        $booleanFields = [
            'is_compliant', 'employer_verified', 'salary_verified',
            'accommodation_verified', 'safety_concerns', 'family_contact_maintained',
            'requires_follow_up'
        ];

        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $this->merge([
                    $field => filter_var($this->input($field), FILTER_VALIDATE_BOOLEAN),
                ]);
            }
        }

        // Parse issues_reported if it's a comma-separated string
        if ($this->has('issues_reported') && is_string($this->issues_reported)) {
            $this->merge([
                'issues_reported' => array_filter(array_map('trim', explode(',', $this->issues_reported))),
            ]);
        }
    }
}
