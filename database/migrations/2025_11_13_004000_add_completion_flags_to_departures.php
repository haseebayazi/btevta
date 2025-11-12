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
        if (Schema::hasTable('departures')) {
            Schema::table('departures', function (Blueprint $table) {
                // Add completion flags if they don't exist
                if (!Schema::hasColumn('departures', 'briefing_completed')) {
                    $table->boolean('briefing_completed')->default(false)->after('briefing_date')
                        ->comment('Flag indicating if pre-departure briefing is completed');
                }

                if (!Schema::hasColumn('departures', 'ready_for_departure')) {
                    $table->boolean('ready_for_departure')->default(false)->after('briefing_completed')
                        ->comment('Flag indicating if candidate is ready to depart');
                }

                // Add indexes for better query performance
                $table->index('briefing_completed', 'departures_briefing_completed_idx');
                $table->index('ready_for_departure', 'departures_ready_for_departure_idx');
                $table->index('departure_date', 'departures_departure_date_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('departures')) {
            Schema::table('departures', function (Blueprint $table) {
                // Drop indexes first
                $table->dropIndex('departures_briefing_completed_idx');
                $table->dropIndex('departures_ready_for_departure_idx');
                $table->dropIndex('departures_departure_date_idx');

                // Drop columns if they exist
                if (Schema::hasColumn('departures', 'briefing_completed')) {
                    $table->dropColumn('briefing_completed');
                }
                if (Schema::hasColumn('departures', 'ready_for_departure')) {
                    $table->dropColumn('ready_for_departure');
                }
            });
        }
    }
};
