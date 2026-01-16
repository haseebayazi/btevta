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
            // Check if columns don't exist before adding
            if (!Schema::hasColumn('complaints', 'sla_breached')) {
                $table->boolean('sla_breached')->default(false)->after('sla_due_date');
            }

            if (!Schema::hasColumn('complaints', 'sla_breached_at')) {
                $table->timestamp('sla_breached_at')->nullable()->after('sla_breached');
            }

            if (!Schema::hasColumn('complaints', 'escalated_at')) {
                $table->timestamp('escalated_at')->nullable()->after('escalation_level');
            }

            if (!Schema::hasColumn('complaints', 'escalation_reason')) {
                $table->text('escalation_reason')->nullable()->after('escalated_at');
            }

            if (!Schema::hasColumn('complaints', 'escalated_to')) {
                $table->unsignedBigInteger('escalated_to')->nullable()->after('escalation_reason');
            }

            // Indexes for performance
            if (!Schema::hasColumn('complaints', 'sla_breached')) {
                $table->index('sla_breached');
            }

            // Foreign key for escalated_to
            if (Schema::hasColumn('complaints', 'escalated_to')) {
                $table->foreign('escalated_to')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            // Drop foreign key first
            if (Schema::hasColumn('complaints', 'escalated_to')) {
                $table->dropForeign(['escalated_to']);
            }

            // Drop index
            if (Schema::hasColumn('complaints', 'sla_breached')) {
                $table->dropIndex(['sla_breached']);
            }

            // Drop columns
            $columns = [
                'sla_breached',
                'sla_breached_at',
                'escalated_at',
                'escalation_reason',
                'escalated_to',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('complaints', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
