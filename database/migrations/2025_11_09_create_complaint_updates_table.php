<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaint_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complaint_id')->constrained('complaints')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('message');
            $table->string('status_changed_from')->nullable();
            $table->string('status_changed_to')->nullable();
            $table->string('priority_changed_from')->nullable();
            $table->string('priority_changed_to')->nullable();
            $table->unsignedBigInteger('assigned_from')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->boolean('is_internal')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['complaint_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_updates');
    }
};
