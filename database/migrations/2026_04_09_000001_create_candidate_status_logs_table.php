<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 50)->nullable();
            $table->string('to_status', 50);
            $table->string('reason', 500)->nullable();
            $table->text('notes')->nullable();
            $table->json('context')->nullable();
            $table->foreignId('changed_by')->constrained('users');
            $table->timestamp('changed_at');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();

            $table->index(['candidate_id', 'changed_at']);
            $table->index('to_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_status_logs');
    }
};
