<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('success_story_evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('success_story_id')->constrained()->cascadeOnDelete();

            $table->enum('evidence_type', ['photo', 'video', 'document', 'interview', 'testimonial', 'certificate']);
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('file_path', 500);
            $table->string('mime_type', 100)->nullable();
            $table->integer('file_size')->nullable();
            $table->string('thumbnail_path', 500)->nullable();

            $table->boolean('is_primary')->default(false);
            $table->integer('display_order')->default(0);

            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();

            $table->index(['success_story_id', 'evidence_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('success_story_evidence');
    }
};
