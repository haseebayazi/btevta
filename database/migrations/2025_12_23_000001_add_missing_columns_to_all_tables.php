<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SCHEMA FIX: Add all missing columns identified during model-migration audit
 *
 * This migration adds columns that are referenced in model $fillable arrays
 * but were missing from the database schema.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // =====================================================
        // CANDIDATES TABLE - Missing 11+ columns
        // =====================================================
        if (Schema::hasTable('candidates')) {
            Schema::table('candidates', function (Blueprint $table) {
                if (!Schema::hasColumn('candidates', 'emergency_contact')) {
                    $table->string('emergency_contact', 50)->nullable()->after('address');
                }
                if (!Schema::hasColumn('candidates', 'blood_group')) {
                    $table->string('blood_group', 10)->nullable()->after('emergency_contact');
                }
                if (!Schema::hasColumn('candidates', 'marital_status')) {
                    $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->nullable()->after('blood_group');
                }
                if (!Schema::hasColumn('candidates', 'qualification')) {
                    $table->string('qualification')->nullable()->after('marital_status');
                }
                if (!Schema::hasColumn('candidates', 'experience_years')) {
                    $table->unsignedTinyInteger('experience_years')->nullable()->after('qualification');
                }
                if (!Schema::hasColumn('candidates', 'passport_number')) {
                    $table->string('passport_number', 20)->nullable()->after('experience_years');
                }
                if (!Schema::hasColumn('candidates', 'passport_expiry')) {
                    $table->date('passport_expiry')->nullable()->after('passport_number');
                }
                if (!Schema::hasColumn('candidates', 'province')) {
                    $table->string('province', 50)->nullable()->after('tehsil');
                }
                if (!Schema::hasColumn('candidates', 'remarks')) {
                    $table->text('remarks')->nullable()->after('photo_path');
                }
                if (!Schema::hasColumn('candidates', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable();
                }
                if (!Schema::hasColumn('candidates', 'updated_by')) {
                    $table->unsignedBigInteger('updated_by')->nullable();
                }
            });
        }

        // =====================================================
        // BATCHES TABLE - Missing coordinator_id and audit columns
        // =====================================================
        if (Schema::hasTable('batches')) {
            Schema::table('batches', function (Blueprint $table) {
                if (!Schema::hasColumn('batches', 'coordinator_id')) {
                    $table->unsignedBigInteger('coordinator_id')->nullable()->after('trainer_id');
                }
                if (!Schema::hasColumn('batches', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable();
                }
                if (!Schema::hasColumn('batches', 'updated_by')) {
                    $table->unsignedBigInteger('updated_by')->nullable();
                }
            });
        }

        // =====================================================
        // COMPLAINTS TABLE - Missing many columns
        // =====================================================
        if (Schema::hasTable('complaints')) {
            Schema::table('complaints', function (Blueprint $table) {
                if (!Schema::hasColumn('complaints', 'complaint_number')) {
                    $table->string('complaint_number', 50)->nullable()->after('id');
                }
                if (!Schema::hasColumn('complaints', 'registered_by')) {
                    $table->unsignedBigInteger('registered_by')->nullable();
                }
                if (!Schema::hasColumn('complaints', 'closed_at')) {
                    $table->timestamp('closed_at')->nullable();
                }
                if (!Schema::hasColumn('complaints', 'closed_by')) {
                    $table->unsignedBigInteger('closed_by')->nullable();
                }
                if (!Schema::hasColumn('complaints', 'reopened_at')) {
                    $table->timestamp('reopened_at')->nullable();
                }
                if (!Schema::hasColumn('complaints', 'reopened_by')) {
                    $table->unsignedBigInteger('reopened_by')->nullable();
                }
                if (!Schema::hasColumn('complaints', 'complainant_name')) {
                    $table->string('complainant_name')->nullable();
                }
                if (!Schema::hasColumn('complaints', 'complainant_contact')) {
                    $table->string('complainant_contact', 50)->nullable();
                }
                if (!Schema::hasColumn('complaints', 'complainant_email')) {
                    $table->string('complainant_email')->nullable();
                }
                if (!Schema::hasColumn('complaints', 'complaint_reference')) {
                    $table->string('complaint_reference', 100)->nullable();
                }
                if (!Schema::hasColumn('complaints', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable();
                }
                if (!Schema::hasColumn('complaints', 'updated_by')) {
                    $table->unsignedBigInteger('updated_by')->nullable();
                }
            });
        }

        // =====================================================
        // CORRESPONDENCE TABLE - Missing columns
        // =====================================================
        if (Schema::hasTable('correspondences')) {
            Schema::table('correspondences', function (Blueprint $table) {
                if (!Schema::hasColumn('correspondences', 'candidate_id')) {
                    $table->unsignedBigInteger('candidate_id')->nullable();
                }
                if (!Schema::hasColumn('correspondences', 'assigned_to')) {
                    $table->unsignedBigInteger('assigned_to')->nullable();
                }
                if (!Schema::hasColumn('correspondences', 'summary')) {
                    $table->text('summary')->nullable();
                }
                if (!Schema::hasColumn('correspondences', 'organization_type')) {
                    $table->string('organization_type', 50)->nullable();
                }
            });
        }

        // =====================================================
        // DEPARTURES TABLE - Missing many columns
        // =====================================================
        if (Schema::hasTable('departures')) {
            Schema::table('departures', function (Blueprint $table) {
                // Status and stage tracking
                if (!Schema::hasColumn('departures', 'status')) {
                    $table->string('status', 50)->nullable()->default('pending');
                }
                if (!Schema::hasColumn('departures', 'current_stage')) {
                    $table->string('current_stage', 50)->nullable();
                }
                if (!Schema::hasColumn('departures', 'briefing_completed')) {
                    $table->boolean('briefing_completed')->default(false);
                }
                if (!Schema::hasColumn('departures', 'ready_for_departure')) {
                    $table->boolean('ready_for_departure')->default(false);
                }

                // Iqama and Absher fields
                if (!Schema::hasColumn('departures', 'iqama_expiry_date')) {
                    $table->date('iqama_expiry_date')->nullable();
                }
                if (!Schema::hasColumn('departures', 'absher_id')) {
                    $table->string('absher_id', 50)->nullable();
                }
                if (!Schema::hasColumn('departures', 'absher_verification_status')) {
                    $table->string('absher_verification_status', 50)->nullable();
                }

                // Qiwa fields
                if (!Schema::hasColumn('departures', 'qiwa_activation_date')) {
                    $table->date('qiwa_activation_date')->nullable();
                }
                if (!Schema::hasColumn('departures', 'qiwa_status')) {
                    $table->string('qiwa_status', 50)->nullable();
                }

                // Salary fields
                if (!Schema::hasColumn('departures', 'salary_currency')) {
                    $table->string('salary_currency', 10)->nullable()->default('SAR');
                }
                if (!Schema::hasColumn('departures', 'salary_confirmed')) {
                    $table->boolean('salary_confirmed')->default(false);
                }
                if (!Schema::hasColumn('departures', 'salary_confirmation_date')) {
                    $table->date('salary_confirmation_date')->nullable();
                }
                if (!Schema::hasColumn('departures', 'salary_remarks')) {
                    $table->text('salary_remarks')->nullable();
                }
                if (!Schema::hasColumn('departures', 'salary_proof_path')) {
                    $table->string('salary_proof_path')->nullable();
                }

                // Pre-briefing fields
                if (!Schema::hasColumn('departures', 'pre_briefing_date')) {
                    $table->date('pre_briefing_date')->nullable();
                }
                if (!Schema::hasColumn('departures', 'pre_briefing_conducted_by')) {
                    $table->unsignedBigInteger('pre_briefing_conducted_by')->nullable();
                }
                if (!Schema::hasColumn('departures', 'briefing_topics')) {
                    $table->json('briefing_topics')->nullable();
                }
                if (!Schema::hasColumn('departures', 'briefing_remarks')) {
                    $table->text('briefing_remarks')->nullable();
                }

                // Airport and travel
                if (!Schema::hasColumn('departures', 'airport')) {
                    $table->string('airport', 100)->nullable();
                }
                if (!Schema::hasColumn('departures', 'departure_remarks')) {
                    $table->text('departure_remarks')->nullable();
                }

                // Accommodation
                if (!Schema::hasColumn('departures', 'accommodation_address')) {
                    $table->text('accommodation_address')->nullable();
                }
                if (!Schema::hasColumn('departures', 'accommodation_verified_date')) {
                    $table->date('accommodation_verified_date')->nullable();
                }
                if (!Schema::hasColumn('departures', 'accommodation_remarks')) {
                    $table->text('accommodation_remarks')->nullable();
                }

                // Employer info
                if (!Schema::hasColumn('departures', 'employer_name')) {
                    $table->string('employer_name')->nullable();
                }
                if (!Schema::hasColumn('departures', 'employer_id_number')) {
                    $table->string('employer_id_number', 50)->nullable();
                }

                // Communication and compliance
                if (!Schema::hasColumn('departures', 'communication_logs')) {
                    $table->json('communication_logs')->nullable();
                }
                if (!Schema::hasColumn('departures', 'last_contact_date')) {
                    $table->date('last_contact_date')->nullable();
                }
                if (!Schema::hasColumn('departures', 'compliance_remarks')) {
                    $table->text('compliance_remarks')->nullable();
                }
                if (!Schema::hasColumn('departures', 'issues')) {
                    $table->json('issues')->nullable();
                }

                // Return tracking
                if (!Schema::hasColumn('departures', 'return_date')) {
                    $table->date('return_date')->nullable();
                }
                if (!Schema::hasColumn('departures', 'return_reason')) {
                    $table->string('return_reason')->nullable();
                }
                if (!Schema::hasColumn('departures', 'return_remarks')) {
                    $table->text('return_remarks')->nullable();
                }

                // Medical
                if (!Schema::hasColumn('departures', 'medical_report_date')) {
                    $table->date('medical_report_date')->nullable();
                }

                // Audit columns
                if (!Schema::hasColumn('departures', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable();
                }
                if (!Schema::hasColumn('departures', 'updated_by')) {
                    $table->unsignedBigInteger('updated_by')->nullable();
                }
            });
        }

        // =====================================================
        // DOCUMENT_ARCHIVES TABLE - Missing columns
        // =====================================================
        if (Schema::hasTable('document_archives')) {
            Schema::table('document_archives', function (Blueprint $table) {
                if (!Schema::hasColumn('document_archives', 'campus_id')) {
                    $table->unsignedBigInteger('campus_id')->nullable();
                }
                if (!Schema::hasColumn('document_archives', 'oep_id')) {
                    $table->unsignedBigInteger('oep_id')->nullable();
                }
                if (!Schema::hasColumn('document_archives', 'document_category')) {
                    $table->string('document_category', 50)->nullable();
                }
                if (!Schema::hasColumn('document_archives', 'document_number')) {
                    $table->string('document_number', 100)->nullable();
                }
                if (!Schema::hasColumn('document_archives', 'file_type')) {
                    $table->string('file_type', 20)->nullable();
                }
                if (!Schema::hasColumn('document_archives', 'file_size')) {
                    $table->unsignedBigInteger('file_size')->nullable();
                }
                if (!Schema::hasColumn('document_archives', 'is_current_version')) {
                    $table->boolean('is_current_version')->default(true);
                }
                if (!Schema::hasColumn('document_archives', 'replaces_document_id')) {
                    $table->unsignedBigInteger('replaces_document_id')->nullable();
                }
                if (!Schema::hasColumn('document_archives', 'issue_date')) {
                    $table->date('issue_date')->nullable();
                }
                if (!Schema::hasColumn('document_archives', 'tags')) {
                    $table->json('tags')->nullable();
                }
                if (!Schema::hasColumn('document_archives', 'uploaded_by')) {
                    $table->unsignedBigInteger('uploaded_by')->nullable();
                }
                if (!Schema::hasColumn('document_archives', 'uploaded_at')) {
                    $table->timestamp('uploaded_at')->nullable();
                }
                if (!Schema::hasColumn('document_archives', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable();
                }
                if (!Schema::hasColumn('document_archives', 'updated_by')) {
                    $table->unsignedBigInteger('updated_by')->nullable();
                }
            });
        }

        // =====================================================
        // REMITTANCES TABLE - Add beneficiary_id for relationship
        // =====================================================
        if (Schema::hasTable('remittances')) {
            Schema::table('remittances', function (Blueprint $table) {
                if (!Schema::hasColumn('remittances', 'beneficiary_id')) {
                    $table->unsignedBigInteger('beneficiary_id')->nullable()->after('candidate_id');
                }
            });
        }

        // =====================================================
        // TRAINING_ATTENDANCES TABLE - Add class_id FK constraint info
        // =====================================================
        if (Schema::hasTable('training_attendances')) {
            Schema::table('training_attendances', function (Blueprint $table) {
                if (!Schema::hasColumn('training_attendances', 'class_id')) {
                    $table->unsignedBigInteger('class_id')->nullable();
                }
            });
        }

        // =====================================================
        // TRAINING_ASSESSMENTS TABLE - Add class_id
        // =====================================================
        if (Schema::hasTable('training_assessments')) {
            Schema::table('training_assessments', function (Blueprint $table) {
                if (!Schema::hasColumn('training_assessments', 'class_id')) {
                    $table->unsignedBigInteger('class_id')->nullable();
                }
            });
        }

        // =====================================================
        // VISA_PROCESSES TABLE - Missing columns
        // =====================================================
        if (Schema::hasTable('visa_processes')) {
            Schema::table('visa_processes', function (Blueprint $table) {
                if (!Schema::hasColumn('visa_processes', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable();
                }
                if (!Schema::hasColumn('visa_processes', 'updated_by')) {
                    $table->unsignedBigInteger('updated_by')->nullable();
                }
            });
        }

        // =====================================================
        // Add indexes for new foreign key columns
        // =====================================================
        $this->addIndexesSafely();
    }

    /**
     * Add indexes for foreign key columns
     */
    private function addIndexesSafely(): void
    {
        $indexes = [
            'candidates' => ['created_by', 'updated_by'],
            'batches' => ['coordinator_id', 'created_by', 'updated_by'],
            'complaints' => ['complaint_number', 'registered_by', 'closed_by', 'reopened_by', 'created_by', 'updated_by'],
            'correspondences' => ['candidate_id', 'assigned_to'],
            'departures' => ['created_by', 'updated_by', 'pre_briefing_conducted_by'],
            'document_archives' => ['campus_id', 'oep_id', 'uploaded_by', 'created_by', 'updated_by'],
            'remittances' => ['beneficiary_id'],
            'visa_processes' => ['created_by', 'updated_by'],
        ];

        foreach ($indexes as $tableName => $columns) {
            if (Schema::hasTable($tableName)) {
                foreach ($columns as $column) {
                    if (Schema::hasColumn($tableName, $column)) {
                        try {
                            Schema::table($tableName, function (Blueprint $table) use ($column, $tableName) {
                                $table->index($column, "idx_{$tableName}_{$column}");
                            });
                        } catch (\Exception $e) {
                            // Index may already exist
                        }
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration adds many columns - down() would need to drop them
        // For safety, we'll leave the columns in place on rollback
        // as removing data is destructive
    }
};
