<?php

namespace App\Events;

use App\Models\Candidate;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CandidateStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Candidate $candidate;
    public string $oldStatus;
    public string $newStatus;
    public ?int $updatedBy;

    public function __construct(Candidate $candidate, string $oldStatus, string $newStatus, ?int $updatedBy = null)
    {
        $this->candidate = $candidate;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        $this->updatedBy = $updatedBy ?? auth()->id();
    }

    public function broadcastOn(): array
    {
        $channels = [
            new Channel('candidates'),
            new PrivateChannel('campus.' . $this->candidate->campus_id),
        ];

        // Also broadcast to admin channel
        $channels[] = new PrivateChannel('admin');

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'candidate.status.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'candidate_id' => $this->candidate->id,
            'candidate_name' => $this->candidate->name,
            'btevta_id' => $this->candidate->btevta_id,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'campus_id' => $this->candidate->campus_id,
            'campus_name' => $this->candidate->campus?->name,
            'updated_at' => now()->toIso8601String(),
            'message' => "{$this->candidate->name} status changed from " .
                        ucfirst(str_replace('_', ' ', $this->oldStatus)) . " to " .
                        ucfirst(str_replace('_', ' ', $this->newStatus)),
        ];
    }
}
