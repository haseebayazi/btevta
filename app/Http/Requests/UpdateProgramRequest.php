<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProgramRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('program'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $programId = $this->route('program')->id;

        return [
            'name' => 'required|string|max:255|unique:programs,name,' . $programId,
            'description' => 'nullable|string|max:1000',
            'duration_weeks' => 'required|integer|min:1|max:104',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'duration_weeks' => 'program duration',
            'is_active' => 'active status',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please enter the program name.',
            'name.unique' => 'A program with this name already exists.',
            'duration_weeks.required' => 'Please enter the program duration.',
            'duration_weeks.min' => 'Program duration must be at least 1 week.',
            'duration_weeks.max' => 'Program duration cannot exceed 104 weeks (2 years).',
        ];
    }
}
