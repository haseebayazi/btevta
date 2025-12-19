<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * TRAINING SCHEDULES: This table stores the training session schedules
     * for each batch, including modules, instructors, and timing.
     */
    public function up(): void
    {
        Schema::create('training_schedules', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('batch_id')
                  ->constrained('batches')
                  ->onDelete('cascade');

            $table->foreignId('campus_id')
                  ->nullable()
                  ->constrained('campuses')
                  ->onDelete('set null');

            $table->foreignId('instructor_id')
                  ->nullable()
                  ->constrained('instructors')
                  ->onDelete('set null');

            $table->foreignId('trade_id')
                  ->nullable()
                  ->constrained('trades')
                  ->onDelete('set null');

            // Module/Session details
            $table->string('module_name');
            $table->text('module_description')->nullable();
            $table->integer('module_number')->default(1);
            $table->integer('duration_hours')->default(2);

            // Scheduling
            $table->date('scheduled_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room')->nullable();
            $table->string('building')->nullable();

            // Status
            $table->enum('status', [
                'scheduled',
                'in_progress',
                'completed',
                'cancelled',
                'postponed',
                'rescheduled'
            ])->default('scheduled');

            // Completion tracking
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('actual_duration_minutes')->nullable();

            // Attendance summary (denormalized for performance)
            $table->integer('expected_attendees')->default(0);
            $table->integer('actual_attendees')->nullable();
            $table->decimal('attendance_percentage', 5, 2)->nullable();

            // Notes
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->date('rescheduled_to')->nullable();

            // Audit trail
            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');
            $table->foreignId('updated_by')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('batch_id');
            $table->index('campus_id');
            $table->index('instructor_id');
            $table->index('scheduled_date');
            $table->index('status');
            $table->index(['batch_id', 'scheduled_date']);
            $table->index(['campus_id', 'scheduled_date']);
            $table->index(['instructor_id', 'scheduled_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_schedules');
    }
};
