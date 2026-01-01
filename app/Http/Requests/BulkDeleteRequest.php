<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkDeleteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->isSuperAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'ids' => 'required|array|min:1|max:100',
            'ids.*' => 'required|integer',
            'model_type' => 'required|in:candidate,batch,campus,complaint,remittance,visa_process',
            'force_delete' => 'nullable|boolean',
            'confirmation' => 'required|string|in:DELETE,CONFIRM',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'ids' => 'records',
            'ids.*' => 'record ID',
            'model_type' => 'record type',
            'force_delete' => 'permanent delete option',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'ids.required' => 'Please select at least one record to delete.',
            'ids.min' => 'Please select at least one record to delete.',
            'ids.max' => 'Cannot delete more than 100 records at once.',
            'model_type.required' => 'Please specify the type of records to delete.',
            'model_type.in' => 'Invalid record type specified.',
            'confirmation.required' => 'Please confirm the bulk delete action.',
            'confirmation.in' => 'Please type DELETE or CONFIRM to proceed.',
        ];
    }
}
