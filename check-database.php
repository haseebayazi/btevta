<?php

/**
 * Quick Database Check Script
 *
 * This script shows current database status and helps verify test data.
 *
 * USAGE:
 * - Browser: http://your-domain.com/check-database.php
 * - Command: php check-database.php
 *
 * DELETE THIS FILE AFTER USE!
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Campus;
use App\Models\Trade;
use App\Models\Oep;
use App\Models\Candidate;
use App\Models\Batch;

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║              BTEVTA - Database Status Check               ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

try {
    // Test database connection
    \DB::connection()->getPdo();
    echo "✅ Database connection: SUCCESS\n";
    echo "   Database: " . env('DB_DATABASE') . "\n";
    echo "\n";

    // Count records
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📊 Current Database Records:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

    $users = User::count();
    $campuses = Campus::count();
    $trades = Trade::count();
    $oeps = Oep::count();
    $batches = Batch::count();
    $candidates = Candidate::count();

    echo "   Users:      {$users}\n";
    echo "   Campuses:   {$campuses}\n";
    echo "   Trades:     {$trades}\n";
    echo "   OEPs:       {$oeps}\n";
    echo "   Batches:    {$batches}\n";
    echo "   Candidates: {$candidates}\n";
    echo "\n";

    // Determine status
    if ($candidates == 0) {
        echo "⚠️  STATUS: NO TEST DATA FOUND\n";
        echo "\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "🚀 TO POPULATE TEST DATA, RUN THIS COMMAND:\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "\n";
        echo "   php artisan db:seed --class=TestDataSeeder\n";
        echo "\n";
        echo "This will create:\n";
        echo "   • 7 Users (admin, campus admins, users)\n";
        echo "   • 4 Campuses\n";
        echo "   • 10 Trades\n";
        echo "   • 3 OEPs\n";
        echo "   • 6 Batches\n";
        echo "   • 41 Candidates (at various stages)\n";
        echo "   • Training, Screening, Registration data\n";
        echo "   • Visa Processing, Departures\n";
        echo "   • Complaints, Correspondence, etc.\n";
        echo "\n";
    } else {
        echo "✅ STATUS: TEST DATA POPULATED\n";
        echo "\n";

        // Show candidate breakdown by status
        $statuses = \DB::table('candidates')
            ->select('status', \DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        if ($statuses->count() > 0) {
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            echo "📋 Candidates by Status:\n";
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            foreach ($statuses as $status) {
                echo "   " . str_pad(ucfirst(str_replace('_', ' ', $status->status)), 20) . " : {$status->count}\n";
            }
            echo "\n";
        }

        echo "✅ You can now test all modules in the application!\n";
    }

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "\n";
    echo "⚠️  DELETE THIS FILE (check-database.php) AFTER USE!\n";
    echo "\n";

} catch (\Exception $e) {
    echo "\n";
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "\n";
    echo "Check your .env file database configuration:\n";
    echo "   DB_CONNECTION=mysql\n";
    echo "   DB_HOST=127.0.0.1\n";
    echo "   DB_DATABASE=your_database_name\n";
    echo "   DB_USERNAME=your_username\n";
    echo "   DB_PASSWORD=your_password\n";
    echo "\n";
}

echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";
