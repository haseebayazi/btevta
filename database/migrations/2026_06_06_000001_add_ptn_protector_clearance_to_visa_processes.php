<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visa_processes', function (Blueprint $table) {
            if (! Schema::hasColumn('visa_processes', 'ptn_cleared')) {
                $table->boolean('ptn_cleared')->default(false)->after('ptn_number');
            }
            if (! Schema::hasColumn('visa_processes', 'protector_clearance_date')) {
                $table->date('protector_clearance_date')->nullable()->after('ptn_cleared');
            }
            if (! Schema::hasColumn('visa_processes', 'protector_clearance_status')) {
                $table->enum('protector_clearance_status', ['pending', 'approved', 'rejected'])
                    ->nullable()
                    ->after('protector_clearance_date');
            }
            if (! Schema::hasColumn('visa_processes', 'protector_clearance_remarks')) {
                $table->text('protector_clearance_remarks')->nullable()->after('protector_clearance_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('visa_processes', function (Blueprint $table) {
            $table->dropColumn([
                'ptn_cleared',
                'protector_clearance_date',
                'protector_clearance_status',
                'protector_clearance_remarks',
            ]);
        });
    }
};
