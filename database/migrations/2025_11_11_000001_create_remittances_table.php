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

            // Foreign Keys
            $table->foreignId('candidate_id')->constrained('candidates')->onDelete('cascade');
            $table->foreignId('departure_id')->nullable()->constrained('departures')->onDelete('set null');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->onDelete('set null');

            // Remittance Details
            $table->string('transaction_reference')->unique(); // Unique transaction ID
            $table->decimal('amount', 12, 2); // Amount in local currency
            $table->string('currency', 3)->default('PKR'); // Currency code (PKR, USD, SAR, etc.)
            $table->decimal('amount_foreign', 12, 2)->nullable(); // Amount in foreign currency
            $table->string('foreign_currency', 3)->nullable(); // Foreign currency code
            $table->decimal('exchange_rate', 10, 4)->nullable(); // Exchange rate used

            // Transfer Information
            $table->date('transfer_date'); // Date of transfer
            $table->string('transfer_method')->nullable(); // Bank, Money Transfer, Mobile Wallet, etc.
            $table->string('sender_name'); // Worker name
            $table->string('sender_location')->nullable(); // Location in foreign country
            $table->string('receiver_name'); // Beneficiary name
            $table->string('receiver_account')->nullable(); // Account/wallet number
            $table->string('bank_name')->nullable(); // Bank or service provider

            // Purpose & Usage
            $table->enum('primary_purpose', [
                'education',
                'health',
                'rent',
                'food',
                'savings',
                'debt_repayment',
                'family_support',
                'business_investment',
                'other'
            ])->default('family_support');
            $table->text('purpose_description')->nullable(); // Detailed description
            $table->boolean('has_proof')->default(false); // Whether proof is uploaded
            $table->date('proof_verified_date')->nullable(); // When proof was verified
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');

            // Status & Tracking
            $table->enum('status', ['pending', 'verified', 'flagged', 'completed'])->default('pending');
            $table->text('notes')->nullable(); // Admin/OEP notes
            $table->text('alert_message')->nullable(); // Automated alerts (if any)

            // Metadata
            $table->boolean('is_first_remittance')->default(false); // Track first remittance
            $table->integer('month_number')->nullable(); // Month since deployment (1, 2, 3...)
            $table->year('year'); // Year of remittance
            $table->tinyInteger('month'); // Month (1-12)
            $table->tinyInteger('quarter')->nullable(); // Quarter (1-4)

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('candidate_id');
            $table->index('transfer_date');
            $table->index('status');
            $table->index(['year', 'month']);
            $table->index('primary_purpose');
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
