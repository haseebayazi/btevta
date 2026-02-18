<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departures', function (Blueprint $table) {
            // PTN JSON details (structured sub-details for PTN)
            if (! Schema::hasColumn('departures', 'ptn_details')) {
                $table->json('ptn_details')->nullable()->after('ptn_deferred_reason');
                // Contains: status, issued_date, expiry_date, evidence_path, notes
            }

            // Protector JSON details (structured sub-details)
            if (! Schema::hasColumn('departures', 'protector_details')) {
                $table->json('protector_details')->nullable()->after('protector_deferred_reason');
                // Contains: applied_date, completion_date, certificate_path, notes
            }

            // Ticket JSON details (full flight information)
            if (! Schema::hasColumn('departures', 'ticket_details')) {
                $table->json('ticket_details')->nullable()->after('flight_type');
                // Contains: airline, flight_number, departure_date, departure_time,
                //           arrival_date, arrival_time, departure_airport, arrival_airport,
                //           ticket_number, ticket_path, pnr
            }

            // Pre-Departure Briefing structured status
            if (! Schema::hasColumn('departures', 'briefing_status')) {
                $table->enum('briefing_status', ['not_scheduled', 'scheduled', 'completed'])
                    ->default('not_scheduled')
                    ->after('ticket_details');
            }

            // Pre-Departure Briefing JSON details
            if (! Schema::hasColumn('departures', 'briefing_details')) {
                $table->json('briefing_details')->nullable()->after('briefing_status');
                // Contains: scheduled_date, completed_date, document_path, video_path,
                //           acknowledgment_signed, acknowledgment_path, notes, conducted_by
            }

            // Structured departure status (additive alongside final_departure_status)
            if (! Schema::hasColumn('departures', 'departure_status')) {
                $table->enum('departure_status', ['processing', 'ready_to_depart', 'departed', 'cancelled'])
                    ->default('processing')
                    ->after('briefing_details');
            }

            // Actual departure timestamp
            if (! Schema::hasColumn('departures', 'departed_at')) {
                $table->timestamp('departed_at')->nullable()->after('departure_status');
            }

            $table->index('briefing_status');
            $table->index('departure_status');
        });
    }

    public function down(): void
    {
        Schema::table('departures', function (Blueprint $table) {
            $table->dropIndex(['briefing_status']);
            $table->dropIndex(['departure_status']);

            $columns = [
                'ptn_details',
                'protector_details',
                'ticket_details',
                'briefing_status',
                'briefing_details',
                'departure_status',
                'departed_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('departures', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
