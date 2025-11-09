<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ==========================================
        // STEP 1: CREATE MISSING TABLES
        // ==========================================
        
        // Create next_of_kins table if it doesn't exist
        if (!Schema::hasTable('next_of_kins')) {
            Schema::create('next_of_kins', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('candidate_id')->nullable();
                $table->string('name');
                $table->string('relationship');
                $table->text('address')->nullable();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->string('occupation')->nullable();
                $table->string('cnic')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->index('candidate_id');
            });
        }

        // Create candidate_screenings table if it doesn't exist
        if (!Schema::hasTable('candidate_screenings')) {
            Schema::create('candidate_screenings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('candidate_id');
                $table->date('screening_date');
                $table->enum('call_number', ['1', '2', '3'])->default('1');
                $table->enum('status', ['pending', 'contacted', 'not_contacted', 'no_response'])->default('pending');
                $table->enum('result', ['pass', 'fail', 'pending'])->default('pending');
                $table->text('remarks')->nullable();
                $table->string('evidence_path')->nullable();
                $table->unsignedBigInteger('screened_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->foreign('candidate_id')->references('id')->on('candidates')->onDelete('cascade');
                $table->index(['candidate_id', 'call_number']);
            });
        }

        // Create registrations table if it doesn't exist
        if (!Schema::hasTable('registrations')) {
            Schema::create('registrations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('candidate_id')->unique();
                $table->date('registration_date');
                $table->string('registration_number')->unique();
                $table->string('photo_path')->nullable();
                $table->enum('status', ['pending', 'completed', 'rejected'])->default('pending');
                $table->text('remarks')->nullable();
                $table->unsignedBigInteger('registered_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->foreign('candidate_id')->references('id')->on('candidates')->onDelete('cascade');
            });
        }

        // Create registration_documents table if it doesn't exist
        if (!Schema::hasTable('registration_documents')) {
            Schema::create('registration_documents', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('candidate_id');
                $table->enum('document_type', ['cnic', 'passport', 'educational_certificate', 'medical_certificate', 'police_certificate', 'domicile', 'other']);
                $table->string('document_path');
                $table->date('expiry_date')->nullable();
                $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
                $table->text('remarks')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->foreign('candidate_id')->references('id')->on('candidates')->onDelete('cascade');
                $table->index(['candidate_id', 'document_type']);
            });
        }

        // Create undertakings table if it doesn't exist
        if (!Schema::hasTable('undertakings')) {
            Schema::create('undertakings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('candidate_id')->unique();
                $table->text('undertaking_text');
                $table->string('signature_path')->nullable();
                $table->date('signed_date')->nullable();
                $table->boolean('is_signed')->default(false);
                $table->timestamps();
                $table->softDeletes();
                
                $table->foreign('candidate_id')->references('id')->on('candidates')->onDelete('cascade');
            });
        }

        // Create training_certificates table if it doesn't exist
        if (!Schema::hasTable('training_certificates')) {
            Schema::create('training_certificates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('candidate_id')->unique();
                $table->string('certificate_number')->unique();
                $table->date('issue_date');
                $table->string('certificate_path')->nullable();
                $table->unsignedBigInteger('issued_by')->nullable();
                $table->unsignedBigInteger('trainer_id')->nullable();
                $table->string('trainer_signature_path')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->foreign('candidate_id')->references('id')->on('candidates')->onDelete('cascade');
            });
        }

        // NOTE: document_archives table is created in the main migration (2025_01_01_000000_create_all_tables.php)
        // This section is commented out to avoid duplication
        // If you need additional columns, add them below in STEP 2

        // Create document_archives table if it doesn't exist
        // if (!Schema::hasTable('document_archives')) {
        //     Schema::create('document_archives', function (Blueprint $table) {
        //         $table->id();
        //         $table->unsignedBigInteger('candidate_id')->nullable();
        //         $table->unsignedBigInteger('campus_id')->nullable();
        //         $table->unsignedBigInteger('trade_id')->nullable();
        //         $table->unsignedBigInteger('oep_id')->nullable();
        //         $table->string('document_type');
        //         $table->string('document_name');
        //         $table->string('document_path');
        //         $table->integer('version')->default(1);
        //         $table->date('expiry_date')->nullable();
        //         $table->unsignedBigInteger('uploaded_by');
        //         $table->integer('download_count')->default(0);
        //         $table->timestamps();
        //         $table->softDeletes();
        //
        //         $table->index(['candidate_id', 'document_type']);
        //         $table->index('expiry_date');
        //     });
        // }

        // ==========================================
        // STEP 2: ADD MISSING COLUMNS TO EXISTING TABLES
        // ==========================================

        // 1. candidates table - add missing columns
        if (Schema::hasTable('candidates')) {
            Schema::table('candidates', function (Blueprint $table) {
                if (!Schema::hasColumn('candidates', 'next_of_kin_id')) {
                    $table->unsignedBigInteger('next_of_kin_id')->nullable();
                }
                if (!Schema::hasColumn('candidates', 'registration_date')) {
                    $table->date('registration_date')->nullable();
                }
                if (!Schema::hasColumn('candidates', 'training_start_date')) {
                    $table->date('training_start_date')->nullable();
                }
                if (!Schema::hasColumn('candidates', 'training_end_date')) {
                    $table->date('training_end_date')->nullable();
                }
                if (!Schema::hasColumn('candidates', 'training_status')) {
                    $table->enum('training_status', ['pending', 'ongoing', 'completed', 'failed'])->default('pending');
                }
            });
        }

        // 2. training_attendances table
        if (Schema::hasTable('training_attendances')) {
            Schema::table('training_attendances', function (Blueprint $table) {
                if (!Schema::hasColumn('training_attendances', 'trainer_id')) {
                    $table->unsignedBigInteger('trainer_id')->nullable();
                }
                if (!Schema::hasColumn('training_attendances', 'detailed_remarks')) {
                    $table->text('detailed_remarks')->nullable();
                }
                if (!Schema::hasColumn('training_attendances', 'leave_type')) {
                    $table->enum('leave_type', ['sick', 'personal', 'emergency'])->nullable();
                }
            });
        }

        // 3. training_assessments table
        if (Schema::hasTable('training_assessments')) {
            Schema::table('training_assessments', function (Blueprint $table) {
                if (!Schema::hasColumn('training_assessments', 'trainer_id')) {
                    $table->unsignedBigInteger('trainer_id')->nullable();
                }
                if (!Schema::hasColumn('training_assessments', 'assessment_location')) {
                    $table->string('assessment_location')->nullable();
                }
                if (!Schema::hasColumn('training_assessments', 'remedial_needed')) {
                    $table->boolean('remedial_needed')->default(false);
                }
            });
        }

        // 4. visa_processes table
        if (Schema::hasTable('visa_processes')) {
            Schema::table('visa_processes', function (Blueprint $table) {
                if (!Schema::hasColumn('visa_processes', 'gamca_certificate_path')) {
                    $table->string('gamca_certificate_path')->nullable();
                }
                if (!Schema::hasColumn('visa_processes', 'etimad_appointment_id')) {
                    $table->string('etimad_appointment_id')->nullable();
                }
                if (!Schema::hasColumn('visa_processes', 'ptaff_number')) {
                    $table->string('ptaff_number')->nullable();
                }
                if (!Schema::hasColumn('visa_processes', 'ptn_number')) {
                    $table->string('ptn_number')->nullable();
                }
                if (!Schema::hasColumn('visa_processes', 'travel_plan_path')) {
                    $table->string('travel_plan_path')->nullable();
                }
                if (!Schema::hasColumn('visa_processes', 'enumber')) {
                    $table->string('enumber')->nullable();
                }
            });
        }

        // 5. complaints table
        if (Schema::hasTable('complaints')) {
            Schema::table('complaints', function (Blueprint $table) {
                if (!Schema::hasColumn('complaints', 'complaint_category')) {
                    $table->enum('complaint_category', ['screening', 'training', 'visa', 'salary', 'conduct', 'accommodation', 'other'])->default('other');
                }
                if (!Schema::hasColumn('complaints', 'sla_due_date')) {
                    $table->dateTime('sla_due_date')->nullable();
                }
                if (!Schema::hasColumn('complaints', 'escalation_level')) {
                    $table->integer('escalation_level')->default(0);
                }
                if (!Schema::hasColumn('complaints', 'resolution_details')) {
                    $table->text('resolution_details')->nullable();
                }
                if (!Schema::hasColumn('complaints', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->nullable();
                }
            });
        }

        // 6. correspondence table
        if (Schema::hasTable('correspondence')) {
            Schema::table('correspondence', function (Blueprint $table) {
                if (!Schema::hasColumn('correspondence', 'file_reference_number')) {
                    $table->string('file_reference_number')->nullable()->unique();
                }
                if (!Schema::hasColumn('correspondence', 'sender')) {
                    $table->string('sender')->nullable();
                }
                if (!Schema::hasColumn('correspondence', 'recipient')) {
                    $table->string('recipient')->nullable();
                }
                if (!Schema::hasColumn('correspondence', 'correspondence_type')) {
                    $table->enum('correspondence_type', ['email', 'letter', 'memo', 'official'])->default('official');
                }
                if (!Schema::hasColumn('correspondence', 'document_path')) {
                    $table->string('document_path')->nullable();
                }
                if (!Schema::hasColumn('correspondence', 'priority_level')) {
                    $table->enum('priority_level', ['urgent', 'normal', 'low'])->default('normal');
                }
            });
        }

        // 7. batches table
        if (Schema::hasTable('batches')) {
            Schema::table('batches', function (Blueprint $table) {
                if (!Schema::hasColumn('batches', 'uuid')) {
                    $table->uuid('uuid')->nullable()->unique();
                }
                if (!Schema::hasColumn('batches', 'intake_period')) {
                    $table->string('intake_period')->nullable();
                }
                if (!Schema::hasColumn('batches', 'district')) {
                    $table->string('district')->nullable();
                }
                if (!Schema::hasColumn('batches', 'specialization')) {
                    $table->string('specialization')->nullable();
                }
            });
        }

        // 8. departures table
        if (Schema::hasTable('departures')) {
            Schema::table('departures', function (Blueprint $table) {
                if (!Schema::hasColumn('departures', 'medical_report_path')) {
                    $table->string('medical_report_path')->nullable();
                }
                if (!Schema::hasColumn('departures', 'employer_contact')) {
                    $table->string('employer_contact')->nullable();
                }
                if (!Schema::hasColumn('departures', 'country_code')) {
                    $table->string('country_code', 2)->default('SA');
                }
                if (!Schema::hasColumn('departures', 'accommodation_status')) {
                    $table->enum('accommodation_status', ['pending', 'verified', 'issue'])->default('pending');
                }
            });
        }

        // 9. next_of_kins table - add columns if table was pre-existing but incomplete
        if (Schema::hasTable('next_of_kins')) {
            Schema::table('next_of_kins', function (Blueprint $table) {
                if (!Schema::hasColumn('next_of_kins', 'address')) {
                    $table->text('address')->nullable();
                }
                if (!Schema::hasColumn('next_of_kins', 'phone')) {
                    $table->string('phone')->nullable();
                }
                if (!Schema::hasColumn('next_of_kins', 'email')) {
                    $table->string('email')->nullable();
                }
                if (!Schema::hasColumn('next_of_kins', 'occupation')) {
                    $table->string('occupation')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        // Drop columns from existing tables
        if (Schema::hasTable('next_of_kins')) {
            Schema::table('next_of_kins', function (Blueprint $table) {
                $columns = ['address', 'phone', 'email', 'occupation'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('next_of_kins', $column)) $table->dropColumn($column);
                }
            });
        }

        if (Schema::hasTable('departures')) {
            Schema::table('departures', function (Blueprint $table) {
                $columns = ['medical_report_path', 'employer_contact', 'country_code', 'accommodation_status'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('departures', $column)) $table->dropColumn($column);
                }
            });
        }

        if (Schema::hasTable('batches')) {
            Schema::table('batches', function (Blueprint $table) {
                $columns = ['uuid', 'intake_period', 'district', 'specialization'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('batches', $column)) $table->dropColumn($column);
                }
            });
        }

        if (Schema::hasTable('correspondence')) {
            Schema::table('correspondence', function (Blueprint $table) {
                $columns = ['file_reference_number', 'sender', 'recipient', 'correspondence_type', 'document_path', 'priority_level'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('correspondence', $column)) $table->dropColumn($column);
                }
            });
        }

        if (Schema::hasTable('complaints')) {
            Schema::table('complaints', function (Blueprint $table) {
                $columns = ['complaint_category', 'sla_due_date', 'escalation_level', 'resolution_details', 'user_id'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('complaints', $column)) $table->dropColumn($column);
                }
            });
        }

        if (Schema::hasTable('visa_processes')) {
            Schema::table('visa_processes', function (Blueprint $table) {
                $columns = ['gamca_certificate_path', 'etimad_appointment_id', 'ptaff_number', 'ptn_number', 'travel_plan_path', 'enumber'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('visa_processes', $column)) $table->dropColumn($column);
                }
            });
        }

        if (Schema::hasTable('training_assessments')) {
            Schema::table('training_assessments', function (Blueprint $table) {
                $columns = ['trainer_id', 'assessment_location', 'remedial_needed'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('training_assessments', $column)) $table->dropColumn($column);
                }
            });
        }

        if (Schema::hasTable('training_attendances')) {
            Schema::table('training_attendances', function (Blueprint $table) {
                $columns = ['trainer_id', 'detailed_remarks', 'leave_type'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('training_attendances', $column)) $table->dropColumn($column);
                }
            });
        }

        if (Schema::hasTable('candidates')) {
            Schema::table('candidates', function (Blueprint $table) {
                $columns = ['next_of_kin_id', 'registration_date', 'training_start_date', 'training_end_date', 'training_status'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('candidates', $column)) $table->dropColumn($column);
                }
            });
        }

        // Drop created tables
        // Note: document_archives is dropped in main migration
        Schema::dropIfExists('training_certificates');
        Schema::dropIfExists('undertakings');
        Schema::dropIfExists('registration_documents');
        Schema::dropIfExists('registrations');
        Schema::dropIfExists('candidate_screenings');
        Schema::dropIfExists('next_of_kins');
    }
};