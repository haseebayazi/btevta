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
        if (!Schema::hasTable('scheduled_notifications')) {
            Schema::create('scheduled_notifications', function (Blueprint $table) {
                $table->id();
                $table->string('recipient_type');
                $table->unsignedBigInteger('recipient_id')->nullable();
                $table->string('recipient_value')->nullable();
                $table->string('type');
                $table->json('data');
                $table->json('channels');
                $table->timestamp('scheduled_for');
                $table->string('status')->default('pending');
                $table->timestamp('sent_at')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamp('failed_at')->nullable();
                $table->timestamps();

                // Indexes for efficient querying
                $table->index('status');
                $table->index('scheduled_for');
                $table->index(['status', 'scheduled_for']);
                $table->index(['recipient_type', 'recipient_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_notifications');
    }
};
