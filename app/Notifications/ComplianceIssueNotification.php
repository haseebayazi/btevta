<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Departure;

class ComplianceIssueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Departure $departure;
    protected array $issues;
    protected array $compliance;

    public function __construct(Departure $departure, array $issues, array $compliance)
    {
        $this->departure = $departure;
        $this->issues = $issues;
        $this->compliance = $compliance;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $candidateName = $this->departure->candidate ? $this->departure->candidate->name : 'Unknown Candidate';
        $daysSince = $this->compliance['days_since_departure'];
        $daysRemaining = max(0, $this->compliance['days_remaining']);
        $percentage = round($this->compliance['compliance_percentage']);
        $status = $this->compliance['status'];

        // Determine severity
        $severity = match($status) {
            'non_compliant' => 'CRITICAL',
            'partial' => 'WARNING',
            default => 'NOTICE',
        };

        $message = (new MailMessage)
            ->subject("[{$severity}] 90-Day Compliance Issue - {$candidateName}")
            ->greeting("90-Day Compliance Alert")
            ->line("A compliance check has identified issues for a departed candidate:");

        // Candidate information
        $message->line("**Candidate:** {$candidateName}");

        if ($this->departure->candidate && $this->departure->candidate->btevta_id) {
            $message->line("**ID:** {$this->departure->candidate->btevta_id}");
        }

        $message->line("**Departure Date:** {$this->departure->departure_date->format('Y-m-d')}");
        $message->line("**Days Since Departure:** {$daysSince} days");
        $message->line("**Compliance Status:** " . ucfirst($status));
        $message->line("**Completion:** {$percentage}%");

        if ($daysRemaining > 0) {
            $message->line("**Days Remaining:** {$daysRemaining} days until 90-day deadline");
        } else {
            $message->line("⚠️ **OVERDUE:** 90-day deadline has passed!");
        }

        // List compliance issues
        if (!empty($this->issues)) {
            $message->line("")
                ->line("**Compliance Issues:**");

            foreach ($this->issues as $issue) {
                $message->line("• {$issue}");
            }
        }

        // Urgency message
        if ($status === 'non_compliant') {
            $message->line("")
                ->line("⚠️ **CRITICAL:** This candidate has passed the 90-day compliance deadline with incomplete requirements. Immediate action required!");
        } elseif ($daysRemaining <= 7) {
            $message->line("")
                ->line("⚠️ **URGENT:** Only {$daysRemaining} days remaining to complete compliance requirements!");
        } elseif ($daysRemaining <= 14) {
            $message->line("")
                ->line("⚠️ **WARNING:** {$daysRemaining} days remaining. Please prioritize completion of pending items.");
        }

        // Action button
        $message->action('View Departure Details', route('departures.show', $this->departure));

        // Next steps
        $message->line("")
            ->line("**Next Steps:**")
            ->line("1. Contact the candidate/employer to obtain missing information")
            ->line("2. Update the system with the required compliance data")
            ->line("3. Verify all information is accurate and complete")
            ->line("4. Monitor for completion before the 90-day deadline");

        return $message;
    }

    public function toArray($notifiable)
    {
        return [
            'departure_id' => $this->departure->id,
            'candidate_name' => $this->departure->candidate?->name,
            'days_since_departure' => $this->compliance['days_since_departure'],
            'days_remaining' => max(0, $this->compliance['days_remaining']),
            'compliance_status' => $this->compliance['status'],
            'compliance_percentage' => $this->compliance['compliance_percentage'],
            'issues' => $this->issues,
            'type' => 'compliance_issue',
        ];
    }
}
