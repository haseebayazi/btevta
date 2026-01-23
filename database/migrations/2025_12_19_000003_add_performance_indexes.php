<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * PERFORMANCE: This migration adds indexes to frequently queried columns
     * that were identified as missing during the system audit.
     *
     * These indexes will significantly improve query performance for:
     * - Search operations (CNIC, phone, email lookups)
     * - Filtering operations (status, date ranges)
     * - Reporting queries (aggregations by status, campus, trade)
     */
    public function up(): void
    {
        // Helper function to check if index exists (database-agnostic)
        $indexExists = function ($table, $indexName) {
            try {
                $connection = Schema::getConnection();
                $schemaManager = $connection->getDoctrineSchemaManager();
                $indexes = $schemaManager->listTableIndexes($table);
                return isset($indexes[strtolower($indexName)]);
            } catch (\Exception $e) {
                return false;
            }
        };

        // Helper to safely add index only if column exists
        $addIndexIfColumnExists = function ($tableName, $columns, $indexName) use ($indexExists) {
            // Ensure columns is an array
            $cols = is_array($columns) ? $columns : [$columns];

            // Check all columns exist
            foreach ($cols as $col) {
                if (!Schema::hasColumn($tableName, $col)) {
                    return; // Skip if any column doesn't exist
                }
            }

            // Check index doesn't already exist
            if ($indexExists($tableName, $indexName)) {
                return;
            }

            // Add index
            try {
                Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName) {
                    $table->index($columns, $indexName);
                });
            } catch (\Exception $e) {
                // Index creation failed, skip
            }
        };

        // CANDIDATES table indexes
        if (Schema::hasTable('candidates')) {
            $addIndexIfColumnExists('candidates', 'phone', 'idx_candidates_phone');
            $addIndexIfColumnExists('candidates', 'email', 'idx_candidates_email');
            $addIndexIfColumnExists('candidates', ['status', 'trade_id'], 'idx_candidates_status_trade');
            $addIndexIfColumnExists('candidates', ['campus_id', 'status'], 'idx_candidates_campus_status');
            $addIndexIfColumnExists('candidates', ['oep_id', 'status'], 'idx_candidates_oep_status');
            $addIndexIfColumnExists('candidates', ['batch_id', 'status'], 'idx_candidates_batch_status');
        }

        // DEPARTURES table indexes
        if (Schema::hasTable('departures')) {
            $addIndexIfColumnExists('departures', 'candidate_id', 'idx_departures_candidate');
            $addIndexIfColumnExists('departures', 'departure_date', 'idx_departures_departure_date');
            $addIndexIfColumnExists('departures', 'status', 'idx_departures_status');
        }

        // VISA_PROCESSES table indexes
        if (Schema::hasTable('visa_processes')) {
            $addIndexIfColumnExists('visa_processes', ['candidate_id', 'overall_status'], 'idx_visa_candidate_status');
            $addIndexIfColumnExists('visa_processes', 'oep_id', 'idx_visa_oep');
        }

        // UNDERTAKINGS table indexes
        if (Schema::hasTable('undertakings')) {
            $addIndexIfColumnExists('undertakings', 'candidate_id', 'idx_undertakings_candidate');
            $addIndexIfColumnExists('undertakings', 'undertaking_type', 'idx_undertakings_type');
        }

        // REGISTRATION_DOCUMENTS table indexes
        if (Schema::hasTable('registration_documents')) {
            $addIndexIfColumnExists('registration_documents', 'status', 'idx_regdocs_status');
            $addIndexIfColumnExists('registration_documents', 'document_type', 'idx_regdocs_type');
            $addIndexIfColumnExists('registration_documents', ['candidate_id', 'document_type'], 'idx_regdocs_candidate_type');
        }

        // TRAINING_CLASSES table indexes
        if (Schema::hasTable('training_classes')) {
            $addIndexIfColumnExists('training_classes', ['campus_id', 'status'], 'idx_classes_campus_status');
            $addIndexIfColumnExists('training_classes', 'batch_id', 'idx_classes_batch');
        }

        // REMITTANCES table indexes
        if (Schema::hasTable('remittances')) {
            $addIndexIfColumnExists('remittances', ['candidate_id', 'transfer_date'], 'idx_remittances_candidate_date');
            $addIndexIfColumnExists('remittances', 'primary_purpose', 'idx_remittances_purpose');
            $addIndexIfColumnExists('remittances', 'status', 'idx_remittances_status');
        }

        // REMITTANCE_BENEFICIARIES table indexes
        if (Schema::hasTable('remittance_beneficiaries')) {
            $addIndexIfColumnExists('remittance_beneficiaries', ['candidate_id', 'is_primary'], 'idx_beneficiaries_primary');
        }

        // REMITTANCE_RECEIPTS table indexes
        if (Schema::hasTable('remittance_receipts')) {
            $addIndexIfColumnExists('remittance_receipts', ['remittance_id', 'is_verified'], 'idx_receipts_verified');
        }

        // COMPLAINTS table indexes
        if (Schema::hasTable('complaints')) {
            $addIndexIfColumnExists('complaints', 'assigned_to', 'idx_complaints_assigned');
            $addIndexIfColumnExists('complaints', ['priority', 'status'], 'idx_complaints_priority_status');
        }

        // ACTIVITY_LOG table indexes (for audit reporting)
        if (Schema::hasTable('activity_log')) {
            $addIndexIfColumnExists('activity_log', ['subject_type', 'subject_id'], 'idx_activity_subject');
            $addIndexIfColumnExists('activity_log', ['causer_type', 'causer_id'], 'idx_activity_causer');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $indexesToDrop = [
            'candidates' => [
                'idx_candidates_phone',
                'idx_candidates_email',
                'idx_candidates_status_trade',
                'idx_candidates_campus_status',
                'idx_candidates_oep_status',
                'idx_candidates_batch_status',
            ],
            'departures' => [
                'idx_departures_candidate',
                'idx_departures_departure_date',
                'idx_departures_status',
            ],
            'visa_processes' => [
                'idx_visa_candidate_status',
                'idx_visa_oep',
            ],
            'undertakings' => [
                'idx_undertakings_candidate',
                'idx_undertakings_type',
            ],
            'registration_documents' => [
                'idx_regdocs_status',
                'idx_regdocs_type',
                'idx_regdocs_candidate_type',
            ],
            'training_classes' => [
                'idx_classes_campus_status',
                'idx_classes_batch',
            ],
            'remittances' => [
                'idx_remittances_candidate_date',
                'idx_remittances_purpose',
                'idx_remittances_status',
            ],
            'remittance_beneficiaries' => [
                'idx_beneficiaries_primary',
            ],
            'remittance_receipts' => [
                'idx_receipts_verified',
            ],
            'complaints' => [
                'idx_complaints_assigned',
                'idx_complaints_priority_status',
            ],
            'activity_log' => [
                'idx_activity_subject',
                'idx_activity_causer',
            ],
        ];

        foreach ($indexesToDrop as $tableName => $indexes) {
            if (Schema::hasTable($tableName)) {
                foreach ($indexes as $indexName) {
                    try {
                        Schema::table($tableName, function (Blueprint $table) use ($indexName) {
                            $table->dropIndex($indexName);
                        });
                    } catch (\Exception $e) {
                        // Index may not exist, continue
                    }
                }
            }
        }
    }
};
