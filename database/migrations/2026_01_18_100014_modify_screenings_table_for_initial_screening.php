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
        Schema::table('candidate_screenings', function (Blueprint $table) {
            // New fields for Initial Screening
            $table->boolean('consent_for_work')->default(false)->after('candidate_id');
            $table->enum('placement_interest', ['local', 'international'])->nullable()->after('consent_for_work');
            $table->foreignId('target_country_id')->nullable()->after('placement_interest')->constrained('countries')->nullOnDelete();
            $table->enum('screening_status', ['pending', 'screened', 'deferred'])->default('pending')->after('target_country_id');
            $table->string('evidence_path', 500)->nullable()->after('notes');
            $table->foreignId('reviewer_id')->nullable()->after('evidence_path')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewer_id');

            // Soft-deprecate old columns (keep for historical data)
            // call_1_date, call_1_outcome, call_2_date, call_2_outcome, call_3_date, call_3_outcome
            // DO NOT DROP - mark as deprecated in model

            $table->index('screening_status');
            $table->index('placement_interest');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidate_screenings', function (Blueprint $table) {
            $table->dropForeign(['target_country_id']);
            $table->dropForeign(['reviewer_id']);
            $table->dropIndex(['screening_status']);
            $table->dropIndex(['placement_interest']);
            $table->dropColumn([
                'consent_for_work',
                'placement_interest',
                'target_country_id',
                'screening_status',
                'evidence_path',
                'reviewer_id',
                'reviewed_at'
            ]);
        });
    }
};
