<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Phase 1: Fix Enum/Database Mismatches
 *
 * This migration addresses the following audit findings:
 *
 * 1.1 CandidateStatus: 'visa' -> 'visa_process' (enum uses visa_process)
 * 1.2 ComplaintPriority: Add priority column with correct default 'normal' (not 'medium')
 * 1.3 TrainingStatus: Expand enum from 4 to 11 values
 * 1.4 VisaStage: Update default from 'pending' to 'initiated'
 *
 * @see docs/COMPREHENSIVE_AUDIT_REPORT.md
 * @see docs/IMPLEMENTATION_PLAN.md - Phase 1
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // =========================================================================
        // 1.1 FIX CANDIDATE STATUS - Update 'visa' to 'visa_process'
        // =========================================================================
        // The CandidateStatus enum uses 'visa_process' but database may have 'visa'

        if (Schema::hasTable('candidates') && Schema::hasColumn('candidates', 'status')) {
            // Update any records with 'visa' to 'visa_process'
            DB::table('candidates')
                ->where('status', 'visa')
                ->update(['status' => 'visa_process']);

            // Log the migration action
            DB::table('activity_log')->insert([
                'log_name' => 'migration',
                'description' => 'Phase 1.1: Updated candidate status from "visa" to "visa_process"',
                'subject_type' => 'migration',
                'subject_id' => 0,
                'causer_type' => null,
                'causer_id' => null,
                'properties' => json_encode(['phase' => '1.1', 'action' => 'enum_fix']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // =========================================================================
        // 1.2 FIX COMPLAINT PRIORITY - Add column with correct default
        // =========================================================================
        // ComplaintPriority enum has: low, normal, high, urgent (NOT 'medium')

        if (Schema::hasTable('complaints')) {
            // Add priority column if it doesn't exist
            if (!Schema::hasColumn('complaints', 'priority')) {
                Schema::table('complaints', function (Blueprint $table) {
                    $table->string('priority', 20)->default('normal')->after('status');
                    $table->index('priority');
                });
            } else {
                // Fix existing column - update 'medium' to 'normal'
                DB::table('complaints')
                    ->where('priority', 'medium')
                    ->update(['priority' => 'normal']);
            }

            // Log the migration action
            DB::table('activity_log')->insert([
                'log_name' => 'migration',
                'description' => 'Phase 1.2: Fixed complaint priority default and updated "medium" to "normal"',
                'subject_type' => 'migration',
                'subject_id' => 0,
                'causer_type' => null,
                'causer_id' => null,
                'properties' => json_encode(['phase' => '1.2', 'action' => 'enum_fix']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // =========================================================================
        // 1.3 FIX TRAINING STATUS - Convert enum to string for flexibility
        // =========================================================================
        // TrainingStatus enum has 11 values but database enum only has 4
        // Solution: Convert to string column to accept all enum values

        if (Schema::hasTable('candidates') && Schema::hasColumn('candidates', 'training_status')) {
            // For MySQL, we need to modify the enum or convert to string
            // Converting to string is safer and more flexible

            // Check if it's currently an enum (MySQL specific)
            $columnType = DB::select("SHOW COLUMNS FROM candidates WHERE Field = 'training_status'");

            if (!empty($columnType) && str_contains($columnType[0]->Type, 'enum')) {
                // MySQL: Modify column from enum to string
                DB::statement("ALTER TABLE candidates MODIFY COLUMN training_status VARCHAR(50) DEFAULT 'pending'");
            }

            // Update any 'ongoing' status to 'in_progress' for consistency
            DB::table('candidates')
                ->where('training_status', 'ongoing')
                ->update(['training_status' => 'in_progress']);

            // Log the migration action
            DB::table('activity_log')->insert([
                'log_name' => 'migration',
                'description' => 'Phase 1.3: Converted training_status from enum to string to support all 11 TrainingStatus values',
                'subject_type' => 'migration',
                'subject_id' => 0,
                'causer_type' => null,
                'causer_id' => null,
                'properties' => json_encode(['phase' => '1.3', 'action' => 'enum_to_string']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // =========================================================================
        // 1.4 FIX VISA STAGE - Update default from 'pending' to 'initiated'
        // =========================================================================
        // VisaStage enum doesn't have 'pending', it starts with 'initiated'

        if (Schema::hasTable('visa_processes')) {
            // Update overall_status default and fix existing 'pending' values
            if (Schema::hasColumn('visa_processes', 'overall_status')) {
                DB::table('visa_processes')
                    ->where('overall_status', 'pending')
                    ->update(['overall_status' => 'initiated']);
            }

            // Update current_stage if it exists
            if (Schema::hasColumn('visa_processes', 'current_stage')) {
                DB::table('visa_processes')
                    ->where('current_stage', 'pending')
                    ->update(['current_stage' => 'initiated']);
            }

            // Update status column if it exists
            if (Schema::hasColumn('visa_processes', 'status')) {
                DB::table('visa_processes')
                    ->where('status', 'pending')
                    ->update(['status' => 'initiated']);
            }

            // Log the migration action
            DB::table('activity_log')->insert([
                'log_name' => 'migration',
                'description' => 'Phase 1.4: Updated visa_processes "pending" to "initiated" to match VisaStage enum',
                'subject_type' => 'migration',
                'subject_id' => 0,
                'causer_type' => null,
                'causer_id' => null,
                'properties' => json_encode(['phase' => '1.4', 'action' => 'enum_fix']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // =========================================================================
        // 1.5 ADDITIONAL: Add 'at_risk' to training candidates
        // =========================================================================
        // TrainingService uses 'at_risk' status - ensure it's supported
        // Since we converted to string, this is now automatically supported

        // Add at_risk_reason column for tracking
        if (Schema::hasTable('candidates') && !Schema::hasColumn('candidates', 'at_risk_reason')) {
            Schema::table('candidates', function (Blueprint $table) {
                $table->text('at_risk_reason')->nullable()->after('training_status');
                $table->timestamp('at_risk_since')->nullable()->after('at_risk_reason');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove at_risk tracking columns
        if (Schema::hasTable('candidates')) {
            Schema::table('candidates', function (Blueprint $table) {
                if (Schema::hasColumn('candidates', 'at_risk_reason')) {
                    $table->dropColumn('at_risk_reason');
                }
                if (Schema::hasColumn('candidates', 'at_risk_since')) {
                    $table->dropColumn('at_risk_since');
                }
            });
        }

        // Note: We don't reverse the data changes as that could cause data loss
        // The 'visa_process' -> 'visa' revert would break enum validation
    }
};
