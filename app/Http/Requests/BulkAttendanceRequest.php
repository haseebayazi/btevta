<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkAttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('bulkRecord', \App\Models\TrainingAttendance::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'batch_id' => 'required|integer|exists:batches,id',
            'date' => 'required|date|before_or_equal:today',
            'attendance' => 'required|array',
            'attendance.*.candidate_id' => 'required|integer|exists:candidates,id',
            'attendance.*.status' => 'required|in:present,absent,late,leave',
            'attendance.*.leave_type' => 'nullable|in:sick,casual,emergency',
            'attendance.*.remarks' => 'nullable|string|max:255',
            'session_type' => 'nullable|in:theory,practical,assessment',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'batch_id' => 'batch',
            'attendance.*.candidate_id' => 'candidate',
            'attendance.*.status' => 'attendance status',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'batch_id.required' => 'Please select a batch.',
            'date.before_or_equal' => 'Attendance cannot be recorded for future dates.',
            'attendance.required' => 'Please provide attendance data.',
            'attendance.*.status.required' => 'Attendance status is required for each candidate.',
        ];
    }
}
