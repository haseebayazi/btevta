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
        return $this->user()->can('create', \App\Models\PreDepartureDocument::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'document_checklist_id' => 'required|exists:document_checklists,id',
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'notes' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'document_checklist_id' => 'document type',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'document_checklist_id.required' => 'Please select the document type.',
            'document_checklist_id.exists' => 'The selected document type is invalid.',
            'document.required' => 'Please select a file to upload.',
            'document.mimes' => 'Document must be a PDF or image file (JPG, JPEG, PNG).',
            'document.max' => 'Document file size cannot exceed 10MB.',
        ];
    }
}
