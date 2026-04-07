<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('correspondences', function (Blueprint $table) {
            $table->string('correspondence_type')->default('incoming')->after('status'); // incoming, outgoing
            $table->string('file_reference_number')->nullable()->after('correspondence_type');
            $table->unsignedBigInteger('created_by')->nullable()->after('file_reference_number');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
        });
    }

    public function down(): void
    {
        Schema::table('correspondences', function (Blueprint $table) {
            $table->dropColumn(['correspondence_type', 'file_reference_number', 'created_by', 'updated_by']);
        });
    }
};
