<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * CRITICAL FIX: Undertakings table had conflicting schemas across migrations.
     * This migration consolidates the schema to match the controller expectations.
     */
    public function up(): void
    {
        // Drop and recreate the undertakings table with the correct schema
        Schema::dropIfExists('undertakings');

        Schema::create('undertakings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('candidate_id');

            // Fields expected by RegistrationController
            $table->string('undertaking_type')->default('employment'); // employment, financial, behavior, other
            $table->text('content'); // Main undertaking text/content
            $table->string('signature_path')->nullable(); // Path to signature image file
            $table->timestamp('signed_at')->nullable(); // When it was signed
            $table->boolean('is_completed')->default(false); // Whether undertaking is completed
            $table->string('witness_name')->nullable(); // Witness full name
            $table->string('witness_cnic', 13)->nullable(); // Witness CNIC (13 digits)

            // Audit fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->softDeletes();
            $table->timestamps();

            // Foreign keys
            $table->foreign('candidate_id')->references('id')->on('candidates')->onDelete('cascade');

            // Indexes
            $table->index('candidate_id');
            $table->index('undertaking_type');
            $table->index('is_completed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('undertakings');

        // Recreate with the old schema from 2025_11_01_000001_create_missing_tables.php
        Schema::create('undertakings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('candidate_id');
            $table->date('undertaking_date');
            $table->string('signed_by')->nullable();
            $table->text('terms')->nullable();
            $table->text('remarks')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('candidate_id')->references('id')->on('candidates')->onDelete('cascade');
            $table->index('candidate_id');
        });
    }
};
