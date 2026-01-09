<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * AUDIT FIX: Add missing 'result' column to training_assessments table
 *
 * The 'result' column is used throughout the application:
 * - TrainingService::recordAssessment() saves result
 * - TrainingService::generateCertificate() queries for result='pass'
 * - ReportController::batchSummary() queries for result='pass'
 * - TrainingService::getBatchStatistics() filters by result
 * - TrainingService::getTrainerPerformance() filters by result
 *
 * Without this column, these queries would fail with SQL errors.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_assessments', function (Blueprint $table) {
            // Add result column after grade column
            if (!Schema::hasColumn('training_assessments', 'result')) {
                $table->string('result', 20)->nullable()->after('grade');
            }

            // Add pass_score column for determining pass/fail threshold
            if (!Schema::hasColumn('training_assessments', 'pass_score')) {
                $table->decimal('pass_score', 5, 2)->default(60)->after('total_marks');
            }

            // Add max_score as alias for total_marks (for consistency with code)
            if (!Schema::hasColumn('training_assessments', 'max_score')) {
                $table->decimal('max_score', 5, 2)->nullable()->after('pass_score');
            }

            // Additional columns used in TrainingService::recordAssessment
            if (!Schema::hasColumn('training_assessments', 'theoretical_score')) {
                $table->decimal('theoretical_score', 5, 2)->nullable()->after('score');
            }
            if (!Schema::hasColumn('training_assessments', 'practical_score')) {
                $table->decimal('practical_score', 5, 2)->nullable()->after('theoretical_score');
            }
            if (!Schema::hasColumn('training_assessments', 'total_score')) {
                $table->decimal('total_score', 5, 2)->nullable()->after('practical_score');
            }

            // Note: trainer_id and assessment_location already added in 2025_11_04_add_missing_columns.php
            if (!Schema::hasColumn('training_assessments', 'trainer_id')) {
                $table->unsignedBigInteger('trainer_id')->nullable()->after('remarks');
            }
            if (!Schema::hasColumn('training_assessments', 'assessment_location')) {
                $table->string('assessment_location')->nullable()->after('trainer_id');
            }

            if (!Schema::hasColumn('training_assessments', 'remedial_needed')) {
                $table->boolean('remedial_needed')->default(false)->after('assessment_location');
            }
        });

        // Add index for performance (only if not exists)
        if (!DB::select("SHOW INDEX FROM training_assessments WHERE Key_name = 'training_assessments_candidate_id_assessment_type_result_index'")) {
            Schema::table('training_assessments', function (Blueprint $table) {
                $table->index(['candidate_id', 'assessment_type', 'result']);
            });
        }

        // Update existing records: derive result from score/total_marks
        // If score >= 60% of total_marks, result = 'pass', else 'fail'
        DB::statement("
            UPDATE training_assessments
            SET result = CASE
                WHEN (score / total_marks * 100) >= 60 THEN 'pass'
                ELSE 'fail'
            END,
            total_score = score,
            max_score = total_marks
            WHERE result IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('training_assessments', function (Blueprint $table) {
            // Drop index if it exists
            if (DB::select("SHOW INDEX FROM training_assessments WHERE Key_name = 'training_assessments_candidate_id_assessment_type_result_index'")) {
                $table->dropIndex(['candidate_id', 'assessment_type', 'result']);
            }

            // Drop columns if they exist
            $columnsToCheck = [
                'result',
                'pass_score',
                'max_score',
                'theoretical_score',
                'practical_score',
                'total_score',
                'remedial_needed',
                // Note: We don't drop trainer_id and assessment_location as they were added in earlier migration
            ];

            $columnsToDrop = [];
            foreach ($columnsToCheck as $column) {
                if (Schema::hasColumn('training_assessments', $column)) {
                    $columnsToDrop[] = $column;
                }
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
