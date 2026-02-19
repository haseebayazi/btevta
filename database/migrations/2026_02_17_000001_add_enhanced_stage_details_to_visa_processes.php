<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Module 5 Enhancement: Add missing stage detail columns and tracking fields.
     *
     * The previous migration (2026_01_18_100016) added:
     * - interview_details, trade_test_details, medical_details, biometric_details
     * - visa_application_status, visa_issued_status, visa_application_details
     *
     * This migration adds:
     * - takamol_details (was missing from original enhancement)
     * - Failed stage tracking columns
     */
    public function up(): void
    {
        Schema::table('visa_processes', function (Blueprint $table) {
            // Takamol details JSON was missing from previous enhancement migration
            if (!Schema::hasColumn('visa_processes', 'takamol_details')) {
                $table->json('takamol_details')->nullable()->after('takamol_status');
            }

            // E-Number status tracking (used by validateStagePrerequisites and updateEnumber)
            if (!Schema::hasColumn('visa_processes', 'enumber_status')) {
                $table->string('enumber_status', 20)->nullable()->after('enumber');
            }

            // Failed stage tracking
            if (!Schema::hasColumn('visa_processes', 'failed_at')) {
                $table->timestamp('failed_at')->nullable()->after('overall_status');
            }
            if (!Schema::hasColumn('visa_processes', 'failed_stage')) {
                $table->string('failed_stage', 50)->nullable()->after('failed_at');
            }
            if (!Schema::hasColumn('visa_processes', 'failure_reason')) {
                $table->text('failure_reason')->nullable()->after('failed_stage');
            }
        });
    }

    public function down(): void
    {
        Schema::table('visa_processes', function (Blueprint $table) {
            $columns = ['takamol_details', 'enumber_status', 'failed_at', 'failed_stage', 'failure_reason'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('visa_processes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
