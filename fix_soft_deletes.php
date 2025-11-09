<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

DB::statement('ALTER TABLE campuses ADD COLUMN deleted_at TIMESTAMP NULL');
DB::statement('ALTER TABLE oeps ADD COLUMN deleted_at TIMESTAMP NULL');
DB::statement('ALTER TABLE trades ADD COLUMN deleted_at TIMESTAMP NULL');
DB::statement('ALTER TABLE batches ADD COLUMN deleted_at TIMESTAMP NULL');

echo "✓ Soft deletes columns added!\n";
