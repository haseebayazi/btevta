<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('document_checklists', function (Blueprint $table) {
            $table->boolean('supports_multiple_pages')->default(false)->after('is_mandatory');
            $table->unsignedSmallInteger('max_pages')->default(1)->after('supports_multiple_pages');
        });

        // Enable multiple pages for CNIC, Passport, FRC, and Driving License
        DB::table('document_checklists')
            ->whereIn('code', ['cnic', 'passport', 'frc', 'driving_license'])
            ->update([
                'supports_multiple_pages' => true,
                'max_pages' => 5,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_checklists', function (Blueprint $table) {
            $table->dropColumn(['supports_multiple_pages', 'max_pages']);
        });
    }
};
