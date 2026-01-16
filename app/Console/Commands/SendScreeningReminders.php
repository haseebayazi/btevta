<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Candidate;
use App\Models\User;
use App\Notifications\ScreeningCallReminderNotification;
use Carbon\Carbon;

class SendScreeningReminders extends Command
{
    protected $signature = 'screening:send-reminders
                            {--days=2 : Days since screening was due to send reminders}';

    protected $description = 'Send reminders to screening staff for pending screening calls';

    public function handle()
    {
        $this->info('=== Screening Call Reminders Started ===');
        $this->info('Time: ' . Carbon::now()->format('Y-m-d H:i:s'));

        $days = $this->option('days');

        // Find candidates pending screening calls for more than X days
        $pendingScreening = Candidate::where('status', 'screening')
            ->where('created_at', '<=', now()->subDays($days))
            ->whereDoesntHave('screenings', function($q) {
                $q->where('status', 'completed');
            })
            ->with(['campus', 'oep'])
            ->get();

        if ($pendingScreening->isEmpty()) {
            $this->info('No pending screening calls found.');
            return 0;
        }

        $this->newLine();
        $this->info("Found {$pendingScreening->count()} candidates with pending screening calls...");
        $this->newLine();

        $stats = [
            'total' => $pendingScreening->count(),
            'notifications_sent' => 0,
            'failed' => 0,
            'by_campus' => [],
        ];

        foreach ($pendingScreening as $candidate) {
            $daysPending = $candidate->created_at->diffInDays(now());

            try {
                $recipients = collect();

                // 1. Notify campus admins if candidate linked to campus
                if ($candidate->campus_id) {
                    $campusAdmins = User::where('role', 'campus_admin')
                        ->where('campus_id', $candidate->campus_id)
                        ->get();

                    foreach ($campusAdmins as $admin) {
                        $admin->notify(new ScreeningCallReminderNotification($candidate, $daysPending));
                        $recipients->push($admin->name);
                    }

                    // Track by campus
                    $campusName = $candidate->campus?->name ?? 'Unknown';
                    if (!isset($stats['by_campus'][$campusName])) {
                        $stats['by_campus'][$campusName] = 0;
                    }
                    $stats['by_campus'][$campusName]++;
                }

                // 2. Notify OEP staff if candidate linked to OEP
                if ($candidate->oep_id) {
                    $oepStaff = User::where('role', 'oep_staff')
                        ->where('oep_id', $candidate->oep_id)
                        ->get();

                    foreach ($oepStaff as $staff) {
                        $staff->notify(new ScreeningCallReminderNotification($candidate, $daysPending));
                        $recipients->push($staff->name);
                    }
                }

                // 3. Notify system admins for overdue screenings (7+ days)
                if ($daysPending >= 7) {
                    $admins = User::where('role', 'admin')->get();

                    foreach ($admins as $admin) {
                        $admin->notify(new ScreeningCallReminderNotification($candidate, $daysPending));
                        $recipients->push($admin->name);
                    }
                }

                $uniqueRecipients = $recipients->unique()->count();
                $stats['notifications_sent'] += $uniqueRecipients;

                $urgency = $daysPending >= 7 ? 'OVERDUE' : 'PENDING';
                $this->line("  [{$urgency}] {$candidate->name} - {$daysPending} days pending ({$uniqueRecipients} notified)");

                // Update reminder timestamp on candidate
                $candidate->update([
                    'screening_reminder_sent_at' => now(),
                ]);

                // Log activity
                activity()
                    ->performedOn($candidate)
                    ->withProperties([
                        'days_pending' => $daysPending,
                        'recipients_count' => $uniqueRecipients,
                    ])
                    ->log('Screening call reminder sent');

            } catch (\Exception $e) {
                $this->error("  Failed to send reminder for {$candidate->name}: {$e->getMessage()}");
                $stats['failed']++;
            }
        }

        // Summary
        $this->newLine();
        $this->info('=== Summary ===');
        $this->line("Total Pending: {$stats['total']}");
        $this->line("Notifications Sent: {$stats['notifications_sent']}");

        if (!empty($stats['by_campus'])) {
            $this->newLine();
            $this->line("By Campus:");
            foreach ($stats['by_campus'] as $campus => $count) {
                $this->line("  {$campus}: {$count}");
            }
        }

        if ($stats['failed'] > 0) {
            $this->newLine();
            $this->error("Failed: {$stats['failed']}");
        }

        $this->newLine();
        $this->info('=== Screening Reminders Completed ===');

        return 0;
    }
}
