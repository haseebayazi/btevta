# Module 7: Post-Departure Enhancement - Implementation Prompt for Claude

**Project:** BTEVTA WASL
**Module:** Module 7 - Post-Departure (Enhancement)
**Status:** Existing Module - Requires Significant Enhancement
**Date:** February 2026

---

## Executive Summary

Module 7 (Post-Departure) is currently **INTEGRATED into Module 6 (Departure)** with:
- 90-day compliance tracking
- Iqama, Absher, Qiwa, Salary tracking
- Issue tracking with UUID-based IDs
- Welfare monitoring dashboard

This prompt focuses on **MAJOR ENHANCEMENTS** for comprehensive post-departure tracking including:
- Residency & Identity management
- Foreign contact/bank details
- Employment tracking with company switches
- Final job contract management

**CRITICAL:** While enhancements are significant, they build on existing infrastructure.

---

## Pre-Implementation Analysis

### Step 1: Read Existing Implementation

```
# Controllers (integrated with Departure)
app/Http/Controllers/DepartureController.php (post-departure methods)

# Services
app/Services/DepartureService.php (post-departure methods)

# Models
app/Models/Departure.php
app/Models/PostDepartureDetail.php (if exists)

# Database
Check: php artisan tinker --execute="Schema::getColumnListing('post_departure_details')"

# Views
resources/views/departure/ (post-departure views)
```

### Step 2: Check PostDepartureDetail Model

Verify if PostDepartureDetail exists and its current structure:
```bash
php artisan tinker --execute="(new ReflectionClass('App\Models\PostDepartureDetail'))->getFileName()"
```

---

## Required Changes (from WASL_CHANGE_IMPACT_ANALYSIS.md)

| Change ID | Type | Description | Priority |
|-----------|------|-------------|----------|
| PD-001 | NEW | Residency & Identity section | HIGH |
| PD-002 | MODIFIED | Residency Proof (Iqama) with enhanced details | HIGH |
| PD-003 | NEW | Foreign License field | MEDIUM |
| PD-004 | NEW | Foreign Mobile Number | MEDIUM |
| PD-005 | NEW | Foreign Bank Account Details | HIGH |
| PD-006 | MODIFIED | Foreign Tracking App (Absher) with registration details | HIGH |
| PD-007 | NEW | Final job contract (Qiwa Agreement) | HIGH |
| PD-008 | NEW | Final Employment Details section | HIGH |
| PD-009 | NEW | Employment fields (Company, Contact, Salary, etc.) | HIGH |
| PD-010 | NEW | First Company SWITCH tracking | MEDIUM |
| PD-011 | NEW | Second Company SWITCH tracking | MEDIUM |

---

## Phase 1: Database Changes

### 1.1 Create/Enhance Post Departure Details Table

```php
// database/migrations/YYYY_MM_DD_create_or_enhance_post_departure_details.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create table if it doesn't exist, or add columns if it does
        if (!Schema::hasTable('post_departure_details')) {
            Schema::create('post_departure_details', function (Blueprint $table) {
                $table->id();
                $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
                $table->foreignId('departure_id')->constrained()->cascadeOnDelete();

                // Residency & Identity
                $table->string('iqama_number', 50)->nullable();
                $table->date('iqama_issue_date')->nullable();
                $table->date('iqama_expiry_date')->nullable();
                $table->string('iqama_evidence_path', 500)->nullable();
                $table->enum('iqama_status', ['pending', 'issued', 'expired', 'renewed'])->default('pending');

                // Foreign License
                $table->string('foreign_license_number', 50)->nullable();
                $table->string('foreign_license_type', 100)->nullable();
                $table->date('foreign_license_expiry')->nullable();
                $table->string('foreign_license_path', 500)->nullable();

                // Foreign Contact
                $table->string('foreign_mobile_number', 20)->nullable();
                $table->string('foreign_mobile_carrier', 50)->nullable();
                $table->string('foreign_address', 500)->nullable();

                // Foreign Bank Account
                $table->string('foreign_bank_name', 100)->nullable();
                $table->string('foreign_bank_account', 50)->nullable();
                $table->string('foreign_bank_iban', 50)->nullable();
                $table->string('foreign_bank_swift', 20)->nullable();
                $table->string('foreign_bank_evidence_path', 500)->nullable();

                // Tracking App (Absher/similar)
                $table->string('tracking_app_name', 50)->nullable();
                $table->string('tracking_app_id', 100)->nullable();
                $table->boolean('tracking_app_registered')->default(false);
                $table->date('tracking_app_registered_date')->nullable();
                $table->string('tracking_app_evidence_path', 500)->nullable();

                // WPS (Wage Protection System)
                $table->boolean('wps_registered')->default(false);
                $table->date('wps_registration_date')->nullable();
                $table->string('wps_evidence_path', 500)->nullable();

                // Employment Contract (Qiwa Agreement)
                $table->string('contract_number', 100)->nullable();
                $table->date('contract_start_date')->nullable();
                $table->date('contract_end_date')->nullable();
                $table->string('contract_evidence_path', 500)->nullable();
                $table->enum('contract_status', ['pending', 'active', 'completed', 'terminated'])->default('pending');

                // 90-Day Compliance
                $table->boolean('compliance_verified')->default(false);
                $table->date('compliance_verified_date')->nullable();
                $table->foreignId('compliance_verified_by')->nullable()->constrained('users')->nullOnDelete();

                $table->timestamps();
                $table->softDeletes();

                $table->unique('candidate_id');
                $table->index('iqama_status');
                $table->index('contract_status');
                $table->index('compliance_verified');
            });
        } else {
            // Add missing columns to existing table
            Schema::table('post_departure_details', function (Blueprint $table) {
                // Add columns that don't exist...
                // Check each column before adding
            });
        }
    }
};
```

### 1.2 Create Employment History Table

```php
// database/migrations/YYYY_MM_DD_create_employment_history_table.php
Schema::create('employment_history', function (Blueprint $table) {
    $table->id();
    $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
    $table->foreignId('post_departure_detail_id')->constrained()->cascadeOnDelete();

    // Employer Details
    $table->foreignId('employer_id')->nullable()->constrained()->nullOnDelete();
    $table->string('company_name', 200);
    $table->string('company_address', 500)->nullable();
    $table->string('employer_contact_name', 100)->nullable();
    $table->string('employer_contact_phone', 20)->nullable();
    $table->string('employer_contact_email', 150)->nullable();

    // Position Details
    $table->string('position_title', 100)->nullable();
    $table->string('department', 100)->nullable();
    $table->string('work_location', 200)->nullable();

    // Compensation
    $table->decimal('base_salary', 12, 2)->nullable();
    $table->string('salary_currency', 10)->default('SAR');
    $table->decimal('housing_allowance', 12, 2)->nullable();
    $table->decimal('food_allowance', 12, 2)->nullable();
    $table->decimal('transport_allowance', 12, 2)->nullable();
    $table->decimal('other_allowance', 12, 2)->nullable();
    $table->json('benefits')->nullable(); // health insurance, vacation days, etc.

    // Dates
    $table->date('commencement_date');
    $table->date('end_date')->nullable();

    // Terms & Conditions
    $table->text('terms_conditions')->nullable();
    $table->string('contract_path', 500)->nullable();

    // Status
    $table->enum('status', ['current', 'previous', 'terminated'])->default('current');
    $table->integer('sequence')->default(1); // 1 = first employer, 2 = first switch, 3 = second switch
    $table->string('termination_reason', 500)->nullable();

    $table->timestamps();
    $table->softDeletes();

    $table->index(['candidate_id', 'status']);
    $table->index('sequence');
});
```

### 1.3 Create Company Switch Log Table

```php
// database/migrations/YYYY_MM_DD_create_company_switch_log_table.php
Schema::create('company_switch_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
    $table->foreignId('from_employment_id')->nullable()->constrained('employment_history')->nullOnDelete();
    $table->foreignId('to_employment_id')->nullable()->constrained('employment_history')->nullOnDelete();

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
```

---

## Phase 2: Create Enums

### 2.1 IqamaStatus Enum

```php
// app/Enums/IqamaStatus.php
<?php

namespace App\Enums;

enum IqamaStatus: string
{
    case PENDING = 'pending';
    case ISSUED = 'issued';
    case EXPIRED = 'expired';
    case RENEWED = 'renewed';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::ISSUED => 'Issued',
            self::EXPIRED => 'Expired',
            self::RENEWED => 'Renewed',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::ISSUED => 'success',
            self::EXPIRED => 'danger',
            self::RENEWED => 'info',
        };
    }
}
```

### 2.2 ContractStatus Enum

```php
// app/Enums/ContractStatus.php
<?php

namespace App\Enums;

enum ContractStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case TERMINATED = 'terminated';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::ACTIVE => 'Active',
            self::COMPLETED => 'Completed',
            self::TERMINATED => 'Terminated',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'secondary',
            self::ACTIVE => 'success',
            self::COMPLETED => 'info',
            self::TERMINATED => 'danger',
        };
    }
}
```

### 2.3 EmploymentStatus Enum

```php
// app/Enums/EmploymentStatus.php
<?php

namespace App\Enums;

enum EmploymentStatus: string
{
    case CURRENT = 'current';
    case PREVIOUS = 'previous';
    case TERMINATED = 'terminated';

    public function label(): string
    {
        return match($this) {
            self::CURRENT => 'Current',
            self::PREVIOUS => 'Previous',
            self::TERMINATED => 'Terminated',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::CURRENT => 'success',
            self::PREVIOUS => 'secondary',
            self::TERMINATED => 'danger',
        };
    }
}
```

### 2.4 SwitchStatus Enum

```php
// app/Enums/SwitchStatus.php
<?php

namespace App\Enums;

enum SwitchStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case COMPLETED = 'completed';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending Approval',
            self::APPROVED => 'Approved',
            self::COMPLETED => 'Completed',
            self::REJECTED => 'Rejected',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::APPROVED => 'info',
            self::COMPLETED => 'success',
            self::REJECTED => 'danger',
        };
    }
}
```

---

## Phase 3: Create Models

### 3.1 PostDepartureDetail Model (Create or Update)

```php
// app/Models/PostDepartureDetail.php
<?php

namespace App\Models;

use App\Enums\IqamaStatus;
use App\Enums\ContractStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PostDepartureDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'candidate_id',
        'departure_id',
        // Iqama
        'iqama_number',
        'iqama_issue_date',
        'iqama_expiry_date',
        'iqama_evidence_path',
        'iqama_status',
        // Foreign License
        'foreign_license_number',
        'foreign_license_type',
        'foreign_license_expiry',
        'foreign_license_path',
        // Foreign Contact
        'foreign_mobile_number',
        'foreign_mobile_carrier',
        'foreign_address',
        // Foreign Bank
        'foreign_bank_name',
        'foreign_bank_account',
        'foreign_bank_iban',
        'foreign_bank_swift',
        'foreign_bank_evidence_path',
        // Tracking App
        'tracking_app_name',
        'tracking_app_id',
        'tracking_app_registered',
        'tracking_app_registered_date',
        'tracking_app_evidence_path',
        // WPS
        'wps_registered',
        'wps_registration_date',
        'wps_evidence_path',
        // Contract
        'contract_number',
        'contract_start_date',
        'contract_end_date',
        'contract_evidence_path',
        'contract_status',
        // Compliance
        'compliance_verified',
        'compliance_verified_date',
        'compliance_verified_by',
    ];

    protected $casts = [
        'iqama_issue_date' => 'date',
        'iqama_expiry_date' => 'date',
        'iqama_status' => IqamaStatus::class,
        'foreign_license_expiry' => 'date',
        'tracking_app_registered' => 'boolean',
        'tracking_app_registered_date' => 'date',
        'wps_registered' => 'boolean',
        'wps_registration_date' => 'date',
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'contract_status' => ContractStatus::class,
        'compliance_verified' => 'boolean',
        'compliance_verified_date' => 'date',
    ];

    // Relationships
    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function departure()
    {
        return $this->belongsTo(Departure::class);
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'compliance_verified_by');
    }

    public function employmentHistory()
    {
        return $this->hasMany(EmploymentHistory::class)->orderBy('sequence');
    }

    public function currentEmployment()
    {
        return $this->hasOne(EmploymentHistory::class)->where('status', 'current');
    }

    public function companySwitches()
    {
        return $this->hasManyThrough(
            CompanySwitchLog::class,
            Candidate::class,
            'id',
            'candidate_id',
            'candidate_id',
            'id'
        );
    }

    // Accessors
    public function getIqamaExpiringAttribute(): bool
    {
        if (!$this->iqama_expiry_date) return false;
        return $this->iqama_expiry_date->diffInDays(now()) <= 30;
    }

    public function getTotalSalaryAttribute(): float
    {
        $employment = $this->currentEmployment;
        if (!$employment) return 0;

        return ($employment->base_salary ?? 0)
            + ($employment->housing_allowance ?? 0)
            + ($employment->food_allowance ?? 0)
            + ($employment->transport_allowance ?? 0)
            + ($employment->other_allowance ?? 0);
    }

    public function getSwitchCountAttribute(): int
    {
        return $this->employmentHistory()->count() - 1;
    }

    // Methods
    public function updateIqama(array $data, $evidenceFile = null): void
    {
        if ($evidenceFile) {
            $data['iqama_evidence_path'] = $this->uploadFile($evidenceFile, 'iqama');
        }

        $this->fill($data);
        $this->save();

        $this->logActivity('Iqama details updated', $data);
    }

    public function updateForeignContact(array $data): void
    {
        $this->fill([
            'foreign_mobile_number' => $data['mobile_number'] ?? $this->foreign_mobile_number,
            'foreign_mobile_carrier' => $data['carrier'] ?? $this->foreign_mobile_carrier,
            'foreign_address' => $data['address'] ?? $this->foreign_address,
        ]);
        $this->save();

        $this->logActivity('Foreign contact updated');
    }

    public function updateForeignBank(array $data, $evidenceFile = null): void
    {
        if ($evidenceFile) {
            $data['foreign_bank_evidence_path'] = $this->uploadFile($evidenceFile, 'bank');
        }

        $this->fill([
            'foreign_bank_name' => $data['bank_name'] ?? null,
            'foreign_bank_account' => $data['account_number'] ?? null,
            'foreign_bank_iban' => $data['iban'] ?? null,
            'foreign_bank_swift' => $data['swift'] ?? null,
            'foreign_bank_evidence_path' => $data['foreign_bank_evidence_path'] ?? $this->foreign_bank_evidence_path,
        ]);
        $this->save();

        $this->logActivity('Foreign bank details updated');
    }

    public function registerTrackingApp(string $appName, string $appId, $evidenceFile = null): void
    {
        $evidencePath = $evidenceFile ? $this->uploadFile($evidenceFile, 'tracking-app') : null;

        $this->fill([
            'tracking_app_name' => $appName,
            'tracking_app_id' => $appId,
            'tracking_app_registered' => true,
            'tracking_app_registered_date' => now(),
            'tracking_app_evidence_path' => $evidencePath,
        ]);
        $this->save();

        $this->logActivity("Registered on {$appName}", ['app_id' => $appId]);
    }

    public function updateContract(array $data, $contractFile = null): void
    {
        if ($contractFile) {
            $data['contract_evidence_path'] = $this->uploadFile($contractFile, 'contract');
        }

        $this->fill([
            'contract_number' => $data['contract_number'] ?? $this->contract_number,
            'contract_start_date' => $data['start_date'] ?? $this->contract_start_date,
            'contract_end_date' => $data['end_date'] ?? $this->contract_end_date,
            'contract_evidence_path' => $data['contract_evidence_path'] ?? $this->contract_evidence_path,
            'contract_status' => $data['status'] ?? $this->contract_status,
        ]);
        $this->save();

        $this->logActivity('Contract details updated');
    }

    public function markComplianceVerified(): void
    {
        $this->compliance_verified = true;
        $this->compliance_verified_date = now();
        $this->compliance_verified_by = auth()->id();
        $this->save();

        $this->candidate->update(['status' => 'post_departure']);

        $this->logActivity('90-day compliance verified');
    }

    public function getComplianceChecklist(): array
    {
        return [
            'iqama' => [
                'label' => 'Iqama/Residency',
                'complete' => $this->iqama_status === IqamaStatus::ISSUED,
                'expiring' => $this->iqama_expiring,
            ],
            'tracking_app' => [
                'label' => 'Tracking App (Absher)',
                'complete' => $this->tracking_app_registered,
            ],
            'wps' => [
                'label' => 'WPS Registration',
                'complete' => $this->wps_registered,
            ],
            'contract' => [
                'label' => 'Employment Contract',
                'complete' => $this->contract_status === ContractStatus::ACTIVE,
            ],
            'bank' => [
                'label' => 'Foreign Bank Account',
                'complete' => !empty($this->foreign_bank_account),
            ],
            'contact' => [
                'label' => 'Foreign Contact Details',
                'complete' => !empty($this->foreign_mobile_number),
            ],
        ];
    }

    protected function uploadFile($file, string $subfolder): string
    {
        $candidateId = $this->candidate_id;
        $timestamp = now()->format('Y-m-d_His');
        $extension = $file->getClientOriginalExtension();
        $filename = "{$subfolder}_{$candidateId}_{$timestamp}.{$extension}";

        return $file->storeAs(
            "post-departure/{$candidateId}/{$subfolder}",
            $filename,
            'private'
        );
    }

    protected function logActivity(string $message, array $properties = []): void
    {
        activity()
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->withProperties($properties)
            ->log($message);
    }
}
```

### 3.2 EmploymentHistory Model

```php
// app/Models/EmploymentHistory.php
<?php

namespace App\Models;

use App\Enums\EmploymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmploymentHistory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'employment_history';

    protected $fillable = [
        'candidate_id',
        'post_departure_detail_id',
        'employer_id',
        'company_name',
        'company_address',
        'employer_contact_name',
        'employer_contact_phone',
        'employer_contact_email',
        'position_title',
        'department',
        'work_location',
        'base_salary',
        'salary_currency',
        'housing_allowance',
        'food_allowance',
        'transport_allowance',
        'other_allowance',
        'benefits',
        'commencement_date',
        'end_date',
        'terms_conditions',
        'contract_path',
        'status',
        'sequence',
        'termination_reason',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'housing_allowance' => 'decimal:2',
        'food_allowance' => 'decimal:2',
        'transport_allowance' => 'decimal:2',
        'other_allowance' => 'decimal:2',
        'benefits' => 'array',
        'commencement_date' => 'date',
        'end_date' => 'date',
        'status' => EmploymentStatus::class,
    ];

    // Relationships
    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function postDepartureDetail()
    {
        return $this->belongsTo(PostDepartureDetail::class);
    }

    public function employer()
    {
        return $this->belongsTo(Employer::class);
    }

    // Accessors
    public function getTotalPackageAttribute(): float
    {
        return ($this->base_salary ?? 0)
            + ($this->housing_allowance ?? 0)
            + ($this->food_allowance ?? 0)
            + ($this->transport_allowance ?? 0)
            + ($this->other_allowance ?? 0);
    }

    public function getEmploymentDurationAttribute(): ?string
    {
        if (!$this->commencement_date) return null;

        $endDate = $this->end_date ?? now();
        $diff = $this->commencement_date->diff($endDate);

        if ($diff->y > 0) {
            return $diff->y . ' year(s), ' . $diff->m . ' month(s)';
        }
        return $diff->m . ' month(s), ' . $diff->d . ' day(s)';
    }

    public function getSequenceLabelAttribute(): string
    {
        return match($this->sequence) {
            1 => 'Initial Employment',
            2 => 'First Company Switch',
            3 => 'Second Company Switch',
            default => "Employment #{$this->sequence}",
        };
    }

    // Methods
    public function terminate(string $reason, ?string $endDate = null): void
    {
        $this->status = EmploymentStatus::TERMINATED;
        $this->termination_reason = $reason;
        $this->end_date = $endDate ?? now();
        $this->save();

        activity()
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->withProperties(['reason' => $reason])
            ->log('Employment terminated');
    }

    public function markAsPrevious(): void
    {
        $this->status = EmploymentStatus::PREVIOUS;
        $this->end_date = $this->end_date ?? now();
        $this->save();
    }
}
```

### 3.3 CompanySwitchLog Model

```php
// app/Models/CompanySwitchLog.php
<?php

namespace App\Models;

use App\Enums\SwitchStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CompanySwitchLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_id',
        'from_employment_id',
        'to_employment_id',
        'switch_number',
        'switch_date',
        'reason',
        'status',
        'release_letter_path',
        'new_contract_path',
        'approval_document_path',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'switch_date' => 'date',
        'status' => SwitchStatus::class,
        'approved_at' => 'datetime',
    ];

    // Relationships
    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function fromEmployment()
    {
        return $this->belongsTo(EmploymentHistory::class, 'from_employment_id');
    }

    public function toEmployment()
    {
        return $this->belongsTo(EmploymentHistory::class, 'to_employment_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Methods
    public function approve(?string $notes = null): void
    {
        $this->status = SwitchStatus::APPROVED;
        $this->approved_by = auth()->id();
        $this->approved_at = now();
        $this->notes = $notes;
        $this->save();

        activity()
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->log('Company switch approved');
    }

    public function complete(): void
    {
        // Mark previous employment as ended
        if ($this->fromEmployment) {
            $this->fromEmployment->markAsPrevious();
        }

        // Mark new employment as current
        if ($this->toEmployment) {
            $this->toEmployment->update(['status' => EmploymentStatus::CURRENT]);
        }

        $this->status = SwitchStatus::COMPLETED;
        $this->save();

        activity()
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->log('Company switch completed');
    }

    public function reject(string $reason): void
    {
        $this->status = SwitchStatus::REJECTED;
        $this->notes = $reason;
        $this->save();

        activity()
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->withProperties(['reason' => $reason])
            ->log('Company switch rejected');
    }
}
```

---

## Phase 4: Create Service

### 4.1 PostDepartureService

```php
// app/Services/PostDepartureService.php
<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\PostDepartureDetail;
use App\Models\EmploymentHistory;
use App\Models\CompanySwitchLog;
use App\Enums\EmploymentStatus;
use Illuminate\Support\Facades\DB;

class PostDepartureService
{
    /**
     * Get or create post departure details for candidate
     */
    public function getOrCreateDetails(Candidate $candidate): PostDepartureDetail
    {
        return PostDepartureDetail::firstOrCreate(
            ['candidate_id' => $candidate->id],
            ['departure_id' => $candidate->departure?->id]
        );
    }

    /**
     * Update iqama/residency details
     */
    public function updateIqama(PostDepartureDetail $detail, array $data, $evidenceFile = null): void
    {
        $detail->updateIqama($data, $evidenceFile);
    }

    /**
     * Update foreign license
     */
    public function updateForeignLicense(PostDepartureDetail $detail, array $data, $licenseFile = null): void
    {
        if ($licenseFile) {
            $data['foreign_license_path'] = $detail->uploadFile($licenseFile, 'license');
        }

        $detail->fill([
            'foreign_license_number' => $data['license_number'] ?? null,
            'foreign_license_type' => $data['license_type'] ?? null,
            'foreign_license_expiry' => $data['expiry_date'] ?? null,
            'foreign_license_path' => $data['foreign_license_path'] ?? $detail->foreign_license_path,
        ]);
        $detail->save();
    }

    /**
     * Update foreign contact details
     */
    public function updateForeignContact(PostDepartureDetail $detail, array $data): void
    {
        $detail->updateForeignContact($data);
    }

    /**
     * Update foreign bank details
     */
    public function updateForeignBank(PostDepartureDetail $detail, array $data, $evidenceFile = null): void
    {
        $detail->updateForeignBank($data, $evidenceFile);
    }

    /**
     * Register tracking app (Absher)
     */
    public function registerTrackingApp(
        PostDepartureDetail $detail,
        string $appName,
        string $appId,
        $evidenceFile = null
    ): void {
        $detail->registerTrackingApp($appName, $appId, $evidenceFile);
    }

    /**
     * Register WPS
     */
    public function registerWPS(PostDepartureDetail $detail, $evidenceFile = null): void
    {
        $evidencePath = $evidenceFile
            ? $detail->uploadFile($evidenceFile, 'wps')
            : null;

        $detail->fill([
            'wps_registered' => true,
            'wps_registration_date' => now(),
            'wps_evidence_path' => $evidencePath,
        ]);
        $detail->save();

        activity()
            ->performedOn($detail)
            ->causedBy(auth()->user())
            ->log('WPS registered');
    }

    /**
     * Update employment contract (Qiwa)
     */
    public function updateContract(PostDepartureDetail $detail, array $data, $contractFile = null): void
    {
        $detail->updateContract($data, $contractFile);
    }

    /**
     * Add initial employment
     */
    public function addInitialEmployment(PostDepartureDetail $detail, array $data, $contractFile = null): EmploymentHistory
    {
        return DB::transaction(function () use ($detail, $data, $contractFile) {
            $contractPath = $contractFile
                ? $detail->uploadFile($contractFile, 'employment-contract')
                : null;

            $employment = EmploymentHistory::create([
                'candidate_id' => $detail->candidate_id,
                'post_departure_detail_id' => $detail->id,
                'employer_id' => $data['employer_id'] ?? null,
                'company_name' => $data['company_name'],
                'company_address' => $data['company_address'] ?? null,
                'employer_contact_name' => $data['contact_name'] ?? null,
                'employer_contact_phone' => $data['contact_phone'] ?? null,
                'employer_contact_email' => $data['contact_email'] ?? null,
                'position_title' => $data['position_title'] ?? null,
                'department' => $data['department'] ?? null,
                'work_location' => $data['work_location'] ?? null,
                'base_salary' => $data['base_salary'] ?? null,
                'salary_currency' => $data['currency'] ?? 'SAR',
                'housing_allowance' => $data['housing_allowance'] ?? null,
                'food_allowance' => $data['food_allowance'] ?? null,
                'transport_allowance' => $data['transport_allowance'] ?? null,
                'other_allowance' => $data['other_allowance'] ?? null,
                'benefits' => $data['benefits'] ?? null,
                'commencement_date' => $data['commencement_date'],
                'terms_conditions' => $data['terms_conditions'] ?? null,
                'contract_path' => $contractPath,
                'status' => EmploymentStatus::CURRENT,
                'sequence' => 1,
            ]);

            activity()
                ->performedOn($employment)
                ->causedBy(auth()->user())
                ->log('Initial employment recorded');

            return $employment;
        });
    }

    /**
     * Initiate company switch
     */
    public function initiateCompanySwitch(
        PostDepartureDetail $detail,
        array $newEmploymentData,
        string $reason,
        $releaseLetterFile = null,
        $newContractFile = null
    ): CompanySwitchLog {
        return DB::transaction(function () use ($detail, $newEmploymentData, $reason, $releaseLetterFile, $newContractFile) {
            $currentEmployment = $detail->currentEmployment;

            if (!$currentEmployment) {
                throw new \Exception('No current employment found to switch from.');
            }

            $switchCount = CompanySwitchLog::where('candidate_id', $detail->candidate_id)
                ->whereIn('status', ['approved', 'completed'])
                ->count();

            if ($switchCount >= 2) {
                throw new \Exception('Maximum of 2 company switches allowed.');
            }

            $switchNumber = $switchCount + 1;

            // Create new employment record (pending)
            $newEmployment = EmploymentHistory::create(array_merge($newEmploymentData, [
                'candidate_id' => $detail->candidate_id,
                'post_departure_detail_id' => $detail->id,
                'sequence' => $currentEmployment->sequence + 1,
                'status' => EmploymentStatus::PREVIOUS, // Will become current when switch completes
            ]));

            // Upload documents
            $releaseLetterPath = $releaseLetterFile
                ? $detail->uploadFile($releaseLetterFile, 'switch/release-letter')
                : null;
            $newContractPath = $newContractFile
                ? $detail->uploadFile($newContractFile, 'switch/contract')
                : null;

            // Create switch log
            $switchLog = CompanySwitchLog::create([
                'candidate_id' => $detail->candidate_id,
                'from_employment_id' => $currentEmployment->id,
                'to_employment_id' => $newEmployment->id,
                'switch_number' => $switchNumber,
                'switch_date' => now(),
                'reason' => $reason,
                'status' => 'pending',
                'release_letter_path' => $releaseLetterPath,
                'new_contract_path' => $newContractPath,
            ]);

            activity()
                ->performedOn($switchLog)
                ->causedBy(auth()->user())
                ->withProperties([
                    'switch_number' => $switchNumber,
                    'from_company' => $currentEmployment->company_name,
                    'to_company' => $newEmploymentData['company_name'],
                ])
                ->log("Company switch #{$switchNumber} initiated");

            return $switchLog;
        });
    }

    /**
     * Approve company switch
     */
    public function approveCompanySwitch(CompanySwitchLog $switch, ?string $notes = null, $approvalDoc = null): void
    {
        if ($approvalDoc) {
            $detail = PostDepartureDetail::where('candidate_id', $switch->candidate_id)->first();
            $switch->approval_document_path = $detail->uploadFile($approvalDoc, 'switch/approval');
            $switch->save();
        }

        $switch->approve($notes);
    }

    /**
     * Complete company switch
     */
    public function completeCompanySwitch(CompanySwitchLog $switch): void
    {
        if ($switch->status->value !== 'approved') {
            throw new \Exception('Switch must be approved before completion.');
        }

        $switch->complete();
    }

    /**
     * Verify 90-day compliance
     */
    public function verifyCompliance(PostDepartureDetail $detail): void
    {
        $checklist = $detail->getComplianceChecklist();
        $incomplete = collect($checklist)->filter(fn($item) => !$item['complete']);

        if ($incomplete->isNotEmpty()) {
            $missing = $incomplete->pluck('label')->join(', ');
            throw new \Exception("Cannot verify compliance. Missing: {$missing}");
        }

        $detail->markComplianceVerified();
    }

    /**
     * Get post-departure dashboard data
     */
    public function getDashboard(?int $campusId = null): array
    {
        $query = PostDepartureDetail::with([
            'candidate.campus',
            'currentEmployment',
        ]);

        if ($campusId) {
            $query->whereHas('candidate', fn($q) => $q->where('campus_id', $campusId));
        }

        $details = $query->get();

        return [
            'summary' => [
                'total' => $details->count(),
                'compliance_verified' => $details->where('compliance_verified', true)->count(),
                'compliance_pending' => $details->where('compliance_verified', false)->count(),
            ],
            'iqama_status' => [
                'pending' => $details->where('iqama_status.value', 'pending')->count(),
                'issued' => $details->where('iqama_status.value', 'issued')->count(),
                'expiring' => $details->filter(fn($d) => $d->iqama_expiring)->count(),
            ],
            'tracking_app' => [
                'registered' => $details->where('tracking_app_registered', true)->count(),
                'pending' => $details->where('tracking_app_registered', false)->count(),
            ],
            'wps' => [
                'registered' => $details->where('wps_registered', true)->count(),
                'pending' => $details->where('wps_registered', false)->count(),
            ],
            'recent_switches' => CompanySwitchLog::with(['candidate', 'fromEmployment', 'toEmployment'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),
        ];
    }
}
```

---

## Phase 5: Create Controller

```php
// app/Http/Controllers/PostDepartureController.php
<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\PostDepartureDetail;
use App\Models\CompanySwitchLog;
use App\Services\PostDepartureService;
use Illuminate\Http\Request;

class PostDepartureController extends Controller
{
    protected PostDepartureService $service;

    public function __construct(PostDepartureService $service)
    {
        $this->service = $service;
    }

    public function dashboard(Request $request)
    {
        $user = auth()->user();
        $campusId = $user->isCampusAdmin() ? $user->campus_id : $request->get('campus_id');

        $dashboard = $this->service->getDashboard($campusId);

        return view('post-departure.dashboard', compact('dashboard'));
    }

    public function show(Candidate $candidate)
    {
        $this->authorize('view', $candidate);

        $detail = $this->service->getOrCreateDetails($candidate);
        $checklist = $detail->getComplianceChecklist();
        $employmentHistory = $detail->employmentHistory;
        $switches = CompanySwitchLog::where('candidate_id', $candidate->id)->get();

        return view('post-departure.show', compact('candidate', 'detail', 'checklist', 'employmentHistory', 'switches'));
    }

    public function updateIqama(Request $request, PostDepartureDetail $detail)
    {
        $this->authorize('update', $detail->candidate);

        $validated = $request->validate([
            'iqama_number' => 'required|string|max:50',
            'iqama_issue_date' => 'required|date',
            'iqama_expiry_date' => 'required|date|after:iqama_issue_date',
            'iqama_status' => 'required|in:pending,issued,expired,renewed',
            'evidence' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
        ]);

        $this->service->updateIqama($detail, $validated, $request->file('evidence'));

        return back()->with('success', 'Iqama details updated.');
    }

    public function updateForeignContact(Request $request, PostDepartureDetail $detail)
    {
        $this->authorize('update', $detail->candidate);

        $validated = $request->validate([
            'mobile_number' => 'required|string|max:20',
            'carrier' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
        ]);

        $this->service->updateForeignContact($detail, $validated);

        return back()->with('success', 'Foreign contact updated.');
    }

    public function updateForeignBank(Request $request, PostDepartureDetail $detail)
    {
        $this->authorize('update', $detail->candidate);

        $validated = $request->validate([
            'bank_name' => 'required|string|max:100',
            'account_number' => 'required|string|max:50',
            'iban' => 'nullable|string|max:50',
            'swift' => 'nullable|string|max:20',
            'evidence' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
        ]);

        $this->service->updateForeignBank($detail, $validated, $request->file('evidence'));

        return back()->with('success', 'Foreign bank details updated.');
    }

    public function registerTrackingApp(Request $request, PostDepartureDetail $detail)
    {
        $this->authorize('update', $detail->candidate);

        $validated = $request->validate([
            'app_name' => 'required|string|max:50',
            'app_id' => 'required|string|max:100',
            'evidence' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
        ]);

        $this->service->registerTrackingApp(
            $detail,
            $validated['app_name'],
            $validated['app_id'],
            $request->file('evidence')
        );

        return back()->with('success', 'Tracking app registration recorded.');
    }

    public function registerWPS(Request $request, PostDepartureDetail $detail)
    {
        $this->authorize('update', $detail->candidate);

        $request->validate([
            'evidence' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
        ]);

        $this->service->registerWPS($detail, $request->file('evidence'));

        return back()->with('success', 'WPS registration recorded.');
    }

    public function addEmployment(Request $request, PostDepartureDetail $detail)
    {
        $this->authorize('update', $detail->candidate);

        $validated = $request->validate([
            'company_name' => 'required|string|max:200',
            'employer_id' => 'nullable|exists:employers,id',
            'company_address' => 'nullable|string|max:500',
            'contact_name' => 'nullable|string|max:100',
            'contact_phone' => 'nullable|string|max:20',
            'contact_email' => 'nullable|email|max:150',
            'position_title' => 'nullable|string|max:100',
            'work_location' => 'nullable|string|max:200',
            'base_salary' => 'required|numeric|min:0',
            'currency' => 'required|string|max:10',
            'housing_allowance' => 'nullable|numeric|min:0',
            'food_allowance' => 'nullable|numeric|min:0',
            'transport_allowance' => 'nullable|numeric|min:0',
            'commencement_date' => 'required|date',
            'contract' => 'nullable|file|max:10240|mimes:pdf',
        ]);

        $this->service->addInitialEmployment($detail, $validated, $request->file('contract'));

        return back()->with('success', 'Employment details recorded.');
    }

    public function initiateSwitch(Request $request, PostDepartureDetail $detail)
    {
        $this->authorize('update', $detail->candidate);

        $validated = $request->validate([
            'company_name' => 'required|string|max:200',
            'reason' => 'required|string|max:500',
            'base_salary' => 'required|numeric|min:0',
            'commencement_date' => 'required|date',
            'release_letter' => 'required|file|max:5120|mimes:pdf',
            'new_contract' => 'nullable|file|max:10240|mimes:pdf',
        ]);

        try {
            $this->service->initiateCompanySwitch(
                $detail,
                $validated,
                $validated['reason'],
                $request->file('release_letter'),
                $request->file('new_contract')
            );

            return back()->with('success', 'Company switch initiated. Awaiting approval.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function approveSwitch(Request $request, CompanySwitchLog $switch)
    {
        $this->authorize('approve', $switch);

        $request->validate([
            'notes' => 'nullable|string|max:1000',
            'approval_document' => 'nullable|file|max:5120|mimes:pdf',
        ]);

        $this->service->approveCompanySwitch($switch, $request->input('notes'), $request->file('approval_document'));

        return back()->with('success', 'Company switch approved.');
    }

    public function completeSwitch(CompanySwitchLog $switch)
    {
        $this->authorize('complete', $switch);

        try {
            $this->service->completeCompanySwitch($switch);
            return back()->with('success', 'Company switch completed.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function verifyCompliance(PostDepartureDetail $detail)
    {
        $this->authorize('verify', $detail);

        try {
            $this->service->verifyCompliance($detail);
            return back()->with('success', '90-day compliance verified successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
```

---

## Phase 6: Add Routes

```php
// routes/web.php
Route::middleware(['auth'])->prefix('post-departure')->name('post-departure.')->group(function () {
    Route::get('/dashboard', [PostDepartureController::class, 'dashboard'])->name('dashboard');
    Route::get('/candidate/{candidate}', [PostDepartureController::class, 'show'])->name('show');

    Route::post('/{detail}/iqama', [PostDepartureController::class, 'updateIqama'])->name('update-iqama');
    Route::post('/{detail}/foreign-contact', [PostDepartureController::class, 'updateForeignContact'])->name('update-contact');
    Route::post('/{detail}/foreign-bank', [PostDepartureController::class, 'updateForeignBank'])->name('update-bank');
    Route::post('/{detail}/tracking-app', [PostDepartureController::class, 'registerTrackingApp'])->name('register-tracking');
    Route::post('/{detail}/wps', [PostDepartureController::class, 'registerWPS'])->name('register-wps');
    Route::post('/{detail}/employment', [PostDepartureController::class, 'addEmployment'])->name('add-employment');
    Route::post('/{detail}/switch', [PostDepartureController::class, 'initiateSwitch'])->name('initiate-switch');
    Route::post('/{detail}/verify-compliance', [PostDepartureController::class, 'verifyCompliance'])->name('verify-compliance');

    Route::post('/switch/{switch}/approve', [PostDepartureController::class, 'approveSwitch'])->name('approve-switch');
    Route::post('/switch/{switch}/complete', [PostDepartureController::class, 'completeSwitch'])->name('complete-switch');
});
```

---

## Phase 7: Create Views

Create the following views with Tailwind CSS matching Module 1-6 design:

1. `resources/views/post-departure/dashboard.blade.php` - Overview dashboard
2. `resources/views/post-departure/show.blade.php` - Candidate post-departure details
3. `resources/views/post-departure/partials/iqama-card.blade.php`
4. `resources/views/post-departure/partials/contact-card.blade.php`
5. `resources/views/post-departure/partials/bank-card.blade.php`
6. `resources/views/post-departure/partials/employment-card.blade.php`
7. `resources/views/post-departure/partials/switch-card.blade.php`
8. `resources/views/post-departure/partials/compliance-checklist.blade.php`

---

## Phase 8: Testing

Create comprehensive tests covering all new functionality.

---

## Validation Checklist

- [ ] PostDepartureDetail model created/enhanced
- [ ] EmploymentHistory model created
- [ ] CompanySwitchLog model created
- [ ] All enums created
- [ ] PostDepartureService created
- [ ] PostDepartureController created
- [ ] All routes working
- [ ] Iqama tracking works
- [ ] Foreign contact update works
- [ ] Foreign bank details work
- [ ] Tracking app registration works
- [ ] WPS registration works
- [ ] Employment history tracking works
- [ ] Company switch workflow works (initiate → approve → complete)
- [ ] 90-day compliance verification works
- [ ] Dashboard shows all statistics
- [ ] All tests pass

---

## Success Criteria

Module 7 Enhancement is complete when:

1. Residency (Iqama) tracking with expiry alerts works
2. Foreign license tracking works
3. Foreign contact details (mobile, address) work
4. Foreign bank account details work
5. Tracking app (Absher) registration works
6. WPS registration works
7. Employment contract (Qiwa) tracking works
8. Initial employment recording works
9. Company switch workflow (max 2 switches) works
10. 90-day compliance checklist and verification works
11. Dashboard shows all breakdowns
12. All tests pass

---

*End of Module 7 Implementation Prompt*
