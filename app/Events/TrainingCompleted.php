<?php

namespace App\Events;

use App\Models\Training;
use App\Models\Candidate;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TrainingCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Training $training,
        public ?Candidate $candidate = null
    ) {
        $this->candidate = $candidate ?? $training->candidate;
    }
}
