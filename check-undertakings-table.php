<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\nChecking undertakings table structure:\n";
echo "═══════════════════════════════════════════════════════════\n";

try {
    $columns = DB::select("SHOW COLUMNS FROM undertakings");

    foreach ($columns as $column) {
        $null = $column->Null === 'YES' ? 'nullable' : 'required';
        echo sprintf("  %-25s %-20s %s\n",
            $column->Field,
            $column->Type,
            $null
        );
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n";
