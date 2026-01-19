<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Employer::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'permission_number' => 'nullable|string|max:50|unique:employers,permission_number',
            'visa_issuing_company' => 'required|string|max:200',
            'country_id' => 'required|exists:countries,id',
            'sector' => 'nullable|string|max:100',
            'trade' => 'nullable|string|max:100',
            'basic_salary' => 'nullable|numeric|min:0|max:999999999',
            'salary_currency' => 'nullable|string|size:3',
            'food_by_company' => 'boolean',
            'transport_by_company' => 'boolean',
            'accommodation_by_company' => 'boolean',
            'other_conditions' => 'nullable|string|max:2000',
            'evidence' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'permission_number' => 'permission number',
            'visa_issuing_company' => 'company name',
            'country_id' => 'country',
            'basic_salary' => 'basic salary',
            'salary_currency' => 'currency',
            'food_by_company' => 'food provided',
            'transport_by_company' => 'transport provided',
            'accommodation_by_company' => 'accommodation provided',
            'other_conditions' => 'other conditions',
            'is_active' => 'active status',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'visa_issuing_company.required' => 'Please enter the employer/company name.',
            'country_id.required' => 'Please select a country.',
            'country_id.exists' => 'The selected country is invalid.',
            'permission_number.unique' => 'This permission number is already registered.',
            'basic_salary.min' => 'Salary cannot be negative.',
            'evidence.mimes' => 'Evidence must be a PDF or image file (JPG, JPEG, PNG).',
            'evidence.max' => 'Evidence file size cannot exceed 5MB.',
        ];
    }
}
