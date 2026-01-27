<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCandidateLicenseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled by policy in controller
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'license_type' => 'required|in:driving,professional',
            'license_name' => 'required|string|max:100',
            'license_number' => 'required|string|max:50',
            'license_category' => 'nullable|string|max:50',
            'issuing_authority' => 'nullable|string|max:150',
            'issue_date' => 'nullable|date|before_or_equal:today',
            'expiry_date' => 'nullable|date|after:issue_date',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'license_type.required' => 'Please select a license type.',
            'license_type.in' => 'License type must be either driving or professional.',
            'license_name.required' => 'Please enter the license name.',
            'license_number.required' => 'Please enter the license number.',
            'issue_date.before_or_equal' => 'Issue date cannot be in the future.',
            'expiry_date.after' => 'Expiry date must be after the issue date.',
            'file.mimes' => 'File must be a PDF, JPG, JPEG, or PNG.',
            'file.max' => 'File size must not exceed 5MB.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'license_type' => 'license type',
            'license_name' => 'license name',
            'license_number' => 'license number',
            'license_category' => 'category',
            'issuing_authority' => 'issuing authority',
            'issue_date' => 'issue date',
            'expiry_date' => 'expiry date',
        ];
    }
}
