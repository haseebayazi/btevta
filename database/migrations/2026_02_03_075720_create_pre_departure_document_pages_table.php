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
        Schema::create('pre_departure_document_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pre_departure_document_id')
                ->constrained('pre_departure_documents')
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('page_number')->default(1);
            $table->string('file_path', 500);
            $table->string('original_filename', 255);
            $table->string('mime_type', 100);
            $table->unsignedInteger('file_size'); // bytes
            $table->timestamps();

            $table->index('pre_departure_document_id');
            $table->unique(['pre_departure_document_id', 'page_number'], 'document_page_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pre_departure_document_pages');
    }
};
