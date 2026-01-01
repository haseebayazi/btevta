<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordBiometricsRequest extends FormRequest
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
            'biometrics_date' => 'required|date|before_or_equal:today',
            'biometrics_location' => 'required|string|max:255',
            'etimad_number' => 'nullable|string|max:50',
            'biometrics_status' => 'required|in:completed,pending,failed,rescheduled',
            'biometrics_receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'remarks' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'biometrics_date' => 'biometrics date',
            'biometrics_location' => 'biometrics location',
            'etimad_number' => 'Etimad number',
            'biometrics_status' => 'biometrics status',
            'biometrics_receipt' => 'biometrics receipt',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'biometrics_date.before_or_equal' => 'Biometrics date cannot be in the future.',
            'biometrics_location.required' => 'Please specify the biometrics location.',
            'biometrics_status.required' => 'Please select the biometrics status.',
            'biometrics_receipt.max' => 'Receipt file must not exceed 5MB.',
        ];
    }
}
