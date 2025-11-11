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
        Schema::create('remittance_receipts', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('remittance_id')->constrained('remittances')->onDelete('cascade');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->onDelete('set null');

            // File Details
            $table->string('file_name'); // Original filename
            $table->string('file_path'); // Storage path
            $table->string('file_type')->nullable(); // image/pdf/etc
            $table->bigInteger('file_size')->nullable(); // Size in bytes
            $table->enum('document_type', [
                'bank_receipt',
                'transfer_slip',
                'mobile_screenshot',
                'email_confirmation',
                'other'
            ])->default('bank_receipt');

            // Verification
            $table->boolean('is_verified')->default(false);
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('remittance_id');
            $table->index('is_verified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remittance_receipts');
    }
};
