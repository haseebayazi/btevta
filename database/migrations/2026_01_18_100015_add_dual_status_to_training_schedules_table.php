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
        Schema::table('training_schedules', function (Blueprint $table) {
            $table->enum('technical_training_status', ['not_started', 'in_progress', 'completed'])
                ->default('not_started')->after('status');
            $table->enum('soft_skills_status', ['not_started', 'in_progress', 'completed'])
                ->default('not_started')->after('technical_training_status');

            $table->index('technical_training_status');
            $table->index('soft_skills_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_schedules', function (Blueprint $table) {
            $table->dropIndex(['technical_training_status']);
            $table->dropIndex(['soft_skills_status']);
            $table->dropColumn(['technical_training_status', 'soft_skills_status']);
        });
    }
};
