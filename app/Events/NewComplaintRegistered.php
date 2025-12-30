<?php

namespace App\Events;

use App\Models\Complaint;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewComplaintRegistered implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Complaint $complaint;

    public function __construct(Complaint $complaint)
    {
        $this->complaint = $complaint;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin'),
            new PrivateChannel('complaints'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'complaint.registered';
    }

    public function broadcastWith(): array
    {
        return [
            'complaint_id' => $this->complaint->id,
            'ticket_number' => $this->complaint->ticket_number,
            'candidate_name' => $this->complaint->candidate?->name,
            'category' => $this->complaint->category,
            'priority' => $this->complaint->priority,
            'status' => $this->complaint->status,
            'created_at' => $this->complaint->created_at->toIso8601String(),
            'message' => "New {$this->complaint->priority} priority complaint registered: {$this->complaint->ticket_number}",
        ];
    }
}
