<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * PERFORMANCE: Add missing indexes identified in Phase 2 audit
     * These indexes significantly improve query performance on frequently accessed columns
     */
    public function up(): void
    {
        if (Schema::hasTable('candidates')) {
            Schema::table('candidates', function (Blueprint $table) {
                // Add index on status for dashboard and filtering queries
                if (Schema::hasColumn('candidates', 'status') && !$this->indexExists('candidates', 'candidates_status_index')) {
                    $table->index('status', 'candidates_status_index');
                }

                // Add index on cnic for quick lookups (if not already existing)
                if (Schema::hasColumn('candidates', 'cnic') && !$this->indexExists('candidates', 'candidates_cnic_index')) {
                    $table->index('cnic', 'candidates_cnic_index');
                }

                // Add index on email for authentication and lookups
                if (Schema::hasColumn('candidates', 'email') && !$this->indexExists('candidates', 'candidates_email_index')) {
                    $table->index('email', 'candidates_email_index');
                }

                // Add index on phone for quick lookups
                if (Schema::hasColumn('candidates', 'phone') && !$this->indexExists('candidates', 'candidates_phone_index')) {
                    $table->index('phone', 'candidates_phone_index');
                }
            });
        }

        if (Schema::hasTable('candidate_screenings')) {
            Schema::table('candidate_screenings', function (Blueprint $table) {
                // Add index on screening_stage for dashboard queries
                if (Schema::hasColumn('candidate_screenings', 'screening_stage') && !$this->indexExists('candidate_screenings', 'candidate_screenings_screening_stage_index')) {
                    $table->index('screening_stage', 'candidate_screenings_screening_stage_index');
                }

                // Add composite index for candidate lookups
                if (Schema::hasColumn('candidate_screenings', 'candidate_id') && Schema::hasColumn('candidate_screenings', 'screening_stage') && !$this->indexExists('candidate_screenings', 'candidate_screenings_candidate_stage_index')) {
                    $table->index(['candidate_id', 'screening_stage'], 'candidate_screenings_candidate_stage_index');
                }
            });
        }

        if (Schema::hasTable('training_attendances')) {
            Schema::table('training_attendances', function (Blueprint $table) {
                // Add composite index for training service queries
                if (Schema::hasColumn('training_attendances', 'candidate_id') && Schema::hasColumn('training_attendances', 'batch_id') && !$this->indexExists('training_attendances', 'training_attendances_candidate_batch_index')) {
                    $table->index(['candidate_id', 'batch_id'], 'training_attendances_candidate_batch_index');
                }
            });
        }

        if (Schema::hasTable('complaints')) {
            Schema::table('complaints', function (Blueprint $table) {
                // Add composite index for campus-status filtering
                if (Schema::hasColumn('complaints', 'campus_id') && Schema::hasColumn('complaints', 'status') && !$this->indexExists('complaints', 'complaints_campus_status_index')) {
                    $table->index(['campus_id', 'status'], 'complaints_campus_status_index');
                }

                // Add index on status for dashboard queries
                if (Schema::hasColumn('complaints', 'status') && !$this->indexExists('complaints', 'complaints_status_index')) {
                    $table->index('status', 'complaints_status_index');
                }
            });
        }

        if (Schema::hasTable('training_assessments')) {
            Schema::table('training_assessments', function (Blueprint $table) {
                // Add composite index for candidate assessment queries
                if (Schema::hasColumn('training_assessments', 'candidate_id') && Schema::hasColumn('training_assessments', 'assessment_type') && !$this->indexExists('training_assessments', 'training_assessments_candidate_type_index')) {
                    $table->index(['candidate_id', 'assessment_type'], 'training_assessments_candidate_type_index');
                }
            });
        }

        if (Schema::hasTable('document_archives')) {
            Schema::table('document_archives', function (Blueprint $table) {
                // Add index on uploaded_at for expiry queries
                if (Schema::hasColumn('document_archives', 'uploaded_at') && !$this->indexExists('document_archives', 'document_archives_uploaded_at_index')) {
                    $table->index('uploaded_at', 'document_archives_uploaded_at_index');
                }

                // Add index on is_current_version for active document queries
                if (Schema::hasColumn('document_archives', 'is_current_version') && !$this->indexExists('document_archives', 'document_archives_is_current_version_index')) {
                    $table->index('is_current_version', 'document_archives_is_current_version_index');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropIndex('candidates_status_index');
            $table->dropIndex('candidates_cnic_index');
            $table->dropIndex('candidates_email_index');
            $table->dropIndex('candidates_phone_index');
        });

        Schema::table('candidate_screenings', function (Blueprint $table) {
            $table->dropIndex('candidate_screenings_screening_stage_index');
            $table->dropIndex('candidate_screenings_candidate_stage_index');
        });

        Schema::table('training_attendances', function (Blueprint $table) {
            $table->dropIndex('training_attendances_candidate_batch_index');
        });

        Schema::table('complaints', function (Blueprint $table) {
            $table->dropIndex('complaints_campus_status_index');
            $table->dropIndex('complaints_status_index');
        });

        Schema::table('training_assessments', function (Blueprint $table) {
            $table->dropIndex('training_assessments_candidate_type_index');
        });

        Schema::table('document_archives', function (Blueprint $table) {
            $table->dropIndex('document_archives_uploaded_at_index');
            $table->dropIndex('document_archives_is_current_version_index');
        });
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = \DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return !empty($indexes);
    }
};
