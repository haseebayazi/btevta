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
        // Helper function to check if index exists
        $indexExists = function($table, $indexName) {
            $indexes = \DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
            return !empty($indexes);
        };

        // Add unique constraint to oeps.registration_number
        if (Schema::hasTable('oeps') && Schema::hasColumn('oeps', 'registration_number')) {
            if (!$indexExists('oeps', 'oeps_registration_number_unique')) {
                Schema::table('oeps', function (Blueprint $table) {
                    $table->unique('registration_number', 'oeps_registration_number_unique');
                });
            }
        }

        // Add unique constraint to candidates.btevta_id (if exists)
        if (Schema::hasTable('candidates') && Schema::hasColumn('candidates', 'btevta_id')) {
            if (!$indexExists('candidates', 'candidates_btevta_id_unique')) {
                Schema::table('candidates', function (Blueprint $table) {
                    $table->unique('btevta_id', 'candidates_btevta_id_unique');
                });
            }
        }

        // Add unique constraint to complaint_reference in complaints table
        if (Schema::hasTable('complaints') && Schema::hasColumn('complaints', 'complaint_reference')) {
            if (!$indexExists('complaints', 'complaints_complaint_reference_unique')) {
                Schema::table('complaints', function (Blueprint $table) {
                    $table->unique('complaint_reference', 'complaints_complaint_reference_unique');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop unique constraint from oeps.registration_number
        Schema::table('oeps', function (Blueprint $table) {
            if (Schema::hasColumn('oeps', 'registration_number')) {
                $table->dropUnique('oeps_registration_number_unique');
            }
        });

        // Drop unique constraint from candidates.btevta_id
        if (Schema::hasTable('candidates') && Schema::hasColumn('candidates', 'btevta_id')) {
            Schema::table('candidates', function (Blueprint $table) {
                $table->dropUnique('candidates_btevta_id_unique');
            });
        }

        // Drop unique constraint from complaint_reference
        if (Schema::hasTable('complaints') && Schema::hasColumn('complaints', 'complaint_reference')) {
            Schema::table('complaints', function (Blueprint $table) {
                $table->dropUnique('complaints_complaint_reference_unique');
            });
        }
    }
};
