<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$tables = [
    'campuses',
    'oeps',
    'trades',
    'users',
    'batches',
    'candidates',
    'candidate_trainings',
    'candidate_screenings',
    'registration_documents',
    'next_of_kins',
    'undertakings',
    'visa_processes',
    'departures',
    'complaints',
    'correspondence',
    'remittances',
    'document_archives',
];

foreach ($tables as $table) {
    echo "\n═══════════════════════════════════════════════════════════\n";
    echo strtoupper($table) . " TABLE\n";
    echo "═══════════════════════════════════════════════════════════\n";

    try {
        $columns = DB::select("SHOW COLUMNS FROM {$table}");

        foreach ($columns as $column) {
            $null = $column->Null === 'YES' ? 'nullable' : 'required';
            $default = $column->Default ? " (default: {$column->Default})" : '';
            echo sprintf("  %-30s %-20s %s%s\n",
                $column->Field,
                $column->Type,
                $null,
                $default
            );
        }
    } catch (\Exception $e) {
        echo "  ❌ Table not found or error: " . $e->getMessage() . "\n";
    }
}

echo "\n═══════════════════════════════════════════════════════════\n";
echo "Done!\n";
