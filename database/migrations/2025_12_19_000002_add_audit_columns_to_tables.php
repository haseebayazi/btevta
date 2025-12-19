<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * AUDIT COMPLIANCE: This migration adds created_by and updated_by columns
     * to all core business tables that were missing these audit trail fields.
     *
     * Government audit standards require tracking WHO created and modified records.
     */
    public function up(): void
    {
        // Tables that need audit columns added
        $tables = [
            'campuses',
            'oeps',
            'trades',
            'departures',
            'visa_processes',
            'training_attendances',
            'training_assessments',
            'training_certificates',
            'correspondences',
            'registration_documents',
            'remittance_beneficiaries',
            'remittance_receipts',
            'remittance_usage_breakdown',
            'remittance_alerts',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    // Only add if column doesn't exist
                    if (!Schema::hasColumn($tableName, 'created_by')) {
                        $table->foreignId('created_by')
                              ->nullable()
                              ->after('updated_at')
                              ->constrained('users')
                              ->onDelete('set null');
                    }

                    if (!Schema::hasColumn($tableName, 'updated_by')) {
                        $table->foreignId('updated_by')
                              ->nullable()
                              ->after('created_by')
                              ->constrained('users')
                              ->onDelete('set null');
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'campuses',
            'oeps',
            'trades',
            'departures',
            'visa_processes',
            'training_attendances',
            'training_assessments',
            'training_certificates',
            'correspondences',
            'registration_documents',
            'remittance_beneficiaries',
            'remittance_receipts',
            'remittance_usage_breakdown',
            'remittance_alerts',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    // Drop foreign keys first
                    if (Schema::hasColumn($tableName, 'created_by')) {
                        $table->dropForeign([$tableName . '_created_by_foreign']);
                        $table->dropColumn('created_by');
                    }

                    if (Schema::hasColumn($tableName, 'updated_by')) {
                        $table->dropForeign([$tableName . '_updated_by_foreign']);
                        $table->dropColumn('updated_by');
                    }
                });
            }
        }
    }
};
