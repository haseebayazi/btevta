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
        Schema::table('complaints', function (Blueprint $table) {
            if (!Schema::hasColumn('complaints', 'assigned_at')) {
                $table->timestamp('assigned_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('complaints', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('complaints', 'status_updated_at')) {
                $table->timestamp('status_updated_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('complaints', 'in_progress_at')) {
                $table->timestamp('in_progress_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('complaints', 'status_remarks')) {
                $table->text('status_remarks')->nullable()->after('status');
            }
            if (!Schema::hasColumn('complaints', 'assignment_remarks')) {
                $table->text('assignment_remarks')->nullable()->after('status');
            }
            if (!Schema::hasColumn('complaints', 'closure_remarks')) {
                $table->text('closure_remarks')->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropColumn(['assigned_at', 'resolved_at', 'status_updated_at', 'in_progress_at', 'status_remarks', 'assignment_remarks', 'closure_remarks']);
        });
    }
};
