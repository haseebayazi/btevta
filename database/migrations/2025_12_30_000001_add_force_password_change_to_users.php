<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add force_password_change column to users table
 *
 * Security Enhancement: This column tracks whether a user must change their
 * password on next login. Used for:
 * - Seeded accounts (must change default password)
 * - Admin-initiated password resets
 * - Password expiry enforcement
 * - Security policy compliance
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Flag to force password change on next login
            $table->boolean('force_password_change')
                  ->default(false)
                  ->after('password_changed_at')
                  ->comment('If true, user must change password on next login');

            // Track when password was last forcibly reset (for audit)
            $table->timestamp('password_force_changed_at')
                  ->nullable()
                  ->after('force_password_change')
                  ->comment('Timestamp of last forced password change');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['force_password_change', 'password_force_changed_at']);
        });
    }
};
