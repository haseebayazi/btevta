<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('correspondences', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('campus_id')->nullable();
            $table->unsignedBigInteger('oep_id')->nullable();
            $table->unsignedBigInteger('candidate_id')->nullable();

            $table->string('subject')->nullable();
            $table->text('message')->nullable();

            $table->boolean('requires_reply')->default(false);
            $table->boolean('replied')->default(false);

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('replied_at')->nullable();

            $table->string('status')->default('pending'); // pending, sent, replied, archived

            $table->string('attachment_path')->nullable();

            $table->timestamps();

            // Optional foreign keys
            // $table->foreign('campus_id')->references('id')->on('campuses')->nullOnDelete();
            // $table->foreign('oep_id')->references('id')->on('oeps')->nullOnDelete();
            // $table->foreign('candidate_id')->references('id')->on('candidates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('correspondences');
    }
};
