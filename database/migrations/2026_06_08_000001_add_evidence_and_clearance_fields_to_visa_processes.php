<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Visa Processing enhancements:
     * - Interview: evidence attachment
     * - Takamol / Medical: test center, appointment slip + result attachments, remarks
     * - PTN Clearance: issue date + uploaded document
     * - Protector Clearance: submission date, performed flag + uploaded document
     */
    public function up(): void
    {
        Schema::table('visa_processes', function (Blueprint $table) {
            // Interview evidence attachment
            if (! Schema::hasColumn('visa_processes', 'interview_evidence_path')) {
                $table->string('interview_evidence_path')->nullable()->after('interview_remarks');
            }

            // Takamol attachments & details
            if (! Schema::hasColumn('visa_processes', 'takamol_center')) {
                $table->string('takamol_center')->nullable()->after('takamol_status');
            }
            if (! Schema::hasColumn('visa_processes', 'takamol_appointment_slip_path')) {
                $table->string('takamol_appointment_slip_path')->nullable()->after('takamol_center');
            }
            if (! Schema::hasColumn('visa_processes', 'takamol_result_path')) {
                $table->string('takamol_result_path')->nullable()->after('takamol_appointment_slip_path');
            }
            if (! Schema::hasColumn('visa_processes', 'takamol_remarks')) {
                $table->text('takamol_remarks')->nullable()->after('takamol_result_path');
            }

            // Medical attachments & details
            if (! Schema::hasColumn('visa_processes', 'medical_center')) {
                $table->string('medical_center')->nullable()->after('medical_status');
            }
            if (! Schema::hasColumn('visa_processes', 'medical_appointment_slip_path')) {
                $table->string('medical_appointment_slip_path')->nullable()->after('medical_center');
            }
            if (! Schema::hasColumn('visa_processes', 'medical_result_path')) {
                $table->string('medical_result_path')->nullable()->after('medical_appointment_slip_path');
            }
            if (! Schema::hasColumn('visa_processes', 'medical_remarks')) {
                $table->text('medical_remarks')->nullable()->after('medical_result_path');
            }

            // PTN Clearance
            if (! Schema::hasColumn('visa_processes', 'ptn_issue_date')) {
                $table->date('ptn_issue_date')->nullable()->after('ptn_number');
            }
            if (! Schema::hasColumn('visa_processes', 'ptn_document_path')) {
                $table->string('ptn_document_path')->nullable()->after('ptn_issue_date');
            }

            // Protector Clearance
            if (! Schema::hasColumn('visa_processes', 'protector_submission_date')) {
                $table->date('protector_submission_date')->nullable()->after('protector_clearance_remarks');
            }
            if (! Schema::hasColumn('visa_processes', 'protector_performed')) {
                $table->boolean('protector_performed')->default(false)->after('protector_submission_date');
            }
            if (! Schema::hasColumn('visa_processes', 'protector_document_path')) {
                $table->string('protector_document_path')->nullable()->after('protector_performed');
            }
        });
    }

    public function down(): void
    {
        Schema::table('visa_processes', function (Blueprint $table) {
            $columns = [
                'interview_evidence_path',
                'takamol_center',
                'takamol_appointment_slip_path',
                'takamol_result_path',
                'takamol_remarks',
                'medical_center',
                'medical_appointment_slip_path',
                'medical_result_path',
                'medical_remarks',
                'ptn_issue_date',
                'ptn_document_path',
                'protector_submission_date',
                'protector_performed',
                'protector_document_path',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('visa_processes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
