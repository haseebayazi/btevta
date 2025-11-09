<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTrainingClassRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'class_name' => 'required|string|max:255',
            'class_code' => 'nullable|string|max:50|unique:training_classes,class_code',
            'campus_id' => 'nullable|exists:campuses,id',
            'trade_id' => 'nullable|exists:trades,id',
            'instructor_id' => 'nullable|exists:instructors,id',
            'batch_id' => 'nullable|exists:batches,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'max_capacity' => 'required|integer|min:1|max:100',
            'room_number' => 'nullable|string|max:50',
            'schedule' => 'nullable|string',
            'status' => 'required|in:scheduled,ongoing,completed,cancelled',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'campus_id' => 'campus',
            'trade_id' => 'trade',
            'instructor_id' => 'instructor',
            'batch_id' => 'batch',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'class_name.required' => 'Please enter a class name.',
            'end_date.after_or_equal' => 'The end date must be after or equal to the start date.',
            'max_capacity.min' => 'The class must have at least 1 student capacity.',
            'max_capacity.max' => 'The class cannot exceed 100 students capacity.',
        ];
    }
}
