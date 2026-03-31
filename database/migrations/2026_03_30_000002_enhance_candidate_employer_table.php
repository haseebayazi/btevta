<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidate_employer', function (Blueprint $table) {
            if (!Schema::hasColumn('candidate_employer', 'employment_type')) {
                $table->enum('employment_type', ['initial', 'transfer', 'switch'])
                    ->default('initial')->after('employer_id');
            }
            if (!Schema::hasColumn('candidate_employer', 'assignment_date')) {
                $table->date('assignment_date')->nullable()->after('employment_type');
            }
            if (!Schema::hasColumn('candidate_employer', 'custom_package')) {
                $table->json('custom_package')->nullable()->after('assignment_date');
            }
            if (!Schema::hasColumn('candidate_employer', 'status')) {
                $table->enum('status', ['pending', 'active', 'completed', 'cancelled'])
                    ->default('pending')->after('custom_package');
            }

            $table->index(['candidate_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('candidate_employer', function (Blueprint $table) {
            $columns = ['employment_type', 'assignment_date', 'custom_package', 'status'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('candidate_employer', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
