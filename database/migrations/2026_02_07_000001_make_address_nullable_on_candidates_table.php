<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Make address nullable - compatible with SQLite for testing
        if (DB::getDriverName() === 'sqlite') {
            // SQLite doesn't support MODIFY - recreate table
            Schema::table('candidates', function (Blueprint $table) {
                $table->text('address')->nullable()->change();
            });
        } else {
            // Use raw SQL for MySQL
            DB::statement('ALTER TABLE `candidates` MODIFY `address` TEXT NULL');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('candidates', function (Blueprint $table) {
                $table->text('address')->nullable(false)->change();
            });
        } else {
            DB::statement("UPDATE `candidates` SET `address` = '' WHERE `address` IS NULL");
            DB::statement('ALTER TABLE `candidates` MODIFY `address` TEXT NOT NULL');
        }
    }
};
