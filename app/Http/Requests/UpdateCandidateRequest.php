<?php

namespace App\Http\Requests;

use App\Enums\CandidateStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCandidateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('candidate'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $candidateId = $this->route('candidate')->id ?? $this->route('candidate');

        return [
            'btevta_id' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('candidates', 'btevta_id')->ignore($candidateId),
            ],
            'name' => 'sometimes|required|string|max:255',
            'father_name' => 'sometimes|required|string|max:255',
            'cnic' => [
                'sometimes',
                'required',
                'string',
                'size:13',
                'regex:/^[0-9]{13}$/',
                Rule::unique('candidates', 'cnic')->ignore($candidateId),
            ],
            'phone' => 'sometimes|required|string|regex:/^03[0-9]{9}$/',
            'email' => 'nullable|email|max:255',
            'date_of_birth' => 'sometimes|required|date|before:-18 years',
            'gender' => 'sometimes|required|in:male,female',
            'district' => 'sometimes|required|string|max:100',
            'tehsil' => 'nullable|string|max:100',
            'province' => 'sometimes|required|string|max:100',
            'address' => 'sometimes|required|string|max:500',
            'emergency_contact' => 'nullable|string|regex:/^03[0-9]{9}$/',
            'blood_group' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'marital_status' => 'nullable|in:single,married,divorced,widowed',
            'qualification' => 'nullable|string|max:100',
            'experience_years' => 'nullable|integer|min:0|max:50',
            'passport_number' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('candidates', 'passport_number')->ignore($candidateId),
            ],
            'passport_expiry' => 'nullable|date|after:today',
            'campus_id' => 'nullable|exists:campuses,id',
            'trade_id' => 'sometimes|required|exists:trades,id',
            'oep_id' => 'nullable|exists:oeps,id',
            'batch_id' => 'nullable|exists:batches,id',
            'status' => ['nullable', Rule::in(array_column(CandidateStatus::cases(), 'value'))],
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'remarks' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'btevta_id' => 'TheLeap ID',
            'cnic' => 'CNIC',
            'date_of_birth' => 'date of birth',
            'trade_id' => 'trade',
            'campus_id' => 'campus',
            'oep_id' => 'OEP',
            'batch_id' => 'batch',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'cnic.size' => 'CNIC must be exactly 13 digits.',
            'cnic.regex' => 'CNIC must contain only numbers.',
            'phone.regex' => 'Phone number must be a valid Pakistani mobile number (03XXXXXXXXX).',
            'date_of_birth.before' => 'Candidate must be at least 18 years old.',
            'passport_expiry.after' => 'Passport must not be expired.',
        ];
    }
}
