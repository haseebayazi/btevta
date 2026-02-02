<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds program_id to batches table for AutoBatchService functionality.
     */
    public function up(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            if (!Schema::hasColumn('batches', 'program_id')) {
                $table->foreignId('program_id')->nullable()->after('campus_id')
                    ->constrained('programs')->nullOnDelete();
            }
            if (!Schema::hasColumn('batches', 'intake_period')) {
                $table->string('intake_period')->nullable()->after('end_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            if (Schema::hasColumn('batches', 'program_id')) {
                $table->dropForeign(['program_id']);
                $table->dropColumn('program_id');
            }
            if (Schema::hasColumn('batches', 'intake_period')) {
                $table->dropColumn('intake_period');
            }
        });
    }
};
