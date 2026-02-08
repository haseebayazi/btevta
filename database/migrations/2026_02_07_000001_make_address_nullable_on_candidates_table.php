<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Use raw SQL for reliable TEXT column modification across MySQL versions
        DB::statement('ALTER TABLE `candidates` MODIFY `address` TEXT NULL');
    }

    public function down(): void
    {
        DB::statement("UPDATE `candidates` SET `address` = '' WHERE `address` IS NULL");
        DB::statement('ALTER TABLE `candidates` MODIFY `address` TEXT NOT NULL');
    }
};
