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
        Schema::table('registration_documents', function (Blueprint $table) {
            // Add audit fields for tracking who uploaded/verified/updated documents
            if (!Schema::hasColumn('registration_documents', 'uploaded_by')) {
                $table->foreignId('uploaded_by')->nullable()->after('file_path')->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('registration_documents', 'verified_by')) {
                $table->foreignId('verified_by')->nullable()->after('uploaded_by')->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('registration_documents', 'verification_status')) {
                $table->string('verification_status')->nullable()->after('status');
            }
            if (!Schema::hasColumn('registration_documents', 'verification_remarks')) {
                $table->text('verification_remarks')->nullable()->after('verification_status');
            }
            if (!Schema::hasColumn('registration_documents', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('verification_remarks');
            }
            if (!Schema::hasColumn('registration_documents', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('rejection_reason')->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('registration_documents', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registration_documents', function (Blueprint $table) {
            $table->dropForeign(['uploaded_by']);
            $table->dropForeign(['verified_by']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropColumn(['uploaded_by', 'verified_by', 'verification_status', 'verification_remarks', 'rejection_reason', 'created_by', 'updated_by']);
        });
    }
};
