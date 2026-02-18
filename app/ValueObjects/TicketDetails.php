<?php

namespace App\ValueObjects;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;

class TicketDetails implements Arrayable
{
    public function __construct(
        public ?string $airline = null,
        public ?string $flightNumber = null,
        public ?string $departureDate = null,
        public ?string $departureTime = null,
        public ?string $arrivalDate = null,
        public ?string $arrivalTime = null,
        public ?string $departureAirport = null,
        public ?string $arrivalAirport = null,
        public ?string $ticketNumber = null,
        public ?string $ticketPath = null,
        public ?string $pnr = null,
    ) {}

    public static function fromArray(?array $data): self
    {
        if (! $data) {
            return new self();
        }

        return new self(
            airline: $data['airline'] ?? null,
            flightNumber: $data['flight_number'] ?? null,
            departureDate: $data['departure_date'] ?? null,
            departureTime: $data['departure_time'] ?? null,
            arrivalDate: $data['arrival_date'] ?? null,
            arrivalTime: $data['arrival_time'] ?? null,
            departureAirport: $data['departure_airport'] ?? null,
            arrivalAirport: $data['arrival_airport'] ?? null,
            ticketNumber: $data['ticket_number'] ?? null,
            ticketPath: $data['ticket_path'] ?? null,
            pnr: $data['pnr'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'airline' => $this->airline,
            'flight_number' => $this->flightNumber,
            'departure_date' => $this->departureDate,
            'departure_time' => $this->departureTime,
            'arrival_date' => $this->arrivalDate,
            'arrival_time' => $this->arrivalTime,
            'departure_airport' => $this->departureAirport,
            'arrival_airport' => $this->arrivalAirport,
            'ticket_number' => $this->ticketNumber,
            'ticket_path' => $this->ticketPath,
            'pnr' => $this->pnr,
        ], fn($v) => $v !== null);
    }

    public function isComplete(): bool
    {
        return $this->airline && $this->flightNumber && $this->departureDate;
    }

    public function getDepartureDateTime(): ?Carbon
    {
        if (! $this->departureDate) {
            return null;
        }

        $dateTime = $this->departureDate;
        if ($this->departureTime) {
            $dateTime .= ' '.$this->departureTime;
        }

        return Carbon::parse($dateTime);
    }
}
