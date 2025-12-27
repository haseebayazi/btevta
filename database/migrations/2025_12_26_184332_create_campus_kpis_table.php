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
        Schema::create('campus_kpis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campus_id')->constrained()->onDelete('cascade');
            $table->integer('year');
            $table->integer('month');

            // Candidate Metrics
            $table->integer('candidates_registered')->default(0);
            $table->integer('candidates_trained')->default(0);
            $table->integer('candidates_departed')->default(0);
            $table->integer('candidates_rejected')->default(0);

            // Training Metrics
            $table->decimal('training_completion_rate', 5, 2)->default(0);
            $table->decimal('assessment_pass_rate', 5, 2)->default(0);
            $table->decimal('attendance_rate', 5, 2)->default(0);

            // Compliance Metrics
            $table->decimal('document_compliance_rate', 5, 2)->default(0);
            $table->decimal('complaint_resolution_rate', 5, 2)->default(0);
            $table->decimal('ninety_day_compliance_rate', 5, 2)->default(0);

            // Financial Metrics
            $table->decimal('funding_allocated', 12, 2)->default(0);
            $table->decimal('funding_utilized', 12, 2)->default(0);
            $table->decimal('cost_per_candidate', 10, 2)->default(0);

            // Equipment Metrics
            $table->decimal('equipment_utilization_rate', 5, 2)->default(0);
            $table->integer('equipment_maintenance_count')->default(0);

            // Overall Performance Score (calculated)
            $table->decimal('performance_score', 5, 2)->default(0);
            $table->string('performance_grade')->nullable(); // A, B, C, D, F

            $table->text('notes')->nullable();
            $table->foreignId('calculated_by')->nullable()->constrained('users');
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            $table->unique(['campus_id', 'year', 'month']);
            $table->index(['year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campus_kpis');
    }
};
