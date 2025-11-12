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
        if (Schema::hasTable('visa_processes')) {
            Schema::table('visa_processes', function (Blueprint $table) {
                // Add completion flags if they don't exist
                if (!Schema::hasColumn('visa_processes', 'interview_completed')) {
                    $table->boolean('interview_completed')->default(false)->after('interview_status')
                        ->comment('Flag indicating if interview is completed');
                }

                if (!Schema::hasColumn('visa_processes', 'trade_test_completed')) {
                    $table->boolean('trade_test_completed')->default(false)->after('trade_test_status')
                        ->comment('Flag indicating if trade test is completed');
                }

                if (!Schema::hasColumn('visa_processes', 'medical_completed')) {
                    $table->boolean('medical_completed')->default(false)->after('medical_status')
                        ->comment('Flag indicating if medical examination is completed');
                }

                if (!Schema::hasColumn('visa_processes', 'biometric_completed')) {
                    $table->boolean('biometric_completed')->default(false)->after('biometric_status')
                        ->comment('Flag indicating if biometric is completed');
                }

                if (!Schema::hasColumn('visa_processes', 'visa_issued')) {
                    $table->boolean('visa_issued')->default(false)->after('visa_status')
                        ->comment('Flag indicating if visa has been issued');
                }

                // Add indexes for better query performance
                $table->index('interview_completed', 'visa_processes_interview_completed_idx');
                $table->index('trade_test_completed', 'visa_processes_trade_test_completed_idx');
                $table->index('medical_completed', 'visa_processes_medical_completed_idx');
                $table->index('biometric_completed', 'visa_processes_biometric_completed_idx');
                $table->index('visa_issued', 'visa_processes_visa_issued_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('visa_processes')) {
            Schema::table('visa_processes', function (Blueprint $table) {
                // Drop indexes first
                $table->dropIndex('visa_processes_interview_completed_idx');
                $table->dropIndex('visa_processes_trade_test_completed_idx');
                $table->dropIndex('visa_processes_medical_completed_idx');
                $table->dropIndex('visa_processes_biometric_completed_idx');
                $table->dropIndex('visa_processes_visa_issued_idx');

                // Drop columns if they exist
                if (Schema::hasColumn('visa_processes', 'interview_completed')) {
                    $table->dropColumn('interview_completed');
                }
                if (Schema::hasColumn('visa_processes', 'trade_test_completed')) {
                    $table->dropColumn('trade_test_completed');
                }
                if (Schema::hasColumn('visa_processes', 'medical_completed')) {
                    $table->dropColumn('medical_completed');
                }
                if (Schema::hasColumn('visa_processes', 'biometric_completed')) {
                    $table->dropColumn('biometric_completed');
                }
                if (Schema::hasColumn('visa_processes', 'visa_issued')) {
                    $table->dropColumn('visa_issued');
                }
            });
        }
    }
};
