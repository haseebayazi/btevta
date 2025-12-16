<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "Checking campuses table structure...\n\n";

try {
    $columns = DB::select("SHOW COLUMNS FROM campuses");

    echo "Columns in 'campuses' table:\n";
    echo "─────────────────────────────────────────────────\n";

    foreach ($columns as $column) {
        echo sprintf("%-20s %-15s %s\n",
            $column->Field,
            $column->Type,
            $column->Null === 'YES' ? '(nullable)' : '(required)'
        );
    }

    echo "\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
