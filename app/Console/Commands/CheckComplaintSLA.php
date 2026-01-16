<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Complaint;
use App\Models\User;
use App\Notifications\ComplaintSLABreachedNotification;
use App\Notifications\ComplaintEscalatedNotification;
use App\Services\ComplaintService;
use Carbon\Carbon;

class CheckComplaintSLA extends Command
{
    protected $signature = 'app:check-complaint-sla
                            {--notify : Send notifications for SLA breaches}
                            {--auto-escalate : Automatically escalate overdue complaints}';

    protected $description = 'Check complaints for SLA breaches, send notifications, and auto-escalate if needed';

    protected ComplaintService $complaintService;

    public function __construct(ComplaintService $complaintService)
    {
        parent::__construct();
        $this->complaintService = $complaintService;
    }

    public function handle()
    {
        $this->info('=== Complaint SLA Check Started ===');
        $this->info('Time: ' . Carbon::now()->format('Y-m-d H:i:s'));

        $notify = $this->option('notify');
        $autoEscalate = $this->option('auto-escalate');

        // Find complaints that have breached SLA
        $breachedComplaints = Complaint::with(['candidate', 'assignee', 'campus', 'oep'])
            ->whereNotIn('status', ['resolved', 'closed'])
            ->whereNotNull('sla_due_date')
            ->where('sla_due_date', '<', now())
            ->get();

        if ($breachedComplaints->isEmpty()) {
            $this->info('No SLA breaches found. All complaints are within SLA.');
            return 0;
        }

        $this->newLine();
        $this->info("Found {$breachedComplaints->count()} complaints with SLA breaches...");
        $this->newLine();

        $stats = [
            'total_breached' => $breachedComplaints->count(),
            'newly_breached' => 0,
            'already_breached' => 0,
            'notifications_sent' => 0,
            'escalated' => 0,
            'by_severity' => [
                'moderate' => 0,
                'serious' => 0,
                'critical' => 0,
            ],
        ];

        foreach ($breachedComplaints as $complaint) {
            $hoursOverdue = now()->diffInHours($complaint->sla_due_date);
            $daysOverdue = ceil($hoursOverdue / 24);
            $severity = $this->calculateSeverity($daysOverdue);

            $stats['by_severity'][$severity]++;

            // Check if this is a new breach
            $isNewBreach = !$complaint->sla_breached;

            if ($isNewBreach) {
                // Mark as breached
                $complaint->update([
                    'sla_breached' => true,
                    'sla_breached_at' => now(),
                ]);
                $stats['newly_breached']++;

                $this->warn("  [NEW BREACH] Complaint #{$complaint->id} - {$hoursOverdue}h overdue ({$severity})");
            } else {
                $stats['already_breached']++;
                $this->line("  [ONGOING] Complaint #{$complaint->id} - {$hoursOverdue}h overdue ({$severity})");
            }

            // Send notifications for new breaches
            if ($notify && $isNewBreach) {
                $notificationsSent = $this->sendBreachNotifications($complaint, $hoursOverdue, $severity);
                $stats['notifications_sent'] += $notificationsSent;
                $this->info("    → {$notificationsSent} notifications sent");
            }

            // Auto-escalate if enabled and conditions are met
            if ($autoEscalate && $daysOverdue >= 2 && $complaint->escalation_level < 4) {
                // Check if already escalated today
                $alreadyEscalatedToday = $complaint->escalated_at &&
                                        Carbon::parse($complaint->escalated_at)->isToday();

                if (!$alreadyEscalatedToday) {
                    $this->escalateComplaint($complaint, $daysOverdue, $notify);
                    $stats['escalated']++;
                    $this->info("    → Escalated to Level {$complaint->fresh()->escalation_level}");
                }
            }

            // Log activity
            if ($isNewBreach) {
                activity()
                    ->performedOn($complaint)
                    ->withProperties([
                        'hours_overdue' => $hoursOverdue,
                        'severity' => $severity,
                        'escalation_level' => $complaint->escalation_level,
                    ])
                    ->log('SLA breached');
            }
        }

        // Summary
        $this->newLine();
        $this->info('=== Summary ===');
        $this->line("Total SLA Breaches: {$stats['total_breached']}");
        $this->line("  Newly Breached: {$stats['newly_breached']}");
        $this->line("  Already Breached: {$stats['already_breached']}");
        $this->newLine();
        $this->line("Severity Breakdown:");
        $this->line("  Moderate (1-2 days): {$stats['by_severity']['moderate']}");
        $this->line("  Serious (3-5 days): {$stats['by_severity']['serious']}");
        $this->line("  Critical (6+ days): {$stats['by_severity']['critical']}");

        if ($notify) {
            $this->newLine();
            $this->line("Notifications Sent: {$stats['notifications_sent']}");
        }

        if ($autoEscalate) {
            $this->newLine();
            $this->line("Complaints Escalated: {$stats['escalated']}");
        }

        $this->newLine();
        $this->info('=== Complaint SLA Check Completed ===');

        return 0;
    }

    /**
     * Calculate severity based on days overdue
     */
    protected function calculateSeverity(int $daysOverdue): string
    {
        if ($daysOverdue <= 2) {
            return 'moderate';
        } elseif ($daysOverdue <= 5) {
            return 'serious';
        } else {
            return 'critical';
        }
    }

    /**
     * Send breach notifications to relevant parties
     */
    protected function sendBreachNotifications(Complaint $complaint, int $hoursOverdue, string $severity): int
    {
        $recipientCount = 0;

        try {
            // 1. Notify assignee if assigned
            if ($complaint->assignee) {
                $complaint->assignee->notify(
                    new ComplaintSLABreachedNotification($complaint, $hoursOverdue, $severity)
                );
                $recipientCount++;
            }

            // 2. Notify escalated_to user if set
            if ($complaint->escalated_to && $complaint->escalatedToUser) {
                $complaint->escalatedToUser->notify(
                    new ComplaintSLABreachedNotification($complaint, $hoursOverdue, $severity)
                );
                $recipientCount++;
            }

            // 3. Notify campus admins if complaint is linked to campus
            if ($complaint->campus_id) {
                $campusAdmins = User::where('role', 'campus_admin')
                    ->where('campus_id', $complaint->campus_id)
                    ->get();

                foreach ($campusAdmins as $admin) {
                    $admin->notify(
                        new ComplaintSLABreachedNotification($complaint, $hoursOverdue, $severity)
                    );
                    $recipientCount++;
                }
            }

            // 4. Notify system admins for serious and critical breaches
            if (in_array($severity, ['serious', 'critical'])) {
                $admins = User::where('role', 'admin')->get();

                foreach ($admins as $admin) {
                    $admin->notify(
                        new ComplaintSLABreachedNotification($complaint, $hoursOverdue, $severity)
                    );
                    $recipientCount++;
                }
            }

        } catch (\Exception $e) {
            $this->error("    Failed to send notifications: {$e->getMessage()}");
        }

        return $recipientCount;
    }

    /**
     * Escalate complaint and send notifications
     */
    protected function escalateComplaint(Complaint $complaint, int $daysOverdue, bool $notify): void
    {
        $previousLevel = $complaint->escalation_level;
        $reason = "Auto-escalated: {$daysOverdue} days overdue";

        // Use ComplaintService to escalate (handles priority increase and SLA recalculation)
        $this->complaintService->escalateComplaint($complaint->id, $reason);

        // Reload complaint to get updated data
        $complaint = $complaint->fresh();
        $newLevel = $complaint->escalation_level;

        // Send escalation notifications if enabled
        if ($notify) {
            $this->sendEscalationNotifications($complaint, $previousLevel, $newLevel, $reason);
        }

        // Log activity
        activity()
            ->performedOn($complaint)
            ->withProperties([
                'previous_level' => $previousLevel,
                'new_level' => $newLevel,
                'reason' => $reason,
                'auto_escalation' => true,
            ])
            ->log('Complaint auto-escalated');
    }

    /**
     * Send escalation notifications
     */
    protected function sendEscalationNotifications(
        Complaint $complaint,
        int $previousLevel,
        int $newLevel,
        string $reason
    ): void {
        try {
            // 1. Notify assignee
            if ($complaint->assignee) {
                $complaint->assignee->notify(
                    new ComplaintEscalatedNotification(
                        $complaint,
                        $previousLevel,
                        $newLevel,
                        $reason,
                        true // isAutoEscalation
                    )
                );
            }

            // 2. Notify escalated_to user
            if ($complaint->escalated_to && $complaint->escalatedToUser) {
                $complaint->escalatedToUser->notify(
                    new ComplaintEscalatedNotification(
                        $complaint,
                        $previousLevel,
                        $newLevel,
                        $reason,
                        true
                    )
                );
            }

            // 3. Notify campus admins
            if ($complaint->campus_id) {
                $campusAdmins = User::where('role', 'campus_admin')
                    ->where('campus_id', $complaint->campus_id)
                    ->get();

                foreach ($campusAdmins as $admin) {
                    $admin->notify(
                        new ComplaintEscalatedNotification(
                            $complaint,
                            $previousLevel,
                            $newLevel,
                            $reason,
                            true
                        )
                    );
                }
            }

            // 4. Notify system admins for level 3+ escalations
            if ($newLevel >= 3) {
                $admins = User::where('role', 'admin')->get();

                foreach ($admins as $admin) {
                    $admin->notify(
                        new ComplaintEscalatedNotification(
                            $complaint,
                            $previousLevel,
                            $newLevel,
                            $reason,
                            true
                        )
                    );
                }
            }

        } catch (\Exception $e) {
            $this->error("    Failed to send escalation notifications: {$e->getMessage()}");
        }
    }
}
