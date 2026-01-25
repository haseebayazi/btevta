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
        if (Schema::hasTable('training_certificates')) {
            Schema::table('training_certificates', function (Blueprint $table) {
                if (!Schema::hasColumn('training_certificates', 'issued_by')) {
                    $table->unsignedBigInteger('issued_by')->nullable()->after('issue_date');
                }
                if (!Schema::hasColumn('training_certificates', 'trainer_id')) {
                    $table->unsignedBigInteger('trainer_id')->nullable()->after('issued_by');
                }
                if (!Schema::hasColumn('training_certificates', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable()->after('remarks');
                }
                if (!Schema::hasColumn('training_certificates', 'updated_by')) {
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
        if (Schema::hasTable('training_certificates')) {
            Schema::table('training_certificates', function (Blueprint $table) {
                $columns = ['issued_by', 'trainer_id', 'created_by', 'updated_by'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('training_certificates', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
