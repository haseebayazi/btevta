<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Phase 2: Model & Relationship Fixes
 *
 * This migration addresses the following audit findings:
 *
 * 2.1 RemittanceBeneficiary: Add beneficiary_id FK to remittances table
 * 2.7 SoftDeletes: Add deleted_at to EquipmentUsageLog, RemittanceAlert, RemittanceUsageBreakdown
 * 2.8 Inverse Relationships: Add class_enrollments pivot table for Candidate-TrainingClass
 *
 * @see docs/COMPREHENSIVE_AUDIT_REPORT.md
 * @see docs/IMPLEMENTATION_PLAN.md - Phase 2
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // =========================================================================
        // 2.1 FIX REMITTANCE BENEFICIARY RELATIONSHIP
        // =========================================================================
        // Add beneficiary_id column to remittances table for proper FK relationship

        if (Schema::hasTable('remittances') && !Schema::hasColumn('remittances', 'beneficiary_id')) {
            Schema::table('remittances', function (Blueprint $table) {
                $table->unsignedBigInteger('beneficiary_id')->nullable()->after('candidate_id');

                // Add foreign key constraint
                $table->foreign('beneficiary_id')
                    ->references('id')
                    ->on('remittance_beneficiaries')
                    ->nullOnDelete();

                $table->index('beneficiary_id');
            });

            // Log the migration action
            if (Schema::hasTable('activity_log')) {
                DB::table('activity_log')->insert([
                    'log_name' => 'migration',
                    'description' => 'Phase 2.1: Added beneficiary_id column to remittances table',
                    'subject_type' => 'migration',
                    'subject_id' => 0,
                    'causer_type' => null,
                    'causer_id' => null,
                    'properties' => json_encode(['phase' => '2.1', 'action' => 'add_fk']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // =========================================================================
        // 2.7 ADD MISSING SOFT DELETES
        // =========================================================================

        // Add soft deletes to equipment_usage_logs
        if (Schema::hasTable('equipment_usage_logs') && !Schema::hasColumn('equipment_usage_logs', 'deleted_at')) {
            Schema::table('equipment_usage_logs', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Add soft deletes to remittance_alerts
        if (Schema::hasTable('remittance_alerts') && !Schema::hasColumn('remittance_alerts', 'deleted_at')) {
            Schema::table('remittance_alerts', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Add soft deletes to remittance_usage_breakdown (singular - correct table name)
        if (Schema::hasTable('remittance_usage_breakdown') && !Schema::hasColumn('remittance_usage_breakdown', 'deleted_at')) {
            Schema::table('remittance_usage_breakdown', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // =========================================================================
        // 2.8 ADD CLASS ENROLLMENTS PIVOT TABLE (if not exists)
        // =========================================================================
        // This allows proper many-to-many relationship between Candidate and TrainingClass

        if (!Schema::hasTable('class_enrollments')) {
            Schema::create('class_enrollments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('candidate_id');
                $table->unsignedBigInteger('training_class_id');
                $table->date('enrolled_at')->nullable();
                $table->string('status')->default('enrolled'); // enrolled, completed, withdrawn
                $table->timestamps();

                $table->foreign('candidate_id')
                    ->references('id')
                    ->on('candidates')
                    ->onDelete('cascade');

                $table->foreign('training_class_id')
                    ->references('id')
                    ->on('training_classes')
                    ->onDelete('cascade');

                $table->unique(['candidate_id', 'training_class_id']);
                $table->index('status');
            });
        }

        // =========================================================================
        // 2.9 ADD INTEGER CASTS COLUMNS (ensure they exist for proper casting)
        // =========================================================================
        // This is handled at model level, not migration level.
        // The models will be updated to add proper casts.

        // Log completion
        if (Schema::hasTable('activity_log')) {
            DB::table('activity_log')->insert([
                'log_name' => 'migration',
                'description' => 'Phase 2: Model & Relationship Fixes completed',
                'subject_type' => 'migration',
                'subject_id' => 0,
                'causer_type' => null,
                'causer_id' => null,
                'properties' => json_encode(['phase' => '2', 'action' => 'complete']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop class_enrollments table
        Schema::dropIfExists('class_enrollments');

        // Remove soft deletes columns
        if (Schema::hasTable('equipment_usage_logs') && Schema::hasColumn('equipment_usage_logs', 'deleted_at')) {
            Schema::table('equipment_usage_logs', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasTable('remittance_alerts') && Schema::hasColumn('remittance_alerts', 'deleted_at')) {
            Schema::table('remittance_alerts', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasTable('remittance_usage_breakdown') && Schema::hasColumn('remittance_usage_breakdown', 'deleted_at')) {
            Schema::table('remittance_usage_breakdown', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        // Remove beneficiary_id from remittances
        if (Schema::hasTable('remittances') && Schema::hasColumn('remittances', 'beneficiary_id')) {
            Schema::table('remittances', function (Blueprint $table) {
                $table->dropForeign(['beneficiary_id']);
                $table->dropColumn('beneficiary_id');
            });
        }
    }
};
