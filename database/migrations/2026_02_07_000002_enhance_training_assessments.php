<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_assessments', function (Blueprint $table) {
            if (!Schema::hasColumn('training_assessments', 'training_id')) {
                $table->unsignedBigInteger('training_id')->nullable()->after('id');
                $table->foreign('training_id')->references('id')->on('trainings')->onDelete('set null');
                $table->index('training_id');
            }
            if (!Schema::hasColumn('training_assessments', 'training_type')) {
                $table->enum('training_type', ['technical', 'soft_skills', 'both'])
                    ->default('both')
                    ->after('assessment_type');
                $table->index('training_type');
            }
            if (!Schema::hasColumn('training_assessments', 'evidence_path')) {
                $table->string('evidence_path', 500)->nullable()->after('remarks');
            }
        });
    }

    public function down(): void
    {
        Schema::table('training_assessments', function (Blueprint $table) {
            if (Schema::hasColumn('training_assessments', 'training_id')) {
                $table->dropForeign(['training_id']);
                $table->dropColumn('training_id');
            }
            if (Schema::hasColumn('training_assessments', 'training_type')) {
                $table->dropColumn('training_type');
            }
            if (Schema::hasColumn('training_assessments', 'evidence_path')) {
                $table->dropColumn('evidence_path');
            }
        });
    }
};
