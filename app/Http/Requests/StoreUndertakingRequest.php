<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUndertakingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Undertaking::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'candidate_id' => 'required|integer|exists:candidates,id',
            'undertaking_type' => 'required|in:training,visa,departure,general,code_of_conduct,financial',
            'undertaking_date' => 'required|date|before_or_equal:today',
            'undertaking_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'witness_name' => 'nullable|string|max:255',
            'witness_cnic' => 'nullable|string|max:15|regex:/^[0-9]{5}-[0-9]{7}-[0-9]$/',
            'witness_phone' => 'nullable|string|max:20',
            'terms_agreed' => 'nullable|boolean',
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
            'undertaking_type' => 'undertaking type',
            'undertaking_date' => 'undertaking date',
            'undertaking_document' => 'undertaking document',
            'witness_name' => 'witness name',
            'witness_cnic' => 'witness CNIC',
            'witness_phone' => 'witness phone',
            'terms_agreed' => 'terms agreement',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'candidate_id.required' => 'Please select a candidate.',
            'undertaking_type.required' => 'Please select an undertaking type.',
            'undertaking_date.required' => 'Please enter the undertaking date.',
            'undertaking_date.before_or_equal' => 'Undertaking date cannot be in the future.',
            'undertaking_document.max' => 'Document file must not exceed 5MB.',
            'witness_cnic.regex' => 'Witness CNIC must be in format: 12345-1234567-1',
        ];
    }
}
