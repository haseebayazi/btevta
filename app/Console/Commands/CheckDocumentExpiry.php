<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DocumentArchive;
use App\Models\User;
use App\Notifications\DocumentExpiringAlert;
use Carbon\Carbon;

class CheckDocumentExpiry extends Command
{
    protected $signature = 'app:check-document-expiry
                            {--threshold=30 : Days threshold for expiry check}
                            {--only-current : Only check current version documents}';

    protected $description = 'Check for expiring documents and send multi-tier alerts (7, 14, 30 days)';

    // Multi-tier alert thresholds
    protected $alertTiers = [
        7 => 'critical',   // 7 days - critical alert
        14 => 'warning',   // 14 days - warning alert
        30 => 'notice',    // 30 days - advance notice
    ];

    public function handle()
    {
        $this->info('=== Document Expiry Check Started ===');
        $this->info('Time: ' . Carbon::now()->format('Y-m-d H:i:s'));

        $stats = [
            'total_checked' => 0,
            'expiring_soon' => 0,
            'already_expired' => 0,
            'notifications_sent' => 0,
        ];

        // Process each alert tier
        foreach ($this->alertTiers as $days => $severity) {
            $this->newLine();
            $this->info("Processing {$days}-day {$severity} alerts...");

            $tierStats = $this->processAlertTier($days, $severity);

            $stats['total_checked'] += $tierStats['checked'];
            $stats['expiring_soon'] += $tierStats['expiring'];
            $stats['notifications_sent'] += $tierStats['notifications'];
        }

        // Check for already expired documents
        $this->newLine();
        $this->warn('Checking for expired documents...');
        $stats['already_expired'] = $this->processExpiredDocuments();

        // Summary
        $this->newLine();
        $this->info('=== Summary ===');
        $this->line("Documents Checked: {$stats['total_checked']}");
        $this->line("Expiring Soon: {$stats['expiring_soon']}");
        $this->warn("Already Expired: {$stats['already_expired']}");
        $this->line("Notifications Sent: {$stats['notifications_sent']}");
        $this->newLine();
        $this->info('=== Document Expiry Check Completed ===');

        return 0;
    }

    /**
     * Process documents for a specific alert tier
     */
    protected function processAlertTier($days, $severity)
    {
        $stats = ['checked' => 0, 'expiring' => 0, 'notifications' => 0];

        // Query for documents expiring in this tier
        $query = DocumentArchive::with(['candidate', 'campus', 'uploader'])
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '>', Carbon::now())
            ->where('expiry_date', '<=', Carbon::now()->addDays($days));

        // If previous tier exists, exclude those already notified
        $previousTier = $this->getPreviousTier($days);
        if ($previousTier) {
            $query->where('expiry_date', '>', Carbon::now()->addDays($previousTier));
        }

        // Only check current version documents if flag is set
        if ($this->option('only-current')) {
            $query->where('is_current_version', true);
        }

        $documents = $query->get();
        $stats['checked'] = $documents->count();

        if ($documents->isEmpty()) {
            $this->line("  No documents in {$days}-day threshold");
            return $stats;
        }

        foreach ($documents as $document) {
            $daysRemaining = Carbon::now()->diffInDays($document->expiry_date, false);
            $stats['expiring']++;

            // Get recipients for this document
            $recipients = $this->getNotificationRecipients($document);

            if ($recipients->isEmpty()) {
                $this->warn("  No recipients for: {$document->document_name}");
                continue;
            }

            // Send notifications
            foreach ($recipients as $recipient) {
                try {
                    $recipient->notify(new DocumentExpiringAlert($document, $daysRemaining));
                    $stats['notifications']++;
                } catch (\Exception $e) {
                    $this->error("  Failed to notify {$recipient->name}: {$e->getMessage()}");
                }
            }

            $this->line("  [{$severity}] {$document->document_name} - {$daysRemaining} days (notified {$recipients->count()} users)");
        }

        return $stats;
    }

    /**
     * Process already expired documents
     */
    protected function processExpiredDocuments()
    {
        $expiredDocs = DocumentArchive::with(['candidate', 'campus'])
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', Carbon::now())
            ->where('is_current_version', true)
            ->get();

        if ($expiredDocs->isEmpty()) {
            $this->line('  No expired documents found');
            return 0;
        }

        foreach ($expiredDocs as $document) {
            $daysExpired = Carbon::now()->diffInDays($document->expiry_date);
            $this->warn("  [EXPIRED] {$document->document_name} - expired {$daysExpired} days ago");
        }

        return $expiredDocs->count();
    }

    /**
     * Get notification recipients for a document
     * Returns collection of users who should be notified
     */
    protected function getNotificationRecipients($document)
    {
        $recipients = collect();

        // 1. System admins always get notified
        $admins = User::where('role', 'admin')->get();
        $recipients = $recipients->merge($admins);

        // 2. Campus admin for the document's campus
        if ($document->campus_id) {
            $campusAdmins = User::where('role', 'campus_admin')
                ->where('campus_id', $document->campus_id)
                ->get();
            $recipients = $recipients->merge($campusAdmins);
        }

        // 3. Document uploader (they uploaded it, they should know)
        if ($document->uploader) {
            $recipients->push($document->uploader);
        }

        // 4. Remove duplicates by user ID
        return $recipients->unique('id');
    }

    /**
     * Get the previous tier days value
     */
    protected function getPreviousTier($currentDays)
    {
        $tiers = array_keys($this->alertTiers);
        $currentIndex = array_search($currentDays, $tiers);

        if ($currentIndex === false || $currentIndex === 0) {
            return null;
        }

        return $tiers[$currentIndex - 1];
    }
}
