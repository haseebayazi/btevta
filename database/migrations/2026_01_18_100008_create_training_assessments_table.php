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
        Schema::create('training_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_schedule_id')->constrained('training_schedules')->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->enum('assessment_type', ['interim', 'final']);
            $table->decimal('score', 5, 2);
            $table->decimal('max_score', 5, 2)->default(100);
            $table->string('grade', 5)->nullable(); // A, B, C, D, F
            $table->string('evidence_path', 500)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('assessed_by')->constrained('users');
            $table->timestamp('assessed_at');
            $table->timestamps();

            $table->unique(['training_schedule_id', 'candidate_id', 'assessment_type'], 'unique_assessment');
            $table->index(['candidate_id', 'assessment_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_assessments');
    }
};
