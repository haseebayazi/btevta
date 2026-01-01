<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRemittanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('remittance'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'amount' => 'sometimes|required|numeric|min:0',
            'currency' => 'sometimes|required|string|max:10',
            'amount_pkr' => 'nullable|numeric|min:0',
            'exchange_rate' => 'nullable|numeric|min:0',
            'remittance_date' => 'sometimes|required|date|before_or_equal:today',
            'channel' => 'sometimes|required|in:bank,money_transfer,mobile_wallet,other',
            'channel_name' => 'nullable|string|max:255',
            'transaction_reference' => 'nullable|string|max:100',
            'sender_name' => 'nullable|string|max:255',
            'sender_country' => 'nullable|string|max:100',
            'beneficiary_id' => 'nullable|integer|exists:remittance_beneficiaries,id',
            'receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'is_verified' => 'nullable|boolean',
            'verified_by' => 'nullable|integer|exists:users,id',
            'verification_date' => 'nullable|date',
            'remarks' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'amount_pkr' => 'amount in PKR',
            'exchange_rate' => 'exchange rate',
            'remittance_date' => 'remittance date',
            'channel_name' => 'channel name',
            'transaction_reference' => 'transaction reference',
            'sender_name' => 'sender name',
            'sender_country' => 'sender country',
            'beneficiary_id' => 'beneficiary',
            'is_verified' => 'verification status',
            'verified_by' => 'verified by',
            'verification_date' => 'verification date',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'amount.min' => 'Amount must be a positive value.',
            'remittance_date.before_or_equal' => 'Remittance date cannot be in the future.',
            'receipt.max' => 'Receipt file must not exceed 5MB.',
        ];
    }
}
