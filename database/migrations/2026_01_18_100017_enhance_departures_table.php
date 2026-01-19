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
        Schema::table('departures', function (Blueprint $table) {
            // PTN Status
            $table->enum('ptn_status', ['not_applied', 'issued', 'done', 'pending', 'not_issued', 'refused'])
                ->default('not_applied')->after('status');
            $table->timestamp('ptn_issued_at')->nullable()->after('ptn_status');
            $table->text('ptn_deferred_reason')->nullable()->after('ptn_issued_at');

            // Protector Status
            $table->enum('protector_status', ['not_applied', 'applied', 'done', 'pending', 'not_issued', 'refused'])
                ->default('not_applied')->after('ptn_deferred_reason');
            $table->timestamp('protector_applied_at')->nullable()->after('protector_status');
            $table->timestamp('protector_done_at')->nullable()->after('protector_applied_at');
            $table->text('protector_deferred_reason')->nullable()->after('protector_done_at');

            // Ticket Details
            $table->date('ticket_date')->nullable()->after('protector_deferred_reason');
            $table->time('ticket_time')->nullable()->after('ticket_date');
            $table->string('departure_platform', 100)->nullable()->after('ticket_time');
            $table->string('landing_platform', 100)->nullable()->after('departure_platform');
            $table->enum('flight_type', ['direct', 'connected'])->nullable()->after('landing_platform');

            // Pre-Departure Briefing
            $table->string('pre_departure_doc_path', 500)->nullable()->after('flight_type');
            $table->string('pre_departure_video_path', 500)->nullable()->after('pre_departure_doc_path');

            // Final Status
            $table->enum('final_departure_status', ['processing', 'ready_to_depart', 'departed'])
                ->default('processing')->after('pre_departure_video_path');

            $table->index('ptn_status');
            $table->index('protector_status');
            $table->index('final_departure_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departures', function (Blueprint $table) {
            $table->dropIndex(['ptn_status']);
            $table->dropIndex(['protector_status']);
            $table->dropIndex(['final_departure_status']);
            $table->dropColumn([
                'ptn_status',
                'ptn_issued_at',
                'ptn_deferred_reason',
                'protector_status',
                'protector_applied_at',
                'protector_done_at',
                'protector_deferred_reason',
                'ticket_date',
                'ticket_time',
                'departure_platform',
                'landing_platform',
                'flight_type',
                'pre_departure_doc_path',
                'pre_departure_video_path',
                'final_departure_status'
            ]);
        });
    }
};
