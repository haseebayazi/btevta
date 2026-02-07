# Module 4: Training Management Enhancement

**Version:** 1.0.0
**Status:** ✅ Complete
**Implementation Date:** February 2026

---

## Overview

Module 4 enhances the existing BTEVTA WASL Training Management with **dual-status tracking** for Technical Training and Soft Skills Training. Each candidate now has two independent training tracks that must both be completed before they can proceed to Visa Processing (Module 5). This module also introduces typed assessments, evidence upload, automatic grade calculation, and a comprehensive dual-status dashboard.

---

## Features

### Core Functionality

1. **Dual-Status Training Tracks**
   - **Technical Training**: Covers technical theory, practical workshops, and final assessment
   - **Soft Skills Training**: Covers cultural orientation, language training, soft skills, and final assessment
   - Each track progresses independently: Not Started → In Progress → Completed
   - Overall completion requires both tracks to be completed

2. **Typed Assessments**
   - Assessments are now scoped by training type (technical / soft_skills)
   - 5 assessment types: Initial, Interim, Midterm, Practical, Final
   - Technical types: Initial, Midterm, Practical, Final
   - Soft skills types: Interim, Final
   - Duplicate assessment prevention per candidate/type/training_type combination

3. **Automatic Grade Calculation**
   - Grades auto-calculated on save: A (≥90%), B (≥80%), C (≥70%), D (≥50%), F (<50%)
   - Pass/fail based on 50% threshold
   - Percentage calculated from score / max_score

4. **Evidence Upload**
   - Optional file upload per assessment
   - Accepted formats: PDF, JPG, JPEG, PNG
   - Maximum file size: 10MB
   - Secure storage in `storage/app/private/training/assessments/`
   - Old evidence deleted when new evidence uploaded

5. **Completion Logic**
   - Technical completion requires: midterm OR practical (pass) AND final (pass)
   - Soft skills completion requires: final (pass)
   - When both tracks complete, `TrainingCompleted` event fires
   - Candidate `training_status` updated to 'completed' automatically

6. **Dual-Status Dashboard**
   - Batch-level overview with doughnut charts per training type
   - Summary cards: total candidates, technical completed, soft skills completed, fully complete
   - Candidate table with dual-status badges and progress bars

7. **Candidate Progress View**
   - Individual candidate training progress across both tracks
   - Assessment history with scores, grades, and pass/fail indicators
   - Inline assessment recording form
   - Attendance summary integration
   - Complete buttons when assessment requirements are met

---

## Workflow

```
┌──────────────────────┐
│    REGISTERED        │
│    (Module 3)        │
└──────────┬───────────┘
           │
           ▼
┌──────────────────────┐
│    TRAINING          │◄─── Start Training (assigns batch)
└──────────┬───────────┘
           │
           ▼
┌───────────────────────────────────────────────────────┐
│  Training Record Created                              │
│  ┌──────────────────┐  ┌───────────────────────────┐  │
│  │ Technical Track   │  │ Soft Skills Track         │  │
│  │ ─────────────────│  │ ──────────────────────────│  │
│  │ Status: Not Started│  │ Status: Not Started       │  │
│  │                   │  │                           │  │
│  │ Assessments:      │  │ Assessments:              │  │
│  │ ○ Initial         │  │ ○ Interim                 │  │
│  │ ○ Midterm         │  │ ○ Final (required)        │  │
│  │ ○ Practical       │  │                           │  │
│  │ ○ Final (required)│  │ Complete when:            │  │
│  │                   │  │ Final ≥ 50%               │  │
│  │ Complete when:    │  │                           │  │
│  │ (Midterm OR       │  └───────────────────────────┘  │
│  │  Practical) ≥ 50% │                                 │
│  │ AND Final ≥ 50%   │                                 │
│  └──────────────────┘                                  │
└──────────┬────────────────────────────────────────────┘
           │
           ├─── Both Complete ──► TRAINING_COMPLETED → VISA_PROCESS
           └─── Not Complete ──► Remains in TRAINING
```

---

## Database Schema

### New Table: `trainings`

| Column                      | Type           | Description                          |
|-----------------------------|----------------|--------------------------------------|
| `id`                        | bigint         | Primary key                          |
| `candidate_id`              | bigint (FK)    | Foreign key to candidates (unique)   |
| `batch_id`                  | bigint (FK)    | Foreign key to batches (nullable)    |
| `status`                    | varchar(20)    | Overall status: not_started, in_progress, completed |
| `technical_training_status` | enum           | not_started, in_progress, completed  |
| `soft_skills_status`        | enum           | not_started, in_progress, completed  |
| `technical_completed_at`    | timestamp      | When technical track completed       |
| `soft_skills_completed_at`  | timestamp      | When soft skills track completed     |
| `completed_at`              | timestamp      | When both tracks completed           |
| `created_by`                | bigint         | User who created record              |
| `updated_by`                | bigint         | User who last updated                |
| `deleted_at`                | timestamp      | Soft delete                          |
| `created_at`                | timestamp      | Created timestamp                    |
| `updated_at`                | timestamp      | Updated timestamp                    |

### Modified Table: `training_assessments` (new columns)

| Column           | Type          | Description                                |
|------------------|---------------|--------------------------------------------|
| `training_id`    | bigint (FK)   | Foreign key to trainings (nullable)        |
| `training_type`  | enum          | 'technical', 'soft_skills', or 'both'      |
| `evidence_path`  | varchar(500)  | Path to uploaded evidence file (nullable)  |

---

## User Interface

### 1. Dual-Status Dashboard

**Route:** `/training/batch/{batch}/dual-status`

**Components:**
- **Summary Cards**
  - Total Candidates count
  - Technical Training Completed count (with in-progress / pending breakdown)
  - Soft Skills Completed count (with in-progress / pending breakdown)
  - Fully Complete count with progress bar

- **Doughnut Charts**
  - Technical Training progress (Not Started / In Progress / Completed)
  - Soft Skills progress (Not Started / In Progress / Completed)

- **Candidates Table**
  - Candidate name, TheLeap ID
  - Technical status badge with icon
  - Soft Skills status badge with icon
  - Combined progress bar (0-100%)
  - View action link

### 2. Candidate Progress View

**Route:** `/training/progress/{training}`

**Layout:**
- Overall completion progress bar
- Two-column dual-track display:
  - **Technical Training Card**: status badge, assessment list with scores/grades, complete button
  - **Soft Skills Card**: status badge, assessment list with scores/grades, complete button
- Attendance summary (5-column grid)
- Collapsible "Record New Assessment" form with:
  - Training type dropdown (Technical / Soft Skills)
  - Assessment type dropdown
  - Score and max score inputs
  - Notes textarea
  - Evidence file upload
  - Submit button

---

## Access Control (RBAC)

| Action                   | Super Admin | Admin | Campus Admin | Instructor | Viewer |
|--------------------------|:-----------:|:-----:|:------------:|:----------:|:------:|
| View Dual Dashboard      | ✓           | ✓     | Campus Only  | ✓          | ✗      |
| View Candidate Progress  | ✓           | ✓     | Campus Only  | ✓          | ✗      |
| Record Assessment        | ✓           | ✓     | Campus Only  | ✓          | ✗      |
| Complete Training Type   | ✓           | ✓     | Campus Only  | ✓          | ✗      |

All routes are protected by `role:admin,campus_admin,instructor` middleware.

---

## API Endpoints

### Web Routes

| Method | Route                                             | Action                     |
|--------|---------------------------------------------------|----------------------------|
| GET    | `/training/batch/{batch}/dual-status`             | Dual-status dashboard      |
| GET    | `/training/progress/{training}`                   | Candidate progress view    |
| POST   | `/training/progress/{training}/typed-assessment`  | Record typed assessment    |
| POST   | `/training/progress/{training}/complete-type`     | Complete training type     |

All routes require authentication and `role:admin,campus_admin,instructor` authorization.

---

## Validation Rules

### Typed Assessment

```php
[
    'candidate_id'    => 'required|exists:candidates,id',
    'assessment_type' => 'required|in:initial,interim,midterm,practical,final',
    'training_type'   => 'required|in:technical,soft_skills',
    'score'           => 'required|numeric|min:0|max:100',
    'max_score'       => 'required|numeric|min:1|max:100',
    'notes'           => 'nullable|string|max:1000',
    'evidence'        => 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png',
]
```

### Complete Training Type

```php
[
    'training_type' => 'required|in:technical,soft_skills',
]
```

---

## Business Logic

### Training Record Lifecycle

1. **Creation**: `Training::findOrCreateForCandidate($candidate)` creates a Training record when a candidate enters training
2. **Auto-Start**: Recording a typed assessment auto-starts the corresponding track if `NOT_STARTED`
3. **Assessment Checks**: Completion requires passing the right assessments for each track
4. **Completion**: When both tracks complete, fires `TrainingCompleted` event, updates candidate status

### Assessment Processing

```php
// Service: recordAssessmentWithType()
DB::transaction(function() {
    // 1. Check for duplicate assessment
    // 2. Calculate pass/fail (score >= 50% of max)
    // 3. Create assessment record with auto-grade
    // 4. Upload evidence (if provided)
    // 5. Auto-start training track if not started
    // 6. Log activity
});
```

### Completion Requirements

| Track     | Required Assessments                                      |
|-----------|-----------------------------------------------------------|
| Technical | (Midterm OR Practical with score ≥ 50%) AND (Final with score ≥ 50%) |
| Soft Skills | Final with score ≥ 50%                                  |

### Data Flow: Module 3 → Module 4

```
Registration (Module 3)
├── Assigns: campus_id, program_id, batch_id, trade_id, oep_id
├── startTraining() → candidate.status = 'training'
└── Batch assignment ready

Training (Module 4)
├── Training::findOrCreateForCandidate() creates training record
├── Links to batch via batch_id
├── Records assessments with training_type
├── Tracks dual-status independently
└── On both complete → candidate ready for visa processing
```

---

## Testing

### Unit Tests (`TrainingDualStatusTest.php`)

25 tests covering:
- Training model starts with NOT_STARTED status
- Completion percentage calculations (0%, 50%, 75%, 100%)
- `isBothComplete()` returns correctly for partial/full completion
- Start technical/soft skills training updates status
- Start technical training is idempotent
- Completion requires passed assessments (technical and soft skills)
- Completion with valid assessments succeeds
- Both tracks completing fires `TrainingCompleted` event
- Both tracks completing sets overall 'completed' status
- Grade calculation: A (≥90%), B (≥80%), C (≥70%), D (≥50%), F (<50%)
- Auto-grade on save
- `findOrCreateForCandidate()` creates new / returns existing
- `TrainingProgress` enum `icon()` method
- Candidate `training()` relationship

**Run:** `php artisan test tests/Unit/TrainingDualStatusTest.php`

### Feature Tests (`TrainingDualStatusControllerTest.php`)

9 tests covering:
- Dual-status dashboard accessible and shows correct counts
- Candidate progress page accessible
- Can record typed technical assessment
- Can record typed soft skills assessment
- Validation rejects invalid data
- Can complete technical training type
- Cannot complete without assessments
- Unauthenticated access blocked

**Run:** `php artisan test tests/Feature/TrainingDualStatusControllerTest.php`

**All Module 4 Tests:** `php artisan test tests/Unit/TrainingDualStatusTest.php tests/Feature/TrainingDualStatusControllerTest.php`

---

## Activity Logging

All training actions are logged via Spatie Activity Log:

```php
activity()
    ->performedOn($training)
    ->causedBy(auth()->user())
    ->withProperties([
        'candidate_id' => $candidate->id,
        'assessment_type' => 'midterm',
        'training_type' => 'technical',
        'score' => 75,
        'max_score' => 100,
        'grade' => 'C',
    ])
    ->log('Assessment recorded: technical - midterm');
```

**Logged Events:**
- Technical training started
- Soft skills training started
- Technical training completed
- Soft skills training completed
- Assessment recorded (with type/score/grade details)

---

## Backward Compatibility

### Existing Training System

- All existing training functionality **PRESERVED** (attendance, assessments, certificates, reports)
- Existing assessments migrated with `training_type = 'both'` for backward compatibility
- Legacy assessment routes and methods still work unchanged
- `TrainingService` original methods untouched; new methods added alongside

### Data Migration

- Existing candidates in training get a `Training` record created automatically
- Status mapped from candidate `training_status` to dual-status columns
- Existing assessments linked to new `training_id` column
- Migration is non-destructive and reversible

---

## File Structure

### Created Files

```
app/
├── Events/
│   └── TrainingCompleted.php
└── Models/
    └── Training.php

database/
├── factories/
│   └── TrainingFactory.php
└── migrations/
    ├── 2026_02_07_000001_create_trainings_table.php
    ├── 2026_02_07_000002_enhance_training_assessments.php
    └── 2026_02_07_000003_migrate_existing_training_status.php

resources/views/training/
├── dual-status-dashboard.blade.php
└── candidate-progress.blade.php

tests/
├── Unit/
│   └── TrainingDualStatusTest.php
└── Feature/
    └── TrainingDualStatusControllerTest.php
```

### Modified Files

```
app/
├── Enums/
│   ├── AssessmentType.php (expanded from 2 to 5 cases)
│   └── TrainingProgress.php (added icon() method)
├── Http/Controllers/
│   └── TrainingController.php (4 new methods)
├── Models/
│   ├── Candidate.php (added training() relationship)
│   └── TrainingAssessment.php (grade calc, evidence upload, training rel)
└── Services/
    └── TrainingService.php (4 new methods)

routes/
└── web.php (4 new routes)

tests/Unit/
└── WASLv3EnumsTest.php (updated AssessmentType assertion)
```

---

## Configuration

### Enums

- `App\Enums\TrainingProgress` — NOT_STARTED, IN_PROGRESS, COMPLETED (with label, color, icon)
- `App\Enums\AssessmentType` — INITIAL, INTERIM, MIDTERM, PRACTICAL, FINAL (with label, isRequired, technicalTypes, softSkillsTypes)

### Assessment Type Mapping

| Assessment Type | Technical | Soft Skills |
|-----------------|:---------:|:-----------:|
| Initial         | ✓         | ✗           |
| Interim         | ✗         | ✓           |
| Midterm         | ✓         | ✗           |
| Practical       | ✓         | ✗           |
| Final           | ✓         | ✓           |

---

## Validation Checklist

- [x] `trainings` table exists with dual-status columns
- [x] `training_assessments` table has `training_id`, `training_type`, `evidence_path`
- [x] `TrainingProgress` enum has `icon()` method
- [x] `AssessmentType` enum has 5 cases with `technicalTypes()` and `softSkillsTypes()`
- [x] `Training` model has dual-status lifecycle methods
- [x] `TrainingAssessment` model has grade calculation and evidence upload
- [x] Service methods for typed assessments work
- [x] Controller methods added and authorized
- [x] Routes working
- [x] Dual-status dashboard shows correct stats
- [x] Technical assessments recorded correctly
- [x] Soft skills assessments recorded correctly
- [x] Cannot complete without required assessments
- [x] Both complete triggers `TrainingCompleted` event
- [x] Candidate status updates correctly
- [x] Existing data migrated properly
- [x] All 34 new tests pass
- [x] All 17 existing training tests pass (no regression)

---

## Known Issues & Limitations

### Current Limitations

1. **No Bulk Typed Assessment**
   - Assessments must be recorded individually per candidate per type
   - Future enhancement: Bulk assessment interface

2. **No Re-Assessment**
   - Once an assessment is recorded for a type/training_type combination, it cannot be overwritten
   - Admin must manually delete the old record to re-assess

3. **Manual Track Start**
   - Tracks auto-start when first assessment is recorded
   - Future enhancement: Explicit "Start Track" action

### Workarounds

- **Bulk Assessment**: Use the existing batch assessment interface for legacy-style assessments
- **Re-Assessment**: Admin can delete the old record via database, then re-record
- **Manual Start**: Record an initial assessment to trigger track start

---

## Troubleshooting

**Problem:** Dual-status dashboard shows zero candidates
**Solution:** Ensure Training records exist for candidates in the batch. Use `Training::findOrCreateForCandidate()` or the data migration.

**Problem:** Cannot complete training type despite passing assessments
**Solution:** Verify assessments have `training_type = 'technical'` or `'soft_skills'` (not `'both'`). Check `result = 'pass'` or `score >= 50`.

**Problem:** Evidence upload fails
**Solution:** Check file size (max 10MB) and format (PDF, JPG, PNG only). Verify `storage/app/private` is writable.

**Problem:** TrainingCompleted event not firing
**Solution:** Both tracks must be `COMPLETED`. Check `technical_training_status` and `soft_skills_status` are both `'completed'`.

---

## Future Enhancements

### Planned Features (v1.1)

- [ ] Bulk typed assessment interface
- [ ] Re-assessment workflow with approval
- [ ] Track-specific attendance tracking
- [ ] Progress notifications (email/SMS)
- [ ] Certificate generation gated on `isBothComplete()`
- [ ] Enhanced analytics per training type
- [ ] Mobile-responsive assessment form
- [ ] PDF export of dual-status report

---

## Support & Maintenance

**Developer Contact:** BTEVTA Development Team
**Documentation:** `/docs/MODULE_4_TRAINING.md`
**Source Code:** `haseebayazi/btevta` repository
**Test Coverage:** 100% (34/34 new tests passing, 17/17 existing tests passing)

---

## Change Log

### Version 1.0.0 (February 2026)
- ✅ Dual-status tracking (Technical + Soft Skills)
- ✅ Training model with lifecycle methods
- ✅ Typed assessments with training_type scoping
- ✅ Automatic grade calculation (A-F)
- ✅ Evidence upload for assessments
- ✅ Dual-status batch dashboard with charts
- ✅ Candidate progress view with inline assessment form
- ✅ TrainingCompleted event on both tracks complete
- ✅ Data migration for existing training records
- ✅ 25 unit tests + 9 feature tests (all passing)
- ✅ AssessmentType enum expanded (5 types)
- ✅ TrainingProgress enum icon() method
- ✅ Backward compatible with existing training system
- ✅ Activity logging for all actions

---

*Last Updated: February 2026*
