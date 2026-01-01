<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordTradeTestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('visaProcess'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'trade_test_date' => 'required|date|before_or_equal:today',
            'trade_test_location' => 'required|string|max:255',
            'trade_test_result' => 'required|in:pass,fail,pending',
            'trade_test_score' => 'nullable|numeric|min:0|max:100',
            'trade_test_certificate_number' => 'nullable|string|max:50',
            'trade_test_certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'remarks' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'trade_test_date' => 'trade test date',
            'trade_test_location' => 'trade test location',
            'trade_test_result' => 'trade test result',
            'trade_test_score' => 'trade test score',
            'trade_test_certificate_number' => 'certificate number',
            'trade_test_certificate' => 'certificate file',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'trade_test_date.before_or_equal' => 'Trade test date cannot be in the future.',
            'trade_test_result.required' => 'Please select the trade test result.',
            'trade_test_certificate.max' => 'Certificate file must not exceed 5MB.',
        ];
    }
}
