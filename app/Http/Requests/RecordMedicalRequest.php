<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordMedicalRequest extends FormRequest
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
            'medical_date' => 'required|date|before_or_equal:today',
            'gamca_id' => 'nullable|string|max:50',
            'medical_center' => 'required|string|max:255',
            'medical_result' => 'required|in:fit,unfit,pending,referred',
            'medical_expiry_date' => 'nullable|date|after:medical_date',
            'medical_report' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'medical_issues' => 'nullable|string|max:1000',
            'remarks' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'medical_date' => 'medical examination date',
            'gamca_id' => 'GAMCA ID',
            'medical_center' => 'medical center',
            'medical_result' => 'medical result',
            'medical_expiry_date' => 'medical expiry date',
            'medical_report' => 'medical report',
            'medical_issues' => 'medical issues',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'medical_date.before_or_equal' => 'Medical examination date cannot be in the future.',
            'medical_center.required' => 'Please specify the medical center.',
            'medical_result.required' => 'Please select the medical examination result.',
            'medical_expiry_date.after' => 'Medical expiry date must be after the examination date.',
            'medical_report.max' => 'Medical report file must not exceed 5MB.',
        ];
    }
}
