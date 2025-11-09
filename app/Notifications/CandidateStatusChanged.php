<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Candidate;

class CandidateStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    protected $candidate;
    protected $oldStatus;
    protected $newStatus;

    public function __construct(Candidate $candidate, $oldStatus, $newStatus)
    {
        $this->candidate = $candidate;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Candidate Status Updated')
            ->line('Candidate ' . $this->candidate->name . ' status changed')
            ->line('From: ' . $this->oldStatus . ' → To: ' . $this->newStatus)
            ->action('View Profile', route('candidates.show', $this->candidate))
            ->line('Thank you for using BTEVTA System');
    }

    public function toArray($notifiable)
    {
        return [
            'candidate_id' => $this->candidate->id,
            'candidate_name' => $this->candidate->name,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'type' => 'candidate_status_changed'
        ];
    }
}
