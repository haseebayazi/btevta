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
        Schema::table('visa_processes', function (Blueprint $table) {
            // JSON columns for stage details
            // Each contains: appointment_date, appointment_time, center, result_status, evidence_path, notes
            $table->json('interview_details')->nullable()->after('interview_status');
            $table->json('trade_test_details')->nullable()->after('trade_test_status');
            $table->json('medical_details')->nullable()->after('medical_status');
            $table->json('biometric_details')->nullable()->after('biometric_status');

            // Visa application status breakdown
            $table->enum('visa_application_status', ['not_applied', 'applied', 'refused'])
                ->default('not_applied')->after('visa_status');
            $table->enum('visa_issued_status', ['pending', 'confirmed', 'refused'])
                ->nullable()->after('visa_application_status');
            $table->json('visa_application_details')->nullable()->after('visa_issued_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visa_processes', function (Blueprint $table) {
            $table->dropColumn([
                'interview_details',
                'trade_test_details',
                'medical_details',
                'biometric_details',
                'visa_application_status',
                'visa_issued_status',
                'visa_application_details'
            ]);
        });
    }
};
