<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * WASL v3 Enhancement Migration
     * This migration enhances the existing remittances table (created by 2025_11_11)
     * to support both legacy family remittance tracking AND new v3 salary/verification workflow.
     *
     * Strategy: Keep all existing 2025 fields for backward compatibility, add v3 fields
     */
    public function up(): void
    {
        Schema::table('remittances', function (Blueprint $table) {
            // Add campus_id for campus-level tracking (v3)
            $table->foreignId('campus_id')->nullable()->after('departure_id')
                ->constrained('campuses')->onDelete('set null');

            // Add transaction_type for categorization (v3)
            $table->string('transaction_type')->nullable()->after('transaction_reference')
                ->comment('salary, bonus, allowance, reimbursement');

            // Add transaction_date as alias/alternative to transfer_date (v3)
            // Note: We keep both transfer_date (2025) and transaction_date (v3) for compatibility
            $table->date('transaction_date')->nullable()->after('transfer_date')
                ->comment('v3 field - same as transfer_date for salary remittances');

            // Add amount_in_pkr for automatic PKR conversion (v3)
            $table->decimal('amount_in_pkr', 15, 2)->nullable()->after('exchange_rate')
                ->comment('Amount converted to PKR');

            // Add banking details for salary remittances (v3)
            $table->string('account_number')->nullable()->after('receiver_account')
                ->comment('Bank account number');
            $table->string('swift_code')->nullable()->after('account_number')
                ->comment('SWIFT/BIC code for international transfers');
            $table->string('iban')->nullable()->after('swift_code')
                ->comment('IBAN for international transfers');

            // Add purpose field (v3) - different from primary_purpose
            $table->string('purpose')->nullable()->after('primary_purpose')
                ->comment('Detailed purpose: Monthly salary, Overtime, End of service, etc.');

            // Add description field (v3) - rename/alias of purpose_description
            $table->text('description')->nullable()->after('purpose_description')
                ->comment('v3 field - additional description');

            // Add month_year for salary tracking (v3)
            $table->string('month_year', 7)->nullable()->after('month')
                ->comment('Format: YYYY-MM for salary remittances');

            // Enhanced proof document fields (v3) - complement has_proof
            $table->string('proof_document_path')->nullable()->after('has_proof')
                ->comment('Path to proof document file (v3)');
            $table->string('proof_document_type', 10)->nullable()->after('proof_document_path')
                ->comment('File type: pdf, jpg, png (v3)');
            $table->integer('proof_document_size')->nullable()->after('proof_document_type')
                ->comment('File size in bytes (v3)');

            // Enhanced verification workflow (v3) - extends existing verified_by
            $table->enum('verification_status', [
                'pending',
                'verified',
                'rejected',
                'under_review'
            ])->nullable()->after('verified_by')
                ->comment('v3 verification workflow status');

            $table->timestamp('verified_at')->nullable()->after('verification_status')
                ->comment('Timestamp when remittance was verified (v3)');

            $table->text('verification_notes')->nullable()->after('verified_at')
                ->comment('Notes from verifier (v3)');

            $table->text('rejection_reason')->nullable()->after('verification_notes')
                ->comment('Reason if verification was rejected (v3)');

            // Add metadata for flexible additional data (v3)
            $table->json('metadata')->nullable()->after('alert_message')
                ->comment('Additional flexible data storage (v3)');

            // Add new indexes for v3 fields
            $table->index('campus_id');
            $table->index('transaction_type');
            $table->index('transaction_date');
            $table->index('month_year');
            $table->index('verification_status');
            $table->index(['candidate_id', 'month_year']);
            $table->index(['campus_id', 'verification_status']);
        });

        // Update existing status enum to include v3 statuses (if not already present)
        // Note: Laravel doesn't support modifying enums directly, so we'll handle this in the model
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('remittances', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['campus_id']);

            // Drop indexes
            $table->dropIndex(['remittances_campus_id_index']);
            $table->dropIndex(['remittances_transaction_type_index']);
            $table->dropIndex(['remittances_transaction_date_index']);
            $table->dropIndex(['remittances_month_year_index']);
            $table->dropIndex(['remittances_verification_status_index']);
            $table->dropIndex(['remittances_candidate_id_month_year_index']);
            $table->dropIndex(['remittances_campus_id_verification_status_index']);

            // Drop columns (in reverse order)
            $table->dropColumn([
                'metadata',
                'rejection_reason',
                'verification_notes',
                'verified_at',
                'verification_status',
                'proof_document_size',
                'proof_document_type',
                'proof_document_path',
                'month_year',
                'description',
                'purpose',
                'iban',
                'swift_code',
                'account_number',
                'amount_in_pkr',
                'transaction_date',
                'transaction_type',
                'campus_id',
            ]);
        });
    }
};
