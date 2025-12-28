<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AUDIT FIX: Add missing database constraints identified during system audit
 *
 * Issues Addressed:
 * - DB-001: Missing FK users.visa_partner_id → visa_partners
 * - DB-002: Missing class_enrollments pivot table
 * - DB-004: Missing indexes on remittances table
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add visa_partner_id column and FK to users table if not exists
        if (!Schema::hasColumn('users', 'visa_partner_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('visa_partner_id')->nullable()->after('oep_id');
                $table->foreign('visa_partner_id')
                    ->references('id')
                    ->on('visa_partners')
                    ->onDelete('set null');
            });
        } else {
            // Column exists but FK may be missing - add FK only
            Schema::table('users', function (Blueprint $table) {
                // Check if foreign key already exists before adding
                $foreignKeys = collect(Schema::getConnection()->getDoctrineSchemaManager()
                    ->listTableForeignKeys('users'))
                    ->pluck('name')
                    ->toArray();

                if (!in_array('users_visa_partner_id_foreign', $foreignKeys)) {
                    $table->foreign('visa_partner_id')
                        ->references('id')
                        ->on('visa_partners')
                        ->onDelete('set null');
                }
            });
        }

        // 2. Create class_enrollments pivot table for TrainingClass ↔ Candidate relationship
        if (!Schema::hasTable('class_enrollments')) {
            Schema::create('class_enrollments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('training_class_id');
                $table->unsignedBigInteger('candidate_id');
                $table->date('enrollment_date')->nullable();
                $table->enum('status', ['enrolled', 'completed', 'dropped', 'transferred'])->default('enrolled');
                $table->text('remarks')->nullable();
                $table->unsignedBigInteger('enrolled_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('training_class_id')
                    ->references('id')
                    ->on('training_classes')
                    ->onDelete('cascade');

                $table->foreign('candidate_id')
                    ->references('id')
                    ->on('candidates')
                    ->onDelete('cascade');

                $table->foreign('enrolled_by')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');

                // Prevent duplicate enrollments
                $table->unique(['training_class_id', 'candidate_id'], 'unique_class_enrollment');

                // Indexes for common queries
                $table->index('status');
                $table->index('enrollment_date');
            });
        }

        // 3. Add missing indexes on remittances table for performance
        Schema::table('remittances', function (Blueprint $table) {
            // Check if indexes don't exist before adding
            $indexes = collect(Schema::getConnection()->getDoctrineSchemaManager()
                ->listTableIndexes('remittances'))
                ->keys()
                ->toArray();

            if (!in_array('remittances_candidate_id_index', $indexes) &&
                !in_array('remittances_candidate_id_foreign', $indexes)) {
                $table->index('candidate_id');
            }

            if (!in_array('remittances_status_index', $indexes)) {
                $table->index('status');
            }

            if (!in_array('remittances_has_proof_index', $indexes)) {
                $table->index('has_proof');
            }

            if (!in_array('remittances_is_first_remittance_index', $indexes)) {
                $table->index('is_first_remittance');
            }

            if (!in_array('remittances_year_month_index', $indexes)) {
                $table->index(['year', 'month']);
            }
        });

        // 4. Add composite index for common candidate queries
        Schema::table('candidates', function (Blueprint $table) {
            $indexes = collect(Schema::getConnection()->getDoctrineSchemaManager()
                ->listTableIndexes('candidates'))
                ->keys()
                ->toArray();

            if (!in_array('candidates_status_training_status_index', $indexes)) {
                $table->index(['status', 'training_status']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove indexes from candidates
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropIndex(['status', 'training_status']);
        });

        // Remove indexes from remittances
        Schema::table('remittances', function (Blueprint $table) {
            $table->dropIndex(['year', 'month']);
            $table->dropIndex(['is_first_remittance']);
            $table->dropIndex(['has_proof']);
            $table->dropIndex(['status']);
            $table->dropIndex(['candidate_id']);
        });

        // Drop class_enrollments table
        Schema::dropIfExists('class_enrollments');

        // Remove visa_partner FK from users (keep column for data preservation)
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['visa_partner_id']);
        });
    }
};
