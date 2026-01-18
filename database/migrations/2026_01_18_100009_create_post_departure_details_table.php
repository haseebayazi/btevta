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
        Schema::create('post_departure_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('departure_id')->constrained()->cascadeOnDelete();

            // Residency & Identity
            $table->string('residency_proof_path', 500)->nullable(); // Iqama
            $table->string('residency_number', 50)->nullable();
            $table->date('residency_expiry')->nullable();
            $table->string('foreign_license_path', 500)->nullable();
            $table->string('foreign_license_number', 50)->nullable();
            $table->string('foreign_mobile_number', 20)->nullable();
            $table->string('foreign_bank_name', 100)->nullable();
            $table->string('foreign_bank_account', 50)->nullable();
            $table->string('tracking_app_registration', 100)->nullable(); // Absher ID
            $table->string('final_contract_path', 500)->nullable(); // Qiwa

            // Final Employment Details
            $table->string('company_name', 200)->nullable();
            $table->string('employer_name', 100)->nullable();
            $table->string('employer_designation', 100)->nullable();
            $table->string('employer_contact', 50)->nullable();
            $table->string('work_location', 200)->nullable();
            $table->decimal('final_salary', 12, 2)->nullable();
            $table->string('salary_currency', 3)->default('SAR');
            $table->text('final_job_terms')->nullable();
            $table->date('job_commencement_date')->nullable();
            $table->text('special_conditions')->nullable();

            $table->timestamps();

            $table->unique('departure_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_departure_details');
    }
};
