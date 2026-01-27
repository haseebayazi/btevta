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
        Schema::create('candidate_licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->enum('license_type', ['driving', 'professional'])->default('professional');
            $table->string('license_name', 100);
            $table->string('license_number', 50);
            $table->string('license_category', 50)->nullable();
            $table->string('issuing_authority', 150)->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('file_path', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('candidate_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidate_licenses');
    }
};
