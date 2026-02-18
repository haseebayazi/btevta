<?php

namespace App\Events;

use App\Models\Candidate;
use App\Models\Departure;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CandidateDeparted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Candidate $candidate,
        public readonly Departure $departure,
    ) {}
}
