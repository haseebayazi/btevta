<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Visa Documents Submission stage fields:
     * - visa_submission_date: date the documents were submitted to the embassy
     * - visa_application_number: tracking/reference number for the submission
     * - embassy: embassy where documents were submitted
     */
    public function up(): void
    {
        Schema::table('visa_processes', function (Blueprint $table) {
            if (! Schema::hasColumn('visa_processes', 'visa_submission_date')) {
                $table->date('visa_submission_date')->nullable()->after('biometric_details');
            }
            if (! Schema::hasColumn('visa_processes', 'visa_application_number')) {
                $table->string('visa_application_number', 100)->nullable()->after('visa_submission_date');
            }
            if (! Schema::hasColumn('visa_processes', 'embassy')) {
                $table->string('embassy')->nullable()->after('visa_application_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('visa_processes', function (Blueprint $table) {
            $columns = [
                'visa_submission_date',
                'visa_application_number',
                'embassy',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('visa_processes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
