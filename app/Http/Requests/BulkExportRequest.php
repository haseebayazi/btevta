<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkExportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('export', \App\Models\Candidate::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'candidate_ids' => 'nullable|array',
            'candidate_ids.*' => 'integer|exists:candidates,id',
            'format' => 'required|in:xlsx,csv,pdf',
            'columns' => 'nullable|array',
            'columns.*' => 'string|in:name,cnic,passport_number,phone,email,status,training_status,campus,batch,trade,oep,registration_date,departure_date',
            'include_documents' => 'nullable|boolean',
            'include_visa_details' => 'nullable|boolean',
            'include_training_records' => 'nullable|boolean',
            'include_remittances' => 'nullable|boolean',
            'filters' => 'nullable|array',
            'filters.status' => 'nullable|array',
            'filters.campus_id' => 'nullable|integer|exists:campuses,id',
            'filters.batch_id' => 'nullable|integer|exists:batches,id',
            'filters.trade_id' => 'nullable|integer|exists:trades,id',
            'filters.date_from' => 'nullable|date',
            'filters.date_to' => 'nullable|date|after_or_equal:filters.date_from',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'candidate_ids' => 'candidates',
            'candidate_ids.*' => 'candidate ID',
            'columns.*' => 'column',
            'include_documents' => 'include documents option',
            'include_visa_details' => 'include visa details option',
            'include_training_records' => 'include training records option',
            'include_remittances' => 'include remittances option',
            'filters.status' => 'status filter',
            'filters.campus_id' => 'campus filter',
            'filters.batch_id' => 'batch filter',
            'filters.trade_id' => 'trade filter',
            'filters.date_from' => 'from date',
            'filters.date_to' => 'to date',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'format.required' => 'Please select an export format.',
            'format.in' => 'Export format must be xlsx, csv, or pdf.',
            'filters.date_to.after_or_equal' => 'End date must be on or after the start date.',
        ];
    }
}
