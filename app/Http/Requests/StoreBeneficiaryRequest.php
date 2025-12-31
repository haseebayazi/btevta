<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBeneficiaryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\RemittanceBeneficiary::class);
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
            'bank_name' => 'nullable|string|max:255',
            'account_title' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:50',
            'iban' => 'nullable|string|max:34',
            'branch_code' => 'nullable|string|max:20',
            'mobile_wallet_provider' => 'nullable|string|max:100',
            'mobile_wallet_number' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'is_primary' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
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
            'bank_name' => 'bank name',
            'account_title' => 'account title',
            'account_number' => 'account number',
            'iban' => 'IBAN',
            'branch_code' => 'branch code',
            'mobile_wallet_provider' => 'mobile wallet provider',
            'mobile_wallet_number' => 'mobile wallet number',
            'is_primary' => 'primary status',
            'is_active' => 'active status',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'candidate_id.required' => 'Please select a candidate.',
            'name.required' => 'Please enter the beneficiary name.',
            'relationship.required' => 'Please select the relationship.',
            'phone.required' => 'Please enter a phone number.',
            'cnic.regex' => 'CNIC must be in format: 12345-1234567-1',
        ];
    }
}
