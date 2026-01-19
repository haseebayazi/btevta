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
        Schema::create('success_stories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('departure_id')->nullable()->constrained()->nullOnDelete();
            $table->text('written_note');
            $table->enum('evidence_type', ['audio', 'video', 'written', 'other'])->nullable();
            $table->string('evidence_path', 500)->nullable();
            $table->string('evidence_filename', 255)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->foreignId('recorded_by')->constrained('users');
            $table->timestamp('recorded_at');
            $table->timestamps();
            $table->softDeletes();

            $table->index('candidate_id');
            $table->index('is_featured');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('success_stories');
    }
};
