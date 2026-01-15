<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBatchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $batch = $this->route('batch');
        return $this->user()->can('update', $batch);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $batchId = $this->route('batch')->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'batch_code' => ['nullable', 'string', 'max:50', Rule::unique('batches')->ignore($batchId)],
            'campus_id' => ['required', 'exists:campuses,id'],
            'trade_id' => ['required', 'exists:trades,id'],
            'oep_id' => ['nullable', 'exists:oeps,id'],
            'start_date' => ['required', 'date'],
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
            'campus_id.exists' => 'The selected campus is invalid.',
            'trade_id.exists' => 'The selected trade is invalid.',
            'capacity.min' => 'Capacity must be at least 1.',
            'capacity.max' => 'Capacity cannot exceed 500.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $batch = $this->route('batch');
            $newCapacity = $this->input('capacity');
            $currentEnrollment = $batch->candidates()->count();

            // Cannot reduce capacity below current enrollment
            if ($newCapacity < $currentEnrollment) {
                $validator->errors()->add(
                    'capacity',
                    "Cannot reduce capacity below current enrollment ({$currentEnrollment} candidates)."
                );
            }
        });
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
