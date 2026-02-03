<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePreDepartureDocumentRequest extends FormRequest
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
            'document_checklist_id' => 'required|exists:document_checklists,id',
            // Support both single file and multiple files
            'file' => 'required_without:files|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
            'files' => 'required_without:file|array|max:5',
            'files.*' => 'file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max per file
            'notes' => 'nullable|string|max:500',
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
            'document_checklist_id.required' => 'Please select a document type.',
            'document_checklist_id.exists' => 'Invalid document type selected.',
            'file.required_without' => 'Please select at least one file to upload.',
            'file.mimes' => 'File must be a PDF, JPG, JPEG, or PNG.',
            'file.max' => 'File size must not exceed 5MB.',
            'files.required_without' => 'Please select at least one file to upload.',
            'files.max' => 'Maximum 5 files can be uploaded at once.',
            'files.*.mimes' => 'All files must be PDF, JPG, JPEG, or PNG.',
            'files.*.max' => 'Each file must not exceed 5MB.',
            'notes.max' => 'Notes cannot exceed 500 characters.',
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
            'document_checklist_id' => 'document type',
            'file' => 'document file',
        ];
    }
}
