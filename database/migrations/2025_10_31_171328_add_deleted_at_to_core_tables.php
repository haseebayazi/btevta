<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add soft deletes to campuses
        if (!Schema::hasColumn('campuses', 'deleted_at')) {
            Schema::table('campuses', function (Blueprint $table) {
                $table->softDeletes()->after('updated_at');
            });
        }

        // Add soft deletes to trades
        if (!Schema::hasColumn('trades', 'deleted_at')) {
            Schema::table('trades', function (Blueprint $table) {
                $table->softDeletes()->after('updated_at');
            });
        }

        // Add soft deletes to oeps
        if (!Schema::hasColumn('oeps', 'deleted_at')) {
            Schema::table('oeps', function (Blueprint $table) {
                $table->softDeletes()->after('updated_at');
            });
        }
    }

    public function down(): void
    {
        Schema::table('campuses', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('trades', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('oeps', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
