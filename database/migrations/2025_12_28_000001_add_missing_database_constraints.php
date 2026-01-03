<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * AUDIT FIX: Add missing database constraints identified during system audit
 *
 * Issues Addressed:
 * - DB-001: Missing FK users.visa_partner_id → visa_partners
 * - DB-002: Missing class_enrollments pivot table
 * - DB-004: Missing indexes on remittances table
 *
 * Updated for Laravel 11 compatibility (removed deprecated Doctrine methods)
 */
return new class extends Migration
{
    /**
     * Check if an index exists on a table (Laravel 11 compatible)
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = Schema::getIndexes($table);
        foreach ($indexes as $index) {
            if ($index['name'] === $indexName) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if a foreign key exists on a table (Laravel 11 compatible)
     */
    private function foreignKeyExists(string $table, string $foreignKeyName): bool
    {
        $foreignKeys = Schema::getForeignKeys($table);
        foreach ($foreignKeys as $fk) {
            if ($fk['name'] === $foreignKeyName) {
                return true;
            }
        }
        return false;
    }

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
        } elseif (!$this->foreignKeyExists('users', 'users_visa_partner_id_foreign')) {
            // Column exists but FK may be missing - add FK only
            Schema::table('users', function (Blueprint $table) {
                $table->foreign('visa_partner_id')
                    ->references('id')
                    ->on('visa_partners')
                    ->onDelete('set null');
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
        $addCandidateIndex = !$this->indexExists('remittances', 'remittances_candidate_id_index') &&
                             !$this->indexExists('remittances', 'remittances_candidate_id_foreign');
        $addStatusIndex = !$this->indexExists('remittances', 'remittances_status_index');
        $addHasProofIndex = !$this->indexExists('remittances', 'remittances_has_proof_index');
        $addFirstRemittanceIndex = !$this->indexExists('remittances', 'remittances_is_first_remittance_index');
        $addYearMonthIndex = !$this->indexExists('remittances', 'remittances_year_month_index');

        if ($addCandidateIndex || $addStatusIndex || $addHasProofIndex || $addFirstRemittanceIndex || $addYearMonthIndex) {
            Schema::table('remittances', function (Blueprint $table) use ($addCandidateIndex, $addStatusIndex, $addHasProofIndex, $addFirstRemittanceIndex, $addYearMonthIndex) {
                if ($addCandidateIndex) {
                    $table->index('candidate_id');
                }
                if ($addStatusIndex) {
                    $table->index('status');
                }
                if ($addHasProofIndex) {
                    $table->index('has_proof');
                }
                if ($addFirstRemittanceIndex) {
                    $table->index('is_first_remittance');
                }
                if ($addYearMonthIndex) {
                    $table->index(['year', 'month']);
                }
            });
        }

        // 4. Add composite index for common candidate queries
        if (!$this->indexExists('candidates', 'candidates_status_training_status_index')) {
            Schema::table('candidates', function (Blueprint $table) {
                $table->index(['status', 'training_status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove indexes from candidates
        if ($this->indexExists('candidates', 'candidates_status_training_status_index')) {
            Schema::table('candidates', function (Blueprint $table) {
                $table->dropIndex(['status', 'training_status']);
            });
        }

        // Remove indexes from remittances
        $dropYearMonthIndex = $this->indexExists('remittances', 'remittances_year_month_index');
        $dropFirstRemittanceIndex = $this->indexExists('remittances', 'remittances_is_first_remittance_index');
        $dropHasProofIndex = $this->indexExists('remittances', 'remittances_has_proof_index');
        $dropStatusIndex = $this->indexExists('remittances', 'remittances_status_index');
        $dropCandidateIndex = $this->indexExists('remittances', 'remittances_candidate_id_index');

        if ($dropYearMonthIndex || $dropFirstRemittanceIndex || $dropHasProofIndex || $dropStatusIndex || $dropCandidateIndex) {
            Schema::table('remittances', function (Blueprint $table) use ($dropYearMonthIndex, $dropFirstRemittanceIndex, $dropHasProofIndex, $dropStatusIndex, $dropCandidateIndex) {
                if ($dropYearMonthIndex) {
                    $table->dropIndex(['year', 'month']);
                }
                if ($dropFirstRemittanceIndex) {
                    $table->dropIndex(['is_first_remittance']);
                }
                if ($dropHasProofIndex) {
                    $table->dropIndex(['has_proof']);
                }
                if ($dropStatusIndex) {
                    $table->dropIndex(['status']);
                }
                if ($dropCandidateIndex) {
                    $table->dropIndex(['candidate_id']);
                }
            });
        }

        // Drop class_enrollments table
        Schema::dropIfExists('class_enrollments');

        // Remove visa_partner FK from users (keep column for data preservation)
        if ($this->foreignKeyExists('users', 'users_visa_partner_id_foreign')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['visa_partner_id']);
            });
        }
    }
};
