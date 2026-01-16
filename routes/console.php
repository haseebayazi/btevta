<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes / Scheduled Tasks
|--------------------------------------------------------------------------
|
| Here you may define all of your scheduled console commands.
| The scheduler runs these commands automatically based on their schedule.
|
| IMPORTANT: Add to crontab:
| * * * * * cd /var/www/btevta && php artisan schedule:run >> /dev/null 2>&1
|
*/

// ============================================================================
// DAILY MAINTENANCE TASKS
// ============================================================================

// Log cleanup - Daily at 1:00 AM
Schedule::command('app:cleanup-old-logs --days=30')
    ->dailyAt('01:00')
    ->description('Clean up logs older than 30 days')
    ->onSuccess(function () {
        logger()->info('Scheduled: Log cleanup completed');
    })
    ->onFailure(function () {
        logger()->error('Scheduled: Log cleanup FAILED');
    });

// Document expiry check - Daily at 6:00 AM
Schedule::command('app:check-document-expiry')
    ->dailyAt('06:00')
    ->description('Check for expiring documents and send notifications')
    ->onSuccess(function () {
        logger()->info('Scheduled: Document expiry check completed');
    })
    ->onFailure(function () {
        logger()->error('Scheduled: Document expiry check FAILED');
    });

// Generate remittance alerts - Daily at 7:00 AM
Schedule::command('remittance:generate-alerts')
    ->dailyAt('07:00')
    ->description('Generate alerts for missing or irregular remittances')
    ->onSuccess(function () {
        logger()->info('Scheduled: Remittance alerts generated');
    })
    ->onFailure(function () {
        logger()->error('Scheduled: Remittance alert generation FAILED');
    });

// 90-day compliance check - Daily at 8:00 AM
Schedule::command('departure:check-compliance --notify')
    ->dailyAt('08:00')
    ->description('Check 90-day compliance for departed candidates')
    ->onSuccess(function () {
        logger()->info('Scheduled: 90-day compliance check completed');
    })
    ->onFailure(function () {
        logger()->error('Scheduled: 90-day compliance check FAILED');
    });

// Salary verification reminders - Weekly on Mondays at 9:00 AM
Schedule::command('departure:salary-reminders')
    ->weeklyOn(1, '09:00')
    ->description('Send salary verification reminders for departed candidates')
    ->onSuccess(function () {
        logger()->info('Scheduled: Salary verification reminders sent');
    })
    ->onFailure(function () {
        logger()->error('Scheduled: Salary verification reminders FAILED');
    });

// ============================================================================
// FREQUENT MONITORING TASKS
// ============================================================================

// SLA breach check - Every 15 minutes (with notifications and auto-escalation)
Schedule::command('app:check-complaint-sla --notify --auto-escalate')
    ->everyFifteenMinutes()
    ->description('Check for SLA breaches, send notifications, and auto-escalate overdue complaints')
    ->withoutOverlapping()
    ->onSuccess(function () {
        logger()->info('Scheduled: Complaint SLA check completed');
    })
    ->onFailure(function () {
        logger()->error('Scheduled: Complaint SLA check FAILED');
    });

// ============================================================================
// WEEKLY MAINTENANCE TASKS
// ============================================================================

// Weekly audit log export - Sundays at 2:00 AM
Schedule::command('audit:export --days=7')
    ->weeklyOn(0, '02:00')
    ->description('Export weekly audit logs for compliance')
    ->onSuccess(function () {
        logger()->info('Scheduled: Weekly audit export completed');
    });

// Weekly cache cleanup - Sundays at 3:00 AM
Schedule::command('cache:prune-stale-tags')
    ->weeklyOn(0, '03:00')
    ->description('Prune stale cache tags')
    ->onSuccess(function () {
        logger()->info('Scheduled: Cache cleanup completed');
    });

// ============================================================================
// MONTHLY MAINTENANCE TASKS
// ============================================================================

// Monthly data purge (dry-run first, then actual) - 1st of month at 3:00 AM
Schedule::command('data:purge --type=all --force')
    ->monthlyOn(1, '03:00')
    ->description('Monthly data retention purge')
    ->environments(['production'])
    ->onSuccess(function () {
        logger()->info('Scheduled: Monthly data purge completed');
    });

// Monthly audit export for compliance - 1st of month at 4:00 AM
Schedule::command('audit:export --days=30 --format=csv')
    ->monthlyOn(1, '04:00')
    ->description('Export monthly audit logs for compliance archival')
    ->environments(['production'])
    ->onSuccess(function () {
        logger()->info('Scheduled: Monthly audit export completed');
    });

// ============================================================================
// PASSWORD EXPIRY NOTIFICATIONS
// ============================================================================

// Password expiry warning - Daily at 8:00 AM
// This is a custom inline command to notify users about expiring passwords
Schedule::call(function () {
    $warningDays = config('password.expiry_warning_days', 14);

    $users = \App\Models\User::where('is_active', true)
        ->whereNotNull('password_changed_at')
        ->get()
        ->filter(fn($user) => $user->isPasswordExpiringSoon());

    foreach ($users as $user) {
        $daysLeft = $user->getDaysUntilPasswordExpiry();

        // Log the warning (in production, you'd send an email notification)
        logger()->info("Password expiring soon", [
            'user_id' => $user->id,
            'email' => $user->email,
            'days_remaining' => $daysLeft,
        ]);

        // Mark for notification if not already notified today
        // You could add a password_expiry_notified_at column to track this
    }

    logger()->info('Scheduled: Password expiry check completed', [
        'users_warned' => $users->count(),
    ]);
})->dailyAt('08:00')
  ->description('Warn users about expiring passwords');

// ============================================================================
// QUEUE MAINTENANCE
// ============================================================================

// Prune old batches from job batches table - Weekly
Schedule::command('queue:prune-batches --hours=48')
    ->weeklyOn(0, '04:00')
    ->description('Prune old job batches');

// Retry failed jobs older than 1 hour - Every 6 hours
Schedule::command('queue:retry --queue=default --range=1-100')
    ->everySixHours()
    ->description('Retry failed jobs')
    ->withoutOverlapping();

// ============================================================================
// HEALTH MONITORING
// ============================================================================

// Health check logging - Every 5 minutes (for monitoring systems)
Schedule::call(function () {
    $healthy = true;

    try {
        // Quick database check
        \Illuminate\Support\Facades\DB::select('SELECT 1');
    } catch (\Exception $e) {
        $healthy = false;
        logger()->error('Health check failed: Database', ['error' => $e->getMessage()]);
    }

    if ($healthy) {
        // Write to a health check file that monitoring can read
        file_put_contents(
            storage_path('framework/health-check.txt'),
            now()->toISOString()
        );
    }
})->everyFiveMinutes()
  ->description('Health check heartbeat')
  ->withoutOverlapping();
