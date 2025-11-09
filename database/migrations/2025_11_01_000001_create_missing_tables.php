<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Departures Table
        Schema::create('departures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('candidate_id');
            $table->date('departure_date')->nullable();
            $table->string('flight_number')->nullable();
            $table->string('destination')->nullable();
            $table->boolean('pre_departure_briefing')->default(false);
            $table->date('briefing_date')->nullable();
            $table->string('iqama_number')->nullable();
            $table->date('iqama_issue_date')->nullable();
            $table->string('post_arrival_medical_path')->nullable();
            $table->boolean('absher_registered')->default(false);
            $table->date('absher_registration_date')->nullable();
            $table->string('qiwa_id')->nullable();
            $table->boolean('qiwa_activated')->default(false);
            $table->decimal('salary_amount', 10, 2)->nullable();
            $table->date('first_salary_date')->nullable();
            $table->boolean('ninety_day_report_submitted')->default(false);
            $table->text('remarks')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            $table->foreign('candidate_id')->references('id')->on('candidates')->onDelete('cascade');
            $table->index('candidate_id');
        });

        // 2. Undertakings Table
        Schema::create('undertakings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('candidate_id');
            $table->date('undertaking_date');
            $table->string('signed_by')->nullable();
            $table->text('terms')->nullable();
            $table->text('remarks')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            $table->foreign('candidate_id')->references('id')->on('candidates')->onDelete('cascade');
            $table->index('candidate_id');
        });

        // 3. Visa Processes Table
        Schema::create('visa_processes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('candidate_id');
            $table->date('interview_date')->nullable();
            $table->string('interview_status')->nullable();
            $table->text('interview_remarks')->nullable();
            
            $table->date('trade_test_date')->nullable();
            $table->string('trade_test_status')->nullable();
            $table->text('trade_test_remarks')->nullable();
            
            $table->date('takamol_date')->nullable();
            $table->string('takamol_status')->nullable();
            
            $table->date('medical_date')->nullable();
            $table->string('medical_status')->nullable();
            
            $table->date('biometric_date')->nullable();
            $table->string('biometric_status')->nullable();
            
            $table->date('visa_date')->nullable();
            $table->string('visa_number')->nullable();
            $table->string('visa_status')->nullable();
            
            $table->boolean('ticket_uploaded')->default(false);
            $table->date('ticket_date')->nullable();
            $table->string('ticket_path')->nullable();
            
            $table->string('overall_status')->default('pending');
            $table->text('remarks')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            $table->foreign('candidate_id')->references('id')->on('candidates')->onDelete('cascade');
            $table->index(['candidate_id', 'overall_status']);
        });

        // 4. Training Attendances Table
        Schema::create('training_attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('candidate_id');
            $table->unsignedBigInteger('batch_id');
            $table->date('date');
            $table->string('status')->default('present');
            $table->text('remarks')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            $table->foreign('candidate_id')->references('id')->on('candidates')->onDelete('cascade');
            $table->foreign('batch_id')->references('id')->on('batches')->onDelete('cascade');
            $table->index(['candidate_id', 'batch_id', 'date']);
        });

        // 5. Training Assessments Table
        Schema::create('training_assessments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('candidate_id');
            $table->unsignedBigInteger('batch_id');
            $table->date('assessment_date');
            $table->string('assessment_type');
            $table->decimal('score', 5, 2);
            $table->decimal('total_marks', 5, 2)->default(100);
            $table->string('grade')->nullable();
            $table->text('remarks')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            $table->foreign('candidate_id')->references('id')->on('candidates')->onDelete('cascade');
            $table->foreign('batch_id')->references('id')->on('batches')->onDelete('cascade');
            $table->index(['candidate_id', 'batch_id']);
        });

        // 6. Training Certificates Table
        Schema::create('training_certificates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('candidate_id');
            $table->unsignedBigInteger('batch_id');
            $table->string('certificate_number')->unique();
            $table->date('issue_date');
            $table->date('validity_period')->nullable();
            $table->string('certificate_path')->nullable();
            $table->string('status')->default('issued');
            $table->text('remarks')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            $table->foreign('candidate_id')->references('id')->on('candidates')->onDelete('cascade');
            $table->foreign('batch_id')->references('id')->on('batches')->onDelete('cascade');
            $table->index(['candidate_id', 'certificate_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_certificates');
        Schema::dropIfExists('training_assessments');
        Schema::dropIfExists('training_attendances');
        Schema::dropIfExists('visa_processes');
        Schema::dropIfExists('undertakings');
        Schema::dropIfExists('departures');
    }
};