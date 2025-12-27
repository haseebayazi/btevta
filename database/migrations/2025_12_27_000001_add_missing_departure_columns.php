<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add missing columns to departures table that are in Departure model $fillable
 * but were not in any previous migration.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('departures')) {
            Schema::table('departures', function (Blueprint $table) {
                // Accommodation type column (model expects this)
                if (!Schema::hasColumn('departures', 'accommodation_type')) {
                    $table->string('accommodation_type', 50)->nullable()->after('accommodation_address');
                }

                // Compliance verified date column (model expects this)
                if (!Schema::hasColumn('departures', 'compliance_verified_date')) {
                    $table->date('compliance_verified_date')->nullable()->after('last_contact_date');
                }

                // Employer address column (model expects this)
                if (!Schema::hasColumn('departures', 'employer_address')) {
                    $table->text('employer_address')->nullable()->after('employer_contact');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('departures')) {
            Schema::table('departures', function (Blueprint $table) {
                $columns = ['accommodation_type', 'compliance_verified_date', 'employer_address'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('departures', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
