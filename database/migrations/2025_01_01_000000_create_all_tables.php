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
        // Create campuses table FIRST (no dependencies)
        Schema::create('campuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->unique();
            $table->text('address');
            $table->string('city');
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
            $table->index('is_active');
        });

        // Create OEPs table SECOND (no dependencies)
        Schema::create('oeps', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('company_name')->nullable();
            $table->string('registration_number')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('contact_person')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
            $table->index('is_active');
        });

        // Create trades table THIRD (no dependencies)
        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->integer('duration_months')->default(12);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
            $table->index('is_active');
        });

        // Create users table FOURTH (depends on campuses, oeps)
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->default('candidate'); // admin, campus_admin, oep, trainer, candidate
            $table->unsignedBigInteger('campus_id')->nullable();
            $table->unsignedBigInteger('oep_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
            
            $table->foreign('campus_id')->references('id')->on('campuses')->onDelete('set null');
            $table->foreign('oep_id')->references('id')->on('oeps')->onDelete('set null');
            $table->index(['email', 'role', 'campus_id', 'oep_id']);
        });

        // Create batches table (depends on trades, campuses, oeps)
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('trade_id');
            $table->unsignedBigInteger('campus_id');
            $table->unsignedBigInteger('oep_id')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->integer('capacity')->default(30);
            $table->string('status')->default('planned'); // planned, active, completed, cancelled
            $table->softDeletes();
            $table->timestamps();
            
            $table->foreign('trade_id')->references('id')->on('trades')->onDelete('cascade');
            $table->foreign('campus_id')->references('id')->on('campuses')->onDelete('cascade');
            $table->foreign('oep_id')->references('id')->on('oeps')->onDelete('set null');
            $table->index('status');
        });

        // Create candidates table (depends on trades, campuses, batches, oeps)
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->string('btevta_id')->unique();
            $table->string('application_id')->unique()->nullable();
            $table->string('cnic', 13)->unique();
            $table->string('name');
            $table->string('father_name');
            $table->date('date_of_birth');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->string('phone', 20);
            $table->string('email')->nullable();
            $table->text('address');
            $table->string('district', 100);
            $table->string('tehsil', 100)->nullable();
            $table->unsignedBigInteger('trade_id');
            $table->unsignedBigInteger('campus_id')->nullable();
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->unsignedBigInteger('oep_id')->nullable();
            $table->string('status')->default('new'); // new, screening, registered, training, visa, departed, rejected
            $table->string('photo_path')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            $table->foreign('trade_id')->references('id')->on('trades')->onDelete('cascade');
            $table->foreign('campus_id')->references('id')->on('campuses')->onDelete('set null');
            $table->foreign('batch_id')->references('id')->on('batches')->onDelete('set null');
            $table->foreign('oep_id')->references('id')->on('oeps')->onDelete('set null');
            $table->index(['status', 'campus_id', 'trade_id']);
        });

        // Other tables remain same...

        Schema::create('correspondence', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campus_id')->nullable();
            $table->unsignedBigInteger('oep_id')->nullable();
            $table->string('subject');
            $table->text('content');
            $table->date('correspondence_date');
            $table->date('reply_date')->nullable();
            $table->text('reply_content')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            $table->foreign('campus_id')->references('id')->on('campuses')->onDelete('set null');
            $table->foreign('oep_id')->references('id')->on('oeps')->onDelete('set null');
        });

        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('candidate_id')->nullable();
            $table->unsignedBigInteger('campus_id')->nullable();
            $table->unsignedBigInteger('oep_id')->nullable();
            $table->string('subject');
            $table->text('description');
            $table->string('status')->default('open');
            $table->date('complaint_date');
            $table->date('resolution_date')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->string('evidence_path')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            $table->foreign('candidate_id')->references('id')->on('candidates')->onDelete('set null');
            $table->foreign('campus_id')->references('id')->on('campuses')->onDelete('set null');
            $table->foreign('oep_id')->references('id')->on('oeps')->onDelete('set null');
            $table->index('status');
            $table->dateTime('registered_at')->nullable();
            $table->integer('sla_days')->default(7);
        });

        Schema::create('document_archives', function (Blueprint $table) {
            $table->id();
            $table->string('document_name');
            $table->string('document_type');
            $table->string('file_path');
            $table->unsignedBigInteger('candidate_id')->nullable();
            $table->date('upload_date');
            $table->date('expiry_date')->nullable();
            $table->string('version')->default('1');
            $table->softDeletes();
            $table->timestamps();
            
            $table->foreign('candidate_id')->references('id')->on('candidates')->onDelete('set null');
        });

        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key')->unique();
            $table->text('setting_value')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('document_archives');
        Schema::dropIfExists('complaints');
        Schema::dropIfExists('correspondence');
        Schema::dropIfExists('candidates');
        Schema::dropIfExists('batches');
        Schema::dropIfExists('users');
        Schema::dropIfExists('trades');
        Schema::dropIfExists('oeps');
        Schema::dropIfExists('campuses');
    }
};
