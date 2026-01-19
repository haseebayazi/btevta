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
        Schema::create('employers', function (Blueprint $table) {
            $table->id();
            $table->string('permission_number', 50)->nullable()->unique();
            $table->string('visa_issuing_company', 200);
            $table->foreignId('country_id')->constrained();
            $table->string('sector', 100)->nullable();
            $table->string('trade', 100)->nullable();

            // Employment Package
            $table->decimal('basic_salary', 12, 2)->nullable();
            $table->string('salary_currency', 3)->default('SAR');
            $table->boolean('food_by_company')->default(false);
            $table->boolean('transport_by_company')->default(false);
            $table->boolean('accommodation_by_company')->default(false);
            $table->text('other_conditions')->nullable();

            // Evidence
            $table->string('evidence_path', 500)->nullable();

            // Metadata
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('country_id');
            $table->index('is_active');
            $table->index('visa_issuing_company');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employers');
    }
};
