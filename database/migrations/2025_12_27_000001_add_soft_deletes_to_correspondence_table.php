<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to add soft delete support to correspondence table.
 *
 * AUDIT FIX: The Correspondence model uses SoftDeletes trait but
 * the correspondences table was missing the deleted_at column.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('correspondence', function (Blueprint $table) {
            if (!Schema::hasColumn('correspondence', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('correspondence', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
