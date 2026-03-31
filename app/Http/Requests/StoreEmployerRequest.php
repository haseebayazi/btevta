<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Employer::class);
    }

    public function rules(): array
    {
        return [
            'permission_number' => 'nullable|string|max:50|unique:employers,permission_number',
            'permission_issue_date' => 'nullable|date',
            'permission_expiry_date' => 'nullable|date|after_or_equal:permission_issue_date',
            'permission_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'visa_issuing_company' => 'required|string|max:200',
            'visa_company_license' => 'nullable|string|max:100',
            'country_id' => 'required|exists:countries,id',
            'city' => 'nullable|string|max:100',
            'sector' => 'nullable|string|max:100',
            'trade' => 'nullable|string|max:100',
            'trade_id' => 'nullable|exists:trades,id',
            'basic_salary' => 'nullable|numeric|min:0|max:999999999',
            'salary_currency' => 'nullable|string|size:3',
            'food_by_company' => 'boolean',
            'transport_by_company' => 'boolean',
            'accommodation_by_company' => 'boolean',
            'other_conditions' => 'nullable|string|max:2000',
            'company_size' => 'nullable|in:small,medium,large,enterprise',
            'notes' => 'nullable|string|max:5000',
            'evidence' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'is_active' => 'boolean',
            // Default package fields
            'package_base_salary' => 'nullable|numeric|min:0',
            'package_currency' => 'nullable|string|size:3',
            'package_housing_allowance' => 'nullable|numeric|min:0',
            'package_food_allowance' => 'nullable|numeric|min:0',
            'package_transport_allowance' => 'nullable|numeric|min:0',
            'package_other_allowance' => 'nullable|numeric|min:0',
        ];
    }

    public function attributes(): array
    {
        return [
            'permission_number' => 'permission number',
            'permission_issue_date' => 'permission issue date',
            'permission_expiry_date' => 'permission expiry date',
            'visa_issuing_company' => 'company name',
            'visa_company_license' => 'visa company license',
            'country_id' => 'country',
            'trade_id' => 'trade',
            'basic_salary' => 'basic salary',
            'salary_currency' => 'currency',
            'food_by_company' => 'food provided',
            'transport_by_company' => 'transport provided',
            'accommodation_by_company' => 'accommodation provided',
            'other_conditions' => 'other conditions',
            'is_active' => 'active status',
        ];
    }

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
            'permission_expiry_date.after_or_equal' => 'Expiry date must be after or equal to the issue date.',
        ];
    }
}
