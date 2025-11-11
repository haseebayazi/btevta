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
        Schema::create('remittance_alerts', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('candidate_id')->constrained('candidates')->onDelete('cascade');
            $table->foreignId('remittance_id')->nullable()->constrained('remittances')->onDelete('cascade');

            // Alert Details
            $table->enum('alert_type', [
                'no_remittance_90_days',
                'first_remittance_received',
                'irregular_pattern',
                'large_amount',
                'missing_proof',
                'beneficiary_change',
                'other'
            ]);
            $table->enum('severity', ['info', 'warning', 'critical'])->default('info');
            $table->string('title');
            $table->text('message');
            $table->json('metadata')->nullable(); // Additional data

            // Status
            $table->boolean('is_read')->default(false);
            $table->boolean('is_resolved')->default(false);
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('candidate_id');
            $table->index('alert_type');
            $table->index(['is_read', 'is_resolved']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remittance_alerts');
    }
};
