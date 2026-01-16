<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Complaint;

class ComplaintEscalatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Complaint $complaint;
    protected int $previousLevel;
    protected int $newLevel;
    protected string $reason;
    protected bool $isAutoEscalation;

    public function __construct(
        Complaint $complaint,
        int $previousLevel,
        int $newLevel,
        string $reason,
        bool $isAutoEscalation = false
    ) {
        $this->complaint = $complaint;
        $this->previousLevel = $previousLevel;
        $this->newLevel = $newLevel;
        $this->reason = $reason;
        $this->isAutoEscalation = $isAutoEscalation;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $candidateName = $this->complaint->candidate ? $this->complaint->candidate->name : 'Unknown';
        $escalationType = $this->isAutoEscalation ? 'AUTO-ESCALATED' : 'ESCALATED';

        $message = (new MailMessage)
            ->subject("[{$escalationType}] Complaint Escalated to Level {$this->newLevel} - #{$this->complaint->id}")
            ->greeting("Complaint Escalation Notice")
            ->line("A complaint has been escalated and now requires your attention:");

        // Escalation details
        $message->line("**Escalation Type:** " . ($this->isAutoEscalation ? 'Automatic (System)' : 'Manual'))
                ->line("**Previous Level:** Level {$this->previousLevel}")
                ->line("**New Level:** Level {$this->newLevel}")
                ->line("**Reason:** {$this->reason}");

        // Complaint details
        $message->line("")
                ->line("**Complaint Details:**");

        $message->line("**Complaint ID:** #{$this->complaint->id}");

        if ($this->complaint->complaint_reference) {
            $message->line("**Reference:** {$this->complaint->complaint_reference}");
        }

        $message->line("**Category:** " . ucfirst(str_replace('_', ' ', $this->complaint->complaint_category)))
                ->line("**Priority:** " . ucfirst($this->complaint->priority) . " (Updated)")
                ->line("**Status:** " . ucfirst(str_replace('_', ' ', $this->complaint->status)))
                ->line("**Subject:** {$this->complaint->subject}");

        // Candidate information
        $message->line("**Candidate:** {$candidateName}");

        // SLA information
        $message->line("")
                ->line("**SLA Information:**")
                ->line("**Original SLA Due:** {$this->complaint->sla_due_date->format('Y-m-d H:i')}")
                ->line("**New SLA Days:** {$this->complaint->sla_days} days (Recalculated)");

        if ($this->complaint->sla_breached) {
            $message->line("**SLA Status:** ⚠️ BREACHED");
            if ($this->complaint->sla_breached_at) {
                $message->line("**Breached At:** {$this->complaint->sla_breached_at->format('Y-m-d H:i')}");
            }
        }

        // Assignment information
        if ($this->complaint->assignee) {
            $message->line("")
                    ->line("**Currently Assigned To:** {$this->complaint->assignee->name}");
        }

        if ($this->complaint->escalated_to) {
            $escalatedToUser = $this->complaint->escalatedToUser;
            if ($escalatedToUser) {
                $message->line("**Escalated To:** {$escalatedToUser->name}");
            }
        }

        // Urgency message based on level
        $message->line("");

        if ($this->newLevel >= 4) {
            $message->line("🚨 **EXECUTIVE LEVEL:** This complaint has reached the highest escalation level. Immediate executive action required.");
        } elseif ($this->newLevel >= 3) {
            $message->line("⚠️ **DIRECTOR LEVEL:** This complaint requires director-level attention and intervention.");
        } elseif ($this->newLevel >= 2) {
            $message->line("⚠️ **MANAGER LEVEL:** This complaint has been escalated to management level. Please review and take appropriate action.");
        } else {
            $message->line("⚠️ **SUPERVISOR LEVEL:** This complaint requires supervisory attention.");
        }

        // Action button
        $message->action('View Complaint', route('complaints.show', $this->complaint));

        // Next steps
        $message->line("")
                ->line("**Required Actions:**")
                ->line("1. Review complaint history and current status")
                ->line("2. Assess the escalation reason and severity")
                ->line("3. Contact the assigned handler for status update")
                ->line("4. Provide guidance or reassign if necessary")
                ->line("5. Update the complainant with progress");

        // Auto-escalation warning
        if ($this->isAutoEscalation) {
            $message->line("")
                    ->line("ℹ️ **Note:** This complaint was automatically escalated by the system due to SLA breach. Manual intervention is now required.");
        }

        // Further escalation warning
        if ($this->newLevel < 4) {
            $message->line("")
                    ->line("⚠️ **Warning:** Further delays may result in additional escalation to Level " . ($this->newLevel + 1) . ".");
        }

        return $message;
    }

    public function toArray($notifiable)
    {
        return [
            'complaint_id' => $this->complaint->id,
            'complaint_reference' => $this->complaint->complaint_reference,
            'candidate_name' => $this->complaint->candidate?->name,
            'previous_level' => $this->previousLevel,
            'new_level' => $this->newLevel,
            'reason' => $this->reason,
            'is_auto_escalation' => $this->isAutoEscalation,
            'priority' => $this->complaint->priority,
            'type' => 'complaint_escalated',
        ];
    }
}
