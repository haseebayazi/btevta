<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FIX: Add missing softDeletes columns to remittance tables
 *
 * The models RemittanceAlert and RemittanceUsageBreakdown use SoftDeletes trait
 * but their original migrations did not include the deleted_at column.
 *
 * Previous migration (phase2_model_relationship_fixes) had a typo:
 * - Used 'remittance_usage_breakdowns' (plural) instead of 'remittance_usage_breakdown' (singular)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add deleted_at to remittance_alerts if missing
        if (Schema::hasTable('remittance_alerts') && !Schema::hasColumn('remittance_alerts', 'deleted_at')) {
            Schema::table('remittance_alerts', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Add deleted_at to remittance_usage_breakdown (singular - correct table name) if missing
        if (Schema::hasTable('remittance_usage_breakdown') && !Schema::hasColumn('remittance_usage_breakdown', 'deleted_at')) {
            Schema::table('remittance_usage_breakdown', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Add created_by and updated_by to remittance_alerts if missing
        if (Schema::hasTable('remittance_alerts')) {
            Schema::table('remittance_alerts', function (Blueprint $table) {
                if (!Schema::hasColumn('remittance_alerts', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable()->after('resolution_notes');
                }
                if (!Schema::hasColumn('remittance_alerts', 'updated_by')) {
                    $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
                }
            });
        }

        // Add created_by and updated_by to remittance_usage_breakdown if missing
        if (Schema::hasTable('remittance_usage_breakdown')) {
            Schema::table('remittance_usage_breakdown', function (Blueprint $table) {
                if (!Schema::hasColumn('remittance_usage_breakdown', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable()->after('has_proof');
                }
                if (!Schema::hasColumn('remittance_usage_breakdown', 'updated_by')) {
                    $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('remittance_alerts')) {
            Schema::table('remittance_alerts', function (Blueprint $table) {
                if (Schema::hasColumn('remittance_alerts', 'deleted_at')) {
                    $table->dropSoftDeletes();
                }
                if (Schema::hasColumn('remittance_alerts', 'created_by')) {
                    $table->dropColumn('created_by');
                }
                if (Schema::hasColumn('remittance_alerts', 'updated_by')) {
                    $table->dropColumn('updated_by');
                }
            });
        }

        if (Schema::hasTable('remittance_usage_breakdown')) {
            Schema::table('remittance_usage_breakdown', function (Blueprint $table) {
                if (Schema::hasColumn('remittance_usage_breakdown', 'deleted_at')) {
                    $table->dropSoftDeletes();
                }
                if (Schema::hasColumn('remittance_usage_breakdown', 'created_by')) {
                    $table->dropColumn('created_by');
                }
                if (Schema::hasColumn('remittance_usage_breakdown', 'updated_by')) {
                    $table->dropColumn('updated_by');
                }
            });
        }
    }
};
