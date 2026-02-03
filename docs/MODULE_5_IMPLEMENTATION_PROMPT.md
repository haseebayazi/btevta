# Module 5: Visa Processing Enhancement - Implementation Prompt for Claude

**Project:** BTEVTA WASL
**Module:** Module 5 - Visa Processing (Enhancement)
**Status:** Existing Module - Requires Modifications
**Date:** February 2026

---

## Executive Summary

Module 5 (Visa Processing) **ALREADY EXISTS** with comprehensive 9-stage pipeline:
1. Interview
2. Trade Test
3. Takamol
4. Medical (GAMCA)
5. Biometrics (Etimad)
6. Visa Applied
7. PTN Issuance
8. Ticket
9. Completed

Existing features include E-Number/PTN generation, prerequisite validation, bottleneck detection, and 90-day SLA tracking.

This prompt focuses on **ENHANCEMENTS** for appointment sub-details, hierarchical status tracking, and mandatory evidence uploads.

**CRITICAL:** This is a working system. Make careful, targeted modifications only.

---

## Pre-Implementation Analysis

### Step 1: Read Existing Implementation

```
# Controllers (20+ methods)
app/Http/Controllers/VisaProcessingController.php

# Services (35+ methods)
app/Services/VisaProcessingService.php

# Models
app/Models/VisaProcess.php
app/Models/VisaPartner.php

# Enums
app/Enums/VisaStage.php
app/Enums/VisaApplicationStatus.php (if exists)
app/Enums/VisaIssuedStatus.php (if exists)

# Views
resources/views/visa/

# Tests
tests/Feature/VisaProcessControllerTest.php
tests/Unit/VisaProcessingServiceTest.php
```

### Step 2: Understand Current Schema

Check current visa_processes table structure:
```bash
php artisan tinker --execute="Schema::getColumnListing('visa_processes')"
```

---

## Required Changes (from WASL_CHANGE_IMPACT_ANALYSIS.md)

| Change ID | Type | Description | Priority |
|-----------|------|-------------|----------|
| VP-001 | MODIFIED | Every stage requires: Appointment status with sub-details | HIGH |
| VP-002 | NEW | Appointment sub-details: date, time, allowed center | MEDIUM |
| VP-003 | MODIFIED | Result status with sub-detail: Pass/Fail/Pending/Refused | HIGH |
| VP-004 | MODIFIED | Evidence upload mandatory at each stage | HIGH |
| VP-005 | EXISTS | Specific trade test stage (Takamol) | N/A |
| VP-006 | MODIFIED | Visa application status: Applied/Not Applied/Refused | HIGH |
| VP-007 | MODIFIED | Visa Issued status: Confirmed/Pending/Refused | HIGH |
| VP-008 | NEW | Hierarchical dashboard interface | HIGH |

---

## Phase 1: Database Changes

### 1.1 Add JSON Detail Columns to Visa Processes

```php
// database/migrations/YYYY_MM_DD_add_stage_details_to_visa_processes.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visa_processes', function (Blueprint $table) {
            // JSON columns for detailed stage tracking
            // Each contains: appointment_date, appointment_time, center, result_status, evidence_path, notes

            if (!Schema::hasColumn('visa_processes', 'interview_details')) {
                $table->json('interview_details')->nullable()->after('interview_status');
            }
            if (!Schema::hasColumn('visa_processes', 'trade_test_details')) {
                $table->json('trade_test_details')->nullable()->after('trade_test_status');
            }
            if (!Schema::hasColumn('visa_processes', 'takamol_details')) {
                $table->json('takamol_details')->nullable()->after('takamol_status');
            }
            if (!Schema::hasColumn('visa_processes', 'medical_details')) {
                $table->json('medical_details')->nullable()->after('medical_status');
            }
            if (!Schema::hasColumn('visa_processes', 'biometric_details')) {
                $table->json('biometric_details')->nullable()->after('biometric_status');
            }
            if (!Schema::hasColumn('visa_processes', 'visa_application_details')) {
                $table->json('visa_application_details')->nullable()->after('visa_status');
            }

            // Enhanced visa status fields
            if (!Schema::hasColumn('visa_processes', 'visa_application_status')) {
                $table->enum('visa_application_status', ['not_applied', 'applied', 'refused'])
                    ->default('not_applied')
                    ->after('visa_application_details');
            }
            if (!Schema::hasColumn('visa_processes', 'visa_issued_status')) {
                $table->enum('visa_issued_status', ['pending', 'confirmed', 'refused'])
                    ->nullable()
                    ->after('visa_application_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('visa_processes', function (Blueprint $table) {
            $table->dropColumn([
                'interview_details', 'trade_test_details', 'takamol_details',
                'medical_details', 'biometric_details', 'visa_application_details',
                'visa_application_status', 'visa_issued_status',
            ]);
        });
    }
};
```

---

## Phase 2: Create/Update Enums

### 2.1 VisaStageResult Enum

```php
// app/Enums/VisaStageResult.php
<?php

namespace App\Enums;

enum VisaStageResult: string
{
    case PENDING = 'pending';
    case SCHEDULED = 'scheduled';
    case PASS = 'pass';
    case FAIL = 'fail';
    case REFUSED = 'refused';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::SCHEDULED => 'Scheduled',
            self::PASS => 'Pass',
            self::FAIL => 'Fail',
            self::REFUSED => 'Refused',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'secondary',
            self::SCHEDULED => 'info',
            self::PASS => 'success',
            self::FAIL => 'danger',
            self::REFUSED => 'dark',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::PENDING => 'fas fa-clock',
            self::SCHEDULED => 'fas fa-calendar-check',
            self::PASS => 'fas fa-check-circle',
            self::FAIL => 'fas fa-times-circle',
            self::REFUSED => 'fas fa-ban',
        };
    }

    public function allowsProgress(): bool
    {
        return $this === self::PASS;
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::FAIL, self::REFUSED]);
    }

    public static function toArray(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn($case) => $case->label(), self::cases())
        );
    }
}
```

### 2.2 VisaApplicationStatus Enum (update if exists)

```php
// app/Enums/VisaApplicationStatus.php
<?php

namespace App\Enums;

enum VisaApplicationStatus: string
{
    case NOT_APPLIED = 'not_applied';
    case APPLIED = 'applied';
    case REFUSED = 'refused';

    public function label(): string
    {
        return match($this) {
            self::NOT_APPLIED => 'Not Applied',
            self::APPLIED => 'Applied',
            self::REFUSED => 'Refused',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::NOT_APPLIED => 'secondary',
            self::APPLIED => 'info',
            self::REFUSED => 'danger',
        };
    }

    public static function toArray(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn($case) => $case->label(), self::cases())
        );
    }
}
```

### 2.3 VisaIssuedStatus Enum

```php
// app/Enums/VisaIssuedStatus.php
<?php

namespace App\Enums;

enum VisaIssuedStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case REFUSED = 'refused';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::CONFIRMED => 'Confirmed',
            self::REFUSED => 'Refused',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::CONFIRMED => 'success',
            self::REFUSED => 'danger',
        };
    }

    public static function toArray(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn($case) => $case->label(), self::cases())
        );
    }
}
```

---

## Phase 3: Create Stage Details DTO

### 3.1 VisaStageDetails Value Object

```php
// app/ValueObjects/VisaStageDetails.php
<?php

namespace App\ValueObjects;

use App\Enums\VisaStageResult;
use Illuminate\Contracts\Support\Arrayable;

class VisaStageDetails implements Arrayable
{
    public function __construct(
        public ?string $appointmentDate = null,
        public ?string $appointmentTime = null,
        public ?string $center = null,
        public ?string $resultStatus = null,
        public ?string $evidencePath = null,
        public ?string $notes = null,
        public ?string $updatedAt = null,
        public ?int $updatedBy = null,
    ) {}

    public static function fromArray(?array $data): self
    {
        if (!$data) {
            return new self();
        }

        return new self(
            appointmentDate: $data['appointment_date'] ?? null,
            appointmentTime: $data['appointment_time'] ?? null,
            center: $data['center'] ?? null,
            resultStatus: $data['result_status'] ?? null,
            evidencePath: $data['evidence_path'] ?? null,
            notes: $data['notes'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
            updatedBy: $data['updated_by'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'appointment_date' => $this->appointmentDate,
            'appointment_time' => $this->appointmentTime,
            'center' => $this->center,
            'result_status' => $this->resultStatus,
            'evidence_path' => $this->evidencePath,
            'notes' => $this->notes,
            'updated_at' => $this->updatedAt,
            'updated_by' => $this->updatedBy,
        ], fn($value) => $value !== null);
    }

    public function isScheduled(): bool
    {
        return $this->appointmentDate !== null;
    }

    public function hasResult(): bool
    {
        return $this->resultStatus !== null;
    }

    public function isPassed(): bool
    {
        return $this->resultStatus === VisaStageResult::PASS->value;
    }

    public function hasEvidence(): bool
    {
        return !empty($this->evidencePath);
    }

    public function getResultEnum(): ?VisaStageResult
    {
        return $this->resultStatus ? VisaStageResult::tryFrom($this->resultStatus) : null;
    }

    public function withAppointment(string $date, string $time, string $center): self
    {
        return new self(
            appointmentDate: $date,
            appointmentTime: $time,
            center: $center,
            resultStatus: VisaStageResult::SCHEDULED->value,
            evidencePath: $this->evidencePath,
            notes: $this->notes,
            updatedAt: now()->toDateTimeString(),
            updatedBy: auth()->id(),
        );
    }

    public function withResult(string $resultStatus, ?string $notes = null, ?string $evidencePath = null): self
    {
        return new self(
            appointmentDate: $this->appointmentDate,
            appointmentTime: $this->appointmentTime,
            center: $this->center,
            resultStatus: $resultStatus,
            evidencePath: $evidencePath ?? $this->evidencePath,
            notes: $notes ?? $this->notes,
            updatedAt: now()->toDateTimeString(),
            updatedBy: auth()->id(),
        );
    }
}
```

---

## Phase 4: Update VisaProcess Model

Add to `app/Models/VisaProcess.php`:

```php
use App\Enums\VisaApplicationStatus;
use App\Enums\VisaIssuedStatus;
use App\Enums\VisaStageResult;
use App\ValueObjects\VisaStageDetails;

// Add to $fillable:
'interview_details',
'trade_test_details',
'takamol_details',
'medical_details',
'biometric_details',
'visa_application_details',
'visa_application_status',
'visa_issued_status',

// Add to $casts:
'interview_details' => 'array',
'trade_test_details' => 'array',
'takamol_details' => 'array',
'medical_details' => 'array',
'biometric_details' => 'array',
'visa_application_details' => 'array',
'visa_application_status' => VisaApplicationStatus::class,
'visa_issued_status' => VisaIssuedStatus::class,

// Add accessors for VisaStageDetails objects:

public function getInterviewDetailsObjectAttribute(): VisaStageDetails
{
    return VisaStageDetails::fromArray($this->interview_details);
}

public function getTradeTestDetailsObjectAttribute(): VisaStageDetails
{
    return VisaStageDetails::fromArray($this->trade_test_details);
}

public function getTakamolDetailsObjectAttribute(): VisaStageDetails
{
    return VisaStageDetails::fromArray($this->takamol_details);
}

public function getMedicalDetailsObjectAttribute(): VisaStageDetails
{
    return VisaStageDetails::fromArray($this->medical_details);
}

public function getBiometricDetailsObjectAttribute(): VisaStageDetails
{
    return VisaStageDetails::fromArray($this->biometric_details);
}

public function getVisaApplicationDetailsObjectAttribute(): VisaStageDetails
{
    return VisaStageDetails::fromArray($this->visa_application_details);
}

/**
 * Get all stages with their details for hierarchical display
 */
public function getStagesOverview(): array
{
    return [
        'interview' => [
            'name' => 'Interview',
            'status' => $this->interview_status,
            'details' => $this->interview_details_object,
            'icon' => 'fas fa-user-tie',
        ],
        'trade_test' => [
            'name' => 'Trade Test',
            'status' => $this->trade_test_status,
            'details' => $this->trade_test_details_object,
            'icon' => 'fas fa-tools',
        ],
        'takamol' => [
            'name' => 'Takamol',
            'status' => $this->takamol_status,
            'details' => $this->takamol_details_object,
            'icon' => 'fas fa-certificate',
        ],
        'medical' => [
            'name' => 'Medical (GAMCA)',
            'status' => $this->medical_status,
            'details' => $this->medical_details_object,
            'icon' => 'fas fa-heartbeat',
        ],
        'biometric' => [
            'name' => 'Biometrics (Etimad)',
            'status' => $this->biometric_status,
            'details' => $this->biometric_details_object,
            'icon' => 'fas fa-fingerprint',
        ],
        'visa_application' => [
            'name' => 'Visa Application',
            'status' => $this->visa_application_status?->value,
            'details' => $this->visa_application_details_object,
            'icon' => 'fas fa-passport',
            'issued_status' => $this->visa_issued_status?->value,
        ],
    ];
}

/**
 * Schedule appointment for a stage
 */
public function scheduleStageAppointment(string $stage, string $date, string $time, string $center): void
{
    $detailsField = "{$stage}_details";
    $currentDetails = VisaStageDetails::fromArray($this->{$detailsField});

    $this->{$detailsField} = $currentDetails->withAppointment($date, $time, $center)->toArray();
    $this->{"{$stage}_status"} = 'scheduled';
    $this->save();

    activity()
        ->performedOn($this)
        ->causedBy(auth()->user())
        ->withProperties([
            'stage' => $stage,
            'appointment_date' => $date,
            'appointment_time' => $time,
            'center' => $center,
        ])
        ->log("Scheduled {$stage} appointment");
}

/**
 * Record result for a stage
 */
public function recordStageResult(
    string $stage,
    string $resultStatus,
    ?string $notes = null,
    ?string $evidencePath = null
): void {
    $detailsField = "{$stage}_details";
    $currentDetails = VisaStageDetails::fromArray($this->{$detailsField});

    // Require evidence for pass/fail results
    if (in_array($resultStatus, ['pass', 'fail']) && !$evidencePath && !$currentDetails->hasEvidence()) {
        throw new \Exception("Evidence is required for {$resultStatus} result.");
    }

    $this->{$detailsField} = $currentDetails->withResult($resultStatus, $notes, $evidencePath)->toArray();
    $this->{"{$stage}_status"} = $resultStatus === 'pass' ? 'completed' : $resultStatus;
    $this->save();

    activity()
        ->performedOn($this)
        ->causedBy(auth()->user())
        ->withProperties([
            'stage' => $stage,
            'result' => $resultStatus,
        ])
        ->log("Recorded {$stage} result: {$resultStatus}");
}

/**
 * Upload evidence for a stage
 */
public function uploadStageEvidence(string $stage, $file): string
{
    $detailsField = "{$stage}_details";
    $currentDetails = VisaStageDetails::fromArray($this->{$detailsField});

    // Delete old evidence if exists
    if ($currentDetails->evidencePath) {
        \Storage::disk('private')->delete($currentDetails->evidencePath);
    }

    $candidateId = $this->candidate_id;
    $timestamp = now()->format('Y-m-d_His');
    $extension = $file->getClientOriginalExtension();
    $filename = "visa_{$stage}_{$candidateId}_{$timestamp}.{$extension}";

    $path = $file->storeAs(
        "visa-process/{$candidateId}",
        $filename,
        'private'
    );

    // Update details with new evidence path
    $this->{$detailsField} = array_merge(
        $currentDetails->toArray(),
        ['evidence_path' => $path, 'updated_at' => now()->toDateTimeString()]
    );
    $this->save();

    return $path;
}

/**
 * Get hierarchical status for dashboard
 */
public function getHierarchicalStatus(): array
{
    $stages = $this->getStagesOverview();

    $scheduled = [];
    $done = [];
    $passed = [];
    $failed = [];
    $pending = [];

    foreach ($stages as $key => $stage) {
        $result = $stage['details']->getResultEnum();

        if ($result === VisaStageResult::PASS) {
            $passed[$key] = $stage;
        } elseif ($result === VisaStageResult::FAIL || $result === VisaStageResult::REFUSED) {
            $failed[$key] = $stage;
        } elseif ($result === VisaStageResult::SCHEDULED || $stage['details']->isScheduled()) {
            $scheduled[$key] = $stage;
        } elseif ($stage['status'] === 'completed') {
            $done[$key] = $stage;
        } else {
            $pending[$key] = $stage;
        }
    }

    return [
        'scheduled' => $scheduled,
        'done' => $done,
        'passed' => $passed,
        'failed' => $failed,
        'pending' => $pending,
    ];
}
```

---

## Phase 5: Create Form Request

### 5.1 VisaStageUpdateRequest

```php
// app/Http/Requests/VisaStageUpdateRequest.php
<?php

namespace App\Http\Requests;

use App\Enums\VisaStageResult;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VisaStageUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $stage = $this->route('stage');

        return [
            'action' => 'required|in:schedule,result,evidence',

            // Scheduling
            'appointment_date' => 'required_if:action,schedule|date|after_or_equal:today',
            'appointment_time' => 'required_if:action,schedule|date_format:H:i',
            'center' => 'required_if:action,schedule|string|max:200',

            // Result
            'result_status' => [
                'required_if:action,result',
                Rule::in(array_column(VisaStageResult::cases(), 'value')),
            ],
            'notes' => 'nullable|string|max:2000',

            // Evidence (required for pass/fail results)
            'evidence' => [
                Rule::requiredIf(fn() =>
                    $this->action === 'result' &&
                    in_array($this->result_status, ['pass', 'fail'])
                ),
                'nullable',
                'file',
                'max:10240',
                'mimes:pdf,jpg,jpeg,png',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'evidence.required' => 'Evidence is required for pass or fail results.',
            'appointment_date.after_or_equal' => 'Appointment date cannot be in the past.',
        ];
    }
}
```

---

## Phase 6: Update Visa Processing Service

Add/modify methods in `app/Services/VisaProcessingService.php`:

```php
use App\Enums\VisaStageResult;
use App\Enums\VisaApplicationStatus;
use App\Enums\VisaIssuedStatus;
use App\ValueObjects\VisaStageDetails;

/**
 * Schedule stage appointment with details
 */
public function scheduleStage(
    VisaProcess $visaProcess,
    string $stage,
    string $date,
    string $time,
    string $center
): void {
    // Validate stage
    $validStages = ['interview', 'trade_test', 'takamol', 'medical', 'biometric'];
    if (!in_array($stage, $validStages)) {
        throw new \Exception("Invalid stage: {$stage}");
    }

    // Check prerequisites
    $this->validateStagePrerequisites($visaProcess, $stage);

    $visaProcess->scheduleStageAppointment($stage, $date, $time, $center);
}

/**
 * Record stage result with details
 */
public function recordStageResult(
    VisaProcess $visaProcess,
    string $stage,
    string $resultStatus,
    ?string $notes = null,
    $evidenceFile = null
): void {
    DB::transaction(function () use ($visaProcess, $stage, $resultStatus, $notes, $evidenceFile) {
        $evidencePath = null;

        // Upload evidence if provided
        if ($evidenceFile) {
            $evidencePath = $visaProcess->uploadStageEvidence($stage, $evidenceFile);
        }

        // Record the result
        $visaProcess->recordStageResult($stage, $resultStatus, $notes, $evidencePath);

        // Handle failed/refused results
        $result = VisaStageResult::from($resultStatus);
        if ($result->isTerminal()) {
            // Mark visa process as failed at this stage
            $visaProcess->update([
                'status' => 'failed',
                'failed_at' => now(),
                'failed_stage' => $stage,
                'failure_reason' => $notes ?? "Failed at {$stage}",
            ]);

            // Update candidate status
            $visaProcess->candidate->update(['status' => 'rejected']);
        }
    });
}

/**
 * Update visa application status
 */
public function updateVisaApplication(
    VisaProcess $visaProcess,
    string $applicationStatus,
    ?string $issuedStatus = null,
    ?string $notes = null,
    $evidenceFile = null
): void {
    DB::transaction(function () use ($visaProcess, $applicationStatus, $issuedStatus, $notes, $evidenceFile) {
        $evidencePath = null;

        if ($evidenceFile) {
            $evidencePath = $visaProcess->uploadStageEvidence('visa_application', $evidenceFile);
        }

        $details = VisaStageDetails::fromArray($visaProcess->visa_application_details);
        $visaProcess->visa_application_details = $details->withResult(
            $applicationStatus,
            $notes,
            $evidencePath
        )->toArray();

        $visaProcess->visa_application_status = VisaApplicationStatus::from($applicationStatus);

        if ($issuedStatus) {
            $visaProcess->visa_issued_status = VisaIssuedStatus::from($issuedStatus);
        }

        // If visa confirmed, update candidate status
        if ($issuedStatus === 'confirmed') {
            $visaProcess->visa_status = 'approved';
            $visaProcess->candidate->update(['status' => 'visa_approved']);
        } elseif ($applicationStatus === 'refused' || $issuedStatus === 'refused') {
            $visaProcess->status = 'failed';
            $visaProcess->candidate->update(['status' => 'rejected']);
        }

        $visaProcess->save();

        activity()
            ->performedOn($visaProcess)
            ->causedBy(auth()->user())
            ->withProperties([
                'application_status' => $applicationStatus,
                'issued_status' => $issuedStatus,
            ])
            ->log('Visa application status updated');
    });
}

/**
 * Get hierarchical dashboard data
 */
public function getHierarchicalDashboard(?int $campusId = null): array
{
    $query = VisaProcess::with(['candidate.campus', 'candidate.trade'])
        ->whereNotIn('status', ['completed', 'cancelled']);

    if ($campusId) {
        $query->whereHas('candidate', fn($q) => $q->where('campus_id', $campusId));
    }

    $processes = $query->get();

    $dashboard = [
        'scheduled' => collect(),
        'done' => collect(),
        'passed' => collect(),
        'failed' => collect(),
        'pending' => collect(),
    ];

    foreach ($processes as $process) {
        $hierarchical = $process->getHierarchicalStatus();

        foreach (['scheduled', 'done', 'passed', 'failed', 'pending'] as $category) {
            foreach ($hierarchical[$category] as $stage => $stageData) {
                $dashboard[$category]->push([
                    'visa_process_id' => $process->id,
                    'candidate' => $process->candidate,
                    'stage' => $stage,
                    'stage_name' => $stageData['name'],
                    'details' => $stageData['details'],
                ]);
            }
        }
    }

    return [
        'counts' => [
            'scheduled' => $dashboard['scheduled']->count(),
            'done' => $dashboard['done']->count(),
            'passed' => $dashboard['passed']->count(),
            'failed' => $dashboard['failed']->count(),
            'pending' => $dashboard['pending']->count(),
        ],
        'items' => $dashboard,
    ];
}

/**
 * Get stages requiring evidence
 */
public function getStagesMissingEvidence(VisaProcess $visaProcess): array
{
    $stages = ['interview', 'trade_test', 'takamol', 'medical', 'biometric'];
    $missing = [];

    foreach ($stages as $stage) {
        $details = VisaStageDetails::fromArray($visaProcess->{"{$stage}_details"});
        $status = $visaProcess->{"{$stage}_status"};

        // If stage has result but no evidence
        if ($details->hasResult() && !$details->hasEvidence()) {
            $missing[] = [
                'stage' => $stage,
                'result' => $details->resultStatus,
            ];
        }
    }

    return $missing;
}
```

---

## Phase 7: Update Controller

Add/modify methods in `app/Http/Controllers/VisaProcessingController.php`:

```php
/**
 * Hierarchical dashboard view
 */
public function hierarchicalDashboard(Request $request)
{
    $this->authorize('viewAny', VisaProcess::class);

    $user = auth()->user();
    $campusId = $user->isCampusAdmin() ? $user->campus_id : $request->get('campus_id');

    $service = app(VisaProcessingService::class);
    $dashboard = $service->getHierarchicalDashboard($campusId);

    $campuses = Campus::active()->orderBy('name')->get();

    return view('visa.hierarchical-dashboard', compact('dashboard', 'campuses'));
}

/**
 * Stage details view
 */
public function stageDetails(VisaProcess $visaProcess, string $stage)
{
    $this->authorize('view', $visaProcess);

    $validStages = ['interview', 'trade_test', 'takamol', 'medical', 'biometric', 'visa_application'];
    if (!in_array($stage, $validStages)) {
        abort(404, 'Invalid stage');
    }

    $details = VisaStageDetails::fromArray($visaProcess->{"{$stage}_details"});
    $stagesOverview = $visaProcess->getStagesOverview();

    return view('visa.stage-details', compact('visaProcess', 'stage', 'details', 'stagesOverview'));
}

/**
 * Update stage with appointment/result/evidence
 */
public function updateStage(VisaStageUpdateRequest $request, VisaProcess $visaProcess, string $stage)
{
    $this->authorize('update', $visaProcess);

    $validated = $request->validated();
    $service = app(VisaProcessingService::class);

    try {
        switch ($validated['action']) {
            case 'schedule':
                $service->scheduleStage(
                    $visaProcess,
                    $stage,
                    $validated['appointment_date'],
                    $validated['appointment_time'],
                    $validated['center']
                );
                $message = ucfirst($stage) . ' appointment scheduled successfully.';
                break;

            case 'result':
                $service->recordStageResult(
                    $visaProcess,
                    $stage,
                    $validated['result_status'],
                    $validated['notes'] ?? null,
                    $request->file('evidence')
                );
                $message = ucfirst($stage) . ' result recorded: ' . $validated['result_status'];
                break;

            case 'evidence':
                $visaProcess->uploadStageEvidence($stage, $request->file('evidence'));
                $message = 'Evidence uploaded successfully.';
                break;

            default:
                throw new \Exception('Invalid action');
        }

        return back()->with('success', $message);

    } catch (\Exception $e) {
        return back()->withInput()->with('error', $e->getMessage());
    }
}

/**
 * Update visa application status
 */
public function updateVisaApplication(Request $request, VisaProcess $visaProcess)
{
    $this->authorize('update', $visaProcess);

    $validated = $request->validate([
        'application_status' => 'required|in:not_applied,applied,refused',
        'issued_status' => 'nullable|in:pending,confirmed,refused',
        'notes' => 'nullable|string|max:2000',
        'evidence' => 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png',
    ]);

    try {
        $service = app(VisaProcessingService::class);
        $service->updateVisaApplication(
            $visaProcess,
            $validated['application_status'],
            $validated['issued_status'] ?? null,
            $validated['notes'] ?? null,
            $request->file('evidence')
        );

        return back()->with('success', 'Visa application status updated.');

    } catch (\Exception $e) {
        return back()->with('error', $e->getMessage());
    }
}
```

---

## Phase 8: Add Routes

```php
// routes/web.php
Route::middleware(['auth'])->prefix('visa')->name('visa.')->group(function () {
    Route::get('/hierarchical-dashboard', [VisaProcessingController::class, 'hierarchicalDashboard'])
        ->name('hierarchical-dashboard');
    Route::get('/{visaProcess}/stage/{stage}', [VisaProcessingController::class, 'stageDetails'])
        ->name('stage-details');
    Route::post('/{visaProcess}/stage/{stage}', [VisaProcessingController::class, 'updateStage'])
        ->name('update-stage');
    Route::post('/{visaProcess}/visa-application', [VisaProcessingController::class, 'updateVisaApplication'])
        ->name('update-visa-application');
});
```

---

## Phase 9: Create Views

### 9.1 Hierarchical Dashboard

Create `resources/views/visa/hierarchical-dashboard.blade.php`:

**Layout:**
```
┌─────────────────────────────────────────────────────────────────────────┐
│  Visa Processing Dashboard                               [Campus Filter] │
├─────────────────────────────────────────────────────────────────────────┤
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐  │
│  │ Scheduled│  │   Done   │  │  Passed  │  │  Failed  │  │ Pending  │  │
│  │    15    │  │    32    │  │    28    │  │    4     │  │    21    │  │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘  └──────────┘  │
├─────────────────────────────────────────────────────────────────────────┤
│  SCHEDULED APPOINTMENTS                                                 │
│  ┌────────────────────────────────────────────────────────────────────┐│
│  │ Candidate    │ Stage     │ Date       │ Time  │ Center   │ Action ││
│  │ John Doe     │ Interview │ 2026-02-05 │ 10:00 │ Lahore   │ [View] ││
│  │ Jane Smith   │ Medical   │ 2026-02-06 │ 14:30 │ Karachi  │ [View] ││
│  └────────────────────────────────────────────────────────────────────┘│
├─────────────────────────────────────────────────────────────────────────┤
│  COMPLETED (Awaiting Next Stage)                                        │
│  ┌────────────────────────────────────────────────────────────────────┐│
│  │ Candidate    │ Last Stage │ Result │ Next Stage │ Action           ││
│  └────────────────────────────────────────────────────────────────────┘│
└─────────────────────────────────────────────────────────────────────────┘
```

### 9.2 Stage Details View

Create `resources/views/visa/stage-details.blade.php`:

**Sections:**
1. Stage progress timeline
2. Appointment details (date, time, center)
3. Result status with evidence
4. Action forms (schedule, record result, upload evidence)

### 9.3 Stage Update Forms (Partials)

Create partials for:
- `partials/schedule-form.blade.php`
- `partials/result-form.blade.php`
- `partials/evidence-upload.blade.php`

---

## Phase 10: Testing

### 10.1 Unit Tests

```php
// tests/Unit/VisaStageDetailsTest.php
public function test_can_create_from_array()
public function test_can_add_appointment()
public function test_can_add_result()
public function test_result_enum_conversion()
public function test_evidence_path_check()

// tests/Unit/VisaProcessEnhancedTest.php
public function test_hierarchical_status_categorization()
public function test_stage_scheduling()
public function test_stage_result_recording()
public function test_evidence_requirement_enforcement()
```

### 10.2 Feature Tests

```php
// tests/Feature/VisaHierarchicalDashboardTest.php
public function test_hierarchical_dashboard_loads()
public function test_stage_details_view_loads()
public function test_can_schedule_appointment()
public function test_can_record_result_with_evidence()
public function test_cannot_record_pass_without_evidence()
public function test_visa_application_status_updates()
public function test_failed_result_updates_candidate_status()
```

---

## Validation Checklist

- [ ] JSON detail columns added to visa_processes
- [ ] visa_application_status and visa_issued_status columns added
- [ ] VisaStageResult enum created
- [ ] VisaApplicationStatus enum created/updated
- [ ] VisaIssuedStatus enum created
- [ ] VisaStageDetails value object created
- [ ] VisaProcess model updated with new methods
- [ ] Service methods for scheduling and results work
- [ ] Evidence upload enforced for pass/fail
- [ ] Hierarchical dashboard shows correct categorization
- [ ] Stage details view shows appointment info
- [ ] Forms work for schedule/result/evidence
- [ ] Failed results update candidate status
- [ ] All tests pass

---

## Files to Create

```
app/Enums/VisaStageResult.php
app/Enums/VisaApplicationStatus.php (if not exists)
app/Enums/VisaIssuedStatus.php
app/ValueObjects/VisaStageDetails.php
app/Http/Requests/VisaStageUpdateRequest.php
database/migrations/YYYY_MM_DD_add_stage_details_to_visa_processes.php
resources/views/visa/hierarchical-dashboard.blade.php
resources/views/visa/stage-details.blade.php
resources/views/visa/partials/schedule-form.blade.php
resources/views/visa/partials/result-form.blade.php
resources/views/visa/partials/evidence-upload.blade.php
tests/Unit/VisaStageDetailsTest.php
tests/Feature/VisaHierarchicalDashboardTest.php
docs/MODULE_5_VISA_PROCESSING.md
```

## Files to Modify

```
app/Models/VisaProcess.php
app/Services/VisaProcessingService.php
app/Http/Controllers/VisaProcessingController.php
routes/web.php
CLAUDE.md
README.md
```

---

## Success Criteria

Module 5 Enhancement is complete when:

1. Each stage has detailed tracking (date, time, center, result, evidence)
2. Hierarchical dashboard shows Scheduled → Done → Pass/Fail → Pending
3. Evidence required for pass/fail results
4. Visa application has distinct Applied/Not Applied/Refused status
5. Visa issued has Pending/Confirmed/Refused status
6. Failed stages properly update candidate status
7. All tests pass
8. No regression in existing visa functionality

---

*End of Module 5 Implementation Prompt*
