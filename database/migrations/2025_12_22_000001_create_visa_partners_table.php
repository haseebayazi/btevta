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
        // Create visa_partners table
        if (!Schema::hasTable('visa_partners')) {
            Schema::create('visa_partners', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('code')->unique()->nullable();
                $table->string('company_name')->nullable();
                $table->string('registration_number')->nullable();
                $table->string('license_number')->nullable();
                $table->string('contact_person')->nullable();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->text('address')->nullable();
                $table->string('website')->nullable();
                $table->string('country')->nullable();
                $table->string('city')->nullable();
                $table->string('specialization')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->softDeletes();
                $table->timestamps();

                $table->index('is_active');
                $table->index('country');
            });
        }

        // Add visa_partner_id to users table
        if (!Schema::hasColumn('users', 'visa_partner_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('visa_partner_id')->nullable()->after('oep_id');
                $table->foreign('visa_partner_id')->references('id')->on('visa_partners')->onDelete('set null');
            });
        }

        // Add visa_partner_id to candidates table (optional - for visa partner assignment)
        if (!Schema::hasColumn('candidates', 'visa_partner_id')) {
            Schema::table('candidates', function (Blueprint $table) {
                $table->unsignedBigInteger('visa_partner_id')->nullable()->after('oep_id');
                $table->foreign('visa_partner_id')->references('id')->on('visa_partners')->onDelete('set null');
            });
        }

        // Add visa_partner_id to visa_processes table
        if (Schema::hasTable('visa_processes') && !Schema::hasColumn('visa_processes', 'visa_partner_id')) {
            Schema::table('visa_processes', function (Blueprint $table) {
                $table->unsignedBigInteger('visa_partner_id')->nullable()->after('candidate_id');
                $table->foreign('visa_partner_id')->references('id')->on('visa_partners')->onDelete('set null');
            });
        }

        // Add phone column to users table if missing
        if (!Schema::hasColumn('users', 'phone')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('phone', 20)->nullable()->after('is_active');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove foreign keys and columns first
        if (Schema::hasColumn('visa_processes', 'visa_partner_id')) {
            Schema::table('visa_processes', function (Blueprint $table) {
                $table->dropForeign(['visa_partner_id']);
                $table->dropColumn('visa_partner_id');
            });
        }

        if (Schema::hasColumn('candidates', 'visa_partner_id')) {
            Schema::table('candidates', function (Blueprint $table) {
                $table->dropForeign(['visa_partner_id']);
                $table->dropColumn('visa_partner_id');
            });
        }

        if (Schema::hasColumn('users', 'visa_partner_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['visa_partner_id']);
                $table->dropColumn('visa_partner_id');
            });
        }

        Schema::dropIfExists('visa_partners');
    }
};
