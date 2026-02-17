<?php

namespace App\Http\Requests;

use App\Enums\VisaStageResult;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VisaStageUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => 'required|in:schedule,result,evidence',

            // Scheduling
            'appointment_date' => 'required_if:action,schedule|nullable|date|after_or_equal:today',
            'appointment_time' => 'required_if:action,schedule|nullable|date_format:H:i',
            'center' => 'required_if:action,schedule|nullable|string|max:200',

            // Result
            'result_status' => [
                'required_if:action,result',
                'nullable',
                Rule::in(array_column(VisaStageResult::cases(), 'value')),
            ],
            'notes' => 'nullable|string|max:2000',

            // Evidence (required for pass/fail results)
            'evidence' => [
                Rule::requiredIf(fn() =>
                    $this->action === 'result' &&
                    in_array($this->result_status, ['pass', 'fail'])
                ),
                'nullable',
                'file',
                'max:10240',
                'mimes:pdf,jpg,jpeg,png',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'evidence.required' => 'Evidence is required for pass or fail results.',
            'appointment_date.after_or_equal' => 'Appointment date cannot be in the past.',
        ];
    }
}
