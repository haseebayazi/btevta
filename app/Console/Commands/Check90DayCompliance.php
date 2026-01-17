<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Departure;
use App\Services\DepartureService;
use App\Notifications\ComplianceIssueNotification;
use Carbon\Carbon;

class Check90DayCompliance extends Command
{
    protected $signature = 'departure:check-compliance
                            {--days=90 : Days since departure to check compliance}
                            {--notify : Send notifications for non-compliant departures}';

    protected $description = 'Check 90-day compliance for departed candidates and send notifications';

    protected DepartureService $departureService;

    public function __construct(DepartureService $departureService)
    {
        parent::__construct();
        $this->departureService = $departureService;
    }

    public function handle()
    {
        $this->info('=== 90-Day Compliance Check Started ===');
        $this->info('Time: ' . Carbon::now()->format('Y-m-d H:i:s'));

        $days = $this->option('days');
        $shouldNotify = $this->option('notify');

        $stats = [
            'total_checked' => 0,
            'compliant' => 0,
            'partial' => 0,
            'non_compliant' => 0,
            'pending' => 0,
            'notifications_sent' => 0,
        ];

        // Get departures that need compliance checking
        // Check departures that:
        // 1. Departed between 30-90 days ago (or custom days)
        // 2. Not yet marked as compliant
        $departures = Departure::whereNotNull('departure_date')
            ->where('departure_date', '<=', now()->subDays(30))
            ->where('departure_date', '>=', now()->subDays($days))
            ->where(function($q) {
                $q->whereNull('ninety_day_compliance_checked')
                  ->orWhere('ninety_day_compliance_checked', false)
                  ->orWhere('ninety_day_compliance_status', '!=', 'compliant');
            })
            ->with(['candidate'])
            ->get();

        $stats['total_checked'] = $departures->count();

        if ($departures->isEmpty()) {
            $this->info('No departures found requiring compliance check.');
            return 0;
        }

        $this->newLine();
        $this->info("Checking {$departures->count()} departures for 90-day compliance...");
        $this->newLine();

        foreach ($departures as $departure) {
            // Get detailed compliance check from service
            $compliance = $this->departureService->check90DayCompliance($departure->id);

            $complianceIssues = [];

            // Collect compliance issues
            foreach ($compliance['compliance_items'] as $key => $item) {
                if (!$item['status']) {
                    $complianceIssues[] = $item['label'] . ': ' . $item['value'];
                }
            }

            // Determine compliance status
            $complianceStatus = $compliance['status'];
            $stats[$complianceStatus]++;

            // Update departure with compliance tracking
            $departure->update([
                'ninety_day_compliance_checked' => true,
                'ninety_day_compliance_status' => $complianceStatus,
                'ninety_day_compliance_issues' => !empty($complianceIssues) ? implode('; ', $complianceIssues) : null,
                'ninety_day_compliance_checked_at' => now(),
                'ninety_day_report_submitted' => $complianceStatus === 'compliant',
            ]);

            // Display status
            $statusColor = match($complianceStatus) {
                'compliant' => 'info',
                'partial' => 'comment',
                'non_compliant' => 'error',
                default => 'line',
            };

            $candidateName = $departure->candidate ? $departure->candidate->name : 'Unknown';
            $daysSince = $compliance['days_since_departure'];
            $percentage = round($compliance['compliance_percentage']);

            $this->$statusColor(
                "  [{$complianceStatus}] {$candidateName} - {$daysSince} days | {$percentage}% complete"
            );

            // Send notifications if requested and there are issues
            if ($shouldNotify && !empty($complianceIssues) && $complianceStatus !== 'compliant') {
                $notificationsSent = $this->sendComplianceNotifications($departure, $complianceIssues, $compliance);
                $stats['notifications_sent'] += $notificationsSent;
            }

            // Log activity
            activity()
                ->performedOn($departure)
                ->withProperties([
                    'status' => $complianceStatus,
                    'issues' => $complianceIssues,
                    'compliance_percentage' => $compliance['compliance_percentage'],
                ])
                ->log('90-day compliance check performed');
        }

        // Summary
        $this->newLine();
        $this->info('=== Summary ===');
        $this->line("Total Checked: {$stats['total_checked']}");
        $this->info("Compliant: {$stats['compliant']}");
        $this->comment("Partial: {$stats['partial']}");
        $this->error("Non-Compliant: {$stats['non_compliant']}");
        $this->line("Pending: {$stats['pending']}");

        if ($shouldNotify) {
            $this->line("Notifications Sent: {$stats['notifications_sent']}");
        } else {
            $this->warn("Note: Use --notify flag to send notifications");
        }

        $this->newLine();
        $this->info('=== 90-Day Compliance Check Completed ===');

        return 0;
    }

    /**
     * Send compliance notifications to relevant parties
     */
    protected function sendComplianceNotifications(Departure $departure, array $issues, array $compliance): int
    {
        $sent = 0;

        try {
            // Get candidate
            $candidate = $departure->candidate;
            if (!$candidate) {
                $this->warn("    No candidate found for departure {$departure->id}");
                return 0;
            }

            // Notify system admins
            $admins = \App\Models\User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(new ComplianceIssueNotification($departure, $issues, $compliance));
                $sent++;
            }

            // Notify campus admin if candidate has campus
            if ($candidate->campus_id) {
                $campusAdmins = \App\Models\User::where('role', 'campus_admin')
                    ->where('campus_id', $candidate->campus_id)
                    ->get();

                foreach ($campusAdmins as $campusAdmin) {
                    $campusAdmin->notify(new ComplianceIssueNotification($departure, $issues, $compliance));
                    $sent++;
                }
            }

            // Notify OEP staff if candidate has OEP
            if ($candidate->oep_id) {
                $oepUsers = \App\Models\User::where('role', 'oep_staff')
                    ->where('oep_id', $candidate->oep_id)
                    ->get();

                foreach ($oepUsers as $oepUser) {
                    $oepUser->notify(new ComplianceIssueNotification($departure, $issues, $compliance));
                    $sent++;
                }
            }

            $this->line("    Notified {$sent} users");

        } catch (\Exception $e) {
            $this->error("    Failed to send notifications: {$e->getMessage()}");
        }

        return $sent;
    }
}
