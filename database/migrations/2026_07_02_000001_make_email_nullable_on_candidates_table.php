<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Make email nullable - candidate registration does not require an email.
        if (DB::getDriverName() === 'sqlite') {
            // SQLite doesn't support MODIFY - recreate table
            Schema::table('candidates', function (Blueprint $table) {
                $table->string('email')->nullable()->change();
            });
        } else {
            // Use raw SQL for MySQL
            DB::statement('ALTER TABLE `candidates` MODIFY `email` VARCHAR(255) NULL');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('candidates', function (Blueprint $table) {
                $table->string('email')->nullable(false)->change();
            });
        } else {
            DB::statement("UPDATE `candidates` SET `email` = 'noreply@theleap.org' WHERE `email` IS NULL");
            DB::statement('ALTER TABLE `candidates` MODIFY `email` VARCHAR(255) NOT NULL');
        }
    }
};
