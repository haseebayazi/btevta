<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * SCREENING WORKFLOW: This migration adds the 3-call workflow structure
     * for candidate screening:
     *
     * Call 1 - Document Verification: Verify candidate has required documents
     * Call 2 - Registration Reminder: Remind to come for registration
     * Call 3 - Confirmation: Confirm registration appointment
     *
     * Each call tracks:
     * - Timestamp
     * - Outcome (answered, no_answer, busy, wrong_number, etc.)
     * - Response (positive, negative, callback, etc.)
     * - Notes
     * - Scheduled callback time
     */
    public function up(): void
    {
        if (Schema::hasTable('candidate_screenings')) {
            Schema::table('candidate_screenings', function (Blueprint $table) {
                // Call workflow stage
                if (!Schema::hasColumn('candidate_screenings', 'call_stage')) {
                    $table->enum('call_stage', [
                        'pending',
                        'call_1_document',
                        'call_2_registration',
                        'call_3_confirmation',
                        'completed',
                        'unreachable'
                    ])->default('pending')->after('screening_type');
                }

                // Call 1: Document Verification
                if (!Schema::hasColumn('candidate_screenings', 'call_1_at')) {
                    $table->timestamp('call_1_at')->nullable()->after('call_stage');
                    $table->enum('call_1_outcome', [
                        'answered',
                        'no_answer',
                        'busy',
                        'wrong_number',
                        'switched_off',
                        'not_reachable'
                    ])->nullable()->after('call_1_at');
                    $table->enum('call_1_response', [
                        'documents_ready',
                        'documents_pending',
                        'not_interested',
                        'callback_requested',
                        'no_response'
                    ])->nullable()->after('call_1_outcome');
                    $table->text('call_1_notes')->nullable()->after('call_1_response');
                    $table->foreignId('call_1_by')->nullable()->after('call_1_notes')
                          ->constrained('users')->onDelete('set null');
                }

                // Call 2: Registration Reminder
                if (!Schema::hasColumn('candidate_screenings', 'call_2_at')) {
                    $table->timestamp('call_2_at')->nullable()->after('call_1_by');
                    $table->enum('call_2_outcome', [
                        'answered',
                        'no_answer',
                        'busy',
                        'wrong_number',
                        'switched_off',
                        'not_reachable'
                    ])->nullable()->after('call_2_at');
                    $table->enum('call_2_response', [
                        'will_register',
                        'needs_more_time',
                        'not_interested',
                        'callback_requested',
                        'no_response'
                    ])->nullable()->after('call_2_outcome');
                    $table->text('call_2_notes')->nullable()->after('call_2_response');
                    $table->foreignId('call_2_by')->nullable()->after('call_2_notes')
                          ->constrained('users')->onDelete('set null');
                }

                // Call 3: Confirmation
                if (!Schema::hasColumn('candidate_screenings', 'call_3_at')) {
                    $table->timestamp('call_3_at')->nullable()->after('call_2_by');
                    $table->enum('call_3_outcome', [
                        'answered',
                        'no_answer',
                        'busy',
                        'wrong_number',
                        'switched_off',
                        'not_reachable'
                    ])->nullable()->after('call_3_at');
                    $table->enum('call_3_response', [
                        'confirmed',
                        'rescheduled',
                        'cancelled',
                        'not_interested',
                        'callback_requested',
                        'no_response'
                    ])->nullable()->after('call_3_outcome');
                    $table->text('call_3_notes')->nullable()->after('call_3_response');
                    $table->foreignId('call_3_by')->nullable()->after('call_3_notes')
                          ->constrained('users')->onDelete('set null');
                }

                // Callback scheduling
                if (!Schema::hasColumn('candidate_screenings', 'callback_scheduled_at')) {
                    $table->timestamp('callback_scheduled_at')->nullable()->after('call_3_by');
                    $table->text('callback_reason')->nullable()->after('callback_scheduled_at');
                }

                // Appointment scheduling
                if (!Schema::hasColumn('candidate_screenings', 'registration_appointment_at')) {
                    $table->timestamp('registration_appointment_at')->nullable()->after('callback_reason');
                    $table->string('registration_appointment_campus')->nullable()->after('registration_appointment_at');
                }

                // Total call attempts counter
                if (!Schema::hasColumn('candidate_screenings', 'total_call_attempts')) {
                    $table->unsignedTinyInteger('total_call_attempts')->default(0)->after('registration_appointment_campus');
                }

                // Final outcome tracking
                if (!Schema::hasColumn('candidate_screenings', 'final_outcome')) {
                    $table->enum('final_outcome', [
                        'pending',
                        'registered',
                        'not_interested',
                        'unreachable',
                        'rejected',
                        'postponed'
                    ])->default('pending')->after('total_call_attempts');
                }

                // Indexes for reporting
                $table->index('call_stage', 'idx_screening_call_stage');
                $table->index('final_outcome', 'idx_screening_final_outcome');
                $table->index('callback_scheduled_at', 'idx_screening_callback');
                $table->index('registration_appointment_at', 'idx_screening_appointment');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('candidate_screenings')) {
            Schema::table('candidate_screenings', function (Blueprint $table) {
                // Drop indexes first
                $table->dropIndex('idx_screening_call_stage');
                $table->dropIndex('idx_screening_final_outcome');
                $table->dropIndex('idx_screening_callback');
                $table->dropIndex('idx_screening_appointment');

                // Drop columns
                $columnsToRemove = [
                    'call_stage',
                    'call_1_at', 'call_1_outcome', 'call_1_response', 'call_1_notes', 'call_1_by',
                    'call_2_at', 'call_2_outcome', 'call_2_response', 'call_2_notes', 'call_2_by',
                    'call_3_at', 'call_3_outcome', 'call_3_response', 'call_3_notes', 'call_3_by',
                    'callback_scheduled_at', 'callback_reason',
                    'registration_appointment_at', 'registration_appointment_campus',
                    'total_call_attempts', 'final_outcome',
                ];

                foreach ($columnsToRemove as $column) {
                    if (Schema::hasColumn('candidate_screenings', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
