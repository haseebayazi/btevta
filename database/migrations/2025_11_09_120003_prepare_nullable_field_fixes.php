<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration to fix nullable field issues (35+ issues)
 *
 * IMPORTANT: This migration includes three phases:
 * 1. Data Audit - Check for existing NULL values (must run manually first)
 * 2. Data Migration - Set default values for NULLs (runs automatically)
 * 3. Schema Update - Remove nullable constraints (runs automatically)
 *
 * WARNING: Before running this migration in production:
 * - Review the data audit results
 * - Confirm default values are appropriate for your business logic
 * - Test on staging environment first
 * - Ensure application validation rules match these constraints
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /**
         * PHASE 1: DATA AUDIT QUERIES
         * Run these queries manually before executing the migration:
         *
         * -- High Priority Fields
         * SELECT COUNT(*) as null_count FROM campuses WHERE email IS NULL;
         * SELECT COUNT(*) as null_count FROM oeps WHERE email IS NULL;
         * SELECT COUNT(*) as null_count FROM candidates WHERE email IS NULL;
         * SELECT COUNT(*) as null_count FROM instructors WHERE name IS NULL;
         * SELECT COUNT(*) as null_count FROM instructors WHERE phone IS NULL;
         *
         * -- Medium Priority Fields
         * SELECT COUNT(*) as null_count FROM correspondences WHERE subject IS NULL;
         * SELECT COUNT(*) as null_count FROM correspondences WHERE message IS NULL;
         * SELECT COUNT(*) as null_count FROM complaints WHERE subject IS NULL;
         * SELECT COUNT(*) as null_count FROM complaints WHERE description IS NULL;
         * SELECT COUNT(*) as null_count FROM document_archives WHERE document_name IS NULL;
         * SELECT COUNT(*) as null_count FROM document_archives WHERE document_type IS NULL;
         * SELECT COUNT(*) as null_count FROM document_archives WHERE file_path IS NULL;
         *
         * -- Low Priority Fields (Foreign Keys)
         * SELECT COUNT(*) as null_count FROM departures WHERE candidate_id IS NULL;
         * SELECT COUNT(*) as null_count FROM undertakings WHERE candidate_id IS NULL;
         * SELECT COUNT(*) as null_count FROM visa_processes WHERE candidate_id IS NULL;
         * SELECT COUNT(*) as null_count FROM training_attendances WHERE candidate_id IS NULL;
         * SELECT COUNT(*) as null_count FROM training_assessments WHERE candidate_id IS NULL;
         */

        // PHASE 2: DATA MIGRATION - Set default values for existing NULLs

        // High Priority - Critical Contact Fields
        if (Schema::hasTable('campuses') && Schema::hasColumn('campuses', 'email')) {
            DB::table('campuses')
                ->whereNull('email')
                ->update(['email' => 'noreply@theleap.org']);
        }

        if (Schema::hasTable('oeps') && Schema::hasColumn('oeps', 'email')) {
            DB::table('oeps')
                ->whereNull('email')
                ->update(['email' => 'noreply@theleap.org']);
        }

        if (Schema::hasTable('candidates') && Schema::hasColumn('candidates', 'email')) {
            DB::table('candidates')
                ->whereNull('email')
                ->update(['email' => 'noreply@theleap.org']);
        }

        if (Schema::hasTable('instructors')) {
            if (Schema::hasColumn('instructors', 'name')) {
                DB::table('instructors')
                    ->whereNull('name')
                    ->update(['name' => 'Unknown Instructor']);
            }

            if (Schema::hasColumn('instructors', 'phone')) {
                DB::table('instructors')
                    ->whereNull('phone')
                    ->update(['phone' => '0000000000']);
            }
        }

        // Medium Priority - Content Fields
        if (Schema::hasTable('correspondences')) {
            if (Schema::hasColumn('correspondences', 'subject')) {
                DB::table('correspondences')
                    ->whereNull('subject')
                    ->update(['subject' => 'No Subject']);
            }

            if (Schema::hasColumn('correspondences', 'message')) {
                DB::table('correspondences')
                    ->whereNull('message')
                    ->update(['message' => 'No message provided']);
            }
        }

        if (Schema::hasTable('complaints')) {
            if (Schema::hasColumn('complaints', 'subject')) {
                DB::table('complaints')
                    ->whereNull('subject')
                    ->update(['subject' => 'No Subject']);
            }

            if (Schema::hasColumn('complaints', 'description')) {
                DB::table('complaints')
                    ->whereNull('description')
                    ->update(['description' => 'No description provided']);
            }
        }

        if (Schema::hasTable('document_archives')) {
            if (Schema::hasColumn('document_archives', 'document_name')) {
                DB::table('document_archives')
                    ->whereNull('document_name')
                    ->update(['document_name' => 'Unnamed Document']);
            }

            if (Schema::hasColumn('document_archives', 'document_type')) {
                DB::table('document_archives')
                    ->whereNull('document_type')
                    ->update(['document_type' => 'general']);
            }

            if (Schema::hasColumn('document_archives', 'file_path')) {
                DB::table('document_archives')
                    ->whereNull('file_path')
                    ->update(['file_path' => 'missing/file.pdf']);
            }
        }

        if (Schema::hasTable('registration_documents') && Schema::hasColumn('registration_documents', 'document_type')) {
            DB::table('registration_documents')
                ->whereNull('document_type')
                ->update(['document_type' => 'general']);
        }

        if (Schema::hasTable('complaint_updates') && Schema::hasColumn('complaint_updates', 'message')) {
            DB::table('complaint_updates')
                ->whereNull('message')
                ->update(['message' => 'No update message']);
        }

        if (Schema::hasTable('complaint_evidence')) {
            if (Schema::hasColumn('complaint_evidence', 'file_name')) {
                DB::table('complaint_evidence')
                    ->whereNull('file_name')
                    ->update(['file_name' => 'unknown.pdf']);
            }

            if (Schema::hasColumn('complaint_evidence', 'file_path')) {
                DB::table('complaint_evidence')
                    ->whereNull('file_path')
                    ->update(['file_path' => 'missing/evidence.pdf']);
            }
        }

        if (Schema::hasTable('training_classes') && Schema::hasColumn('training_classes', 'class_name')) {
            DB::table('training_classes')
                ->whereNull('class_name')
                ->update(['class_name' => 'Unnamed Class']);
        }

        // Low Priority - Foreign Key Fields
        // WARNING: These should ideally be cleaned up manually as they indicate data integrity issues
        // For now, we'll document them but NOT set defaults, as orphaned records should be investigated

        /**
         * The following fields have NULL foreign keys that should be investigated:
         * - departures.candidate_id
         * - undertakings.candidate_id, undertaking_date
         * - visa_processes.candidate_id
         * - training_attendances.candidate_id, batch_id, date
         * - training_assessments.candidate_id, batch_id, assessment_date, assessment_type, score
         * - training_certificates.candidate_id, batch_id, issue_date
         * - audit_logs.action
         *
         * These records with NULL foreign keys should be:
         * 1. Reviewed manually to understand why they exist
         * 2. Either assigned to proper entities OR
         * 3. Soft-deleted if they represent orphaned data
         *
         * Uncomment and modify the following code blocks only after manual review:
         */

        /*
        // Example: Delete orphaned records
        if (Schema::hasTable('departures') && Schema::hasColumn('departures', 'candidate_id')) {
            DB::table('departures')->whereNull('candidate_id')->delete();
        }
        */

        // PHASE 3: SCHEMA UPDATE - Remove nullable constraints

        // High Priority - Critical Contact Fields
        if (Schema::hasTable('campuses') && Schema::hasColumn('campuses', 'email')) {
            Schema::table('campuses', function (Blueprint $table) {
                $table->string('email')->nullable(false)->change();
            });
        }

        if (Schema::hasTable('oeps') && Schema::hasColumn('oeps', 'email')) {
            Schema::table('oeps', function (Blueprint $table) {
                $table->string('email')->nullable(false)->change();
            });
        }

        if (Schema::hasTable('candidates') && Schema::hasColumn('candidates', 'email')) {
            Schema::table('candidates', function (Blueprint $table) {
                $table->string('email')->nullable(false)->change();
            });
        }

        if (Schema::hasTable('instructors')) {
            Schema::table('instructors', function (Blueprint $table) {
                if (Schema::hasColumn('instructors', 'name')) {
                    $table->string('name')->nullable(false)->change();
                }

                if (Schema::hasColumn('instructors', 'phone')) {
                    $table->string('phone')->nullable(false)->change();
                }
            });
        }

        // Medium Priority - Content Fields
        if (Schema::hasTable('correspondences')) {
            Schema::table('correspondences', function (Blueprint $table) {
                if (Schema::hasColumn('correspondences', 'subject')) {
                    $table->text('subject')->nullable(false)->change();
                }

                if (Schema::hasColumn('correspondences', 'message')) {
                    $table->text('message')->nullable(false)->change();
                }
            });
        }

        if (Schema::hasTable('complaints')) {
            Schema::table('complaints', function (Blueprint $table) {
                if (Schema::hasColumn('complaints', 'subject')) {
                    $table->text('subject')->nullable(false)->change();
                }

                if (Schema::hasColumn('complaints', 'description')) {
                    $table->text('description')->nullable(false)->change();
                }
            });
        }

        if (Schema::hasTable('document_archives')) {
            Schema::table('document_archives', function (Blueprint $table) {
                if (Schema::hasColumn('document_archives', 'document_name')) {
                    $table->string('document_name')->nullable(false)->change();
                }

                if (Schema::hasColumn('document_archives', 'document_type')) {
                    $table->string('document_type')->nullable(false)->change();
                }

                if (Schema::hasColumn('document_archives', 'file_path')) {
                    $table->string('file_path')->nullable(false)->change();
                }
            });
        }

        if (Schema::hasTable('registration_documents') && Schema::hasColumn('registration_documents', 'document_type')) {
            Schema::table('registration_documents', function (Blueprint $table) {
                $table->string('document_type')->nullable(false)->change();
            });
        }

        if (Schema::hasTable('complaint_updates') && Schema::hasColumn('complaint_updates', 'message')) {
            Schema::table('complaint_updates', function (Blueprint $table) {
                $table->text('message')->nullable(false)->change();
            });
        }

        if (Schema::hasTable('complaint_evidence')) {
            Schema::table('complaint_evidence', function (Blueprint $table) {
                if (Schema::hasColumn('complaint_evidence', 'file_name')) {
                    $table->string('file_name')->nullable(false)->change();
                }

                if (Schema::hasColumn('complaint_evidence', 'file_path')) {
                    $table->string('file_path')->nullable(false)->change();
                }
            });
        }

        if (Schema::hasTable('training_classes') && Schema::hasColumn('training_classes', 'class_name')) {
            Schema::table('training_classes', function (Blueprint $table) {
                $table->string('class_name')->nullable(false)->change();
            });
        }

        // Note: Low priority foreign key fields are intentionally left nullable
        // until manual data cleanup is performed
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert schema changes back to nullable

        // High Priority Fields
        if (Schema::hasTable('campuses') && Schema::hasColumn('campuses', 'email')) {
            Schema::table('campuses', function (Blueprint $table) {
                $table->string('email')->nullable()->change();
            });
        }

        if (Schema::hasTable('oeps') && Schema::hasColumn('oeps', 'email')) {
            Schema::table('oeps', function (Blueprint $table) {
                $table->string('email')->nullable()->change();
            });
        }

        if (Schema::hasTable('candidates') && Schema::hasColumn('candidates', 'email')) {
            Schema::table('candidates', function (Blueprint $table) {
                $table->string('email')->nullable()->change();
            });
        }

        if (Schema::hasTable('instructors')) {
            Schema::table('instructors', function (Blueprint $table) {
                if (Schema::hasColumn('instructors', 'name')) {
                    $table->string('name')->nullable()->change();
                }

                if (Schema::hasColumn('instructors', 'phone')) {
                    $table->string('phone')->nullable()->change();
                }
            });
        }

        // Medium Priority Fields
        if (Schema::hasTable('correspondences')) {
            Schema::table('correspondences', function (Blueprint $table) {
                if (Schema::hasColumn('correspondences', 'subject')) {
                    $table->text('subject')->nullable()->change();
                }

                if (Schema::hasColumn('correspondences', 'message')) {
                    $table->text('message')->nullable()->change();
                }
            });
        }

        if (Schema::hasTable('complaints')) {
            Schema::table('complaints', function (Blueprint $table) {
                if (Schema::hasColumn('complaints', 'subject')) {
                    $table->text('subject')->nullable()->change();
                }

                if (Schema::hasColumn('complaints', 'description')) {
                    $table->text('description')->nullable()->change();
                }
            });
        }

        if (Schema::hasTable('document_archives')) {
            Schema::table('document_archives', function (Blueprint $table) {
                if (Schema::hasColumn('document_archives', 'document_name')) {
                    $table->string('document_name')->nullable()->change();
                }

                if (Schema::hasColumn('document_archives', 'document_type')) {
                    $table->string('document_type')->nullable()->change();
                }

                if (Schema::hasColumn('document_archives', 'file_path')) {
                    $table->string('file_path')->nullable()->change();
                }
            });
        }

        if (Schema::hasTable('registration_documents') && Schema::hasColumn('registration_documents', 'document_type')) {
            Schema::table('registration_documents', function (Blueprint $table) {
                $table->string('document_type')->nullable()->change();
            });
        }

        if (Schema::hasTable('complaint_updates') && Schema::hasColumn('complaint_updates', 'message')) {
            Schema::table('complaint_updates', function (Blueprint $table) {
                $table->text('message')->nullable()->change();
            });
        }

        if (Schema::hasTable('complaint_evidence')) {
            Schema::table('complaint_evidence', function (Blueprint $table) {
                if (Schema::hasColumn('complaint_evidence', 'file_name')) {
                    $table->string('file_name')->nullable()->change();
                }

                if (Schema::hasColumn('complaint_evidence', 'file_path')) {
                    $table->string('file_path')->nullable()->change();
                }
            });
        }

        if (Schema::hasTable('training_classes') && Schema::hasColumn('training_classes', 'class_name')) {
            Schema::table('training_classes', function (Blueprint $table) {
                $table->string('class_name')->nullable()->change();
            });
        }

        // Note: We do NOT revert the default values set in Phase 2
        // as those are data changes, not schema changes
    }
};
