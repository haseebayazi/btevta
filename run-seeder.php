<?php

/**
 * Database Diagnostic and Seeder Runner
 *
 * This script will:
 * 1. Check database connection
 * 2. Show current data counts
 * 3. Run the seeder with verbose output
 * 4. Show what was created
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
use Illuminate\Support\Facades\Artisan;

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║         BTEVTA - Database Diagnostic & Seeder             ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Step 1: Check database connection
echo "STEP 1: Checking Database Connection...\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
try {
    $pdo = DB::connection()->getPdo();
    echo "✅ Database connected successfully!\n";
    echo "   Driver: " . DB::connection()->getDriverName() . "\n";
    echo "   Database: " . DB::connection()->getDatabaseName() . "\n";
    echo "\n";
} catch (\Exception $e) {
    echo "❌ Database connection failed!\n";
    echo "   Error: " . $e->getMessage() . "\n";
    echo "\n";
    echo "Check your .env file:\n";
    echo "   DB_CONNECTION=" . env('DB_CONNECTION') . "\n";
    echo "   DB_HOST=" . env('DB_HOST') . "\n";
    echo "   DB_DATABASE=" . env('DB_DATABASE') . "\n";
    echo "   DB_USERNAME=" . env('DB_USERNAME') . "\n";
    exit(1);
}

// Step 2: Count BEFORE seeding
echo "STEP 2: Current Database Counts (BEFORE seeding)...\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

function getCounts() {
    return [
        'users' => User::count(),
        'campuses' => Campus::count(),
        'trades' => Trade::count(),
        'oeps' => Oep::count(),
        'batches' => Batch::count(),
        'candidates' => Candidate::count(),
    ];
}

$beforeCounts = getCounts();

foreach ($beforeCounts as $table => $count) {
    echo "   " . str_pad(ucfirst($table), 15) . ": $count\n";
}
echo "\n";

// Step 3: Run the seeder
echo "STEP 3: Running TestDataSeeder...\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

try {
    // Run seeder with output
    $exitCode = Artisan::call('db:seed', [
        '--class' => 'TestDataSeeder',
        '--force' => true
    ]);

    // Get seeder output
    $output = Artisan::output();
    echo $output;

    if ($exitCode === 0) {
        echo "\n✅ Seeder completed successfully!\n";
    } else {
        echo "\n❌ Seeder failed with exit code: $exitCode\n";
    }
    echo "\n";

} catch (\Exception $e) {
    echo "❌ Seeder encountered an error!\n";
    echo "   Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

// Step 4: Count AFTER seeding
echo "STEP 4: Database Counts (AFTER seeding)...\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$afterCounts = getCounts();

foreach ($afterCounts as $table => $count) {
    $before = $beforeCounts[$table];
    $added = $count - $before;
    $status = $added > 0 ? "✅ (+$added)" : "⚠️  (no change)";
    echo "   " . str_pad(ucfirst($table), 15) . ": $count $status\n";
}
echo "\n";

// Step 5: Verify candidates by status
if ($afterCounts['candidates'] > 0) {
    echo "STEP 5: Candidates by Status...\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

    $statuses = DB::table('candidates')
        ->select('status', DB::raw('count(*) as count'))
        ->groupBy('status')
        ->get();

    foreach ($statuses as $status) {
        echo "   " . str_pad(ucfirst(str_replace('_', ' ', $status->status)), 25) . ": {$status->count}\n";
    }
    echo "\n";
} else {
    echo "STEP 5: Verification...\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "⚠️  WARNING: No candidates were created!\n";
    echo "\n";
    echo "Possible issues:\n";
    echo "1. Check if there were errors in the seeder output above\n";
    echo "2. Verify all required tables exist (run: php artisan migrate)\n";
    echo "3. Check Laravel logs: storage/logs/laravel.log\n";
    echo "4. Try running: php artisan migrate:fresh\n";
    echo "   Then run this script again\n";
    echo "\n";
}

// Step 6: Sample data check
if ($afterCounts['candidates'] > 0) {
    echo "STEP 6: Sample Candidate Data...\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

    $samples = Candidate::with(['campus', 'trade'])->take(3)->get();

    foreach ($samples as $candidate) {
        echo "   • " . $candidate->name . "\n";
        echo "     BTEVTA ID: " . $candidate->btevta_id . "\n";
        echo "     Status: " . $candidate->status . "\n";
        echo "     Campus: " . ($candidate->campus->name ?? 'N/A') . "\n";
        echo "     Trade: " . ($candidate->trade->name ?? 'N/A') . "\n";
        echo "\n";
    }
}

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║                    DIAGNOSTIC COMPLETE                     ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

if ($afterCounts['candidates'] > 0) {
    echo "✅ SUCCESS! Test data has been populated.\n";
    echo "\n";
    echo "Next steps:\n";
    echo "1. Login to your application\n";
    echo "2. Go to Candidates menu\n";
    echo "3. You should see {$afterCounts['candidates']} candidates\n";
    echo "\n";
} else {
    echo "⚠️  No data was created. Please review the output above for errors.\n";
    echo "\n";
}

echo "⚠️  DELETE THIS FILE (run-seeder.php) AFTER USE!\n";
echo "\n";
