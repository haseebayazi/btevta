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
        // Add foreign keys to correspondences table
        Schema::table('correspondences', function (Blueprint $table) {
            $table->foreign('campus_id')->references('id')->on('campuses')->nullOnDelete();
            $table->foreign('oep_id')->references('id')->on('oeps')->nullOnDelete();
            $table->foreign('candidate_id')->references('id')->on('candidates')->nullOnDelete();
        });

        // Add foreign keys to complaints table
        if (Schema::hasColumn('complaints', 'assigned_to')) {
            Schema::table('complaints', function (Blueprint $table) {
                $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            });
        }

        if (Schema::hasColumn('complaints', 'user_id')) {
            Schema::table('complaints', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            });
        }

        // Add foreign key to batches table
        if (Schema::hasColumn('batches', 'trainer_id')) {
            Schema::table('batches', function (Blueprint $table) {
                $table->foreign('trainer_id')->references('id')->on('users')->onDelete('set null');
            });
        }

        // Add foreign keys to complaint_updates table
        if (Schema::hasTable('complaint_updates')) {
            Schema::table('complaint_updates', function (Blueprint $table) {
                if (Schema::hasColumn('complaint_updates', 'assigned_from')) {
                    $table->foreign('assigned_from')->references('id')->on('users')->onDelete('set null');
                }
                if (Schema::hasColumn('complaint_updates', 'assigned_to')) {
                    $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
                }
                if (Schema::hasColumn('complaint_updates', 'created_by')) {
                    $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
                }
                if (Schema::hasColumn('complaint_updates', 'updated_by')) {
                    $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
                }
            });
        }

        // Add foreign keys to complaint_evidence table
        if (Schema::hasTable('complaint_evidence')) {
            Schema::table('complaint_evidence', function (Blueprint $table) {
                if (Schema::hasColumn('complaint_evidence', 'uploaded_by')) {
                    $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('set null');
                }
                if (Schema::hasColumn('complaint_evidence', 'created_by')) {
                    $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
                }
                if (Schema::hasColumn('complaint_evidence', 'updated_by')) {
                    $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
                }
            });
        }

        // Add foreign keys to training_classes table
        if (Schema::hasTable('training_classes')) {
            Schema::table('training_classes', function (Blueprint $table) {
                if (Schema::hasColumn('training_classes', 'created_by')) {
                    $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
                }
                if (Schema::hasColumn('training_classes', 'updated_by')) {
                    $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign keys from correspondences table
        Schema::table('correspondences', function (Blueprint $table) {
            $table->dropForeign(['campus_id']);
            $table->dropForeign(['oep_id']);
            $table->dropForeign(['candidate_id']);
        });

        // Drop foreign keys from complaints table
        if (Schema::hasColumn('complaints', 'assigned_to')) {
            Schema::table('complaints', function (Blueprint $table) {
                $table->dropForeign(['assigned_to']);
            });
        }

        if (Schema::hasColumn('complaints', 'user_id')) {
            Schema::table('complaints', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        }

        // Drop foreign key from batches table
        if (Schema::hasColumn('batches', 'trainer_id')) {
            Schema::table('batches', function (Blueprint $table) {
                $table->dropForeign(['trainer_id']);
            });
        }

        // Drop foreign keys from complaint_updates table
        if (Schema::hasTable('complaint_updates')) {
            Schema::table('complaint_updates', function (Blueprint $table) {
                if (Schema::hasColumn('complaint_updates', 'assigned_from')) {
                    $table->dropForeign(['assigned_from']);
                }
                if (Schema::hasColumn('complaint_updates', 'assigned_to')) {
                    $table->dropForeign(['assigned_to']);
                }
                if (Schema::hasColumn('complaint_updates', 'created_by')) {
                    $table->dropForeign(['created_by']);
                }
                if (Schema::hasColumn('complaint_updates', 'updated_by')) {
                    $table->dropForeign(['updated_by']);
                }
            });
        }

        // Drop foreign keys from complaint_evidence table
        if (Schema::hasTable('complaint_evidence')) {
            Schema::table('complaint_evidence', function (Blueprint $table) {
                if (Schema::hasColumn('complaint_evidence', 'uploaded_by')) {
                    $table->dropForeign(['uploaded_by']);
                }
                if (Schema::hasColumn('complaint_evidence', 'created_by')) {
                    $table->dropForeign(['created_by']);
                }
                if (Schema::hasColumn('complaint_evidence', 'updated_by')) {
                    $table->dropForeign(['updated_by']);
                }
            });
        }

        // Drop foreign keys from training_classes table
        if (Schema::hasTable('training_classes')) {
            Schema::table('training_classes', function (Blueprint $table) {
                if (Schema::hasColumn('training_classes', 'created_by')) {
                    $table->dropForeign(['created_by']);
                }
                if (Schema::hasColumn('training_classes', 'updated_by')) {
                    $table->dropForeign(['updated_by']);
                }
            });
        }
    }
};
