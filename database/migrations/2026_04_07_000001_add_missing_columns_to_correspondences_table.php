<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds all columns required by the Correspondence module that were absent
 * from the original 2025_10_31 migration.
 *
 * Every column addition is guarded with hasColumn() so the migration is
 * idempotent — safe to run even when the test DB already contains columns
 * from a previous version of this file (same filename, evolved content).
 *
 * Canonical column names:
 *   `type`                – direction (incoming|outgoing)
 *   `file_reference_number` – auto-generated COR-YYYYMM-NNNNN
 *   `priority_level`      – urgency (low|normal|high|urgent)
 *   `message`             – body text (existed in original schema)
 *   `sent_at`             – date sent/received (existed in original schema)
 *   `replied_at`          – reply timestamp (existed in original schema)
 */
return new class extends Migration {
    private string $table = 'correspondences';

    public function up(): void
    {
        Schema::table($this->table, function (Blueprint $table) {
            // ── type (direction) ──────────────────────────────────────────────
            // Renamed from `correspondence_type` in a previous iteration.
            // If the old column exists rename it; otherwise create fresh.
            if (Schema::hasColumn($this->table, 'correspondence_type')
                && !Schema::hasColumn($this->table, 'type')) {
                $table->renameColumn('correspondence_type', 'type');
            } elseif (!Schema::hasColumn($this->table, 'type')) {
                $table->string('type')->default('incoming')->after('status');
            }

            if (!Schema::hasColumn($this->table, 'file_reference_number')) {
                $table->string('file_reference_number')->nullable();
            }

            if (!Schema::hasColumn($this->table, 'organization_type')) {
                $table->string('organization_type')->nullable();
            }

            if (!Schema::hasColumn($this->table, 'sender')) {
                $table->string('sender')->nullable();
            }

            if (!Schema::hasColumn($this->table, 'recipient')) {
                $table->string('recipient')->nullable();
            }

            if (!Schema::hasColumn($this->table, 'priority_level')) {
                $table->string('priority_level')->default('normal');
            }

            if (!Schema::hasColumn($this->table, 'description')) {
                $table->text('description')->nullable();
            }

            if (!Schema::hasColumn($this->table, 'notes')) {
                $table->text('notes')->nullable();
            }

            if (!Schema::hasColumn($this->table, 'due_date')) {
                $table->date('due_date')->nullable();
            }

            if (!Schema::hasColumn($this->table, 'assigned_to')) {
                $table->unsignedBigInteger('assigned_to')->nullable();
            }

            if (!Schema::hasColumn($this->table, 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable();
            }

            if (!Schema::hasColumn($this->table, 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table($this->table, function (Blueprint $table) {
            $columns = [
                'type', 'file_reference_number', 'organization_type',
                'sender', 'recipient', 'priority_level', 'description',
                'notes', 'due_date', 'assigned_to', 'created_by', 'updated_by',
            ];

            $existing = array_filter(
                $columns,
                fn ($col) => Schema::hasColumn($this->table, $col)
            );

            if (!empty($existing)) {
                $table->dropColumn(array_values($existing));
            }
        });
    }
};
