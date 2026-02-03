<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitialScreeningRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Policy handles authorization via controller
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'candidate_id' => 'required|exists:candidates,id',
            'consent_for_work' => 'required|boolean|accepted',
            'placement_interest' => 'required|in:local,international',
            'target_country_id' => 'required_if:placement_interest,international|nullable|exists:countries,id',
            'screening_status' => 'required|in:pending,screened,deferred',
            'notes' => 'nullable|string|max:2000',
            'evidence' => 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'consent_for_work.accepted' => 'Candidate must consent to work before screening can proceed.',
            'consent_for_work.required' => 'Consent for work is required.',
            'placement_interest.required' => 'Please select placement interest (Local or International).',
            'target_country_id.required_if' => 'Target country is required for international placement.',
            'screening_status.required' => 'Please select a screening outcome.',
            'evidence.max' => 'Evidence file must not exceed 10MB.',
            'evidence.mimes' => 'Evidence must be a file of type: pdf, jpg, jpeg, png.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'consent_for_work' => 'consent for work',
            'placement_interest' => 'placement interest',
            'target_country_id' => 'target country',
            'screening_status' => 'screening outcome',
        ];
    }
}
