<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instructors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('cnic')->unique();
            $table->string('email')->unique();
            $table->string('phone');
            $table->text('address')->nullable();
            $table->string('qualification')->nullable();
            $table->string('specialization')->nullable();
            $table->integer('experience_years')->default(0);
            $table->foreignId('campus_id')->nullable()->constrained('campuses')->onDelete('set null');
            $table->foreignId('trade_id')->nullable()->constrained('trades')->onDelete('set null');
            $table->enum('employment_type', ['permanent', 'contract', 'visiting'])->default('permanent');
            $table->date('joining_date')->nullable();
            $table->enum('status', ['active', 'inactive', 'on_leave', 'terminated'])->default('active');
            $table->string('photo_path')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['campus_id', 'status']);
            $table->index('trade_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instructors');
    }
};
