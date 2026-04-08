<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaint_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('category', 50);
            $table->text('description_template');
            $table->json('required_evidence_types')->nullable();
            $table->json('suggested_actions')->nullable();
            $table->enum('default_priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->integer('suggested_sla_hours')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_templates');
    }
};
