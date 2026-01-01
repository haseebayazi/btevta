<?php

namespace App\Http\Requests;

use App\Enums\VisaStage;
use Illuminate\Foundation\Http\FormRequest;

class ScheduleInterviewRequest extends FormRequest
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
            'interview_date' => 'required|date|after_or_equal:today',
            'interview_time' => 'required|date_format:H:i',
            'interview_location' => 'required|string|max:255',
            'interviewer_name' => 'nullable|string|max:255',
            'interview_type' => 'nullable|in:in_person,video,phone',
            'remarks' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'interview_date' => 'interview date',
            'interview_time' => 'interview time',
            'interview_location' => 'interview location',
            'interviewer_name' => 'interviewer name',
            'interview_type' => 'interview type',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'interview_date.after_or_equal' => 'Interview date must be today or in the future.',
            'interview_time.date_format' => 'Please enter a valid time in HH:MM format.',
            'interview_location.required' => 'Please specify the interview location.',
        ];
    }
}
