<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Departure;

class SalaryVerificationReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Departure $departure;
    protected int $daysSinceDeparture;
    protected int $daysUntilDeadline;

    public function __construct(Departure $departure, int $daysSinceDeparture, int $daysUntilDeadline)
    {
        $this->departure = $departure;
        $this->daysSinceDeparture = $daysSinceDeparture;
        $this->daysUntilDeadline = $daysUntilDeadline;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $candidateName = $this->departure->candidate ? $this->departure->candidate->name : 'Unknown Candidate';

        // Determine urgency
        $severity = $this->daysUntilDeadline <= 14 ? 'URGENT' : 'REMINDER';

        $message = (new MailMessage)
            ->subject("[{$severity}] Salary Verification Required - {$candidateName}")
            ->greeting("Salary Verification Reminder")
            ->line("This is a reminder to verify and confirm the first salary receipt for a departed candidate:");

        // Candidate information
        $message->line("**Candidate:** {$candidateName}");

        if ($this->departure->candidate && $this->departure->candidate->btevta_id) {
            $message->line("**ID:** {$this->departure->candidate->btevta_id}");
        }

        $message->line("**Departure Date:** {$this->departure->departure_date->format('Y-m-d')}");
        $message->line("**Days Since Departure:** {$this->daysSinceDeparture} days");

        if ($this->daysUntilDeadline > 0) {
            $message->line("**Days Until 90-Day Deadline:** {$this->daysUntilDeadline} days");
        } else {
            $message->line("⚠️ **OVERDUE:** The 90-day compliance deadline has passed!");
        }

        // Urgency message
        if ($this->daysUntilDeadline <= 7) {
            $message->line("")
                ->line("⚠️ **CRITICAL:** Only {$this->daysUntilDeadline} days remaining! Salary confirmation must be completed immediately to meet the 90-day compliance deadline.");
        } elseif ($this->daysUntilDeadline <= 14) {
            $message->line("")
                ->line("⚠️ **URGENT:** {$this->daysUntilDeadline} days remaining. Please prioritize salary verification to ensure timely compliance.");
        } elseif ($this->daysUntilDeadline <= 30) {
            $message->line("")
                ->line("⚠️ **ACTION REQUIRED:** {$this->daysUntilDeadline} days remaining. Please verify and confirm salary receipt soon.");
        } else {
            $message->line("")
                ->line("ℹ️ **REMINDER:** Please verify and confirm first salary receipt at your earliest convenience.");
        }

        // What needs to be done
        $message->line("")
            ->line("**Required Information:**")
            ->line("• Salary amount")
            ->line("• Currency")
            ->line("• First salary receipt date")
            ->line("• Proof of payment (bank statement, pay slip, or transfer receipt)");

        // Action button
        $message->action('Confirm Salary', route('departures.show', $this->departure));

        // Next steps
        $message->line("")
            ->line("**Next Steps:**")
            ->line("1. Contact the candidate/employer to obtain salary proof")
            ->line("2. Verify the salary amount and payment date")
            ->line("3. Upload proof document in the system")
            ->line("4. Mark salary as confirmed");

        return $message;
    }

    public function toArray($notifiable)
    {
        return [
            'departure_id' => $this->departure->id,
            'candidate_name' => $this->departure->candidate?->name,
            'days_since_departure' => $this->daysSinceDeparture,
            'days_until_deadline' => $this->daysUntilDeadline,
            'type' => 'salary_verification_reminder',
        ];
    }
}
