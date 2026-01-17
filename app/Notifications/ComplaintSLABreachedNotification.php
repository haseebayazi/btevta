<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Complaint;

class ComplaintSLABreachedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Complaint $complaint;
    protected int $hoursOverdue;
    protected string $severity;

    public function __construct(Complaint $complaint, int $hoursOverdue, string $severity)
    {
        $this->complaint = $complaint;
        $this->hoursOverdue = $hoursOverdue;
        $this->severity = $severity;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $candidateName = $this->complaint->candidate ? $this->complaint->candidate->name : 'Unknown';
        $assigneeName = $this->complaint->assignee ? $this->complaint->assignee->name : 'Unassigned';

        $severityLabel = strtoupper($this->severity);
        $daysOverdue = ceil($this->hoursOverdue / 24);

        $message = (new MailMessage)
            ->subject("[{$severityLabel}] SLA Breach - Complaint #{$this->complaint->id}")
            ->greeting("SLA Breach Alert")
            ->line("A complaint has breached its SLA deadline and requires immediate attention:");

        // Complaint details
        $message->line("**Complaint ID:** #{$this->complaint->id}");

        if ($this->complaint->complaint_reference) {
            $message->line("**Reference:** {$this->complaint->complaint_reference}");
        }

        $message->line("**Category:** " . ucfirst(str_replace('_', ' ', $this->complaint->complaint_category)))
                ->line("**Priority:** " . ucfirst($this->complaint->priority))
                ->line("**Status:** " . ucfirst(str_replace('_', ' ', $this->complaint->status)))
                ->line("**Subject:** {$this->complaint->subject}");

        // Candidate information
        $message->line("")
                ->line("**Candidate:** {$candidateName}");

        // Assignment information
        $message->line("**Assigned To:** {$assigneeName}");

        // SLA breach details
        $message->line("")
                ->line("**SLA Due Date:** {$this->complaint->sla_due_date->format('Y-m-d H:i')}")
                ->line("**Hours Overdue:** {$this->hoursOverdue} hours ({$daysOverdue} days)")
                ->line("**Escalation Level:** Level {$this->complaint->escalation_level}");

        // Severity-based urgency message
        if ($this->severity === 'critical') {
            $message->line("")
                    ->line("🚨 **CRITICAL:** This complaint is severely overdue and requires immediate escalation!");
        } elseif ($this->severity === 'serious') {
            $message->line("")
                    ->line("⚠️ **SERIOUS:** This complaint has been overdue for multiple days. Please take action immediately.");
        } else {
            $message->line("")
                    ->line("⚠️ **ALERT:** This complaint has breached its SLA deadline. Please review and take appropriate action.");
        }

        // Action button
        $message->action('View Complaint', route('complaints.show', $this->complaint));

        // Next steps
        $message->line("")
                ->line("**Recommended Actions:**")
                ->line("1. Review the complaint status and progress")
                ->line("2. Update the investigation notes with current status")
                ->line("3. Escalate to higher level if necessary")
                ->line("4. Contact the complainant with progress update")
                ->line("5. Take immediate action to resolve or reassign");

        // Escalation warning
        if ($this->complaint->escalation_level < 4 && $daysOverdue >= 2) {
            $message->line("")
                    ->line("⚠️ **Auto-escalation may be triggered if not resolved within 2 days of breach.**");
        }

        return $message;
    }

    public function toArray($notifiable)
    {
        return [
            'complaint_id' => $this->complaint->id,
            'complaint_reference' => $this->complaint->complaint_reference,
            'candidate_name' => $this->complaint->candidate?->name,
            'category' => $this->complaint->complaint_category,
            'priority' => $this->complaint->priority,
            'hours_overdue' => $this->hoursOverdue,
            'severity' => $this->severity,
            'escalation_level' => $this->complaint->escalation_level,
            'type' => 'complaint_sla_breach',
        ];
    }
}
