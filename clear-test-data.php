<?php

/**
 * Clear Test Data Script
 *
 * Run this to clear all seeded test data before running the seeder again
 *
 * USAGE: php clear-test-data.php
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "           CLEARING TEST DATA                               \n";
echo "═══════════════════════════════════════════════════════════\n";
echo "\n";

try {
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');

    $tables = [
        'document_archives',
        'remittances',
        'correspondence',
        'complaints',
        'departures',
        'visa_processes',
        'undertakings',
        'next_of_kins',
        'registration_documents',
        'candidate_screenings',
        'candidates',
        'batches',
        'trades',
        'oeps',
        'campuses',
    ];

    foreach ($tables as $table) {
        $count = DB::table($table)->count();
        if ($count > 0) {
            DB::table($table)->truncate();
            echo "✓ Cleared {$table} ({$count} records)\n";
        } else {
            echo "- {$table} (already empty)\n";
        }
    }

    // Clear users except the main admin
    $userCount = DB::table('users')->where('email', '!=', 'admin@btevta.gov.pk')->count();
    if ($userCount > 0) {
        DB::table('users')->where('email', '!=', 'admin@btevta.gov.pk')->delete();
        echo "✓ Cleared users except admin ({$userCount} records)\n";
    } else {
        echo "- users (only admin remains)\n";
    }

    DB::statement('SET FOREIGN_KEY_CHECKS=1;');

    echo "\n";
    echo "═══════════════════════════════════════════════════════════\n";
    echo "✅ All test data cleared successfully!\n";
    echo "\n";
    echo "Now you can run: php artisan db:seed --class=TestDataSeeder\n";
    echo "═══════════════════════════════════════════════════════════\n";
    echo "\n";

} catch (\Exception $e) {
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    echo "\n";
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "\n";
}
