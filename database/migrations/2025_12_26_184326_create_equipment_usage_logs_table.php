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
        Schema::create('equipment_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained('campus_equipment')->onDelete('cascade');
            $table->foreignId('batch_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('usage_type'); // training, maintenance, idle, repair
            $table->datetime('start_time');
            $table->datetime('end_time')->nullable();
            $table->decimal('hours_used', 8, 2)->nullable();
            $table->integer('students_count')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('active'); // active, completed
            $table->timestamps();

            $table->index(['equipment_id', 'start_time']);
            $table->index(['batch_id', 'usage_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_usage_logs');
    }
};
