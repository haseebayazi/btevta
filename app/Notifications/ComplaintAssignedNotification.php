<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Complaint;

class ComplaintAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $complaint;

    public function __construct(Complaint $complaint)
    {
        $this->complaint = $complaint;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('New Complaint Assigned - ' . $this->complaint->title)
            ->line('A new complaint has been assigned to you')
            ->line('Title: ' . $this->complaint->title)
            ->line('Candidate: ' . $this->complaint->candidate->name)
            ->line('Priority: ' . strtoupper($this->complaint->priority))
            ->action('View Complaint', route('complaints.show', $this->complaint))
            ->line('Please address this complaint within the SLA');
    }

    public function toArray($notifiable)
    {
        return [
            'complaint_id' => $this->complaint->id,
            'title' => $this->complaint->title,
            'priority' => $this->complaint->priority,
            'type' => 'complaint_assigned'
        ];
    }
}

