<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNextOfKinRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\NextOfKin::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'candidate_id' => 'required|integer|exists:candidates,id',
            'name' => 'required|string|max:255',
            'relationship' => 'required|in:father,mother,spouse,brother,sister,son,daughter,guardian,other',
            'cnic' => 'nullable|string|max:15|regex:/^[0-9]{5}-[0-9]{7}-[0-9]$/',
            'phone' => 'required|string|max:20',
            'alternate_phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'is_emergency_contact' => 'nullable|boolean',
            'is_beneficiary' => 'nullable|boolean',
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
            'cnic' => 'CNIC',
            'alternate_phone' => 'alternate phone number',
            'is_emergency_contact' => 'emergency contact status',
            'is_beneficiary' => 'beneficiary status',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'candidate_id.required' => 'Please select a candidate.',
            'name.required' => 'Please enter the next of kin name.',
            'relationship.required' => 'Please select the relationship.',
            'phone.required' => 'Please enter a phone number.',
            'cnic.regex' => 'CNIC must be in format: 12345-1234567-1',
        ];
    }
}
