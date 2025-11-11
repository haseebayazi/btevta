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
        Schema::create('remittance_beneficiaries', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('candidate_id')->constrained('candidates')->onDelete('cascade');

            // Beneficiary Details
            $table->string('full_name');
            $table->enum('relationship', [
                'spouse',
                'father',
                'mother',
                'son',
                'daughter',
                'brother',
                'sister',
                'other_relative',
                'self'
            ]);
            $table->string('cnic')->nullable(); // National ID
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('district')->nullable();

            // Bank Details
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('iban')->nullable();
            $table->string('mobile_wallet')->nullable(); // JazzCash, Easypaisa, etc.

            // Status
            $table->boolean('is_primary')->default(false); // Primary beneficiary
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('candidate_id');
            $table->index('is_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remittance_beneficiaries');
    }
};
