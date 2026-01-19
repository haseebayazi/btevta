<?php

namespace App\Http\Requests;

use App\Enums\TrainingType;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCourseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('course'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $courseId = $this->route('course')->id;
        $trainingTypes = implode(',', array_keys(TrainingType::toArray()));

        return [
            'name' => 'required|string|max:255|unique:courses,name,' . $courseId,
            'description' => 'nullable|string|max:1000',
            'duration_days' => 'required|integer|min:1|max:365',
            'training_type' => 'required|string|in:' . $trainingTypes,
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'duration_days' => 'course duration',
            'training_type' => 'training type',
            'is_active' => 'active status',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please enter the course name.',
            'name.unique' => 'A course with this name already exists.',
            'duration_days.required' => 'Please enter the course duration.',
            'duration_days.min' => 'Course duration must be at least 1 day.',
            'duration_days.max' => 'Course duration cannot exceed 365 days (1 year).',
            'training_type.required' => 'Please select the training type.',
            'training_type.in' => 'The selected training type is invalid.',
        ];
    }
}
