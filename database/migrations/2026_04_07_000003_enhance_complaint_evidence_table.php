<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaint_evidence', function (Blueprint $table) {
            if (! Schema::hasColumn('complaint_evidence', 'evidence_category')) {
                $table->enum('evidence_category', [
                    'initial_report',
                    'supporting_document',
                    'photo_video',
                    'witness_statement',
                    'communication_record',
                    'resolution_proof',
                    'other',
                ])->default('supporting_document')->after('file_path');
            }
            if (! Schema::hasColumn('complaint_evidence', 'is_confidential')) {
                $table->boolean('is_confidential')->default(false)->after('evidence_category');
            }
            if (! Schema::hasColumn('complaint_evidence', 'verified')) {
                $table->boolean('verified')->default(false)->after('is_confidential');
            }
            if (! Schema::hasColumn('complaint_evidence', 'verified_by')) {
                $table->foreignId('verified_by')->nullable()->after('verified')
                    ->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('complaint_evidence', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('verified_by');
            }
        });

        try {
            Schema::table('complaint_evidence', function (Blueprint $table) {
                $table->index('evidence_category');
            });
        } catch (\Throwable $e) {
            // Index already exists — skip
        }
    }

    public function down(): void
    {
        Schema::table('complaint_evidence', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $cols = ['evidence_category', 'is_confidential', 'verified', 'verified_by', 'verified_at'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('complaint_evidence', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
