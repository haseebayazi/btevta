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
        Schema::create('employment_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('departure_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('switch_number'); // 1 = First Switch, 2 = Second Switch
            $table->string('company_name', 200);
            $table->string('work_location', 200)->nullable();
            $table->decimal('salary', 12, 2)->nullable();
            $table->string('salary_currency', 3)->default('SAR');
            $table->text('job_terms')->nullable();
            $table->date('commencement_date')->nullable();
            $table->text('special_conditions')->nullable();
            $table->date('switch_date');
            $table->text('reason_for_switch')->nullable();
            $table->foreignId('recorded_by')->constrained('users');
            $table->timestamps();

            $table->unique(['departure_id', 'switch_number']);
            $table->index('departure_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employment_histories');
    }
};
