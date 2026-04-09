<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * All commands are idempotent; safe to re-run if a cron window is missed.
     */
    protected function schedule(Schedule $schedule): void
    {
        // ── Every 15 minutes ────────────────────────────────────────────────
        $schedule->command('complaints:check-sla')->everyFifteenMinutes()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/scheduler.log'));

        // ── Hourly ──────────────────────────────────────────────────────────
        $schedule->command('remittance:generate-alerts')->hourly()
            ->withoutOverlapping();

        // ── Daily jobs ──────────────────────────────────────────────────────
        $schedule->command('documents:check-expiry')->dailyAt('06:00')
            ->withoutOverlapping();

        $schedule->command('documents:create-renewal-requests')->dailyAt('06:30')
            ->withoutOverlapping();

        $schedule->command('compliance:check-90-day')->dailyAt('07:00')
            ->withoutOverlapping();

        $schedule->command('pipeline:send-daily-summary')->dailyAt('08:00')
            ->withoutOverlapping();

        $schedule->command('screening:send-reminders')->dailyAt('08:30')
            ->withoutOverlapping();

        $schedule->command('salary:send-reminders')->dailyAt('09:00')
            ->withoutOverlapping();

        // ── Nightly cleanup ─────────────────────────────────────────────────
        $schedule->command('logs:cleanup')->dailyAt('01:00')
            ->withoutOverlapping();

        $schedule->command('data:purge-old')->dailyAt('02:00')
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
    }
}
