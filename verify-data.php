<?php

/**
 * Quick Data Verification Script
 *
 * Run this to see if test data exists in your database RIGHT NOW
 *
 * USAGE: php verify-data.php
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
use Illuminate\Support\Facades\DB;

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "           QUICK DATA VERIFICATION CHECK                   \n";
echo "═══════════════════════════════════════════════════════════\n";
echo "\n";

try {
    // Test database connection
    $dbName = DB::connection()->getDatabaseName();
    echo "✅ Database: {$dbName}\n";
    echo "\n";

    // Count all records
    echo "Current Record Counts:\n";
    echo "─────────────────────────────────────────────────────────\n";

    $counts = [
        'Users' => User::count(),
        'Campuses' => Campus::count(),
        'Trades' => Trade::count(),
        'OEPs' => Oep::count(),
        'Batches' => Batch::count(),
        'Candidates' => Candidate::count(),
    ];

    $total = 0;
    foreach ($counts as $table => $count) {
        echo sprintf("%-15s: %d\n", $table, $count);
        $total += $count;
    }

    echo "─────────────────────────────────────────────────────────\n";
    echo "Total Records  : {$total}\n";
    echo "\n";

    // Detailed candidate status if candidates exist
    if ($counts['Candidates'] > 0) {
        echo "✅ CANDIDATES FOUND! Breakdown by status:\n";
        echo "─────────────────────────────────────────────────────────\n";

        $statuses = DB::table('candidates')
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->orderBy('count', 'desc')
            ->get();

        foreach ($statuses as $status) {
            echo sprintf("  %-25s: %d\n",
                ucfirst(str_replace('_', ' ', $status->status)),
                $status->count
            );
        }
        echo "\n";

        // Show a few sample candidates
        echo "Sample Candidates:\n";
        echo "─────────────────────────────────────────────────────────\n";

        $samples = Candidate::with(['campus', 'trade'])
            ->orderBy('id', 'asc')
            ->take(5)
            ->get();

        foreach ($samples as $candidate) {
            echo "  • {$candidate->btevta_id} - {$candidate->name}\n";
            echo "    Status: {$candidate->status}\n";
            echo "    Campus: " . ($candidate->campus->name ?? 'N/A') . "\n";
            echo "    Trade: " . ($candidate->trade->name ?? 'N/A') . "\n";
            echo "\n";
        }

        echo "═══════════════════════════════════════════════════════════\n";
        echo "✅ SUCCESS! Test data exists in your database.\n";
        echo "\n";
        echo "If you can't see this data in your application:\n";
        echo "1. Clear application cache: php artisan cache:clear\n";
        echo "2. Clear config cache: php artisan config:clear\n";
        echo "3. Clear view cache: php artisan view:clear\n";
        echo "4. Make sure you're logged in as admin\n";
        echo "5. Check if your user has permission to view candidates\n";
        echo "\n";

    } else {
        echo "❌ NO CANDIDATES FOUND\n";
        echo "\n";

        if ($total == 0) {
            echo "⚠️  DATABASE IS COMPLETELY EMPTY!\n";
            echo "\n";
            echo "This means the seeder didn't run successfully.\n";
            echo "\n";
            echo "Try running:\n";
            echo "  php run-seeder.php\n";
            echo "\n";
            echo "This will show you detailed output of what's happening.\n";
            echo "\n";

        } else {
            echo "⚠️  Some data exists but no candidates.\n";
            echo "\n";
            echo "Possible issues:\n";
            echo "1. Seeder ran partially then failed\n";
            echo "2. Foreign key constraint errors\n";
            echo "3. Missing required fields\n";
            echo "\n";
            echo "Run this to see detailed seeder output:\n";
            echo "  php run-seeder.php\n";
            echo "\n";
        }
    }

} catch (\Exception $e) {
    echo "\n";
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\n";
}

echo "═══════════════════════════════════════════════════════════\n";
echo "\n";
