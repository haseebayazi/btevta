<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('candidate_id');
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->string('status', 20)->default('not_started');
            $table->enum('technical_training_status', ['not_started', 'in_progress', 'completed'])
                ->default('not_started');
            $table->enum('soft_skills_status', ['not_started', 'in_progress', 'completed'])
                ->default('not_started');
            $table->timestamp('technical_completed_at')->nullable();
            $table->timestamp('soft_skills_completed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('candidate_id')->references('id')->on('candidates')->onDelete('cascade');
            $table->foreign('batch_id')->references('id')->on('batches')->onDelete('set null');

            $table->unique('candidate_id');
            $table->index('technical_training_status');
            $table->index('soft_skills_status');
            $table->index('status');
            $table->index('batch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainings');
    }
};
