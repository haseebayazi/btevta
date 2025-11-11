<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Performance indexes for global search and filtering operations
     */
    public function up(): void
    {
        // ========================================================================
        // CANDIDATES TABLE - Core search entity
        // ========================================================================
        Schema::table('candidates', function (Blueprint $table) {
            // Search fields - composite index for common searches
            $table->index(['name', 'status'], 'idx_candidates_name_status');
            $table->index('cnic', 'idx_candidates_cnic');
            $table->index('btevta_id', 'idx_candidates_btevta_id');
            $table->index('phone', 'idx_candidates_phone');
            $table->index('email', 'idx_candidates_email');

            // Foreign keys for filtering
            $table->index(['campus_id', 'status'], 'idx_candidates_campus_status');
            $table->index(['trade_id', 'status'], 'idx_candidates_trade_status');
            $table->index(['batch_id', 'status'], 'idx_candidates_batch_status');

            // Status and district filtering
            $table->index('district', 'idx_candidates_district');
        });

        // ========================================================================
        // TRADES TABLE - Master data search
        // ========================================================================
        Schema::table('trades', function (Blueprint $table) {
            $table->index('name', 'idx_trades_name');
            $table->index('code', 'idx_trades_code');
            $table->index('is_active', 'idx_trades_is_active');
        });

        // ========================================================================
        // CAMPUSES TABLE - Master data search
        // ========================================================================
        Schema::table('campuses', function (Blueprint $table) {
            $table->index('name', 'idx_campuses_name');
            $table->index('code', 'idx_campuses_code');
            $table->index('city', 'idx_campuses_city');
            $table->index('is_active', 'idx_campuses_is_active');
        });

        // ========================================================================
        // OEPS TABLE - Master data search
        // ========================================================================
        Schema::table('oeps', function (Blueprint $table) {
            $table->index('name', 'idx_oeps_name');
            $table->index('code', 'idx_oeps_code');
            $table->index('company_name', 'idx_oeps_company_name');
            $table->index(['country', 'is_active'], 'idx_oeps_country_active');
            $table->index('is_active', 'idx_oeps_is_active');
        });

        // ========================================================================
        // BATCHES TABLE - Search with status
        // ========================================================================
        Schema::table('batches', function (Blueprint $table) {
            $table->index('batch_code', 'idx_batches_batch_code');
            $table->index('name', 'idx_batches_name');
            $table->index(['status', 'campus_id'], 'idx_batches_status_campus');
        });

        // ========================================================================
        // DEPARTURES TABLE - Search by candidate and flight
        // ========================================================================
        Schema::table('departures', function (Blueprint $table) {
            $table->index('flight_number', 'idx_departures_flight_number');
            $table->index('destination', 'idx_departures_destination');
            $table->index(['candidate_id', 'departure_date'], 'idx_departures_candidate_date');
            $table->index('departure_date', 'idx_departures_departure_date');
        });

        // ========================================================================
        // VISA_PROCESSES TABLE - Search by status
        // ========================================================================
        Schema::table('visa_processes', function (Blueprint $table) {
            $table->index('overall_status', 'idx_visa_processes_overall_status');
            $table->index(['candidate_id', 'overall_status'], 'idx_visa_processes_candidate_status');
        });

        // ========================================================================
        // REMITTANCES TABLE - Critical for search performance
        // ========================================================================
        Schema::table('remittances', function (Blueprint $table) {
            $table->index('transaction_reference', 'idx_remittances_transaction_ref');
            $table->index('sender_name', 'idx_remittances_sender_name');

            // Date-based queries (common for reports)
            $table->index(['year', 'month'], 'idx_remittances_year_month');
            $table->index('transfer_date', 'idx_remittances_transfer_date');

            // Status and proof filtering
            $table->index(['status', 'has_proof'], 'idx_remittances_status_proof');
            $table->index(['candidate_id', 'transfer_date'], 'idx_remittances_candidate_date');

            // First remittance tracking
            $table->index('is_first_remittance', 'idx_remittances_is_first');
        });

        // ========================================================================
        // REMITTANCE_ALERTS TABLE - Critical for monitoring
        // ========================================================================
        Schema::table('remittance_alerts', function (Blueprint $table) {
            $table->index('alert_type', 'idx_remittance_alerts_alert_type');
            $table->index('severity', 'idx_remittance_alerts_severity');

            // Unresolved alerts query optimization
            $table->index(['is_resolved', 'severity'], 'idx_remittance_alerts_resolved_severity');
            $table->index(['candidate_id', 'is_resolved'], 'idx_remittance_alerts_candidate_resolved');

            // Read status
            $table->index('is_read', 'idx_remittance_alerts_is_read');
        });

        // ========================================================================
        // ACTIVITY_LOG TABLE - Admin audit queries
        // ========================================================================
        Schema::table('activity_log', function (Blueprint $table) {
            // Already has indexes from Spatie, but add composite ones for filtering
            $table->index(['causer_type', 'causer_id', 'created_at'], 'idx_activity_log_causer_date');
            $table->index(['subject_type', 'subject_id'], 'idx_activity_log_subject');
            $table->index('created_at', 'idx_activity_log_created_at');
        });

        // ========================================================================
        // USERS TABLE - Login and causer lookups
        // ========================================================================
        Schema::table('users', function (Blueprint $table) {
            $table->index('email', 'idx_users_email');
            $table->index(['role', 'is_active'], 'idx_users_role_active');
            $table->index('campus_id', 'idx_users_campus_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop all indexes in reverse order
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_email');
            $table->dropIndex('idx_users_role_active');
            $table->dropIndex('idx_users_campus_id');
        });

        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropIndex('idx_activity_log_causer_date');
            $table->dropIndex('idx_activity_log_subject');
            $table->dropIndex('idx_activity_log_created_at');
        });

        Schema::table('remittance_alerts', function (Blueprint $table) {
            $table->dropIndex('idx_remittance_alerts_alert_type');
            $table->dropIndex('idx_remittance_alerts_severity');
            $table->dropIndex('idx_remittance_alerts_resolved_severity');
            $table->dropIndex('idx_remittance_alerts_candidate_resolved');
            $table->dropIndex('idx_remittance_alerts_is_read');
        });

        Schema::table('remittances', function (Blueprint $table) {
            $table->dropIndex('idx_remittances_transaction_ref');
            $table->dropIndex('idx_remittances_sender_name');
            $table->dropIndex('idx_remittances_year_month');
            $table->dropIndex('idx_remittances_transfer_date');
            $table->dropIndex('idx_remittances_status_proof');
            $table->dropIndex('idx_remittances_candidate_date');
            $table->dropIndex('idx_remittances_is_first');
        });

        Schema::table('visa_processes', function (Blueprint $table) {
            $table->dropIndex('idx_visa_processes_overall_status');
            $table->dropIndex('idx_visa_processes_candidate_status');
        });

        Schema::table('departures', function (Blueprint $table) {
            $table->dropIndex('idx_departures_flight_number');
            $table->dropIndex('idx_departures_destination');
            $table->dropIndex('idx_departures_candidate_date');
            $table->dropIndex('idx_departures_departure_date');
        });

        Schema::table('batches', function (Blueprint $table) {
            $table->dropIndex('idx_batches_batch_code');
            $table->dropIndex('idx_batches_name');
            $table->dropIndex('idx_batches_status_campus');
        });

        Schema::table('oeps', function (Blueprint $table) {
            $table->dropIndex('idx_oeps_name');
            $table->dropIndex('idx_oeps_code');
            $table->dropIndex('idx_oeps_company_name');
            $table->dropIndex('idx_oeps_country_active');
            $table->dropIndex('idx_oeps_is_active');
        });

        Schema::table('campuses', function (Blueprint $table) {
            $table->dropIndex('idx_campuses_name');
            $table->dropIndex('idx_campuses_code');
            $table->dropIndex('idx_campuses_city');
            $table->dropIndex('idx_campuses_is_active');
        });

        Schema::table('trades', function (Blueprint $table) {
            $table->dropIndex('idx_trades_name');
            $table->dropIndex('idx_trades_code');
            $table->dropIndex('idx_trades_is_active');
        });

        Schema::table('candidates', function (Blueprint $table) {
            $table->dropIndex('idx_candidates_name_status');
            $table->dropIndex('idx_candidates_cnic');
            $table->dropIndex('idx_candidates_btevta_id');
            $table->dropIndex('idx_candidates_phone');
            $table->dropIndex('idx_candidates_email');
            $table->dropIndex('idx_candidates_campus_status');
            $table->dropIndex('idx_candidates_trade_status');
            $table->dropIndex('idx_candidates_batch_status');
            $table->dropIndex('idx_candidates_district');
        });
    }
};
