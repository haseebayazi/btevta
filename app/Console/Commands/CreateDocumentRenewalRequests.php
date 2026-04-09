<?php

namespace App\Console\Commands;

use App\Services\DocumentRenewalService;
use Illuminate\Console\Command;

class CreateDocumentRenewalRequests extends Command
{
    protected $signature = 'documents:create-renewal-requests';

    protected $description = 'Auto-create renewal requests for pre-departure documents expiring within 30 days';

    public function handle(DocumentRenewalService $renewalService): int
    {
        $this->info('Scanning for expiring documents...');

        $count = $renewalService->createRenewalRequestsForExpiringDocuments();

        if ($count > 0) {
            $this->info("Created {$count} renewal request(s).");
        } else {
            $this->line('No expiring documents found requiring renewal requests.');
        }

        return self::SUCCESS;
    }
}
