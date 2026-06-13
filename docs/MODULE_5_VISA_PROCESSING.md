# Module 5: Visa Processing Enhancement

**Version:** 1.0.0
**Status:** Complete
**Implementation Date:** February 2026

---

## Overview

Module 5 enhances the existing BTEVTA WASL Visa Processing system with **hierarchical stage tracking**, **appointment sub-details**, **mandatory evidence uploads**, and a **stage-based dashboard**. Each visa processing stage (Interview, Trade Test, Takamol, Medical, Biometrics, Visa Application) now has its own detail panel with appointment scheduling, result recording, and evidence management via the `VisaStageDetails` value object pattern. The module also introduces a seamless **Training to Visa Processing transition** via the `TrainingCompleted` event listener.

---

## Features

### Core Functionality

1. **Hierarchical Stage Details**
   - Each stage stores appointment date, time, center, result status, evidence path, and notes as a JSON column
   - `VisaStageDetails` value object provides immutable, type-safe access to stage data
   - Stages: Interview, Trade Test, Takamol, Medical, Biometrics, Visa Application

2. **Stage Dashboard (Hierarchical View)**
   - Categorizes all active visa processes by stage status: Scheduled, Pending, Passed, Done, Failed
   - Summary cards with counts per category
   - Tabbed tables with candidate name, stage name, details, and action links
   - Optional campus filtering for campus-scoped admins

3. **Appointment Scheduling**
   - Per-stage appointment with date, time, and center
   - Future date validation (cannot schedule in the past)
   - Stage prerequisite enforcement (e.g., interview must pass before trade test)
   - Activity logging for all scheduling actions

4. **Result Recording with Evidence**
   - Pass/Fail/Refused results per stage
   - Evidence upload mandatory for pass/fail results (PDF, JPG, PNG; max 10MB)
   - Secure storage in `storage/app/private/visa-process/{candidate_id}/`
   - Old evidence automatically deleted on re-upload

5. **Visa Application Status Tracking**
   - Two-tier status: Application Status (Not Applied / Applied / Refused) and Issued Status (Pending / Confirmed / Refused)
   - Visa confirmed triggers candidate transition to `VISA_APPROVED`
   - Visa refused triggers candidate transition to `REJECTED` with failure tracking

6. **Terminal Failure Handling**
   - Failed/Refused results at any stage mark the process as failed
   - Records `failed_at`, `failed_stage`, `failure_reason` on the visa process
   - Candidate status transitions to `REJECTED`

7. **Training to Visa Transition**
   - `HandleTrainingCompleted` listener fires when Module 4 dual-status training completes
   - Creates VisaProcess record via `firstOrCreate` (idempotent, prevents duplicates)
   - Sets candidate status to `VISA_PROCESS`
   - Guards: only runs if candidate status is `TRAINING` and training_status is `completed`

---

## Workflow

```
+-----------------------+
|    TRAINING           |
|    (Module 4)         |
+----------+------------+
           |
           | TrainingCompleted event
           v
+-----------------------+
|    VISA_PROCESS       |<--- HandleTrainingCompleted listener
+----------+------------+     creates VisaProcess record
           |
           v
+----------------------------------------------------------+
|  Visa Processing Pipeline (12 stages)                     |
|                                                           |
|  1. Initiated                                             |
|  2. Interview -----> Schedule -> Record Result -> Pass    |
|  3. Trade Test ----> Schedule -> Record Result -> Pass    |
|  4. Takamol -------> Schedule -> Record Result -> Pass    |
|  5. Medical (GAMCA)> Schedule -> Record Result -> Pass    |
|  6. E-Number ------> Generated externally (no biometric   |
|                       prerequisite enforced)              |
|  7. Biometrics ----> Schedule -> Record Result -> Pass    |
|  8. Visa Submission                                       |
|  9. Visa Issuance -> visa_status, visa_number, visa_date  |
| 10. PTN Clearance -> Yes / No (ptn_cleared boolean)       |
| 11. Protector -----> Date + Status (pending/approved/     |
|                       rejected)                           |
| 12. Completed                                             |
|                                                           |
|  NOTE: Ticket & Travel Plan are NOT part of visa          |
|  processing — they are recorded in the Departure module   |
|  (Module 6) after completion.                             |
|                                                           |
|  Detail Stages (with VisaStageDetails):                   |
|  interview, trade_test, takamol, medical,                 |
|  biometric, visa_application                              |
+----------------------------------------------------------+
           |                              |
           | All required stages complete | Any stage FAIL/REFUSED
           v                              v
+------------------+            +------------------+
|    COMPLETED     |            |    REJECTED      |
| candidate -> DEPARTURE_PROCESSING                |
| (a Departure record is auto-created)             |
+------------------+            +------------------+
```

### Completion & Hand-off to Departure

Completion is **gated on the required visa stages**, not on a ticket upload.
The "Complete & Send to Departure" action is available (on both the candidate
show view and the edit view) only when `VisaProcess::isReadyToComplete()` returns
true — i.e. all of the following are satisfied:

- Interview passed
- Medical cleared as `fit`
- Biometrics completed
- E-Number generated **and** verified
- Visa issued
- PTN clearance confirmed (`ptn_cleared = true`)
- Protector clearance performed (`protector_performed = true`)

`VisaProcess::getOutstandingCompletionRequirements()` returns the human-readable
list of any unmet requirements, which the UI surfaces as a "Pending for
Completion" checklist so the operator always knows what is missing.

On completion (`VisaProcessingController@complete`):

1. `overall_status` is set to `completed` (the candidate dashboard/progress bar
   then reflects 100% / "Completed" — it no longer gets stuck at "Visa Issuance").
2. The candidate transitions to `CandidateStatus::DEPARTURE_PROCESSING`.
3. A `Departure` record is auto-created (`firstOrCreate`) so the candidate
   immediately appears in the Departure module.

### Stage Prerequisites

| Stage              | Prerequisite                               |
|--------------------|--------------------------------------------|
| Interview          | None                                       |
| Trade Test         | Interview must be passed                   |
| Takamol            | Interview must be passed                   |
| Medical            | Interview must be passed                   |
| E-Number           | None (externally generated, no prerequisite enforced) |
| Biometric          | Medical must be fit/completed/passed       |
| PTN Clearance      | Visa must be issued                        |
| Protector          | None (independent clearance)               |

> **Note:** Ticket & Travel Plan are **not** visa-processing stages. They were
> removed from this module and are handled by the Departure module (Module 6).

---

## Database Schema

### Modified Table: `visa_processes` (new columns)

| Column              | Type          | Description                                      |
|---------------------|---------------|--------------------------------------------------|
| `takamol_details`   | json          | Takamol stage details (appointment, result, evidence) |
| `failed_at`         | timestamp     | When the process was marked as failed            |
| `failed_stage`      | varchar(50)   | Which stage caused the failure                   |
| `failure_reason`    | text          | Reason for failure                               |
| `enumber_date`      | date          | Date the E-Number was generated/verified (migration `2026_06_13_000001`) |
| `etimad_center`     | varchar       | Etimad biometric enrolment center (migration `2026_06_13_000001`) |

> **Edit-form persistence fix:** `enumber_date` and `etimad_center` were
> previously submitted by the edit/show forms but had no backing column (and
> `etimad_appointment_id`/`etimad_center` were not persisted by the biometric
> update service), so they re-appeared blank after saving. These columns now
> exist, are in `$fillable`, and the biometric update persists the Etimad
> appointment id and center.

### Previously Enhanced Columns (Migration `2026_01_18_100016`)

| Column                   | Type          | Description                                   |
|--------------------------|---------------|-----------------------------------------------|
| `interview_details`      | json          | Interview stage details                       |
| `trade_test_details`     | json          | Trade test stage details                      |
| `medical_details`        | json          | Medical/GAMCA stage details                   |
| `biometric_details`      | json          | Biometrics/Etimad stage details               |
| `visa_application_status`| varchar       | Enum: not_applied, applied, refused           |
| `visa_issued_status`     | varchar       | Enum: pending, confirmed, refused             |
| `visa_application_details`| json         | Visa application stage details                |

### JSON Details Structure (VisaStageDetails)

Each `*_details` JSON column stores:

```json
{
    "appointment_date": "2026-03-15",
    "appointment_time": "10:00",
    "center": "Test Center Lahore",
    "result_status": "pass",
    "evidence_path": "visa-process/42/visa_interview_42_2026-03-15_100000.pdf",
    "notes": "Excellent performance",
    "updated_at": "2026-03-15 10:30:00",
    "updated_by": 1
}
```

---

## User Interface

### 1. Hierarchical Stage Dashboard

**Route:** `/visa-processing/hierarchical-dashboard`

**Components:**
- **Summary Cards**: Scheduled, Pending, Passed, Done, Failed counts
- **Tabbed Tables**: Each category tab shows candidate name, stage, status details, and action links
- **Campus Filter**: Dropdown for admin to scope by campus
- **Styling**: Tailwind CSS + Alpine.js tabs

### 2. Stage Details View

**Route:** `/visa-processing/stage/{visaProcess}/{stage}`

**Layout:**
- Left sidebar: candidate info card + stage navigation with status badges
- Right panel: current stage details + action forms
- Three action tabs (Alpine.js):
  - **Schedule**: Date, time, center fields
  - **Record Result**: Pass/fail/refused dropdown, notes, evidence upload
  - **Upload Evidence**: Standalone evidence upload
- Visa application stage has dedicated application_status and issued_status selects

### 3. Candidate Visa Process View (show.blade.php)

**Route:** `/visa-processing/{candidate}`

**Layout:**
- Progress bar with stage stepper
- Left column: candidate info card, key numbers (E-Number, PTN, Visa Number), and a
  completion panel that shows **either** a "Complete & Send to Departure" button
  (when ready), a "Pending for Completion" checklist (when not ready), or a
  "Go to Departure Module" link (once completed)
- Right column: stage cards with status badges, details, and "Manage Stage" links.
  Stage 7 shows the **Final Clearances (PTN & Protector)** summary — the old
  "Ticket & Travel Plan" card was removed (now in the Departure module)
- Failure info panel (if process failed)
- **Styling**: Fully rewritten to Tailwind CSS (was Bootstrap)

### 4. Index View (index.blade.php)

**Route:** `/visa-processing`

**Layout:**
- Candidates table with stage status badges
- Quick links to Stage Dashboard
- **Styling**: Fully rewritten to Tailwind CSS (was Bootstrap)

---

## Access Control (RBAC)

| Action                    | Super Admin | Admin | Campus Admin | Project Director | OEP | Visa Partner |
|---------------------------|:-----------:|:-----:|:------------:|:----------------:|:---:|:------------:|
| View Stage Dashboard      | Y           | Y     | Campus Only  | Y                | Y   | Y            |
| View Stage Details        | Y           | Y     | Campus Only  | Y                | Y   | Y            |
| Schedule Appointment      | Y           | Y     | Campus Only  | N                | N   | N            |
| Record Stage Result       | Y           | Y     | Campus Only  | N                | N   | N            |
| Upload Evidence           | Y           | Y     | Campus Only  | N                | N   | N            |
| Update Visa Application   | Y           | Y     | Campus Only  | N                | N   | N            |

All routes are protected by `role:admin,project_director,campus_admin,instructor,oep,visa_partner` middleware.

---

## API Endpoints

### Web Routes (Module 5 Enhancement)

| Method | Route                                              | Action                        |
|--------|-----------------------------------------------------|-------------------------------|
| GET    | `/visa-processing/hierarchical-dashboard`          | Hierarchical stage dashboard  |
| GET    | `/visa-processing/stage/{visaProcess}/{stage}`     | View stage details            |
| POST   | `/visa-processing/stage/{visaProcess}/{stage}`     | Schedule/record/upload stage  |
| POST   | `/visa-processing/visa-application/{visaProcess}`  | Update visa application status|

### Retained Routes

| Method | Route                                              | Action                        |
|--------|-----------------------------------------------------|-------------------------------|
| GET    | `/visa-processing`                                 | Index (candidates list)       |
| GET    | `/visa-processing/create`                          | Create new visa process form  |
| POST   | `/visa-processing`                                 | Store new visa process        |
| GET    | `/visa-processing/{candidate}`                     | Show visa process details     |
| GET    | `/visa-processing/{candidate}/edit`                | Edit visa process form        |
| PUT    | `/visa-processing/{candidate}`                     | Update visa process           |
| GET    | `/visa-processing/{candidate}/timeline`            | Timeline view                 |
| POST   | `/visa-processing/{candidate}/update-enumber`      | E-number update (external)    |
| POST   | `/visa-processing/{candidate}/update-biometric`    | Biometrics (persists Etimad appointment id + center) |
| POST   | `/visa-processing/{candidate}/complete`            | Complete visa process & hand off to Departure |
| GET    | `/visa-processing/dashboard`                       | Analytics dashboard           |
| GET    | `/visa-processing/reports/overdue`                 | Overdue report                |
| POST   | `/visa-processing/reports/generate`                | Generate report               |

All routes require authentication and role-based authorization.

---

## Validation Rules

### Stage Update (VisaStageUpdateRequest)

```php
[
    'action'           => 'required|in:schedule,result,evidence',
    'appointment_date' => 'required_if:action,schedule|nullable|date|after_or_equal:today',
    'appointment_time' => 'required_if:action,schedule|nullable|date_format:H:i',
    'center'           => 'required_if:action,schedule|nullable|string|max:200',
    'result_status'    => 'required_if:action,result|nullable|in:pending,scheduled,pass,fail,refused',
    'notes'            => 'nullable|string|max:2000',
    'evidence'         => 'required_if pass/fail result|nullable|file|max:10240|mimes:pdf,jpg,jpeg,png',
]
```

### Visa Application Update

```php
[
    'application_status' => 'required|in:not_applied,applied,refused',
    'issued_status'      => 'nullable|in:pending,confirmed,refused',
    'visa_number'        => 'nullable|string|max:50',
    'visa_date'          => 'nullable|date',
    'ptn_number'         => 'nullable|string|max:50',
    'notes'              => 'nullable|string|max:2000',
    'evidence'           => 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png',
]
```

---

## Business Logic

### VisaStageDetails Value Object

Immutable value object stored as JSON in `*_details` columns:

```php
class VisaStageDetails implements Arrayable
{
    // Constructor with promoted properties
    public ?string $appointmentDate, $appointmentTime, $center,
                   $resultStatus, $evidencePath, $notes,
                   $updatedAt;
    public ?int $updatedBy;

    // Factory & serialization
    public static function fromArray(?array $data): self;
    public function toArray(): array;

    // Query methods
    public function isScheduled(): bool;
    public function hasResult(): bool;
    public function isPassed(): bool;
    public function hasEvidence(): bool;
    public function getResultEnum(): ?VisaStageResult;

    // Immutable transitions
    public function withAppointment(string $date, string $time, string $center): self;
    public function withResult(string $resultStatus, ?string $notes, ?string $evidencePath): self;
}
```

### Stage Scheduling Flow

```php
// Service: scheduleStage()
1. Validate stage is schedulable (interview, trade_test, takamol, medical, biometric)
2. Validate prerequisites (e.g., interview must pass before trade_test)
3. Model: scheduleStageAppointment()
   - Create VisaStageDetails with appointment data
   - Set {stage}_status = 'scheduled'
   - Save + log activity
```

### Result Recording Flow

```php
// Service: recordStageResultWithDetails()
DB::transaction(function() {
    // 1. Upload evidence file if provided
    // 2. Model: recordStageResult() - update details + status
    // 3. If terminal (FAIL/REFUSED):
    //    - Set failed_at, failed_stage, failure_reason
    //    - Transition candidate to REJECTED
    // 4. Log activity
});
```

### Visa Application Status Flow

```php
// Service: updateVisaApplicationStatus()
DB::transaction(function() {
    // 1. Map application/issued status to VisaStageResult for details JSON:
    //    - confirmed → PASS, refused → REFUSED, applied → SCHEDULED, else → PENDING
    // 2. Upload evidence if provided
    // 3. Update visa_application_details via VisaStageDetails (with mapped result)
    // 4. Set visa_application_status enum
    // 5. If issued_status provided, set visa_issued_status enum
    // 6. Branch:
    //    - 'confirmed' → visa_issued=true, candidate → VISA_APPROVED
    //    - 'refused' → set failure fields, candidate → REJECTED
    // 7. Log activity
});
```

### Data Flow: Module 4 to Module 5

```
Training (Module 4)
├── Both tracks (Technical + Soft Skills) complete
├── Fires TrainingCompleted event
└── HandleTrainingCompleted listener:
    ├── Guard: candidate.status must be 'training'
    ├── Guard: training_status must be 'completed'
    ├── Sets candidate.status = VISA_PROCESS
    ├── Creates VisaProcess via firstOrCreate (idempotent)
    ├── Initializes all sub-statuses to 'pending'
    └── Logs activity

Legacy Training Path
├── TrainingService::completeTraining() sets training_status='completed'
├── Does NOT fire TrainingCompleted event
└── Manual visa process creation via /visa-processing/create
```

---

## Testing

### Unit Tests (`VisaStageDetailsTest.php`)

17 tests covering:
- Creates empty details from null
- Creates from array with all fields
- Serializes to array (toArray)
- Handles partial data correctly
- `isScheduled()` returns true when appointment exists
- `hasResult()` returns true when result exists
- `isPassed()` returns true for pass result
- `hasEvidence()` returns true when evidence path exists
- `getResultEnum()` returns correct VisaStageResult
- `getResultEnum()` returns null for empty details
- Filters null values in toArray

**Run:** `php artisan test tests/Unit/VisaStageDetailsTest.php`

### Unit Tests (`VisaEnumsTest.php`)

10 tests covering:
- VisaStageResult has all expected cases (PENDING, SCHEDULED, PASS, FAIL, REFUSED)
- VisaStageResult `label()`, `color()`, `icon()` methods
- VisaStageResult `allowsProgress()` only true for PASS
- VisaStageResult `isTerminal()` true for FAIL and REFUSED
- VisaApplicationStatus has all expected cases
- VisaApplicationStatus `label()`, `color()` methods
- VisaIssuedStatus has all expected cases
- VisaIssuedStatus `label()`, `color()` methods
- All enums have `toArray()` static methods

**Run:** `php artisan test tests/Unit/VisaEnumsTest.php`

### Feature Tests (`VisaProcessingEnhancementTest.php`)

19 tests covering:
- Admin can access hierarchical dashboard
- Dashboard shows category counts (scheduled, pending, passed, failed)
- Unauthenticated user redirected from dashboard
- Admin can view stage details
- Invalid stage returns 404
- Admin can schedule interview (with future date validation)
- Schedule validation requires future date
- Admin can record stage result with evidence
- Admin can update visa application status
- Visa confirmed updates candidate status to VISA_APPROVED
- Training completed listener creates visa process
- Listener does not duplicate existing visa process
- Listener skips non-training status candidates
- Model casts JSON details to array
- Model details object accessors return VisaStageDetails
- Model stages overview returns all 6 stages
- Model hierarchical status returns categorized data
- Model casts visa_application_status enum
- Model casts visa_issued_status enum

**Run:** `php artisan test tests/Feature/VisaProcessingEnhancementTest.php`

**All Module 5 Tests:** `php artisan test tests/Unit/VisaStageDetailsTest.php tests/Unit/VisaEnumsTest.php tests/Feature/VisaProcessingEnhancementTest.php`

---

## Activity Logging

All visa processing actions are logged via Spatie Activity Log:

```php
activity()
    ->performedOn($visaProcess)
    ->causedBy(auth()->user())
    ->withProperties([
        'stage' => 'interview',
        'appointment_date' => '2026-03-15',
        'appointment_time' => '10:00',
        'center' => 'Test Center Lahore',
    ])
    ->log('Scheduled interview appointment');
```

**Logged Events:**
- Stage appointment scheduled (with date, time, center)
- Stage result recorded (with stage, result)
- Visa application status updated (with application_status, issued_status)
- Training to visa transition (with training_id, transition)
- Evidence uploaded (via model save)

---

## Backward Compatibility & Legacy Deprecation

### Deprecated Legacy Routes (REMOVED)

The following legacy routes have been **removed** in favor of Module 5 stage management:

| Removed Route | Module 5 Replacement |
|---|---|
| `POST /update-interview` | `POST /stage/{visaProcess}/interview` |
| `POST /update-trade-test` | `POST /stage/{visaProcess}/trade_test` |
| `POST /update-takamol` | `POST /stage/{visaProcess}/takamol` |
| `POST /update-medical` | `POST /stage/{visaProcess}/medical` |
| `POST /update-biometric` | `POST /stage/{visaProcess}/biometric` |
| `POST /upload-takamol-result` | `POST /stage/{visaProcess}/takamol` (evidence action) |
| `POST /upload-gamca-result` | `POST /stage/{visaProcess}/medical` (evidence action) |
| `POST /update-visa-submission` | `POST /visa-application/{visaProcess}` |
| `POST /update-visa` | `POST /visa-application/{visaProcess}` |
| `POST /update-ptn` | `POST /visa-application/{visaProcess}` (ptn_number field) |

### Retained Routes (No Module 5 Equivalent)

| Route | Reason |
|---|---|
| `POST /update-enumber` | E-Number is externally generated |
| `POST /complete` | Process completion validation + hand-off to Departure |
| `GET /timeline` | Timeline view |
| `GET /dashboard` | Analytics dashboard |
| Resource routes (CRUD) | Index, create, store, show, edit, update, destroy |

### Legacy-Compatible Status Values

Module 5's `recordStageResult()` maps VisaStageResult values to legacy-compatible status values:

| Stage | pass → | fail → | Legacy Expected |
|---|---|---|---|
| Interview | `passed` | `failed` | `passed`/`failed` |
| Trade Test | `passed` | `failed` | `passed`/`failed` |
| Takamol | `completed` | `failed` | `completed`/`failed` |
| Medical | `fit` | `unfit` | `fit`/`unfit` |
| Biometric | `completed` | `failed` | `completed`/`failed` |

Module 5 also sets `*_completed` booleans and advances `overall_status` on pass results, ensuring the progress bar, completion validation, and all legacy code paths work correctly.

### Legacy Training Path

- The legacy `TrainingService::completeTraining()` method does NOT fire `TrainingCompleted` event
- This means the listener never runs for legacy path (no double-fire risk)
- Legacy candidates can still be manually added to visa processing via `/visa-processing/create`
- Candidate status field is NOT cast as an enum (stays as string), ensuring listener string comparison works correctly

---

## File Structure

### Created Files

```
app/
├── Http/
│   └── Requests/
│       └── VisaStageUpdateRequest.php
├── Listeners/
│   └── HandleTrainingCompleted.php
└── ValueObjects/
    └── VisaStageDetails.php

database/
└── migrations/
    └── 2026_02_17_000001_add_enhanced_stage_details_to_visa_processes.php

resources/views/visa-processing/
├── hierarchical-dashboard.blade.php
└── stage-details.blade.php

tests/
├── Unit/
│   ├── VisaStageDetailsTest.php
│   └── VisaEnumsTest.php
└── Feature/
    └── VisaProcessingEnhancementTest.php
```

### Modified Files

```
app/
├── Enums/
│   ├── VisaStageResult.php (added SCHEDULED case, color/icon/allowsProgress/isTerminal/toArray methods)
│   ├── VisaApplicationStatus.php (added color/toArray methods)
│   └── VisaIssuedStatus.php (added color/toArray methods)
├── Http/Controllers/
│   └── VisaProcessingController.php (4 new methods: hierarchicalDashboard, stageDetails, updateStage, updateVisaApplication)
├── Models/
│   └── VisaProcess.php (JSON casts, enum casts, DETAIL_STAGES constant, detail accessors, stage overview/hierarchy, appointment/result/evidence methods)
├── Providers/
│   └── AppServiceProvider.php (registered TrainingCompleted → HandleTrainingCompleted listener)
└── Services/
    └── VisaProcessingService.php (6 new methods: scheduleStage, recordStageResultWithDetails, updateVisaApplicationStatus, getHierarchicalDashboard, getStagesMissingEvidence, validateStagePrerequisites)

resources/views/visa-processing/
├── show.blade.php (rewritten from Bootstrap to Tailwind CSS, added stage-details links)
└── index.blade.php (rewritten from Bootstrap to Tailwind CSS)

routes/
└── web.php (4 new routes before resource routes)
```

---

## Configuration

### Enums

- `App\Enums\VisaStageResult` -- PENDING, SCHEDULED, PASS, FAIL, REFUSED (with label, color, icon, allowsProgress, isTerminal)
- `App\Enums\VisaApplicationStatus` -- NOT_APPLIED, APPLIED, REFUSED (with label, color)
- `App\Enums\VisaIssuedStatus` -- PENDING, CONFIRMED, REFUSED (with label, color)

### Detail Stages

Stages supporting detailed tracking with `VisaStageDetails`:

```php
const DETAIL_STAGES = ['interview', 'trade_test', 'takamol', 'medical', 'biometric', 'visa_application'];
```

### Processing Stages (10 + Completed)

```php
const STAGES = [
    'initiated'       => ['label' => 'Initiated',             'order' => 1,  'color' => 'secondary'],
    'interview'       => ['label' => 'Interview',             'order' => 2,  'color' => 'info'],
    'trade_test'      => ['label' => 'Trade Test',            'order' => 3,  'color' => 'info'],
    'takamol'         => ['label' => 'Takamol Test',          'order' => 4,  'color' => 'info'],
    'medical'         => ['label' => 'Medical (GAMCA)',        'order' => 5,  'color' => 'info'],
    'enumber'         => ['label' => 'E-Number',              'order' => 6,  'color' => 'info'],
    'biometrics'      => ['label' => 'Biometrics (Etimad)',    'order' => 7,  'color' => 'info'],
    'visa_submission' => ['label' => 'Visa Submission',        'order' => 8,  'color' => 'warning'],
    'visa_issued'     => ['label' => 'Visa Issuance',          'order' => 9,  'color' => 'primary'],
    'ptn'             => ['label' => 'PTN Clearance',          'order' => 10, 'color' => 'primary'],
    'protector'       => ['label' => 'Protector Clearance',    'order' => 11, 'color' => 'warning'],
    'completed'       => ['label' => 'Completed',              'order' => 12, 'color' => 'success'],
];
```

---

## Validation Checklist

- [x] `visa_processes` table has `takamol_details`, `failed_at`, `failed_stage`, `failure_reason` columns
- [x] `VisaStageDetails` value object with `fromArray()`, `toArray()`, `withAppointment()`, `withResult()`
- [x] `VisaStageResult` enum has SCHEDULED case and helper methods
- [x] `VisaApplicationStatus` enum has `color()` and `toArray()` methods
- [x] `VisaIssuedStatus` enum has `color()` and `toArray()` methods
- [x] `VisaProcess` model has JSON casts, enum casts, and detail object accessors
- [x] Service methods for scheduling, result recording, and visa application work
- [x] Controller methods added and authorized
- [x] Module 5 routes placed BEFORE resource routes (prevents route collision)
- [x] Hierarchical dashboard shows correct categorized data
- [x] Stage details view shows appointment/result/evidence data
- [x] Appointments schedule correctly with future date validation
- [x] Results record correctly with mandatory evidence for pass/fail
- [x] Terminal failures (FAIL/REFUSED) mark process as failed and reject candidate
- [x] Visa confirmed transitions candidate to VISA_APPROVED
- [x] Training completed listener creates VisaProcess (idempotent)
- [x] Listener guards prevent duplicate processing
- [x] Legacy routes and methods still work unchanged
- [x] All views use Tailwind CSS consistently
- [x] All 46 Module 5 tests pass (17 unit + 10 enum unit + 19 feature)

---

## Known Issues & Limitations

### Current Limitations

1. **No Bulk Stage Updates**
   - Stages must be updated individually per candidate
   - Future enhancement: Bulk scheduling interface

2. **No Stage Re-Scheduling**
   - Scheduling a new appointment overwrites the previous one
   - No history of rescheduled appointments (audit trail via Spatie Activity Log)

3. **Evidence Required for Pass/Fail Only**
   - Evidence is mandatory for pass/fail results but optional for scheduled/refused
   - Future enhancement: Configurable evidence requirements per stage

4. **Legacy Route Overlap**
   - Both legacy update routes (`/update-interview`) and new stage routes (`/stage/{id}/interview`) coexist
   - Legacy routes preserved for backward compatibility with existing integrations

### Workarounds

- **Bulk Updates**: Use legacy update routes with batch scripts
- **Re-Scheduling**: Schedule again (overwrites). Check activity log for history.
- **Evidence Later**: Use the standalone "Upload Evidence" action tab after recording result

---

## Troubleshooting

**Problem:** Hierarchical dashboard shows zero items
**Solution:** Ensure VisaProcess records exist with `overall_status != 'completed'`. Check campus filter dropdown if campus admin.

**Problem:** Cannot schedule a stage (prerequisite error)
**Solution:** Verify prerequisite stages have passed. For example, trade test requires interview to be passed first. Check `interview_details` has `result_status = 'pass'` or `interview_status = 'completed'`.

**Problem:** Evidence upload fails
**Solution:** Check file size (max 10MB) and format (PDF, JPG, PNG only). Verify `storage/app/private` is writable.

**Problem:** Training to Visa transition not happening
**Solution:** Ensure both training tracks (Technical + Soft Skills) are completed. Check candidate `status = 'training'` and `training_status = 'completed'`. The listener only fires for Module 4 dual-status path.

**Problem:** Stage details route returns 404
**Solution:** Ensure the stage name matches one of: `interview`, `trade_test`, `takamol`, `medical`, `biometric`, `visa_application`.

**Problem:** Show page looks unstyled
**Solution:** The view has been rewritten to Tailwind CSS. Ensure Vite is running (`npm run dev`) or assets are built (`npm run build`).

---

## Future Enhancements

### Planned Features (v1.1)

- [ ] Bulk stage scheduling interface
- [ ] Stage rescheduling with history
- [ ] Email/SMS notifications for appointments
- [ ] Visa partner portal (limited access)
- [ ] Stage timeline visualization
- [ ] PDF export of visa process summary
- [ ] Automated stage progression reminders
- [ ] Integration with external GAMCA/Etimad APIs
- [ ] Evidence gallery view per candidate
- [ ] Stage SLA tracking and breach alerts

---

## Support & Maintenance

**Developer Contact:** BTEVTA Development Team
**Documentation:** `/docs/MODULE_5_VISA_PROCESSING.md`
**Source Code:** `haseebayazi/btevta` repository
**Test Coverage:** 100% (46/46 new tests passing)

---

## Change Log

### Version 1.1.0 (June 2026) — Bug-fix pass

Fixes for four reported visa-processing issues:

1. **Blank-on-edit fields persisted.** Added `enumber_date` and `etimad_center`
   columns (migration `2026_06_13_000001`) and made them fillable. The biometric
   update service now persists `etimad_appointment_id` and `etimad_center`, so
   saved values no longer reappear blank when the edit form is reopened.
2. **Individual candidate dashboard no longer stuck at "Visa Issuance".**
   Completion now drives `overall_status` to `completed`, so the progress bar
   reflects the true state once the process is completed.
3. **Ticket & Travel Plan removed from visa processing.** Removed the
   `upload-ticket` and `upload-travel-plan` routes, controller methods, service
   methods, and the show-view card. These are handled by the Departure module.
   The show view now shows a **Final Clearances (PTN & Protector)** card instead.
4. **Reliable hand-off to Departure.** Completion is gated on
   `VisaProcess::isReadyToComplete()` (required stages, not a ticket upload) and
   is reachable from both the show and edit views. Completing transitions the
   candidate to `DEPARTURE_PROCESSING` and auto-creates a `Departure` record so
   the candidate appears in the Departure module. A "Pending for Completion"
   checklist (`getOutstandingCompletionRequirements()`) shows any unmet
   requirements.

Tests: added feature tests for Etimad persistence, departure creation on
completion, completion gating, and E-Number date persistence.

### Version 1.0.0 (February 2026)
- VisaStageDetails value object for immutable stage data
- Hierarchical stage dashboard with categorized views
- Per-stage appointment scheduling with prerequisites
- Per-stage result recording with mandatory evidence
- Visa application two-tier status (applied/issued)
- Terminal failure handling with candidate rejection
- Training to Visa transition via HandleTrainingCompleted listener
- VisaStageResult enum enhanced (SCHEDULED case, helper methods)
- VisaApplicationStatus and VisaIssuedStatus enums enhanced
- VisaProcess model with JSON/enum casts and stage accessors
- VisaProcessingService with 6 new methods
- VisaProcessingController with 4 new methods
- VisaStageUpdateRequest form request with validation
- 4 new routes (dashboard, stage details, stage update, visa application)
- show.blade.php and index.blade.php rewritten to Tailwind CSS
- 2 new Tailwind views (hierarchical-dashboard, stage-details)
- Event listener registered in AppServiceProvider
- 17 unit tests + 10 enum tests + 19 feature tests (all passing)
- All existing visa processing functionality preserved
- Backward compatible with legacy training path

---

*Last Updated: February 2026*
