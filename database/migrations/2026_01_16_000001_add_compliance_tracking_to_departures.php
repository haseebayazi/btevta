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
        Schema::table('departures', function (Blueprint $table) {
            // 90-day compliance tracking fields
            $table->boolean('ninety_day_compliance_checked')->default(false)->after('ninety_day_report_submitted');
            $table->string('ninety_day_compliance_status')->nullable()->after('ninety_day_compliance_checked');
            $table->text('ninety_day_compliance_issues')->nullable()->after('ninety_day_compliance_status');
            $table->timestamp('ninety_day_compliance_checked_at')->nullable()->after('ninety_day_compliance_issues');

            // Salary verification tracking
            $table->unsignedBigInteger('salary_confirmed_by')->nullable()->after('salary_confirmed');
            $table->timestamp('salary_confirmed_at')->nullable()->after('salary_confirmed_by');

            // Indexes for performance
            $table->index('ninety_day_compliance_checked');
            $table->index('ninety_day_compliance_status');
            $table->index('salary_confirmed');

            // Foreign key for salary_confirmed_by
            $table->foreign('salary_confirmed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departures', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['salary_confirmed_by']);

            // Drop indexes
            $table->dropIndex(['ninety_day_compliance_checked']);
            $table->dropIndex(['ninety_day_compliance_status']);
            $table->dropIndex(['salary_confirmed']);

            // Drop columns
            $table->dropColumn([
                'ninety_day_compliance_checked',
                'ninety_day_compliance_status',
                'ninety_day_compliance_issues',
                'ninety_day_compliance_checked_at',
                'salary_confirmed_by',
                'salary_confirmed_at',
            ]);
        });
    }
};
