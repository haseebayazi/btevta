<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds all columns required by the Correspondence module that were missing
 * from the original 2025_10_31 migration.
 *
 * Canonical column names chosen to satisfy both the API tests and the
 * DashboardController:
 *   - `type`               instead of `correspondence_type`
 *   - `file_reference_number` for the auto-generated reference
 *   - `priority_level`     for urgency (low / normal / high / urgent)
 *   - `message`            already exists – body text kept as-is
 *   - `sent_at`            already exists – used for all date variants
 *   - `replied_at`         already exists – timestamp when reply was sent
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('correspondences', function (Blueprint $table) {
            // Direction: incoming | outgoing
            $table->string('type')->default('incoming')->after('status');

            // Auto-generated reference number (e.g. COR-202604-00001)
            $table->string('file_reference_number')->nullable()->after('type');

            // Who the correspondence is with / about
            $table->string('organization_type')->nullable()->after('file_reference_number'); // btevta|oep|embassy|campus|government|other
            $table->string('sender')->nullable()->after('organization_type');
            $table->string('recipient')->nullable()->after('sender');

            // Urgency
            $table->string('priority_level')->default('normal')->after('recipient'); // low|normal|high|urgent

            // Extended body / description separate from the short `message`
            $table->text('description')->nullable()->after('priority_level');

            // Internal notes / reply notes
            $table->text('notes')->nullable()->after('description');

            // Deadlines & response tracking
            $table->date('due_date')->nullable()->after('notes');

            // Assignment
            $table->unsignedBigInteger('assigned_to')->nullable()->after('due_date');

            // Audit trail
            $table->unsignedBigInteger('created_by')->nullable()->after('assigned_to');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
        });
    }

    public function down(): void
    {
        Schema::table('correspondences', function (Blueprint $table) {
            $table->dropColumn([
                'type',
                'file_reference_number',
                'organization_type',
                'sender',
                'recipient',
                'priority_level',
                'description',
                'notes',
                'due_date',
                'assigned_to',
                'created_by',
                'updated_by',
            ]);
        });
    }
};
