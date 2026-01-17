<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Departure;
use App\Notifications\SalaryVerificationReminderNotification;
use Carbon\Carbon;

class SendSalaryVerificationReminders extends Command
{
    protected $signature = 'departure:salary-reminders
                            {--days=30 : Days after departure to send reminders}';

    protected $description = 'Send salary verification reminders for departed candidates without confirmed salary';

    public function handle()
    {
        $this->info('=== Salary Verification Reminders Started ===');
        $this->info('Time: ' . Carbon::now()->format('Y-m-d H:i:s'));

        $days = $this->option('days');

        // Find departures that:
        // 1. Departed more than X days ago (default 30)
        // 2. No salary confirmed yet
        // 3. Not yet passed 90 days
        $needingReminders = Departure::with(['candidate.oep', 'candidate.campus'])
            ->whereNotNull('departure_date')
            ->where('departure_date', '<=', now()->subDays($days))
            ->where('departure_date', '>=', now()->subDays(90))
            ->where(function($q) {
                $q->where('salary_confirmed', false)
                  ->orWhereNull('salary_confirmed');
            })
            ->get();

        if ($needingReminders->isEmpty()) {
            $this->info('No departures found needing salary verification reminders.');
            return 0;
        }

        $this->newLine();
        $this->info("Sending reminders for {$needingReminders->count()} departures...");
        $this->newLine();

        $stats = [
            'total' => $needingReminders->count(),
            'notifications_sent' => 0,
            'failed' => 0,
        ];

        foreach ($needingReminders as $departure) {
            $candidate = $departure->candidate;

            if (!$candidate) {
                $this->warn("  Departure {$departure->id}: No candidate found");
                $stats['failed']++;
                continue;
            }

            $daysSinceDeparture = $departure->departure_date->diffInDays(now());
            $daysUntilDeadline = 90 - $daysSinceDeparture;

            try {
                $recipients = collect();

                // 1. Notify candidate if they have email
                if ($candidate->email) {
                    $candidate->notify(new SalaryVerificationReminderNotification($departure, $daysSinceDeparture, $daysUntilDeadline));
                    $recipients->push('Candidate');
                }

                // 2. Notify OEP staff
                if ($candidate->oep_id) {
                    $oepUsers = \App\Models\User::where('role', 'oep_staff')
                        ->where('oep_id', $candidate->oep_id)
                        ->get();

                    foreach ($oepUsers as $user) {
                        $user->notify(new SalaryVerificationReminderNotification($departure, $daysSinceDeparture, $daysUntilDeadline));
                        $recipients->push($user->name);
                    }
                }

                // 3. Notify campus admin
                if ($candidate->campus_id) {
                    $campusAdmins = \App\Models\User::where('role', 'campus_admin')
                        ->where('campus_id', $candidate->campus_id)
                        ->get();

                    foreach ($campusAdmins as $admin) {
                        $admin->notify(new SalaryVerificationReminderNotification($departure, $daysSinceDeparture, $daysUntilDeadline));
                        $recipients->push($admin->name);
                    }
                }

                // 4. Notify system admins
                $admins = \App\Models\User::where('role', 'admin')->get();
                foreach ($admins as $admin) {
                    $admin->notify(new SalaryVerificationReminderNotification($departure, $daysSinceDeparture, $daysUntilDeadline));
                    $recipients->push($admin->name);
                }

                $uniqueRecipients = $recipients->unique()->count();
                $stats['notifications_sent'] += $uniqueRecipients;

                $urgency = $daysUntilDeadline <= 14 ? 'URGENT' : 'REMINDER';
                $this->line("  [{$urgency}] {$candidate->name} - {$daysSinceDeparture} days since departure ({$uniqueRecipients} notified)");

                // Log activity
                activity()
                    ->performedOn($departure)
                    ->withProperties([
                        'days_since_departure' => $daysSinceDeparture,
                        'days_until_deadline' => $daysUntilDeadline,
                        'recipients_count' => $uniqueRecipients,
                    ])
                    ->log('Salary verification reminder sent');

            } catch (\Exception $e) {
                $this->error("  Failed to send reminder for {$candidate->name}: {$e->getMessage()}");
                $stats['failed']++;
            }
        }

        // Summary
        $this->newLine();
        $this->info('=== Summary ===');
        $this->line("Total Departures: {$stats['total']}");
        $this->line("Notifications Sent: {$stats['notifications_sent']}");

        if ($stats['failed'] > 0) {
            $this->error("Failed: {$stats['failed']}");
        }

        $this->newLine();
        $this->info('=== Salary Verification Reminders Completed ===');

        return 0;
    }
}
