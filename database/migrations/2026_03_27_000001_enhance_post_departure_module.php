<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Enhance post_departure_details table with new columns
        Schema::table('post_departure_details', function (Blueprint $table) {
            // Add candidate_id reference
            if (!Schema::hasColumn('post_departure_details', 'candidate_id')) {
                $table->foreignId('candidate_id')->after('id')->constrained()->cascadeOnDelete();
            }

            // Iqama enhanced fields
            if (!Schema::hasColumn('post_departure_details', 'iqama_number')) {
                $table->string('iqama_number', 50)->nullable()->after('residency_number');
            }
            if (!Schema::hasColumn('post_departure_details', 'iqama_issue_date')) {
                $table->date('iqama_issue_date')->nullable()->after('iqama_number');
            }
            if (!Schema::hasColumn('post_departure_details', 'iqama_expiry_date')) {
                $table->date('iqama_expiry_date')->nullable()->after('iqama_issue_date');
            }
            if (!Schema::hasColumn('post_departure_details', 'iqama_evidence_path')) {
                $table->string('iqama_evidence_path', 500)->nullable()->after('iqama_expiry_date');
            }
            if (!Schema::hasColumn('post_departure_details', 'iqama_status')) {
                $table->enum('iqama_status', ['pending', 'issued', 'expired', 'renewed'])->default('pending')->after('iqama_evidence_path');
            }

            // Foreign License enhanced fields
            if (!Schema::hasColumn('post_departure_details', 'foreign_license_type')) {
                $table->string('foreign_license_type', 100)->nullable()->after('foreign_license_number');
            }
            if (!Schema::hasColumn('post_departure_details', 'foreign_license_expiry')) {
                $table->date('foreign_license_expiry')->nullable()->after('foreign_license_type');
            }

            // Foreign Contact enhanced
            if (!Schema::hasColumn('post_departure_details', 'foreign_mobile_carrier')) {
                $table->string('foreign_mobile_carrier', 50)->nullable()->after('foreign_mobile_number');
            }
            if (!Schema::hasColumn('post_departure_details', 'foreign_address')) {
                $table->string('foreign_address', 500)->nullable()->after('foreign_mobile_carrier');
            }

            // Foreign Bank enhanced
            if (!Schema::hasColumn('post_departure_details', 'foreign_bank_iban')) {
                $table->string('foreign_bank_iban', 50)->nullable()->after('foreign_bank_account');
            }
            if (!Schema::hasColumn('post_departure_details', 'foreign_bank_swift')) {
                $table->string('foreign_bank_swift', 20)->nullable()->after('foreign_bank_iban');
            }
            if (!Schema::hasColumn('post_departure_details', 'foreign_bank_evidence_path')) {
                $table->string('foreign_bank_evidence_path', 500)->nullable()->after('foreign_bank_swift');
            }

            // Tracking App enhanced
            if (!Schema::hasColumn('post_departure_details', 'tracking_app_name')) {
                $table->string('tracking_app_name', 50)->nullable()->after('tracking_app_registration');
            }
            if (!Schema::hasColumn('post_departure_details', 'tracking_app_id')) {
                $table->string('tracking_app_id', 100)->nullable()->after('tracking_app_name');
            }
            if (!Schema::hasColumn('post_departure_details', 'tracking_app_registered')) {
                $table->boolean('tracking_app_registered')->default(false)->after('tracking_app_id');
            }
            if (!Schema::hasColumn('post_departure_details', 'tracking_app_registered_date')) {
                $table->date('tracking_app_registered_date')->nullable()->after('tracking_app_registered');
            }
            if (!Schema::hasColumn('post_departure_details', 'tracking_app_evidence_path')) {
                $table->string('tracking_app_evidence_path', 500)->nullable()->after('tracking_app_registered_date');
            }

            // WPS (Wage Protection System)
            if (!Schema::hasColumn('post_departure_details', 'wps_registered')) {
                $table->boolean('wps_registered')->default(false)->after('tracking_app_evidence_path');
            }
            if (!Schema::hasColumn('post_departure_details', 'wps_registration_date')) {
                $table->date('wps_registration_date')->nullable()->after('wps_registered');
            }
            if (!Schema::hasColumn('post_departure_details', 'wps_evidence_path')) {
                $table->string('wps_evidence_path', 500)->nullable()->after('wps_registration_date');
            }

            // Employment Contract (Qiwa Agreement)
            if (!Schema::hasColumn('post_departure_details', 'contract_number')) {
                $table->string('contract_number', 100)->nullable()->after('final_contract_path');
            }
            if (!Schema::hasColumn('post_departure_details', 'contract_start_date')) {
                $table->date('contract_start_date')->nullable()->after('contract_number');
            }
            if (!Schema::hasColumn('post_departure_details', 'contract_end_date')) {
                $table->date('contract_end_date')->nullable()->after('contract_start_date');
            }
            if (!Schema::hasColumn('post_departure_details', 'contract_evidence_path')) {
                $table->string('contract_evidence_path', 500)->nullable()->after('contract_end_date');
            }
            if (!Schema::hasColumn('post_departure_details', 'contract_status')) {
                $table->enum('contract_status', ['pending', 'active', 'completed', 'terminated'])->default('pending')->after('contract_evidence_path');
            }

            // 90-Day Compliance
            if (!Schema::hasColumn('post_departure_details', 'compliance_verified')) {
                $table->boolean('compliance_verified')->default(false)->after('contract_status');
            }
            if (!Schema::hasColumn('post_departure_details', 'compliance_verified_date')) {
                $table->date('compliance_verified_date')->nullable()->after('compliance_verified');
            }
            if (!Schema::hasColumn('post_departure_details', 'compliance_verified_by')) {
                $table->foreignId('compliance_verified_by')->nullable()->after('compliance_verified_date')->constrained('users')->nullOnDelete();
            }

            // Soft deletes
            if (!Schema::hasColumn('post_departure_details', 'deleted_at')) {
                $table->softDeletes();
            }

            // Indexes
            $table->index('iqama_status');
            $table->index('contract_status');
            $table->index('compliance_verified');
        });

        // Enhance employment_histories table
        Schema::table('employment_histories', function (Blueprint $table) {
            // Add candidate_id
            if (!Schema::hasColumn('employment_histories', 'candidate_id')) {
                $table->foreignId('candidate_id')->after('id')->constrained()->cascadeOnDelete();
            }

            // Add post_departure_detail_id
            if (!Schema::hasColumn('employment_histories', 'post_departure_detail_id')) {
                $table->foreignId('post_departure_detail_id')->nullable()->after('candidate_id')->constrained()->cascadeOnDelete();
            }

            // Add employer_id
            if (!Schema::hasColumn('employment_histories', 'employer_id')) {
                $table->foreignId('employer_id')->nullable()->after('post_departure_detail_id')->constrained()->nullOnDelete();
            }

            // Enhanced employer fields
            if (!Schema::hasColumn('employment_histories', 'company_address')) {
                $table->string('company_address', 500)->nullable()->after('company_name');
            }
            if (!Schema::hasColumn('employment_histories', 'employer_contact_name')) {
                $table->string('employer_contact_name', 100)->nullable()->after('company_address');
            }
            if (!Schema::hasColumn('employment_histories', 'employer_contact_phone')) {
                $table->string('employer_contact_phone', 20)->nullable()->after('employer_contact_name');
            }
            if (!Schema::hasColumn('employment_histories', 'employer_contact_email')) {
                $table->string('employer_contact_email', 150)->nullable()->after('employer_contact_phone');
            }

            // Position details
            if (!Schema::hasColumn('employment_histories', 'position_title')) {
                $table->string('position_title', 100)->nullable()->after('employer_contact_email');
            }
            if (!Schema::hasColumn('employment_histories', 'department')) {
                $table->string('department', 100)->nullable()->after('position_title');
            }

            // Enhanced compensation
            if (!Schema::hasColumn('employment_histories', 'base_salary')) {
                $table->decimal('base_salary', 12, 2)->nullable()->after('department');
            }
            if (!Schema::hasColumn('employment_histories', 'housing_allowance')) {
                $table->decimal('housing_allowance', 12, 2)->nullable()->after('base_salary');
            }
            if (!Schema::hasColumn('employment_histories', 'food_allowance')) {
                $table->decimal('food_allowance', 12, 2)->nullable()->after('housing_allowance');
            }
            if (!Schema::hasColumn('employment_histories', 'transport_allowance')) {
                $table->decimal('transport_allowance', 12, 2)->nullable()->after('food_allowance');
            }
            if (!Schema::hasColumn('employment_histories', 'other_allowance')) {
                $table->decimal('other_allowance', 12, 2)->nullable()->after('transport_allowance');
            }
            if (!Schema::hasColumn('employment_histories', 'benefits')) {
                $table->json('benefits')->nullable()->after('other_allowance');
            }

            // End date and contract
            if (!Schema::hasColumn('employment_histories', 'end_date')) {
                $table->date('end_date')->nullable()->after('commencement_date');
            }
            if (!Schema::hasColumn('employment_histories', 'terms_conditions')) {
                $table->text('terms_conditions')->nullable()->after('end_date');
            }
            if (!Schema::hasColumn('employment_histories', 'contract_path')) {
                $table->string('contract_path', 500)->nullable()->after('terms_conditions');
            }

            // Status tracking
            if (!Schema::hasColumn('employment_histories', 'status')) {
                $table->enum('status', ['current', 'previous', 'terminated'])->default('current')->after('contract_path');
            }
            if (!Schema::hasColumn('employment_histories', 'sequence')) {
                $table->integer('sequence')->default(1)->after('status');
            }
            if (!Schema::hasColumn('employment_histories', 'termination_reason')) {
                $table->string('termination_reason', 500)->nullable()->after('sequence');
            }

            // Soft deletes
            if (!Schema::hasColumn('employment_histories', 'deleted_at')) {
                $table->softDeletes();
            }

            // Indexes
            $table->index(['candidate_id', 'status']);
            $table->index('sequence');
        });

        // Create company_switch_logs table
        Schema::create('company_switch_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_employment_id')->nullable()->constrained('employment_histories')->nullOnDelete();
            $table->foreignId('to_employment_id')->nullable()->constrained('employment_histories')->nullOnDelete();

            $table->integer('switch_number'); // 1 = first switch, 2 = second switch
            $table->date('switch_date');
            $table->string('reason', 500)->nullable();
            $table->enum('status', ['pending', 'approved', 'completed', 'rejected'])->default('pending');

            // Documentation
            $table->string('release_letter_path', 500)->nullable();
            $table->string('new_contract_path', 500)->nullable();
            $table->string('approval_document_path', 500)->nullable();

            // Approval
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['candidate_id', 'switch_number']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_switch_logs');

        Schema::table('employment_histories', function (Blueprint $table) {
            $columns = [
                'candidate_id', 'post_departure_detail_id', 'employer_id',
                'company_address', 'employer_contact_name', 'employer_contact_phone', 'employer_contact_email',
                'position_title', 'department',
                'base_salary', 'housing_allowance', 'food_allowance', 'transport_allowance', 'other_allowance', 'benefits',
                'end_date', 'terms_conditions', 'contract_path',
                'status', 'sequence', 'termination_reason', 'deleted_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('employment_histories', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('post_departure_details', function (Blueprint $table) {
            $columns = [
                'candidate_id',
                'iqama_number', 'iqama_issue_date', 'iqama_expiry_date', 'iqama_evidence_path', 'iqama_status',
                'foreign_license_type', 'foreign_license_expiry',
                'foreign_mobile_carrier', 'foreign_address',
                'foreign_bank_iban', 'foreign_bank_swift', 'foreign_bank_evidence_path',
                'tracking_app_name', 'tracking_app_id', 'tracking_app_registered', 'tracking_app_registered_date', 'tracking_app_evidence_path',
                'wps_registered', 'wps_registration_date', 'wps_evidence_path',
                'contract_number', 'contract_start_date', 'contract_end_date', 'contract_evidence_path', 'contract_status',
                'compliance_verified', 'compliance_verified_date', 'compliance_verified_by',
                'deleted_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('post_departure_details', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
