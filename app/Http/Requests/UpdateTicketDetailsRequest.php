<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketDetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'airline' => 'required|string|max:100',
            'flight_number' => 'required|string|max:20',
            'departure_date' => 'required|date|after_or_equal:today',
            'departure_time' => 'required|date_format:H:i',
            'arrival_date' => 'required|date|after_or_equal:departure_date',
            'arrival_time' => 'required|date_format:H:i',
            'departure_airport' => 'required|string|max:100',
            'arrival_airport' => 'required|string|max:100',
            'ticket_number' => 'nullable|string|max:50',
            'pnr' => 'nullable|string|max:20',
            'ticket_file' => 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png',
        ];
    }

    public function messages(): array
    {
        return [
            'departure_date.after_or_equal' => 'Departure date must be today or in the future.',
            'arrival_date.after_or_equal' => 'Arrival date must be on or after the departure date.',
            'departure_time.date_format' => 'Departure time must be in HH:MM format.',
            'arrival_time.date_format' => 'Arrival time must be in HH:MM format.',
        ];
    }
}
