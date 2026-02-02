<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            if (!Schema::hasColumn('complaints', 'evidence_files')) {
                $table->json('evidence_files')->nullable()->after('evidence_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropColumn('evidence_files');
        });
    }
};
