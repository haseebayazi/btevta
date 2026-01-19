<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreImplementingPartnerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\ImplementingPartner::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:implementing_partners,name',
            'contact_person' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'required|string|max:20|regex:/^[\d\s\+\-\(\)]+$/',
            'address' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'contact_person' => 'contact person name',
            'contact_email' => 'contact email',
            'contact_phone' => 'contact phone',
            'is_active' => 'active status',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please enter the implementing partner name.',
            'name.unique' => 'An implementing partner with this name already exists.',
            'contact_person.required' => 'Please enter the contact person name.',
            'contact_email.required' => 'Please enter the contact email.',
            'contact_email.email' => 'Please enter a valid email address.',
            'contact_phone.required' => 'Please enter the contact phone number.',
            'contact_phone.regex' => 'Please enter a valid phone number.',
        ];
    }
}
