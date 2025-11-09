<?php
require 'vendor/autoload.php';

// Boot Laravel
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Get database connection
$db = $app->make('db');

try {
    echo "Testing database connection...\n";
    $db->connection()->getPdo();
    echo "✓ Database connection successful!\n\n";
    
    // Get all migrations
    $migrationPath = 'database/migrations';
    $files = array_diff(scandir($migrationPath), ['.', '..']);
    
    foreach ($files as $file) {
        if (strpos($file, '.php') !== false) {
            echo "Processing migration: $file\n";
            require $migrationPath . '/' . $file;
        }
    }
    
    echo "\n✓ Database setup complete!\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
    exit(1);
}
