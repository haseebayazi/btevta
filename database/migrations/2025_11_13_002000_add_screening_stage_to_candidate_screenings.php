<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('candidate_screenings')) {
            Schema::table('candidate_screenings', function (Blueprint $table) {
                // Add screening_stage column if it doesn't exist
                if (!Schema::hasColumn('candidate_screenings', 'screening_stage')) {
                    $table->tinyInteger('screening_stage')->nullable()->after('screening_type')
                        ->comment('Screening stage: 1=Call 1 (Document Collection), 2=Call 2 (Registration), 3=Call 3 (Confirmation)');

                    // Add index for better query performance
                    $table->index('screening_stage', 'candidate_screenings_screening_stage_idx');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('candidate_screenings') && Schema::hasColumn('candidate_screenings', 'screening_stage')) {
            Schema::table('candidate_screenings', function (Blueprint $table) {
                $table->dropIndex('candidate_screenings_screening_stage_idx');
                $table->dropColumn('screening_stage');
            });
        }
    }
};
