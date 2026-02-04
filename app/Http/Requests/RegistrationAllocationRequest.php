<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistrationAllocationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Allocation
            'campus_id' => 'required|exists:campuses,id',
            'program_id' => 'required|exists:programs,id',
            'trade_id' => 'required|exists:trades,id',
            'oep_id' => 'nullable|exists:oeps,id',
            'implementing_partner_id' => 'nullable|exists:implementing_partners,id',

            // Course Assignment
            'course_id' => 'required|exists:courses,id',
            'course_start_date' => 'required|date|after_or_equal:today',
            'course_end_date' => 'required|date|after:course_start_date',

            // Next of Kin (enhanced)
            'nok_name' => 'required|string|max:100',
            'nok_relationship' => 'required|string|max:50',
            'nok_cnic' => 'required|digits:13',
            'nok_phone' => 'required|string|max:20',
            'nok_address' => 'nullable|string|max:500',
            'nok_payment_method_id' => 'required|exists:payment_methods,id',
            'nok_account_number' => 'required|string|max:50',
            'nok_bank_name' => 'nullable|required_if:nok_payment_method_requires_bank,true|string|max:100',
            'nok_id_card' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'campus_id.required' => 'Please select a campus.',
            'program_id.required' => 'Please select a program.',
            'trade_id.required' => 'Please select a trade.',
            'course_id.required' => 'Please select a course.',
            'course_start_date.required' => 'Course start date is required.',
            'course_start_date.after_or_equal' => 'Course start date must be today or later.',
            'course_end_date.required' => 'Course end date is required.',
            'course_end_date.after' => 'Course end date must be after start date.',
            'nok_name.required' => 'Next of kin name is required.',
            'nok_relationship.required' => 'Next of kin relationship is required.',
            'nok_cnic.required' => 'Next of kin CNIC is required.',
            'nok_cnic.digits' => 'CNIC must be exactly 13 digits.',
            'nok_phone.required' => 'Next of kin phone number is required.',
            'nok_payment_method_id.required' => 'Payment method is required.',
            'nok_account_number.required' => 'Account number is required.',
            'nok_bank_name.required_if' => 'Bank name is required when payment method is Bank Account.',
            'nok_id_card.max' => 'ID card file must not exceed 5MB.',
            'nok_id_card.mimes' => 'ID card must be a PDF or image file (jpg, jpeg, png).',
        ];
    }

    /**
     * Get custom attribute names.
     */
    public function attributes(): array
    {
        return [
            'campus_id' => 'campus',
            'program_id' => 'program',
            'trade_id' => 'trade',
            'oep_id' => 'OEP',
            'implementing_partner_id' => 'implementing partner',
            'course_id' => 'course',
            'course_start_date' => 'course start date',
            'course_end_date' => 'course end date',
            'nok_name' => 'next of kin name',
            'nok_relationship' => 'next of kin relationship',
            'nok_cnic' => 'next of kin CNIC',
            'nok_phone' => 'next of kin phone',
            'nok_address' => 'next of kin address',
            'nok_payment_method_id' => 'payment method',
            'nok_account_number' => 'account number',
            'nok_bank_name' => 'bank name',
            'nok_id_card' => 'next of kin ID card',
        ];
    }
}
