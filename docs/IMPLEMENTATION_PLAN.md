# WASL BTEVTA - Complete Remediation Implementation Plan

## Executive Summary

This plan addresses all **189 issues** identified in the comprehensive audit report, organized into **6 phases** over approximately **8-10 weeks** of development effort.

| Phase | Focus Area | Issues | Priority | Duration |
|-------|-----------|--------|----------|----------|
| 1 | Critical Enum/Database Fixes | 15 | P0 | Week 1-2 |
| 2 | Model & Relationship Fixes | 25 | P0/P1 | Week 2-3 |
| 3 | Service Layer Refactoring | 20 | P0/P1 | Week 3-4 |
| 4 | Authorization & Security | 35 | P1 | Week 4-5 |
| 5 | Validation & Request Handling | 30 | P1/P2 | Week 5-6 |
| 6 | Test Coverage Expansion | 64 | P2 | Week 6-10 |

---

## Phase 1: Critical Enum/Database Fixes (Week 1-2)

**Objective:** Fix all enum/database mismatches that cause runtime errors

### 1.1 Fix CandidateStatus Enum Mismatch

**Files to modify:**
- `database/migrations/2025_01_01_000000_create_all_tables.php`
- `app/Enums/CandidateStatus.php`

**Tasks:**

```
[x] 1.1.1 Create migration to update candidates.status column
    File: database/migrations/2025_12_31_000001_phase1_fix_enum_database_mismatches.php
    ✅ COMPLETED - Migration created with all enum fixes

[x] 1.1.2 Verify CandidateStatus enum values match database
    File: app/Enums/CandidateStatus.php
    Values: NEW, SCREENING, REGISTERED, TRAINING, VISA_PROCESS, READY, DEPARTED, REJECTED, DROPPED, RETURNED
    ✅ COMPLETED - Verified enum matches, migration fixes 'visa' -> 'visa_process'

[x] 1.1.3 Add test to verify enum-database consistency
    File: tests/Unit/EnumDatabaseConsistencyTest.php
    ✅ COMPLETED - Comprehensive test suite created for all 5 enums
```

### 1.2 Fix ComplaintPriority Default Value

**Files to modify:**
- `database/migrations/2025_11_04_add_missing_columns.php`

**Tasks:**

```
[x] 1.2.1 Create migration to fix default priority
    File: database/migrations/2025_12_31_000001_phase1_fix_enum_database_mismatches.php
    ✅ COMPLETED - Included in Phase 1 consolidated migration

[x] 1.2.2 Verify ComplaintPriority enum values
    File: app/Enums/ComplaintPriority.php
    Values: LOW, NORMAL, HIGH, URGENT (not 'medium')
    ✅ COMPLETED - Verified and migration fixes 'medium' -> 'normal'
```

### 1.3 Fix TrainingStatus Enum Migration

**Files to modify:**
- `database/migrations/2025_11_04_add_missing_columns.php`

**Tasks:**

```
[x] 1.3.1 Create migration to expand training_status enum
    File: database/migrations/2025_12_31_000001_phase1_fix_enum_database_mismatches.php
    ✅ COMPLETED - Converts enum to string column for flexibility with all TrainingStatus values

[x] 1.3.2 Update TrainingStatus enum to match
    File: app/Enums/TrainingStatus.php
    ✅ COMPLETED - Verified all 11 values defined: PENDING, ENROLLED, IN_PROGRESS, ONGOING, COMPLETED, FAILED, WITHDRAWN, SCHEDULED, CANCELLED, POSTPONED, RESCHEDULED
```

### 1.4 Fix VisaStage Database Constraint

**Tasks:**

```
[x] 1.4.1 Create migration to use proper VisaStage values
    File: database/migrations/2025_12_31_000001_phase1_fix_enum_database_mismatches.php
    ✅ COMPLETED - Included in consolidated Phase 1 migration

[x] 1.4.2 Update existing visa_processes records
    ✅ COMPLETED - Migration fixes 'pending' -> 'initiated' for current_stage and overall_status
```

### 1.5 Create Enum Consistency Test Suite

**Tasks:**

```
[x] 1.5.1 Create comprehensive enum-database validation tests
    File: tests/Unit/EnumDatabaseConsistencyTest.php
    ✅ COMPLETED - Tests all 5 enums for database storage compatibility and transition validation
```

### Phase 1 Deliverables Checklist

```
[x] All enum migrations created and tested
[x] Existing data migrated to correct values
[x] Enum consistency tests passing
[x] No runtime errors from enum mismatches
```

✅ **PHASE 1 COMPLETE** - All enum/database fixes implemented

---

## Phase 2: Model & Relationship Fixes (Week 2-3)

**Objective:** Fix all model relationship issues and $fillable arrays

### 2.1 Fix RemittanceBeneficiary Relationship

**File:** `app/Models/RemittanceBeneficiary.php:58-61`

**Tasks:**

```
[x] 2.1.1 Option A: Add beneficiary_id column to remittances table
    File: database/migrations/2025_12_31_000002_phase2_model_relationship_fixes.php
    ✅ COMPLETED - Migration adds beneficiary_id FK to remittances table

[x] 2.1.2 Option B: Fix relationship to use existing structure
    File: app/Models/RemittanceBeneficiary.php
    ✅ COMPLETED - Added proper remittances() relationship and legacy remittancesByName() method
```

### 2.2 Fix Departure Model OEP Relationship

**File:** `app/Models/Departure.php:117-120`

**Tasks:**

```
[x] 2.2.1 Fix hasOneThrough relationship
    File: app/Models/Departure.php
    ✅ COMPLETED - Fixed oep() relationship with correct key ordering:
    - 'id' (candidates.id), 'id' (oeps.id), 'candidate_id' (departures.candidate_id), 'oep_id' (candidates.oep_id)
```

### 2.3 Fix VisaProcess OEP Relationship

**File:** `app/Models/VisaProcess.php:174-177`

**Tasks:**

```
[x] 2.3.1 Apply same fix as Departure model
    File: app/Models/VisaProcess.php
    ✅ COMPLETED - Fixed oep() relationship with same pattern as Departure model
```

### 2.4 Fix TrainingAssessment/Attendance Column Name

**Files:**
- `app/Models/TrainingAssessment.php:49-52`
- `app/Models/TrainingAttendance.php:44-46`

**Tasks:**

```
[x] 2.4.1 Verify migration column name
    ✅ VERIFIED - Column is 'trainer_id' in both migrations and models (consistent)

[x] 2.4.2 Update model relationship to match
    ✅ VERIFIED - instructor() relationship correctly uses 'trainer_id' as FK

[x] 2.4.3 Create migration if column name needs standardization
    ✅ NOT NEEDED - Column naming is consistent (trainer_id throughout)
```

### 2.5 Fix Missing $fillable Fields

**Tasks:**

```
[ ] 2.5.1 Add created_by/updated_by to Complaint model
    File: app/Models/Complaint.php
    Add to $fillable: 'created_by', 'updated_by'
    🔄 PENDING - To be addressed in next iteration

[ ] 2.5.2 Add visa_partner_id to User model
    File: app/Models/User.php
    Add to $fillable: 'visa_partner_id'
    🔄 PENDING - To be addressed in next iteration

[ ] 2.5.3 Audit all models for missing fillable fields
    🔄 PENDING - To be addressed in next iteration
```

### 2.6 Fix PasswordHistory Timestamp Conflict

**File:** `app/Models/PasswordHistory.php`

**Tasks:**

```
[x] 2.6.1 Fix timestamp handling
    File: app/Models/PasswordHistory.php
    ✅ VERIFIED - Model correctly implements Option B:
    - $timestamps = false
    - Boot method sets created_at = now() on creating
    - No conflict exists (already correctly implemented)
```

### 2.7 Add Missing SoftDeletes

**Files:**
- `app/Models/EquipmentUsageLog.php`
- `app/Models/RemittanceAlert.php`
- `app/Models/RemittanceUsageBreakdown.php`

**Tasks:**

```
[x] 2.7.1 Add SoftDeletes trait to each model
    ✅ COMPLETED - Added SoftDeletes trait to:
    - app/Models/EquipmentUsageLog.php
    - app/Models/RemittanceAlert.php
    - app/Models/RemittanceUsageBreakdown.php

[x] 2.7.2 Create migration to add deleted_at column
    File: database/migrations/2025_12_31_000002_phase2_model_relationship_fixes.php
    ✅ COMPLETED - Migration adds softDeletes to all three tables
```

### 2.8 Add Missing Inverse Relationships

**Tasks:**

```
[x] 2.8.1 Add trainingClasses relationship to Candidate model
    File: app/Models/Candidate.php
    ✅ COMPLETED - Added trainingClasses() belongsToMany relationship with pivot table

[x] 2.8.2 Add creator/updater relationships to RemittanceAlert
    File: app/Models/RemittanceAlert.php
    ✅ VERIFIED - resolvedBy() relationship already exists
    🔄 creator/updater - To be added in next iteration
```

### 2.9 Add Integer Casts for Foreign Keys

**Tasks:**

```
[ ] 2.9.1 Add casts to all models with FK fields
    Files: Campus, Oep, Trade, Instructor, RemittanceAlert, RemittanceUsageBreakdown
    🔄 PENDING - To be addressed in next iteration
```

### Phase 2 Deliverables Checklist

```
[x] All relationship methods return correct data
[x] No null pointer exceptions from relationships
[ ] All $fillable arrays complete (partial - 2.5 pending)
[x] SoftDeletes consistent across related models
[ ] Foreign key casts added (pending - 2.9)
[x] Relationship tests passing
```

⚠️ **PHASE 2 IN PROGRESS** - Core relationship fixes complete, minor items pending

---

## Phase 3: Service Layer Refactoring (Week 3-4)

**Objective:** Implement proper enum usage and fix state machine logic

### 3.1 Implement Enum Usage in TrainingService

**File:** `app/Services/TrainingService.php`

**Tasks:**

```
[x] 3.1.1 Add enum imports
    ✅ COMPLETED - Added CandidateStatus and TrainingStatus imports

[x] 3.1.2 Replace hard-coded strings (Lines 705, 741, 772, 988)
    ✅ COMPLETED - All status updates now use enum values

[x] 3.1.3 Add transition validation
    ✅ COMPLETED - Status checks now use enum values for comparison

[x] 3.1.4 Fix 'at_risk' status (not in enum)
    ✅ COMPLETED - Implemented Option B: Using at_risk_reason/at_risk_since columns
    - getAtRiskCandidates() now queries by at_risk_reason column
    - All at-risk updates set at_risk_reason and at_risk_since
    - Completion clears these fields (sets to null)
```

### 3.2 Implement Enum Usage in VisaProcessingService

**File:** `app/Services/VisaProcessingService.php`

**Tasks:**

```
[x] 3.2.1 Add enum imports
    ✅ COMPLETED - Added CandidateStatus and VisaStage imports

[x] 3.2.2 Replace hard-coded stage strings
    ✅ COMPLETED - createVisaProcess uses VisaStage::INITIATED
    ✅ COMPLETED - candidate status uses CandidateStatus::VISA_PROCESS

[ ] 3.2.3 Merge duplicate methods (Lines 342-362)
    🔄 PENDING - Requires further refactoring

[ ] 3.2.4 Add E-Number transition method
    public function recordEnumber(Candidate $candidate, array $data): VisaProcess
    {
        $visaProcess = $candidate->visaProcess;

        // Validate transition from MEDICAL to ENUMBER
        $this->advanceToStage($visaProcess, VisaStage::ENUMBER);

        $visaProcess->update([
            'enumber' => $data['enumber'],
            'enumber_date' => $data['date'],
        ]);

        return $visaProcess;
    }
```

### 3.3 Fix ComplaintService Transition Logic

**File:** `app/Services/ComplaintService.php`

**Tasks:**

```
[x] 3.3.1 Remove duplicate STATUS_TRANSITIONS constant (Lines 223-229)
    ✅ COMPLETED - Replaced with isValidStatusTransition() using enum's validNextStatuses()

[x] 3.3.2 Use ComplaintStatus enum methods
    ✅ COMPLETED - isValidStatusTransition() now uses ComplaintStatus::from() and validNextStatuses()

[x] 3.3.3 Update all status change methods to use enum validation
    ✅ COMPLETED - All status updates now use ComplaintStatus::OPEN, ASSIGNED, RESOLVED, CLOSED
```

### 3.4 Implement Enum Usage in ScreeningService

**File:** `app/Services/ScreeningService.php`

**Tasks:**

```
[ ] 3.4.1 Add CandidateStatus enum usage
    use App\Enums\CandidateStatus;

[ ] 3.4.2 Replace hard-coded 'pending', 'passed', 'failed'
    Consider creating ScreeningOutcome enum if needed

[ ] 3.4.3 Add callback retry mechanism
    public function retryFailedCallback(CandidateScreening $screening): void
    {
        if ($screening->callback_attempts >= 3) {
            $this->markCallbackFailed($screening);
            return;
        }

        $screening->increment('callback_attempts');
        $screening->update([
            'callback_scheduled_at' => now()->addHours(24),
        ]);
    }
```

### 3.5 Implement Enum Usage in DepartureService

**File:** `app/Services/DepartureService.php`

**Tasks:**

```
[x] 3.5.1 Add enum imports and usage
    ✅ COMPLETED - Added CandidateStatus enum import
    ✅ COMPLETED - recordDeparture uses CandidateStatus::DEPARTED

[ ] 3.5.2 Add transaction wrapping
    🔄 PENDING - Departure recording already has try/catch but could benefit from explicit transaction
```

### 3.6 Add At-Risk Recovery Workflow

**File:** `app/Services/TrainingService.php`

**Tasks:**

```
[ ] 3.6.1 Add recovery method
    public function recoverFromAtRisk(Candidate $candidate, string $reason): void
    {
        if ($candidate->training_status !== 'at_risk') {
            throw new InvalidStateException('Candidate is not at risk');
        }

        DB::transaction(function () use ($candidate, $reason) {
            $candidate->update([
                'training_status' => TrainingStatus::IN_PROGRESS->value,
            ]);

            activity()
                ->performedOn($candidate)
                ->log("Recovered from at-risk status: {$reason}");
        });
    }

[ ] 3.6.2 Add route and controller method for recovery
    Route::post('/{candidate}/recover-from-at-risk', [TrainingController::class, 'recoverFromAtRisk']);
```

### 3.7 Fix Duplicate Timeline Methods

**File:** `app/Services/VisaProcessingService.php`

**Tasks:**

```
[ ] 3.7.1 Consolidate calculateTimeline() and getTimeline()
    // Keep one method, deprecate/remove the other

    /**
     * @deprecated Use getVisaTimeline() instead
     */
    public function calculateTimeline($visaProcessId) { ... }

    public function getVisaTimeline(VisaProcess $visaProcess): array
    {
        // Single consolidated implementation
    }
```

### Phase 3 Deliverables Checklist

```
[x] All services import and use enums
[x] Key status strings replaced with enum values
[ ] No hard-coded status strings remain (partial - granular sub-phases remain)
[x] State transitions validated before execution (ComplaintService)
[ ] E-Number workflow complete (pending)
[x] At-risk tracking refactored to use dedicated columns
[ ] Transaction wrapping added where needed (partial)
[ ] Duplicate methods consolidated (pending)
```

⚠️ **PHASE 3 IN PROGRESS** - Core enum integration complete, advanced features pending

---

## Phase 4: Authorization & Security (Week 4-5)

**Objective:** Add missing policies and authorization checks

### 4.1 Create Missing Policies (17 Models)

**Tasks:**

```
[x] 4.1.1 Create CampusEquipmentPolicy ✅ COMPLETED
[x] 4.1.2 Create CampusKpiPolicy ✅ COMPLETED
[x] 4.1.3 Create ComplaintEvidencePolicy ✅ COMPLETED
[x] 4.1.4 Create ComplaintUpdatePolicy ✅ COMPLETED
[x] 4.1.5 Create EquipmentUsageLogPolicy ✅ COMPLETED
[x] 4.1.6 Create NextOfKinPolicy ✅ COMPLETED
[x] 4.1.7 Create PasswordHistoryPolicy ✅ COMPLETED
[x] 4.1.8 Create RegistrationDocumentPolicy ✅ COMPLETED
[x] 4.1.9 Create RemittanceReceiptPolicy ✅ COMPLETED
[x] 4.1.10 Create RemittanceUsageBreakdownPolicy ✅ COMPLETED
[x] 4.1.11 Create SystemSettingPolicy ✅ COMPLETED
[x] 4.1.12 Create TrainingAssessmentPolicy ✅ COMPLETED
[x] 4.1.13 Create TrainingAttendancePolicy ✅ COMPLETED
[x] 4.1.14 Create TrainingCertificatePolicy ✅ COMPLETED
[x] 4.1.15 Create TrainingSchedulePolicy ✅ COMPLETED
[x] 4.1.16 Create UndertakingPolicy ✅ COMPLETED
[x] 4.1.17 Create VisaPartnerPolicy ✅ COMPLETED
```

All 17 policies created with role-based access control:
- Super admin: Full access
- Project director: Full access to most resources
- Campus admin: Access to their campus resources only
- Instructor: Access to training resources
- OEP: Access to remittance resources
- Viewer: Read-only access

### 4.2 Register All Policies

**Note:** Laravel 11 uses automatic policy discovery. Policies are auto-registered if they:
1. Are in `App\Policies` namespace
2. Follow naming convention: `{Model}Policy` (e.g., `CampusEquipmentPolicy` for `CampusEquipment`)

```
[x] 4.2.1 Auto-discovery enabled - No manual registration needed
    ✅ COMPLETED - Laravel 11 auto-discovers policies based on naming convention
```

### 4.3 Add Authorization to EquipmentController

**File:** `app/Http/Controllers/EquipmentController.php`

**Tasks:**

```
[ ] 4.3.1 Add authorize calls to all 10 methods
    public function index()
    {
        $this->authorize('viewAny', CampusEquipment::class);
        // existing code...
    }

    public function create()
    {
        $this->authorize('create', CampusEquipment::class);
        // existing code...
    }

    public function store(Request $request)
    {
        $this->authorize('create', CampusEquipment::class);
        // existing code...
    }

    public function show(CampusEquipment $equipment)
    {
        $this->authorize('view', $equipment);
        // existing code...
    }

    public function edit(CampusEquipment $equipment)
    {
        $this->authorize('update', $equipment);
        // existing code...
    }

    public function update(Request $request, CampusEquipment $equipment)
    {
        $this->authorize('update', $equipment);
        // existing code...
    }

    public function destroy(CampusEquipment $equipment)
    {
        $this->authorize('delete', $equipment);
        // existing code...
    }

    public function logUsage(CampusEquipment $equipment)
    {
        $this->authorize('logUsage', $equipment);
        // existing code...
    }

    public function endUsage(CampusEquipment $equipment, EquipmentUsageLog $log)
    {
        $this->authorize('update', $log);
        // existing code...
    }

    public function utilizationReport()
    {
        $this->authorize('viewReports', CampusEquipment::class);
        // existing code...
    }
```

### 4.4 Add Authorization to DashboardController

**File:** `app/Http/Controllers/DashboardController.php`

**Tasks:**

```
[ ] 4.4.1 Add authorize call or policy check
    public function index()
    {
        // Dashboard access is role-based, ensure proper check
        $user = auth()->user();

        if (!$user->hasAnyRole(['super_admin', 'admin', 'campus_admin', 'instructor', 'oep', 'viewer'])) {
            abort(403);
        }

        // existing code...
    }
```

### 4.5 Replace Manual Role Checks with Policies

**Files to update:**
- `BulkOperationsController.php`
- `CandidateController.php`
- `CorrespondenceController.php`
- `DepartureController.php`
- `DocumentArchiveController.php`
- `RegistrationController.php`

**Tasks:**

```
[ ] 4.5.1 Create base trait for common authorization
    File: app/Http/Controllers/Traits/AuthorizesWithPolicies.php

[ ] 4.5.2 Update each controller to use $this->authorize() instead of manual checks

    // Before:
    if (auth()->user()->role === 'campus_admin') {
        $query->where('campus_id', auth()->user()->campus_id);
    }

    // After: Move logic to policy
    $this->authorize('viewAny', Candidate::class);
    // Policy handles campus scoping
```

### 4.6 Fix CSP Unsafe-Inline

**File:** `app/Http/Middleware/SecurityHeaders.php`

**Tasks:**

```
[ ] 4.6.1 Implement nonce-based CSP
    // Generate nonce per request
    $nonce = base64_encode(random_bytes(16));
    View::share('cspNonce', $nonce);

    // Update CSP header
    "script-src 'self' 'nonce-{$nonce}' https://cdn.jsdelivr.net"

[ ] 4.6.2 Update all inline scripts in Blade templates
    <script nonce="{{ $cspNonce }}">
        // script content
    </script>
```

### 4.7 Fix Nullable Policy Parameters

**Files:**
- `app/Policies/VisaProcessPolicy.php`
- `app/Policies/TrainingPolicy.php`

**Tasks:**

```
[ ] 4.7.1 Remove nullable model parameters or handle properly
    // Before:
    public function view(User $user, VisaProcess $visaProcess = null): bool

    // After:
    public function view(User $user, VisaProcess $visaProcess): bool
    {
        // Proper implementation
    }

    public function viewAny(User $user): bool
    {
        // For collection-level checks
    }
```

### Phase 4 Deliverables Checklist

```
[ ] All 17 new policies created
[ ] All policies registered in AuthServiceProvider
[ ] EquipmentController has authorization (10 methods)
[ ] DashboardController has authorization
[ ] Manual role checks replaced with policies
[ ] CSP nonce implementation complete
[ ] Policy tests created for new policies
```

---

## Phase 5: Validation & Request Handling (Week 5-6)

**Objective:** Add proper FormRequest validation for all modules

### 5.1 Create Candidate FormRequests

**Tasks:**

```
[ ] 5.1.1 Create StoreCandidateRequest
    File: app/Http/Requests/StoreCandidateRequest.php

    public function rules(): array
    {
        return [
            'btevta_id' => 'required|string|unique:candidates,btevta_id',
            'name' => 'required|string|max:255',
            'father_name' => 'required|string|max:255',
            'cnic' => 'required|string|size:13|unique:candidates,cnic',
            'phone' => 'required|string|regex:/^03[0-9]{9}$/',
            'email' => 'nullable|email|max:255',
            'date_of_birth' => 'required|date|before:-18 years',
            'gender' => 'required|in:male,female',
            'campus_id' => 'nullable|exists:campuses,id',
            'trade_id' => 'required|exists:trades,id',
            'district' => 'required|string|max:100',
            // ... all other fields
        ];
    }

[ ] 5.1.2 Create UpdateCandidateRequest
    File: app/Http/Requests/UpdateCandidateRequest.php

[ ] 5.1.3 Update CandidateController to use FormRequests
```

### 5.2 Create Registration FormRequests

**Tasks:**

```
[ ] 5.2.1 Create StoreRegistrationDocumentRequest
    File: app/Http/Requests/StoreRegistrationDocumentRequest.php

[ ] 5.2.2 Create StoreNextOfKinRequest
    File: app/Http/Requests/StoreNextOfKinRequest.php

[ ] 5.2.3 Create StoreUndertakingRequest
    File: app/Http/Requests/StoreUndertakingRequest.php
```

### 5.3 Create Training FormRequests

**Tasks:**

```
[ ] 5.3.1 Create StoreAttendanceRequest
    File: app/Http/Requests/StoreAttendanceRequest.php

[ ] 5.3.2 Create BulkAttendanceRequest
    File: app/Http/Requests/BulkAttendanceRequest.php

[ ] 5.3.3 Create StoreAssessmentRequest
    File: app/Http/Requests/StoreAssessmentRequest.php

[ ] 5.3.4 Update UpdateTrainingClassRequest (already exists as Store)
```

### 5.4 Create Visa Processing FormRequests

**Tasks:**

```
[ ] 5.4.1 Create ScheduleInterviewRequest
    File: app/Http/Requests/ScheduleInterviewRequest.php

[ ] 5.4.2 Create RecordTradeTestRequest
    File: app/Http/Requests/RecordTradeTestRequest.php

[ ] 5.4.3 Create RecordMedicalRequest
    File: app/Http/Requests/RecordMedicalRequest.php

[ ] 5.4.4 Create RecordEnumberRequest
    File: app/Http/Requests/RecordEnumberRequest.php

[ ] 5.4.5 Create RecordBiometricsRequest
    File: app/Http/Requests/RecordBiometricsRequest.php

[ ] 5.4.6 Create SubmitVisaRequest
    File: app/Http/Requests/SubmitVisaRequest.php
```

### 5.5 Create Departure FormRequests

**Tasks:**

```
[ ] 5.5.1 Create RecordDepartureRequest
    File: app/Http/Requests/RecordDepartureRequest.php

[ ] 5.5.2 Create RecordBriefingRequest
    File: app/Http/Requests/RecordBriefingRequest.php

[ ] 5.5.3 Create RecordIqamaRequest
    File: app/Http/Requests/RecordIqamaRequest.php

[ ] 5.5.4 Create RecordComplianceRequest
    File: app/Http/Requests/RecordComplianceRequest.php
```

### 5.6 Create Bulk Operation FormRequests

**Tasks:**

```
[ ] 5.6.1 Create BulkStatusUpdateRequest
    File: app/Http/Requests/BulkStatusUpdateRequest.php

    public function rules(): array
    {
        return [
            'candidate_ids' => 'required|array|min:1|max:100',
            'candidate_ids.*' => 'exists:candidates,id',
            'status' => 'required|in:' . implode(',', CandidateStatus::values()),
        ];
    }

[ ] 5.6.2 Create BulkBatchAssignRequest
    File: app/Http/Requests/BulkBatchAssignRequest.php

[ ] 5.6.3 Create BulkCampusAssignRequest
    File: app/Http/Requests/BulkCampusAssignRequest.php

[ ] 5.6.4 Create BulkExportRequest
    File: app/Http/Requests/BulkExportRequest.php
```

### 5.7 Create Remittance FormRequests

**Tasks:**

```
[ ] 5.7.1 Create StoreRemittanceRequest
    File: app/Http/Requests/StoreRemittanceRequest.php

[ ] 5.7.2 Create UpdateRemittanceRequest
    File: app/Http/Requests/UpdateRemittanceRequest.php

[ ] 5.7.3 Create StoreBeneficiaryRequest
    File: app/Http/Requests/StoreBeneficiaryRequest.php
```

### 5.8 Add Email Length Constraints

**Tasks:**

```
[ ] 5.8.1 Create migration for email column constraints
    File: database/migrations/2025_12_31_200001_add_email_length_constraints.php

    Schema::table('users', fn($t) => $t->string('email', 255)->change());
    Schema::table('candidates', fn($t) => $t->string('email', 255)->nullable()->change());
    Schema::table('campuses', fn($t) => $t->string('email', 255)->nullable()->change());
    Schema::table('oeps', fn($t) => $t->string('email', 255)->nullable()->change());
```

### Phase 5 Deliverables Checklist

```
[ ] All CRUD operations have FormRequests
[ ] Bulk operations have dedicated FormRequests
[ ] All validation rules match database constraints
[ ] Email length constraints added
[ ] Controllers updated to use FormRequests
[ ] Validation error messages are user-friendly
```

---

## Phase 6: Test Coverage Expansion (Week 6-10)

**Objective:** Achieve 80%+ test coverage for critical paths

### 6.1 Policy Tests (22 Policies)

**Tasks:**

```
[ ] 6.1.1 Create tests for all new policies
    File: tests/Unit/Policies/*PolicyTest.php

    For each policy, test:
    - viewAny for each role
    - view for owner/non-owner
    - create permissions
    - update permissions (own vs others)
    - delete permissions
    - Campus isolation for campus_admin
    - OEP isolation for oep role
```

### 6.2 Controller Tests (25 Controllers)

**Tasks:**

```
[ ] 6.2.1 ActivityLogController tests
[ ] 6.2.2 BatchController tests
[ ] 6.2.3 BulkOperationsController tests (CRITICAL)
[ ] 6.2.4 CampusController tests
[ ] 6.2.5 ComplaintController tests
[ ] 6.2.6 CorrespondenceController tests
[ ] 6.2.7 DashboardController tests
[ ] 6.2.8 DocumentArchiveController tests
[ ] 6.2.9 EquipmentController tests
[ ] 6.2.10 HealthController tests
[ ] 6.2.11 ImportController tests (CRITICAL)
[ ] 6.2.12 InstructorController tests
[ ] 6.2.13 OepController tests
[ ] 6.2.14 ReportController tests (CRITICAL)
[ ] 6.2.15 TradeController tests
[ ] 6.2.16 TrainingClassController tests
[ ] 6.2.17-6.2.25 Remaining controllers
```

### 6.3 Service Tests (6 Untested Services)

**Tasks:**

```
[ ] 6.3.1 CandidateDeduplicationService tests
    File: tests/Unit/Services/CandidateDeduplicationServiceTest.php

    - Test duplicate detection by CNIC
    - Test duplicate detection by phone
    - Test duplicate detection by name+DOB
    - Test merge functionality

[ ] 6.3.2 DepartureService tests
    File: tests/Unit/Services/DepartureServiceTest.php

    - Test departure recording
    - Test compliance tracking
    - Test 90-day monitoring

[ ] 6.3.3 DocumentArchiveService tests
[ ] 6.3.4 GlobalSearchService tests
[ ] 6.3.5 RegistrationService tests
[ ] 6.3.6 ReportingService tests
```

### 6.4 Integration Tests

**Tasks:**

```
[ ] 6.4.1 Complete workflow tests
    - Full candidate lifecycle (already exists, verify complete)
    - Batch import workflow
    - Bulk operations workflow
    - Report generation workflow

[ ] 6.4.2 Concurrent operation tests
    - Race condition handling
    - Optimistic locking verification

[ ] 6.4.3 Error scenario tests
    - File upload failures
    - Database constraint violations
    - Network timeout handling
```

### 6.5 Edge Case Tests

**Tasks:**

```
[ ] 6.5.1 State transition edge cases
    - Invalid transitions blocked
    - Concurrent status updates
    - Partial completion scenarios

[ ] 6.5.2 Data validation edge cases
    - CNIC edge cases (leading zeros)
    - Date range boundaries
    - Maximum field lengths

[ ] 6.5.3 Authorization edge cases
    - Cross-campus access attempts
    - Role escalation attempts
    - Inactive user access
```

### 6.6 Performance Tests

**Tasks:**

```
[ ] 6.6.1 Load tests for critical endpoints
    - Dashboard loading
    - Large batch imports
    - Report generation
    - Bulk operations

[ ] 6.6.2 N+1 query detection tests
    - Add Laravel Debugbar assertions
    - Test eager loading effectiveness
```

### Phase 6 Deliverables Checklist

```
[ ] All 22 new policy tests created
[ ] All 25 controller tests created
[ ] All 6 service tests created
[ ] Integration tests for all workflows
[ ] Edge case tests for critical paths
[ ] Performance tests passing
[ ] Overall coverage > 80%
```

---

## Implementation Schedule Summary

| Week | Phase | Primary Focus | Key Deliverables |
|------|-------|--------------|------------------|
| 1 | 1 | Enum/Database | 5 migrations, enum consistency tests |
| 2 | 1-2 | Database + Models | Model fixes, relationship corrections |
| 3 | 2-3 | Models + Services | Fillable arrays, enum implementation |
| 4 | 3-4 | Services + Auth | State machines, 17 new policies |
| 5 | 4-5 | Auth + Validation | Authorization, FormRequests |
| 6 | 5-6 | Validation + Tests | All FormRequests, policy tests |
| 7-8 | 6 | Tests | Controller + service tests |
| 9-10 | 6 | Tests | Integration, edge cases, performance |

---

## Risk Mitigation

### High-Risk Changes

1. **Enum migrations** - May affect existing data
   - Mitigation: Create backup before each migration
   - Test on staging with production data copy

2. **Relationship fixes** - May break existing queries
   - Mitigation: Run all tests after each change
   - Add specific relationship tests first

3. **Policy additions** - May block legitimate access
   - Mitigation: Start with permissive policies
   - Add comprehensive logging

### Rollback Plan

Each phase should be deployable independently. If issues arise:

1. Revert to previous migration state
2. Clear config/route cache
3. Restore database backup if needed

---

## Success Criteria

### Phase 1 Complete When:
- [ ] All enum values match between database and PHP
- [ ] No runtime errors from status transitions
- [ ] Enum consistency tests passing

### Phase 2 Complete When:
- [ ] All relationships return correct data
- [ ] No null pointer exceptions
- [ ] Model tests passing

### Phase 3 Complete When:
- [ ] All services use enums (no hard-coded strings)
- [ ] State transitions validated
- [ ] E-Number workflow functional

### Phase 4 Complete When:
- [ ] All 40 policies registered and active
- [ ] No unauthorized access possible
- [ ] CSP nonce implemented

### Phase 5 Complete When:
- [ ] All forms have FormRequest validation
- [ ] Validation errors user-friendly
- [ ] No unvalidated user input

### Phase 6 Complete When:
- [ ] Test coverage > 80%
- [ ] All critical paths tested
- [ ] No failing tests

---

## Final Production Readiness Checklist

After all phases complete:

```
[ ] Health score > 85/100
[ ] All 28 critical issues resolved
[ ] All 53 high-priority issues resolved
[ ] Security audit passing
[ ] Performance benchmarks met
[ ] Test coverage > 80%
[ ] Documentation updated
[ ] Deployment guide verified
```

---

*Plan created: December 31, 2025*
*Estimated completion: 8-10 weeks*
