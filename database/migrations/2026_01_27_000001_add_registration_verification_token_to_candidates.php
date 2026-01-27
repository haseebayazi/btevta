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
            if (!Schema::hasColumn('candidates', 'registration_verification_token')) {
                $table->string('registration_verification_token', 64)->nullable()->unique()->after('cnic');
            }

            if (!Schema::hasColumn('candidates', 'registration_verification_sent_at')) {
                $table->timestamp('registration_verification_sent_at')->nullable()->after('registration_verification_token');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            if (Schema::hasColumn('candidates', 'registration_verification_sent_at')) {
                $table->dropColumn('registration_verification_sent_at');
            }

            if (Schema::hasColumn('candidates', 'registration_verification_token')) {
                $table->dropUnique(['registration_verification_token']);
                $table->dropColumn('registration_verification_token');
            }
        });
    }
};