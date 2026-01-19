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
        Schema::table('complaints', function (Blueprint $table) {
            $table->text('current_issue')->nullable()->after('description');
            $table->text('support_steps_taken')->nullable()->after('current_issue');
            $table->text('suggestions')->nullable()->after('support_steps_taken');
            $table->text('conclusion')->nullable()->after('suggestions');
            $table->enum('evidence_type', ['audio', 'video', 'screenshot', 'document', 'other'])
                ->nullable()->after('evidence_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropColumn([
                'current_issue',
                'support_steps_taken',
                'suggestions',
                'conclusion',
                'evidence_type'
            ]);
        });
    }
};
