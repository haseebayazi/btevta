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

        // Add foreign key constraints for audit columns (skip if already exist)
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->foreign('created_by', 'users_created_by_foreign')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        } catch (\Exception $e) {
            // Foreign key may already exist
        }

        try {
            Schema::table('users', function (Blueprint $table) {
                $table->foreign('updated_by', 'users_updated_by_foreign')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        } catch (\Exception $e) {
            // Foreign key may already exist
        }
    }

    public function down(): void
    {
        // Drop foreign keys first (ignore if they don't exist)
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign('users_created_by_foreign');
            });
        } catch (\Exception $e) {
            // Foreign key may not exist
        }

        try {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign('users_updated_by_foreign');
            });
        } catch (\Exception $e) {
            // Foreign key may not exist
        }

        // Drop columns
        Schema::table('users', function (Blueprint $table) {
            $columns = ['failed_login_attempts', 'locked_until', 'password_changed_at', 'created_by', 'updated_by'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
