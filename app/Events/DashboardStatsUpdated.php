<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DashboardStatsUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $stats;
    public ?int $campusId;

    public function __construct(array $stats, ?int $campusId = null)
    {
        $this->stats = $stats;
        $this->campusId = $campusId;
    }

    public function broadcastOn(): array
    {
        if ($this->campusId) {
            return [new Channel('dashboard.campus.' . $this->campusId)];
        }
        return [new Channel('dashboard')];
    }

    public function broadcastAs(): string
    {
        return 'stats.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'stats' => $this->stats,
            'campus_id' => $this->campusId,
            'updated_at' => now()->toIso8601String(),
        ];
    }
}
