<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds two columns that the visa-processing edit/show forms were already
     * submitting but had nowhere to persist (hence values appeared blank again
     * after saving):
     * - enumber_date: date the E-Number was generated/verified
     * - etimad_center: the Etimad biometric enrolment center
     */
    public function up(): void
    {
        Schema::table('visa_processes', function (Blueprint $table) {
            if (! Schema::hasColumn('visa_processes', 'enumber_date')) {
                $table->date('enumber_date')->nullable()->after('enumber_status');
            }
            if (! Schema::hasColumn('visa_processes', 'etimad_center')) {
                $table->string('etimad_center')->nullable()->after('etimad_appointment_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('visa_processes', function (Blueprint $table) {
            $columns = [
                'enumber_date',
                'etimad_center',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('visa_processes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
