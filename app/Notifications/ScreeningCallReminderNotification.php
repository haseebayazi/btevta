<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Candidate;

class ScreeningCallReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Candidate $candidate;
    protected int $daysPending;

    public function __construct(Candidate $candidate, int $daysPending)
    {
        $this->candidate = $candidate;
        $this->daysPending = $daysPending;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $severity = $this->daysPending >= 7 ? 'OVERDUE' : 'REMINDER';

        $message = (new MailMessage)
            ->subject("[{$severity}] Screening Call Pending - {$this->candidate->name}")
            ->greeting("Screening Call Reminder")
            ->line("A candidate is pending screening call and requires your attention:");

        // Candidate details
        $message->line("**Candidate:** {$this->candidate->name}");

        if ($this->candidate->btevta_id) {
            $message->line("**ID:** {$this->candidate->btevta_id}");
        }

        if ($this->candidate->application_id) {
            $message->line("**Application ID:** {$this->candidate->application_id}");
        }

        $message->line("**Contact:** {$this->candidate->phone}")
                ->line("**Days Pending:** {$this->daysPending} days");

        // Campus and OEP info
        if ($this->candidate->campus) {
            $message->line("**Campus:** {$this->candidate->campus->name}");
        }

        if ($this->candidate->oep) {
            $message->line("**OEP:** {$this->candidate->oep->name}");
        }

        // Urgency message
        $message->line("");

        if ($this->daysPending >= 14) {
            $message->line("🚨 **CRITICAL:** This screening is severely overdue! Immediate action required.");
        } elseif ($this->daysPending >= 7) {
            $message->line("⚠️ **OVERDUE:** This screening is past the target timeline. Please complete as soon as possible.");
        } else {
            $message->line("ℹ️ **REMINDER:** Please schedule and complete this screening call.");
        }

        // Action button
        if (function_exists('route')) {
            $message->action('View Candidate', route('candidates.show', $this->candidate));
        }

        // Next steps
        $message->line("")
                ->line("**Required Actions:**")
                ->line("1. Review candidate application")
                ->line("2. Schedule screening call")
                ->line("3. Conduct screening interview")
                ->line("4. Record screening outcome");

        // Screening criteria reminder
        $message->line("")
                ->line("**Screening Criteria:**")
                ->line("✓ Verify candidate identity and contact information")
                ->line("✓ Assess candidate's motivation and expectations")
                ->line("✓ Confirm eligibility for selected trade")
                ->line("✓ Check availability for training schedule");

        return $message;
    }

    public function toArray($notifiable)
    {
        return [
            'candidate_id' => $this->candidate->id,
            'candidate_name' => $this->candidate->name,
            'candidate_btevta_id' => $this->candidate->btevta_id,
            'days_pending' => $this->daysPending,
            'campus' => $this->candidate->campus?->name,
            'oep' => $this->candidate->oep?->name,
            'severity' => $this->daysPending >= 7 ? 'overdue' : 'pending',
            'type' => 'screening_call_reminder',
        ];
    }
}
