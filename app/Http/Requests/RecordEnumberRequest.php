<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordEnumberRequest extends FormRequest
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
            'enumber' => 'required|string|max:50',
            'enumber_issue_date' => 'required|date|before_or_equal:today',
            'enumber_expiry_date' => 'nullable|date|after:enumber_issue_date',
            'sponsor_id' => 'nullable|string|max:50',
            'sponsor_name' => 'nullable|string|max:255',
            'visa_type' => 'nullable|string|max:100',
            'remarks' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'enumber' => 'E-Number',
            'enumber_issue_date' => 'E-Number issue date',
            'enumber_expiry_date' => 'E-Number expiry date',
            'sponsor_id' => 'sponsor ID',
            'sponsor_name' => 'sponsor name',
            'visa_type' => 'visa type',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'enumber.required' => 'Please enter the E-Number.',
            'enumber_issue_date.required' => 'Please enter the E-Number issue date.',
            'enumber_issue_date.before_or_equal' => 'E-Number issue date cannot be in the future.',
            'enumber_expiry_date.after' => 'E-Number expiry date must be after the issue date.',
        ];
    }
}
