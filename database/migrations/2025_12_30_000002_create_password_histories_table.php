<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create password_histories table
 *
 * Security Feature: Stores hashed passwords to prevent password reuse.
 * This helps enforce password rotation policies required for government systems.
 *
 * The number of passwords to keep is configurable via PASSWORD_HISTORY_COUNT.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('password_histories', function (Blueprint $table) {
            $table->id();

            // User relationship
            $table->foreignId('user_id')
                  ->constrained()
                  ->onDelete('cascade')
                  ->comment('User who owns this password history');

            // Hashed password (bcrypt hash is 60 chars, but we allow more for future algorithms)
            $table->string('password', 255)
                  ->comment('Hashed password for comparison');

            // Timestamp when this password was set
            $table->timestamp('created_at')
                  ->useCurrent()
                  ->comment('When this password was created');

            // Index for faster lookups
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('password_histories');
    }
};
