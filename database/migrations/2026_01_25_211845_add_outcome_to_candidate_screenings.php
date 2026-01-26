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
        Schema::table('candidate_screenings', function (Blueprint $table) {
            if (!Schema::hasColumn('candidate_screenings', 'outcome')) {
                $table->string('outcome')->nullable()->after('status');
            }
            if (!Schema::hasColumn('candidate_screenings', 'screening_date')) {
                $table->date('screening_date')->nullable()->after('screening_type');
            }
            if (!Schema::hasColumn('candidate_screenings', 'screener_name')) {
                $table->string('screener_name')->nullable()->after('screening_date');
            }
            if (!Schema::hasColumn('candidate_screenings', 'contact_method')) {
                $table->string('contact_method')->nullable()->after('screener_name');
            }
            if (!Schema::hasColumn('candidate_screenings', 'next_steps')) {
                $table->text('next_steps')->nullable()->after('remarks');
            }
            if (!Schema::hasColumn('candidate_screenings', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('screened_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidate_screenings', function (Blueprint $table) {
            $table->dropColumn(['outcome', 'screening_date', 'screener_name', 'contact_method', 'next_steps', 'completed_at']);
        });
    }
};
