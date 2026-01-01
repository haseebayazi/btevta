<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\TrainingAttendance::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'candidate_id' => 'required|integer|exists:candidates,id',
            'batch_id' => 'nullable|integer|exists:batches,id',
            'date' => 'required|date|before_or_equal:today',
            'status' => 'required|in:present,absent,late,leave',
            'session_type' => 'nullable|in:theory,practical,assessment,makeup',
            'leave_type' => 'nullable|required_if:status,leave|in:sick,casual,emergency',
            'remarks' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'candidate_id' => 'candidate',
            'batch_id' => 'batch',
            'session_type' => 'session type',
            'leave_type' => 'leave type',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'candidate_id.required' => 'Please select a candidate.',
            'date.before_or_equal' => 'Attendance cannot be recorded for future dates.',
            'status.required' => 'Please select attendance status.',
            'leave_type.required_if' => 'Please select a leave type when marking as leave.',
        ];
    }
}
