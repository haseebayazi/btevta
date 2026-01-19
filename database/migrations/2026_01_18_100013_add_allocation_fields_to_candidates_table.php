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
        Schema::table('candidates', function (Blueprint $table) {
            $table->foreignId('program_id')->nullable()->after('campus_id')->constrained()->nullOnDelete();
            $table->foreignId('implementing_partner_id')->nullable()->after('oep_id')->constrained()->nullOnDelete();
            $table->string('allocated_number', 50)->nullable()->unique()->after('batch_id');

            $table->index('program_id');
            $table->index('implementing_partner_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropForeign(['program_id']);
            $table->dropForeign(['implementing_partner_id']);
            $table->dropIndex(['program_id']);
            $table->dropIndex(['implementing_partner_id']);
            $table->dropColumn(['program_id', 'implementing_partner_id', 'allocated_number']);
        });
    }
};
