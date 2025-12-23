<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SECURITY FIX: Add security and audit columns to users table
 * - failed_login_attempts: Track failed login attempts for lockout
 * - locked_until: Timestamp for account lockout expiry
 * - created_by/updated_by: Audit trail for user modifications
 * - password_changed_at: Track when password was last changed
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Account lockout columns
            if (!Schema::hasColumn('users', 'failed_login_attempts')) {
                $table->unsignedTinyInteger('failed_login_attempts')->default(0)->after('is_active');
            }
            if (!Schema::hasColumn('users', 'locked_until')) {
                $table->timestamp('locked_until')->nullable()->after('failed_login_attempts');
            }

            // Password security
            if (!Schema::hasColumn('users', 'password_changed_at')) {
                $table->timestamp('password_changed_at')->nullable()->after('locked_until');
            }

            // Audit columns
            if (!Schema::hasColumn('users', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('updated_at');
            }
            if (!Schema::hasColumn('users', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }
        });

        // Add foreign key constraints for audit columns
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'created_by_constraint')) {
                $table->foreign('created_by', 'users_created_by_foreign')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('users', 'updated_by_constraint')) {
                $table->foreign('updated_by', 'users_updated_by_foreign')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign('users_created_by_foreign');
            $table->dropForeign('users_updated_by_foreign');

            // Drop columns
            $table->dropColumn([
                'failed_login_attempts',
                'locked_until',
                'password_changed_at',
                'created_by',
                'updated_by',
            ]);
        });
    }
};
