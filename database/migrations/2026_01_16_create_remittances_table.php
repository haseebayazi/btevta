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
        Schema::create('remittances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->onDelete('cascade');
            $table->foreignId('departure_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('campus_id')->nullable()->constrained()->onDelete('set null');

            // Transaction Information
            $table->string('transaction_reference')->unique();
            $table->string('transaction_type')->default('salary'); // salary, bonus, allowance, reimbursement
            $table->date('transaction_date');

            // Amount Details
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('SAR'); // SAR, PKR, USD
            $table->decimal('exchange_rate', 10, 4)->nullable();
            $table->decimal('amount_in_pkr', 15, 2)->nullable(); // Converted amount

            // Transfer Details
            $table->string('transfer_method')->nullable(); // bank_transfer, cash, mobile_wallet
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('swift_code')->nullable();
            $table->string('iban')->nullable();

            // Purpose and Description
            $table->string('purpose'); // Monthly salary, Overtime, End of service, etc.
            $table->text('description')->nullable();
            $table->string('month_year')->nullable(); // For salary remittances (e.g., "2026-01")

            // Documentation
            $table->string('proof_document_path')->nullable(); // Receipt/Proof file
            $table->string('proof_document_type')->nullable(); // pdf, jpg, png
            $table->integer('proof_document_size')->nullable(); // bytes

            // Verification
            $table->enum('verification_status', [
                'pending',
                'verified',
                'rejected',
                'under_review'
            ])->default('pending');
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_notes')->nullable();
            $table->text('rejection_reason')->nullable();

            // Status Tracking
            $table->enum('status', [
                'initiated',
                'processing',
                'completed',
                'failed',
                'cancelled'
            ])->default('initiated');

            // Additional Information
            $table->json('metadata')->nullable(); // Additional flexible data
            $table->foreignId('recorded_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('candidate_id');
            $table->index('departure_id');
            $table->index('campus_id');
            $table->index('transaction_date');
            $table->index('verification_status');
            $table->index('status');
            $table->index('month_year');
            $table->index(['candidate_id', 'transaction_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remittances');
    }
};
