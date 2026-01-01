<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRegistrationDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\RegistrationDocument::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'candidate_id' => 'required|integer|exists:candidates,id',
            'document_type' => 'required|in:cnic,passport,photo,education_certificate,domicile,birth_certificate,experience_letter,medical_report,police_clearance,other',
            'document_number' => 'nullable|string|max:100',
            'document_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'issue_date' => 'nullable|date|before_or_equal:today',
            'expiry_date' => 'nullable|date|after:issue_date',
            'issuing_authority' => 'nullable|string|max:255',
            'is_verified' => 'nullable|boolean',
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
            'document_type' => 'document type',
            'document_number' => 'document number',
            'document_file' => 'document file',
            'issue_date' => 'issue date',
            'expiry_date' => 'expiry date',
            'issuing_authority' => 'issuing authority',
            'is_verified' => 'verification status',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'candidate_id.required' => 'Please select a candidate.',
            'document_type.required' => 'Please select a document type.',
            'document_file.required' => 'Please upload the document file.',
            'document_file.max' => 'Document file must not exceed 10MB.',
            'issue_date.before_or_equal' => 'Issue date cannot be in the future.',
            'expiry_date.after' => 'Expiry date must be after the issue date.',
        ];
    }
}
