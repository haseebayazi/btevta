<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitVisaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('visaProcess'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'visa_submission_date' => 'required|date|before_or_equal:today',
            'visa_number' => 'nullable|string|max:50',
            'visa_issue_date' => 'nullable|date|after_or_equal:visa_submission_date',
            'visa_expiry_date' => 'nullable|date|after:visa_issue_date',
            'ptn_number' => 'nullable|string|max:50',
            'ptn_issue_date' => 'nullable|date',
            'visa_type' => 'required|string|max:100',
            'visa_category' => 'nullable|string|max:100',
            'profession_on_visa' => 'nullable|string|max:255',
            'visa_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'remarks' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'visa_submission_date' => 'visa submission date',
            'visa_number' => 'visa number',
            'visa_issue_date' => 'visa issue date',
            'visa_expiry_date' => 'visa expiry date',
            'ptn_number' => 'PTN number',
            'ptn_issue_date' => 'PTN issue date',
            'visa_type' => 'visa type',
            'visa_category' => 'visa category',
            'profession_on_visa' => 'profession on visa',
            'visa_document' => 'visa document',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'visa_submission_date.required' => 'Please enter the visa submission date.',
            'visa_submission_date.before_or_equal' => 'Visa submission date cannot be in the future.',
            'visa_issue_date.after_or_equal' => 'Visa issue date must be on or after submission date.',
            'visa_expiry_date.after' => 'Visa expiry date must be after the issue date.',
            'visa_type.required' => 'Please specify the visa type.',
            'visa_document.max' => 'Visa document must not exceed 5MB.',
        ];
    }
}
