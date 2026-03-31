<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employers', function (Blueprint $table) {
            // Permission Number dates & document
            if (!Schema::hasColumn('employers', 'permission_issue_date')) {
                $table->date('permission_issue_date')->nullable()->after('permission_number');
            }
            if (!Schema::hasColumn('employers', 'permission_expiry_date')) {
                $table->date('permission_expiry_date')->nullable()->after('permission_issue_date');
            }
            if (!Schema::hasColumn('employers', 'permission_document_path')) {
                $table->string('permission_document_path', 500)->nullable()->after('permission_expiry_date');
            }

            // Visa Issuing Company License
            if (!Schema::hasColumn('employers', 'visa_company_license')) {
                $table->string('visa_company_license', 100)->nullable()->after('visa_issuing_company');
            }

            // City
            if (!Schema::hasColumn('employers', 'city')) {
                $table->string('city', 100)->nullable()->after('country_id');
            }

            // Trade relationship (foreign key, alongside existing 'trade' string field)
            if (!Schema::hasColumn('employers', 'trade_id')) {
                $table->foreignId('trade_id')->nullable()->after('sector')
                    ->constrained()->nullOnDelete();
            }

            // Default Employment Package (JSON)
            if (!Schema::hasColumn('employers', 'default_package')) {
                $table->json('default_package')->nullable()->after('other_conditions');
            }

            // Company Size
            if (!Schema::hasColumn('employers', 'company_size')) {
                $table->enum('company_size', ['small', 'medium', 'large', 'enterprise'])
                    ->nullable()->after('default_package');
            }

            // Verification
            if (!Schema::hasColumn('employers', 'verified')) {
                $table->boolean('verified')->default(false)->after('company_size');
            }
            if (!Schema::hasColumn('employers', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('verified');
            }
            if (!Schema::hasColumn('employers', 'verified_by')) {
                $table->foreignId('verified_by')->nullable()->after('verified_at')
                    ->constrained('users')->nullOnDelete();
            }

            // Notes
            if (!Schema::hasColumn('employers', 'notes')) {
                $table->text('notes')->nullable()->after('verified_by');
            }

            // Indexes
            $table->index('permission_expiry_date');
            $table->index('sector');
            $table->index('verified');
        });
    }

    public function down(): void
    {
        Schema::table('employers', function (Blueprint $table) {
            $columns = [
                'permission_issue_date', 'permission_expiry_date', 'permission_document_path',
                'visa_company_license', 'city', 'trade_id', 'default_package',
                'company_size', 'verified', 'verified_at', 'verified_by', 'notes',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('employers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
