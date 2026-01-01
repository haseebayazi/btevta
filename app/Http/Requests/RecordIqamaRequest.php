<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordIqamaRequest extends FormRequest
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
            'iqama_number' => [
                'required',
                'string',
                'regex:/^[0-9]{10}$/', // Saudi Iqama is 10 digits
            ],
            'issue_date' => 'required|date|before_or_equal:today',
            'expiry_date' => 'required|date|after:issue_date',
            'sponsor_name' => 'nullable|string|max:255',
            'sponsor_id' => 'nullable|string|max:50',
            'profession' => 'nullable|string|max:255',
            'nationality_code' => 'nullable|string|max:10',
            'absher_registered' => 'nullable|boolean',
            'absher_registration_date' => 'nullable|date|required_if:absher_registered,true',
            'qiwa_id' => 'nullable|string|max:50',
            'qiwa_activation_date' => 'nullable|date',
            'medical_report_path' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
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
            'iqama_number' => 'Iqama number',
            'issue_date' => 'issue date',
            'expiry_date' => 'expiry date',
            'sponsor_name' => 'sponsor name',
            'sponsor_id' => 'sponsor ID',
            'absher_registered' => 'Absher registration',
            'absher_registration_date' => 'Absher registration date',
            'qiwa_id' => 'Qiwa ID',
            'qiwa_activation_date' => 'Qiwa activation date',
            'medical_report_path' => 'medical report',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'candidate_id.required' => 'Please select a candidate.',
            'iqama_number.required' => 'Please enter Iqama number.',
            'iqama_number.regex' => 'Iqama number must be exactly 10 digits.',
            'issue_date.required' => 'Please enter issue date.',
            'issue_date.before_or_equal' => 'Issue date cannot be in the future.',
            'expiry_date.required' => 'Please enter expiry date.',
            'expiry_date.after' => 'Expiry date must be after issue date.',
            'absher_registration_date.required_if' => 'Absher registration date is required when Absher is registered.',
            'medical_report_path.max' => 'Medical report file size cannot exceed 5MB.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Remove any dashes or spaces from Iqama number
        if ($this->has('iqama_number')) {
            $this->merge([
                'iqama_number' => preg_replace('/[^0-9]/', '', $this->iqama_number),
            ]);
        }

        // Convert absher_registered to boolean
        if ($this->has('absher_registered')) {
            $this->merge([
                'absher_registered' => filter_var($this->input('absher_registered'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }
}
