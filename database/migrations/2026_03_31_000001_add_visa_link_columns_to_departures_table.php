<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add visa_process_id and oep_id to departures table.
 *
 * These FK columns are referenced in Departure::$fillable and are set by
 * VisaProcessingController when auto-creating a departure record upon visa
 * completion. They were missing from all previous migrations.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departures', function (Blueprint $table) {
            if (! Schema::hasColumn('departures', 'visa_process_id')) {
                $table->unsignedBigInteger('visa_process_id')
                    ->nullable()
                    ->after('candidate_id');
                $table->foreign('visa_process_id')
                    ->references('id')->on('visa_processes')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('departures', 'oep_id')) {
                $table->unsignedBigInteger('oep_id')
                    ->nullable()
                    ->after('visa_process_id');
                $table->foreign('oep_id')
                    ->references('id')->on('oeps')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('departures', function (Blueprint $table) {
            if (Schema::hasColumn('departures', 'oep_id')) {
                $table->dropForeign(['oep_id']);
                $table->dropColumn('oep_id');
            }

            if (Schema::hasColumn('departures', 'visa_process_id')) {
                $table->dropForeign(['visa_process_id']);
                $table->dropColumn('visa_process_id');
            }
        });
    }
};
