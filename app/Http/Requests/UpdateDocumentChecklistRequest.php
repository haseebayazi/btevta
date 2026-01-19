<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDocumentChecklistRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('document_checklist'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $checklistId = $this->route('document_checklist')->id;

        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:document_checklists,code,' . $checklistId . '|alpha_dash',
            'description' => 'nullable|string|max:1000',
            'category' => 'required|string|in:mandatory,optional',
            'is_mandatory' => 'boolean',
            'display_order' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Auto-set is_mandatory based on category if not explicitly set
        if (!$this->has('is_mandatory') && $this->has('category')) {
            $this->merge([
                'is_mandatory' => $this->category === 'mandatory',
            ]);
        }
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'is_mandatory' => 'mandatory status',
            'display_order' => 'display order',
            'is_active' => 'active status',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please enter the document name.',
            'code.required' => 'Please enter a unique code for this document.',
            'code.unique' => 'This document code is already in use.',
            'code.alpha_dash' => 'Document code can only contain letters, numbers, dashes and underscores.',
            'category.required' => 'Please select a category.',
            'category.in' => 'Category must be either mandatory or optional.',
            'display_order.required' => 'Please enter the display order.',
            'display_order.min' => 'Display order cannot be negative.',
        ];
    }
}
