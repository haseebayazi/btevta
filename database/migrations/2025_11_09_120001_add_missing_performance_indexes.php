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
        // Add indexes to campuses table
        Schema::table('campuses', function (Blueprint $table) {
            $table->index(['email', 'phone'], 'campuses_contact_idx');
        });

        // Add indexes to oeps table
        Schema::table('oeps', function (Blueprint $table) {
            $table->index(['email', 'phone'], 'oeps_contact_idx');
        });

        // Add indexes to trades table
        Schema::table('trades', function (Blueprint $table) {
            $table->index('category', 'trades_category_idx');
        });

        // Add indexes to users table
        Schema::table('users', function (Blueprint $table) {
            $table->index(['role', 'is_active'], 'users_role_active_idx');
        });

        // Add indexes to batches table
        Schema::table('batches', function (Blueprint $table) {
            $table->index(['start_date', 'end_date'], 'batches_dates_idx');
            if (Schema::hasColumn('batches', 'status')) {
                $table->index('status', 'batches_status_idx');
            }
        });

        // Add indexes to candidates table
        Schema::table('candidates', function (Blueprint $table) {
            $table->index(['email', 'phone'], 'candidates_contact_idx');
            if (Schema::hasColumn('candidates', 'date_of_birth')) {
                $table->index('date_of_birth', 'candidates_dob_idx');
            }
            if (Schema::hasColumn('candidates', 'status')) {
                $table->index('status', 'candidates_status_idx');
            }
        });

        // Add indexes to correspondence table (if exists - old name)
        if (Schema::hasTable('correspondence')) {
            Schema::table('correspondence', function (Blueprint $table) {
                $table->index(['campus_id', 'oep_id'], 'correspondence_campus_oep_idx');
                $table->index('correspondence_date', 'correspondence_date_idx');
            });
        }

        // Add indexes to correspondences table (new name)
        if (Schema::hasTable('correspondences')) {
            Schema::table('correspondences', function (Blueprint $table) {
                $table->index(['campus_id', 'oep_id', 'candidate_id'], 'correspondences_relations_idx');
                $table->index(['status', 'sent_at'], 'correspondences_status_sent_idx');
            });
        }

        // Add indexes to complaints table
        Schema::table('complaints', function (Blueprint $table) {
            if (Schema::hasColumn('complaints', 'complaint_date')) {
                $table->index('complaint_date', 'complaints_date_idx');
            }
            $table->index(['candidate_id', 'campus_id', 'oep_id'], 'complaints_relations_idx');
            if (Schema::hasColumn('complaints', 'status')) {
                $table->index('status', 'complaints_status_idx');
            }
        });

        // Add indexes to document_archives table
        if (Schema::hasTable('document_archives')) {
            Schema::table('document_archives', function (Blueprint $table) {
                $table->index(['candidate_id', 'document_type'], 'doc_archives_candidate_type_idx');
                if (Schema::hasColumn('document_archives', 'upload_date')) {
                    $table->index('upload_date', 'doc_archives_upload_date_idx');
                }
                if (Schema::hasColumn('document_archives', 'expiry_date')) {
                    $table->index('expiry_date', 'doc_archives_expiry_date_idx');
                }
            });
        }

        // Add indexes to registration_documents table
        if (Schema::hasTable('registration_documents')) {
            Schema::table('registration_documents', function (Blueprint $table) {
                $table->index(['status', 'document_type'], 'reg_docs_status_type_idx');
                if (Schema::hasColumn('registration_documents', 'expiry_date')) {
                    $table->index('expiry_date', 'reg_docs_expiry_date_idx');
                }
            });
        }

        // Add indexes to departures table
        if (Schema::hasTable('departures')) {
            Schema::table('departures', function (Blueprint $table) {
                $table->index('departure_date', 'departures_date_idx');
                if (Schema::hasColumn('departures', 'status')) {
                    $table->index('status', 'departures_status_idx');
                }
            });
        }

        // Add indexes to visa_processes table
        if (Schema::hasTable('visa_processes')) {
            Schema::table('visa_processes', function (Blueprint $table) {
                if (Schema::hasColumn('visa_processes', 'visa_date')) {
                    $table->index('visa_date', 'visa_processes_date_idx');
                }
                if (Schema::hasColumn('visa_processes', 'overall_status')) {
                    $table->index('overall_status', 'visa_processes_status_idx');
                }
            });
        }

        // Add indexes to training_attendances table
        if (Schema::hasTable('training_attendances')) {
            Schema::table('training_attendances', function (Blueprint $table) {
                $table->index(['candidate_id', 'batch_id', 'date'], 'training_attendance_idx');
                if (Schema::hasColumn('training_attendances', 'status')) {
                    $table->index('status', 'training_attendance_status_idx');
                }
            });
        }

        // Add indexes to training_assessments table
        if (Schema::hasTable('training_assessments')) {
            Schema::table('training_assessments', function (Blueprint $table) {
                $table->index(['candidate_id', 'batch_id'], 'training_assessments_candidate_batch_idx');
                if (Schema::hasColumn('training_assessments', 'assessment_type')) {
                    $table->index('assessment_type', 'training_assessments_type_idx');
                }
                if (Schema::hasColumn('training_assessments', 'assessment_date')) {
                    $table->index('assessment_date', 'training_assessments_date_idx');
                }
            });
        }

        // Add indexes to training_certificates table
        if (Schema::hasTable('training_certificates')) {
            Schema::table('training_certificates', function (Blueprint $table) {
                if (Schema::hasColumn('training_certificates', 'status')) {
                    $table->index('status', 'training_certificates_status_idx');
                }
                if (Schema::hasColumn('training_certificates', 'issue_date')) {
                    $table->index('issue_date', 'training_certificates_issue_date_idx');
                }
            });
        }

        // Add indexes to audit_logs table
        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->index(['action', 'created_at'], 'audit_logs_action_created_idx');
            });
        }

        // Add indexes to training_classes table
        if (Schema::hasTable('training_classes')) {
            Schema::table('training_classes', function (Blueprint $table) {
                if (Schema::hasColumn('training_classes', 'status')) {
                    $table->index('status', 'training_classes_status_idx');
                }
            });
        }

        // Add indexes to instructors table
        if (Schema::hasTable('instructors')) {
            Schema::table('instructors', function (Blueprint $table) {
                if (Schema::hasColumn('instructors', 'status')) {
                    $table->index('status', 'instructors_status_idx');
                }
                if (Schema::hasColumn('instructors', 'phone')) {
                    $table->index('phone', 'instructors_phone_idx');
                }
            });
        }

        // Add indexes to undertakings table
        if (Schema::hasTable('undertakings')) {
            Schema::table('undertakings', function (Blueprint $table) {
                if (Schema::hasColumn('undertakings', 'candidate_id')) {
                    $table->index('candidate_id', 'undertakings_candidate_idx');
                }
                if (Schema::hasColumn('undertakings', 'status')) {
                    $table->index('status', 'undertakings_status_idx');
                }
            });
        }

        // Add indexes to next_of_kin table
        if (Schema::hasTable('next_of_kin')) {
            Schema::table('next_of_kin', function (Blueprint $table) {
                if (Schema::hasColumn('next_of_kin', 'candidate_id')) {
                    $table->index('candidate_id', 'next_of_kin_candidate_idx');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes from campuses table
        Schema::table('campuses', function (Blueprint $table) {
            $table->dropIndex('campuses_contact_idx');
        });

        // Drop indexes from oeps table
        Schema::table('oeps', function (Blueprint $table) {
            $table->dropIndex('oeps_contact_idx');
        });

        // Drop indexes from trades table
        Schema::table('trades', function (Blueprint $table) {
            $table->dropIndex('trades_category_idx');
        });

        // Drop indexes from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_active_idx');
        });

        // Drop indexes from batches table
        Schema::table('batches', function (Blueprint $table) {
            $table->dropIndex('batches_dates_idx');
            if (Schema::hasColumn('batches', 'status')) {
                $table->dropIndex('batches_status_idx');
            }
        });

        // Drop indexes from candidates table
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropIndex('candidates_contact_idx');
            if (Schema::hasColumn('candidates', 'date_of_birth')) {
                $table->dropIndex('candidates_dob_idx');
            }
            if (Schema::hasColumn('candidates', 'status')) {
                $table->dropIndex('candidates_status_idx');
            }
        });

        // Drop indexes from correspondence table (if exists - old name)
        if (Schema::hasTable('correspondence')) {
            Schema::table('correspondence', function (Blueprint $table) {
                $table->dropIndex('correspondence_campus_oep_idx');
                $table->dropIndex('correspondence_date_idx');
            });
        }

        // Drop indexes from correspondences table (new name)
        if (Schema::hasTable('correspondences')) {
            Schema::table('correspondences', function (Blueprint $table) {
                $table->dropIndex('correspondences_relations_idx');
                $table->dropIndex('correspondences_status_sent_idx');
            });
        }

        // Drop indexes from complaints table
        Schema::table('complaints', function (Blueprint $table) {
            if (Schema::hasColumn('complaints', 'complaint_date')) {
                $table->dropIndex('complaints_date_idx');
            }
            $table->dropIndex('complaints_relations_idx');
            if (Schema::hasColumn('complaints', 'status')) {
                $table->dropIndex('complaints_status_idx');
            }
        });

        // Drop indexes from document_archives table
        if (Schema::hasTable('document_archives')) {
            Schema::table('document_archives', function (Blueprint $table) {
                $table->dropIndex('doc_archives_candidate_type_idx');
                if (Schema::hasColumn('document_archives', 'upload_date')) {
                    $table->dropIndex('doc_archives_upload_date_idx');
                }
                if (Schema::hasColumn('document_archives', 'expiry_date')) {
                    $table->dropIndex('doc_archives_expiry_date_idx');
                }
            });
        }

        // Drop indexes from registration_documents table
        if (Schema::hasTable('registration_documents')) {
            Schema::table('registration_documents', function (Blueprint $table) {
                $table->dropIndex('reg_docs_status_type_idx');
                if (Schema::hasColumn('registration_documents', 'expiry_date')) {
                    $table->dropIndex('reg_docs_expiry_date_idx');
                }
            });
        }

        // Drop indexes from departures table
        if (Schema::hasTable('departures')) {
            Schema::table('departures', function (Blueprint $table) {
                $table->dropIndex('departures_date_idx');
                if (Schema::hasColumn('departures', 'status')) {
                    $table->dropIndex('departures_status_idx');
                }
            });
        }

        // Drop indexes from visa_processes table
        if (Schema::hasTable('visa_processes')) {
            Schema::table('visa_processes', function (Blueprint $table) {
                if (Schema::hasColumn('visa_processes', 'visa_date')) {
                    $table->dropIndex('visa_processes_date_idx');
                }
                if (Schema::hasColumn('visa_processes', 'overall_status')) {
                    $table->dropIndex('visa_processes_status_idx');
                }
            });
        }

        // Drop indexes from training_attendances table
        if (Schema::hasTable('training_attendances')) {
            Schema::table('training_attendances', function (Blueprint $table) {
                $table->dropIndex('training_attendance_idx');
                if (Schema::hasColumn('training_attendances', 'status')) {
                    $table->dropIndex('training_attendance_status_idx');
                }
            });
        }

        // Drop indexes from training_assessments table
        if (Schema::hasTable('training_assessments')) {
            Schema::table('training_assessments', function (Blueprint $table) {
                $table->dropIndex('training_assessments_candidate_batch_idx');
                if (Schema::hasColumn('training_assessments', 'assessment_type')) {
                    $table->dropIndex('training_assessments_type_idx');
                }
                if (Schema::hasColumn('training_assessments', 'assessment_date')) {
                    $table->dropIndex('training_assessments_date_idx');
                }
            });
        }

        // Drop indexes from training_certificates table
        if (Schema::hasTable('training_certificates')) {
            Schema::table('training_certificates', function (Blueprint $table) {
                if (Schema::hasColumn('training_certificates', 'status')) {
                    $table->dropIndex('training_certificates_status_idx');
                }
                if (Schema::hasColumn('training_certificates', 'issue_date')) {
                    $table->dropIndex('training_certificates_issue_date_idx');
                }
            });
        }

        // Drop indexes from audit_logs table
        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropIndex('audit_logs_action_created_idx');
            });
        }

        // Drop indexes from training_classes table
        if (Schema::hasTable('training_classes')) {
            Schema::table('training_classes', function (Blueprint $table) {
                if (Schema::hasColumn('training_classes', 'status')) {
                    $table->dropIndex('training_classes_status_idx');
                }
            });
        }

        // Drop indexes from instructors table
        if (Schema::hasTable('instructors')) {
            Schema::table('instructors', function (Blueprint $table) {
                if (Schema::hasColumn('instructors', 'status')) {
                    $table->dropIndex('instructors_status_idx');
                }
                if (Schema::hasColumn('instructors', 'phone')) {
                    $table->dropIndex('instructors_phone_idx');
                }
            });
        }

        // Drop indexes from undertakings table
        if (Schema::hasTable('undertakings')) {
            Schema::table('undertakings', function (Blueprint $table) {
                if (Schema::hasColumn('undertakings', 'candidate_id')) {
                    $table->dropIndex('undertakings_candidate_idx');
                }
                if (Schema::hasColumn('undertakings', 'status')) {
                    $table->dropIndex('undertakings_status_idx');
                }
            });
        }

        // Drop indexes from next_of_kin table
        if (Schema::hasTable('next_of_kin')) {
            Schema::table('next_of_kin', function (Blueprint $table) {
                if (Schema::hasColumn('next_of_kin', 'candidate_id')) {
                    $table->dropIndex('next_of_kin_candidate_idx');
                }
            });
        }
    }
};
