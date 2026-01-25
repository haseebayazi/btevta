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
        if (Schema::hasTable('document_archives')) {
            Schema::table('document_archives', function (Blueprint $table) {
                if (!Schema::hasColumn('document_archives', 'trade_id')) {
                    $table->unsignedBigInteger('trade_id')->nullable()->after('campus_id');
                }
                if (!Schema::hasColumn('document_archives', 'mime_type')) {
                    $table->string('mime_type', 100)->nullable()->after('file_type');
                }
                if (!Schema::hasColumn('document_archives', 'uploaded_by')) {
                    $table->unsignedBigInteger('uploaded_by')->nullable()->after('version');
                }
                if (!Schema::hasColumn('document_archives', 'uploaded_at')) {
                    $table->timestamp('uploaded_at')->nullable()->after('uploaded_by');
                }
                if (!Schema::hasColumn('document_archives', 'description')) {
                    $table->text('description')->nullable()->after('issue_date');
                }
                if (!Schema::hasColumn('document_archives', 'archived_at')) {
                    $table->timestamp('archived_at')->nullable()->after('is_current_version');
                }
                if (!Schema::hasColumn('document_archives', 'download_count')) {
                    $table->integer('download_count')->default(0)->after('archived_at');
                }
                if (!Schema::hasColumn('document_archives', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable()->after('download_count');
                }
                if (!Schema::hasColumn('document_archives', 'updated_by')) {
                    $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('document_archives')) {
            Schema::table('document_archives', function (Blueprint $table) {
                if (Schema::hasColumn('document_archives', 'trade_id')) {
                    $table->dropColumn('trade_id');
                }
                if (Schema::hasColumn('document_archives', 'mime_type')) {
                    $table->dropColumn('mime_type');
                }
                if (Schema::hasColumn('document_archives', 'uploaded_by')) {
                    $table->dropColumn('uploaded_by');
                }
                if (Schema::hasColumn('document_archives', 'uploaded_at')) {
                    $table->dropColumn('uploaded_at');
                }
                if (Schema::hasColumn('document_archives', 'description')) {
                    $table->dropColumn('description');
                }
                if (Schema::hasColumn('document_archives', 'archived_at')) {
                    $table->dropColumn('archived_at');
                }
                if (Schema::hasColumn('document_archives', 'download_count')) {
                    $table->dropColumn('download_count');
                }
                if (Schema::hasColumn('document_archives', 'created_by')) {
                    $table->dropColumn('created_by');
                }
                if (Schema::hasColumn('document_archives', 'updated_by')) {
                    $table->dropColumn('updated_by');
                }
            });
        }
    }
};
