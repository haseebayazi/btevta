<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds financial account fields for NOK as per Module 3 requirements:
     * - Payment method (EasyPaisa, JazzCash, Bank Account)
     * - Account number
     * - Bank name (required for bank accounts)
     * - ID card document path
     * 
     * Also adds audit columns if missing (for databases created by earlier migrations).
     */
    public function up(): void
    {
        Schema::table('next_of_kins', function (Blueprint $table) {
            // Add audit columns if they don't exist (for existing databases)
            if (!Schema::hasColumn('next_of_kins', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('occupation');
            }
            if (!Schema::hasColumn('next_of_kins', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }
            if (!Schema::hasColumn('next_of_kins', 'monthly_income')) {
                $table->decimal('monthly_income', 10, 2)->nullable()->after('updated_by');
            }
            if (!Schema::hasColumn('next_of_kins', 'emergency_contact')) {
                $table->string('emergency_contact', 20)->nullable()->after('monthly_income');
            }
            
            // Add Module 3 financial fields
            if (!Schema::hasColumn('next_of_kins', 'payment_method_id')) {
                $table->unsignedBigInteger('payment_method_id')->nullable()->after('relationship');
            }
            if (!Schema::hasColumn('next_of_kins', 'account_number')) {
                $table->string('account_number', 50)->nullable()->after('payment_method_id');
            }
            if (!Schema::hasColumn('next_of_kins', 'bank_name')) {
                $table->string('bank_name', 100)->nullable()->after('account_number');
            }
            if (!Schema::hasColumn('next_of_kins', 'id_card_path')) {
                $table->string('id_card_path', 500)->nullable()->after('bank_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('next_of_kins', function (Blueprint $table) {
            if (Schema::hasColumn('next_of_kins', 'id_card_path')) {
                $table->dropColumn('id_card_path');
            }
            if (Schema::hasColumn('next_of_kins', 'bank_name')) {
                $table->dropColumn('bank_name');
            }
            if (Schema::hasColumn('next_of_kins', 'account_number')) {
                $table->dropColumn('account_number');
            }
            if (Schema::hasColumn('next_of_kins', 'payment_method_id')) {
                $table->dropColumn('payment_method_id');
            }
        });
    }
};
