<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordDepartureRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Departure::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'candidate_id' => 'required|integer|exists:candidates,id',
            'departure_date' => 'required|date',
            'flight_number' => 'required|string|max:20',
            'airline' => 'required|string|max:100',
            'destination_city' => 'required|string|max:100',
            'destination_country' => 'required|string|max:100',
            'departure_airport' => 'nullable|string|max:100',
            'arrival_airport' => 'nullable|string|max:100',
            'ticket_number' => 'nullable|string|max:50',
            'employer_name' => 'nullable|string|max:255',
            'employer_contact' => 'nullable|string|max:50',
            'local_agent' => 'nullable|string|max:255',
            'visa_number' => 'nullable|string|max:50',
            'work_permit_number' => 'nullable|string|max:50',
            'contract_duration_months' => 'nullable|integer|min:1|max:60',
            'monthly_salary' => 'nullable|numeric|min:0',
            'salary_currency' => 'nullable|string|max:10',
            'remarks' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'candidate_id' => 'candidate',
            'departure_date' => 'departure date',
            'flight_number' => 'flight number',
            'destination_city' => 'destination city',
            'destination_country' => 'destination country',
            'departure_airport' => 'departure airport',
            'arrival_airport' => 'arrival airport',
            'ticket_number' => 'ticket number',
            'employer_name' => 'employer name',
            'employer_contact' => 'employer contact',
            'visa_number' => 'visa number',
            'work_permit_number' => 'work permit number',
            'contract_duration_months' => 'contract duration',
            'monthly_salary' => 'monthly salary',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'candidate_id.required' => 'Please select a candidate.',
            'departure_date.required' => 'Please enter departure date.',
            'flight_number.required' => 'Please enter flight number.',
            'airline.required' => 'Please enter airline name.',
            'destination_city.required' => 'Please enter destination city.',
            'destination_country.required' => 'Please enter destination country.',
            'contract_duration_months.min' => 'Contract duration must be at least 1 month.',
            'monthly_salary.min' => 'Monthly salary cannot be negative.',
        ];
    }
}
