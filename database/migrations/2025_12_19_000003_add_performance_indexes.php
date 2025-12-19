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
        // Helper function to check if index exists
        $indexExists = function ($table, $indexName) {
            $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
            return count($indexes) > 0;
        };

        // CANDIDATES table indexes
        if (Schema::hasTable('candidates')) {
            Schema::table('candidates', function (Blueprint $table) use ($indexExists) {
                // Search indexes
                if (!$indexExists('candidates', 'idx_candidates_phone')) {
                    $table->index('phone', 'idx_candidates_phone');
                }

                if (!$indexExists('candidates', 'idx_candidates_email')) {
                    $table->index('email', 'idx_candidates_email');
                }

                // Composite indexes for common queries
                if (!$indexExists('candidates', 'idx_candidates_status_trade')) {
                    $table->index(['status', 'trade_id'], 'idx_candidates_status_trade');
                }

                if (!$indexExists('candidates', 'idx_candidates_campus_status')) {
                    $table->index(['campus_id', 'status'], 'idx_candidates_campus_status');
                }

                if (!$indexExists('candidates', 'idx_candidates_oep_status')) {
                    $table->index(['oep_id', 'status'], 'idx_candidates_oep_status');
                }

                if (!$indexExists('candidates', 'idx_candidates_batch_status')) {
                    $table->index(['batch_id', 'status'], 'idx_candidates_batch_status');
                }
            });
        }

        // DEPARTURES table indexes
        if (Schema::hasTable('departures')) {
            Schema::table('departures', function (Blueprint $table) use ($indexExists) {
                if (!$indexExists('departures', 'idx_departures_candidate')) {
                    $table->index('candidate_id', 'idx_departures_candidate');
                }

                if (!$indexExists('departures', 'idx_departures_departure_date')) {
                    $table->index('departure_date', 'idx_departures_departure_date');
                }

                if (!$indexExists('departures', 'idx_departures_status')) {
                    $table->index('status', 'idx_departures_status');
                }
            });
        }

        // VISA_PROCESSES table indexes
        if (Schema::hasTable('visa_processes')) {
            Schema::table('visa_processes', function (Blueprint $table) use ($indexExists) {
                if (!$indexExists('visa_processes', 'idx_visa_candidate_status')) {
                    $table->index(['candidate_id', 'overall_status'], 'idx_visa_candidate_status');
                }

                if (!$indexExists('visa_processes', 'idx_visa_oep')) {
                    $table->index('oep_id', 'idx_visa_oep');
                }
            });
        }

        // UNDERTAKINGS table indexes
        if (Schema::hasTable('undertakings')) {
            Schema::table('undertakings', function (Blueprint $table) use ($indexExists) {
                if (!$indexExists('undertakings', 'idx_undertakings_candidate')) {
                    $table->index('candidate_id', 'idx_undertakings_candidate');
                }

                if (!$indexExists('undertakings', 'idx_undertakings_type')) {
                    $table->index('undertaking_type', 'idx_undertakings_type');
                }
            });
        }

        // REGISTRATION_DOCUMENTS table indexes
        if (Schema::hasTable('registration_documents')) {
            Schema::table('registration_documents', function (Blueprint $table) use ($indexExists) {
                if (!$indexExists('registration_documents', 'idx_regdocs_status')) {
                    $table->index('status', 'idx_regdocs_status');
                }

                if (!$indexExists('registration_documents', 'idx_regdocs_type')) {
                    $table->index('document_type', 'idx_regdocs_type');
                }

                if (!$indexExists('registration_documents', 'idx_regdocs_candidate_type')) {
                    $table->index(['candidate_id', 'document_type'], 'idx_regdocs_candidate_type');
                }
            });
        }

        // TRAINING_CLASSES table indexes
        if (Schema::hasTable('training_classes')) {
            Schema::table('training_classes', function (Blueprint $table) use ($indexExists) {
                if (!$indexExists('training_classes', 'idx_classes_campus_status')) {
                    $table->index(['campus_id', 'status'], 'idx_classes_campus_status');
                }

                if (!$indexExists('training_classes', 'idx_classes_batch')) {
                    $table->index('batch_id', 'idx_classes_batch');
                }
            });
        }

        // REMITTANCES table indexes
        if (Schema::hasTable('remittances')) {
            Schema::table('remittances', function (Blueprint $table) use ($indexExists) {
                if (!$indexExists('remittances', 'idx_remittances_candidate_date')) {
                    $table->index(['candidate_id', 'transfer_date'], 'idx_remittances_candidate_date');
                }

                if (!$indexExists('remittances', 'idx_remittances_purpose')) {
                    $table->index('primary_purpose', 'idx_remittances_purpose');
                }

                if (!$indexExists('remittances', 'idx_remittances_status')) {
                    $table->index('status', 'idx_remittances_status');
                }
            });
        }

        // REMITTANCE_BENEFICIARIES table indexes
        if (Schema::hasTable('remittance_beneficiaries')) {
            Schema::table('remittance_beneficiaries', function (Blueprint $table) use ($indexExists) {
                if (!$indexExists('remittance_beneficiaries', 'idx_beneficiaries_primary')) {
                    $table->index(['candidate_id', 'is_primary'], 'idx_beneficiaries_primary');
                }
            });
        }

        // REMITTANCE_RECEIPTS table indexes
        if (Schema::hasTable('remittance_receipts')) {
            Schema::table('remittance_receipts', function (Blueprint $table) use ($indexExists) {
                if (!$indexExists('remittance_receipts', 'idx_receipts_verified')) {
                    $table->index(['remittance_id', 'is_verified'], 'idx_receipts_verified');
                }
            });
        }

        // COMPLAINTS table indexes
        if (Schema::hasTable('complaints')) {
            Schema::table('complaints', function (Blueprint $table) use ($indexExists) {
                if (!$indexExists('complaints', 'idx_complaints_assigned')) {
                    $table->index('assigned_to', 'idx_complaints_assigned');
                }

                if (!$indexExists('complaints', 'idx_complaints_priority_status')) {
                    $table->index(['priority', 'status'], 'idx_complaints_priority_status');
                }
            });
        }

        // ACTIVITY_LOG table indexes (for audit reporting)
        if (Schema::hasTable('activity_log')) {
            Schema::table('activity_log', function (Blueprint $table) use ($indexExists) {
                if (!$indexExists('activity_log', 'idx_activity_subject')) {
                    $table->index(['subject_type', 'subject_id'], 'idx_activity_subject');
                }

                if (!$indexExists('activity_log', 'idx_activity_causer')) {
                    $table->index(['causer_type', 'causer_id'], 'idx_activity_causer');
                }
            });
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
                Schema::table($tableName, function (Blueprint $table) use ($indexes) {
                    foreach ($indexes as $indexName) {
                        try {
                            $table->dropIndex($indexName);
                        } catch (\Exception $e) {
                            // Index may not exist, continue
                        }
                    }
                });
            }
        }
    }
};
