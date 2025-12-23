<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the next_of_kins table for storing emergency contact
     * and next of kin information for candidates.
     */
    public function up(): void
    {
        // Skip if table already exists (idempotent migration)
        if (Schema::hasTable('next_of_kins')) {
            return;
        }

        Schema::create('next_of_kins', function (Blueprint $table) {
            $table->id();

            // Foreign key to candidates table
            $table->foreignId('candidate_id')
                  ->constrained('candidates')
                  ->onDelete('cascade');

            // Personal Information
            $table->string('name');
            $table->enum('relationship', [
                'father',
                'mother',
                'spouse',
                'sibling',
                'child',
                'other'
            ])->default('other');

            // Identity & Contact (CNIC stored without formatting)
            $table->string('cnic', 15)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();

            // Additional Information
            $table->string('occupation')->nullable();
            $table->decimal('monthly_income', 12, 2)->nullable();
            $table->string('emergency_contact', 20)->nullable();

            // Audit Trail
            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');
            $table->foreignId('updated_by')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            // Indexes for frequently queried columns
            $table->index('candidate_id');
            $table->index('relationship');
            $table->index(['candidate_id', 'relationship']);
            $table->index('cnic');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('next_of_kins');
    }
};
