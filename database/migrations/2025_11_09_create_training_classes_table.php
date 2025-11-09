<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create training_classes table
        if (!Schema::hasTable('training_classes')) {
            Schema::create('training_classes', function (Blueprint $table) {
                $table->id();
                $table->string('class_name');
                $table->string('class_code')->unique();
                $table->foreignId('campus_id')->nullable()->constrained('campuses')->onDelete('set null');
                $table->foreignId('trade_id')->nullable()->constrained('trades')->onDelete('set null');
                $table->foreignId('instructor_id')->nullable()->constrained('instructors')->onDelete('set null');
                $table->foreignId('batch_id')->nullable()->constrained('batches')->onDelete('set null');
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->integer('max_capacity')->default(30);
                $table->integer('current_enrollment')->default(0);
                $table->text('schedule')->nullable();
                $table->string('room_number')->nullable();
                $table->enum('status', ['scheduled', 'ongoing', 'completed', 'cancelled'])->default('scheduled');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['campus_id', 'status']);
                $table->index('instructor_id');
                $table->index('start_date');
            });
        }

        // Create class_enrollments pivot table
        if (!Schema::hasTable('class_enrollments')) {
            Schema::create('class_enrollments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('training_class_id')->constrained('training_classes')->onDelete('cascade');
                $table->foreignId('candidate_id')->constrained('candidates')->onDelete('cascade');
                $table->timestamp('enrolled_at')->nullable();
                $table->enum('status', ['enrolled', 'completed', 'dropped', 'transferred'])->default('enrolled');
                $table->date('completion_date')->nullable();
                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->unique(['training_class_id', 'candidate_id']);
                $table->index('status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('class_enrollments');
        Schema::dropIfExists('training_classes');
    }
};
