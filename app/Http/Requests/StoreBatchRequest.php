<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBatchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Batch::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'batch_code' => ['nullable', 'string', 'max:50', 'unique:batches,batch_code'],
            'campus_id' => ['required', 'exists:campuses,id'],
            'trade_id' => ['required', 'exists:trades,id'],
            'oep_id' => ['nullable', 'exists:oeps,id'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'capacity' => ['required', 'integer', 'min:1', 'max:500'],
            'status' => ['required', Rule::in(['planned', 'active', 'completed', 'cancelled'])],
            'description' => ['nullable', 'string', 'max:1000'],
            'trainer_id' => ['nullable', 'exists:users,id'],
            'coordinator_id' => ['nullable', 'exists:users,id'],
            'district' => ['nullable', 'string', 'max:255'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'intake_period' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'batch_code.unique' => 'A batch with this code already exists.',
            'end_date.after' => 'End date must be after start date.',
            'start_date.after_or_equal' => 'Start date cannot be in the past.',
            'campus_id.exists' => 'The selected campus is invalid.',
            'trade_id.exists' => 'The selected trade is invalid.',
            'capacity.min' => 'Capacity must be at least 1.',
            'capacity.max' => 'Capacity cannot exceed 500.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'oep_id' => 'OEP',
            'start_date' => 'start date',
            'end_date' => 'end date',
        ];
    }
}
