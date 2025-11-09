<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DocumentArchive;
use App\Models\User;
use App\Notifications\DocumentExpiringAlert;
use Carbon\Carbon;

class CheckDocumentExpiry extends Command
{
    protected $signature = 'app:check-document-expiry';
    protected $description = 'Check for expiring documents and send alerts';

    public function handle()
    {
        $this->info('Checking for expiring documents...');

        $expiringDocs = DocumentArchive::where('expiry_date', '<=', Carbon::now()->addDays(30))
            ->where('expiry_date', '>', Carbon::now())
            ->get();

        foreach ($expiringDocs as $doc) {
            $daysRemaining = $doc->expiry_date->diffInDays(Carbon::now());
            $admins = User::where('role', 'admin')->get();
            
            foreach ($admins as $admin) {
                $admin->notify(new DocumentExpiringAlert($doc, $daysRemaining));
            }

            $this->line('Notified: ' . $doc->title);
        }

        $this->info('Document expiry check completed!');
    }
}
