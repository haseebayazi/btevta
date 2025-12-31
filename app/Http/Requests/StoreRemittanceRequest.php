<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRemittanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Remittance::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'candidate_id' => 'required|integer|exists:candidates,id',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|max:10',
            'amount_pkr' => 'nullable|numeric|min:0',
            'exchange_rate' => 'nullable|numeric|min:0',
            'remittance_date' => 'required|date|before_or_equal:today',
            'channel' => 'required|in:bank,money_transfer,mobile_wallet,other',
            'channel_name' => 'nullable|string|max:255',
            'transaction_reference' => 'nullable|string|max:100',
            'sender_name' => 'nullable|string|max:255',
            'sender_country' => 'nullable|string|max:100',
            'beneficiary_id' => 'nullable|integer|exists:remittance_beneficiaries,id',
            'receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
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
            'amount_pkr' => 'amount in PKR',
            'exchange_rate' => 'exchange rate',
            'remittance_date' => 'remittance date',
            'channel_name' => 'channel name',
            'transaction_reference' => 'transaction reference',
            'sender_name' => 'sender name',
            'sender_country' => 'sender country',
            'beneficiary_id' => 'beneficiary',
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
            'amount.required' => 'Please enter the remittance amount.',
            'amount.min' => 'Amount must be a positive value.',
            'currency.required' => 'Please specify the currency.',
            'remittance_date.required' => 'Please enter the remittance date.',
            'remittance_date.before_or_equal' => 'Remittance date cannot be in the future.',
            'channel.required' => 'Please select the remittance channel.',
            'receipt.max' => 'Receipt file must not exceed 5MB.',
        ];
    }
}
