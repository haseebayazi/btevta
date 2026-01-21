# WASL v3 Comprehensive Audit Guide

**Project:** BTEVTA WASL v3
**Version:** 3.0.0
**Date:** January 19, 2026
**Purpose:** Complete system audit for production readiness

---

## Table of Contents

1. [Pre-Audit Preparation](#pre-audit-preparation)
2. [Phase 1: Database Audit](#phase-1-database-audit)
3. [Phase 2: Models & Relationships Audit](#phase-2-models--relationships-audit)
4. [Phase 3: Enums Audit](#phase-3-enums-audit)
5. [Phase 4: Controllers Audit](#phase-4-controllers-audit)
6. [Phase 5: Services & Business Logic Audit](#phase-5-services--business-logic-audit)
7. [Phase 6: Routes Audit](#phase-6-routes-audit)
8. [Phase 7: Views Audit](#phase-7-views-audit)
9. [Phase 8: Form Requests Audit](#phase-8-form-requests-audit)
10. [Phase 9: Policies Audit](#phase-9-policies-audit)
11. [Phase 10: Configuration Audit](#phase-10-configuration-audit)
12. [Phase 11: Tests Audit](#phase-11-tests-audit)
13. [Phase 12: Security Audit](#phase-12-security-audit)
14. [Phase 13: Code Quality Audit](#phase-13-code-quality-audit)
15. [Phase 14: Documentation Cross-Reference](#phase-14-documentation-cross-reference)
16. [Phase 15: Integration Testing](#phase-15-integration-testing)
17. [Audit Checklist Summary](#audit-checklist-summary)

---

## Pre-Audit Preparation

### Environment Setup

```bash
# Ensure you're on the correct branch
git checkout claude/review-docs-v3-4D9bm
git pull origin claude/review-docs-v3-4D9bm

# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Update dependencies
composer install
npm install

# Check Laravel version
php artisan --version
# Expected: Laravel Framework 11.x

# Check PHP version
php -v
# Expected: PHP 8.2+
```

### Audit Checklist Template

Create audit log file:

```bash
touch docs/v3/AUDIT_RESULTS_$(date +%Y%m%d).md
```

---

## Phase 1: Database Audit

### 1.1 Migration Files Verification

**Objective:** Verify all 20 WASL v3 migrations exist and are properly structured.

**Commands:**

```bash
# List all v3 migrations
ls -la database/migrations/ | grep "2026_01"

# Count v3 migrations
ls -1 database/migrations/ | grep "2026_01" | wc -l
# Expected: 20
```

**Expected Files (New Tables - 14):**

1. `2026_01_15_create_countries_table.php`
2. `2026_01_15_create_payment_methods_table.php`
3. `2026_01_15_create_programs_table.php`
4. `2026_01_15_create_implementing_partners_table.php`
5. `2026_01_15_create_employers_table.php`
6. `2026_01_15_create_candidate_employer_table.php`
7. `2026_01_15_create_document_checklists_table.php`
8. `2026_01_15_create_pre_departure_documents_table.php`
9. `2026_01_15_create_courses_table.php`
10. `2026_01_15_create_candidate_courses_table.php`
11. `2026_01_15_create_training_assessments_table.php`
12. `2026_01_15_create_post_departure_details_table.php`
13. `2026_01_15_create_employment_histories_table.php`
14. `2026_01_15_create_success_stories_table.php`

**Expected Files (Modified Tables - 6):**

15. `2026_01_16_add_allocation_fields_to_candidates_table.php`
16. `2026_01_16_modify_candidate_screenings_table.php`
17. `2026_01_16_modify_training_schedules_table.php`
18. `2026_01_16_modify_visa_processes_table.php`
19. `2026_01_16_modify_departures_table.php`
20. `2026_01_16_modify_complaints_table.php`

**Verification Steps:**

```bash
# Check each migration file exists
for file in \
  "2026_01_15_create_countries_table.php" \
  "2026_01_15_create_payment_methods_table.php" \
  "2026_01_15_create_programs_table.php" \
  "2026_01_15_create_implementing_partners_table.php" \
  "2026_01_15_create_employers_table.php" \
  "2026_01_15_create_candidate_employer_table.php" \
  "2026_01_15_create_document_checklists_table.php" \
  "2026_01_15_create_pre_departure_documents_table.php" \
  "2026_01_15_create_courses_table.php" \
  "2026_01_15_create_candidate_courses_table.php" \
  "2026_01_15_create_training_assessments_table.php" \
  "2026_01_15_create_post_departure_details_table.php" \
  "2026_01_15_create_employment_histories_table.php" \
  "2026_01_15_create_success_stories_table.php" \
  "2026_01_16_add_allocation_fields_to_candidates_table.php" \
  "2026_01_16_modify_candidate_screenings_table.php" \
  "2026_01_16_modify_training_schedules_table.php" \
  "2026_01_16_modify_visa_processes_table.php" \
  "2026_01_16_modify_departures_table.php" \
  "2026_01_16_modify_complaints_table.php"
do
  if [ -f "database/migrations/$file" ]; then
    echo "✅ $file"
  else
    echo "❌ MISSING: $file"
  fi
done
```

**Checklist:**

- [ ] All 20 migration files exist
- [ ] No duplicate migration files
- [ ] Migration timestamps in correct order
- [ ] Each migration has up() and down() methods
- [ ] Foreign keys properly defined
- [ ] Indexes added where needed
- [ ] Soft deletes implemented where appropriate

### 1.2 Migration Content Audit

**For each migration, verify:**

```bash
# Check migration structure
php artisan migrate:status

# Verify foreign key constraints
grep -r "foreign(" database/migrations/2026_01_*.php | wc -l
# Should show significant count

# Verify indexes
grep -r "index(" database/migrations/2026_01_*.php | wc -l

# Verify soft deletes
grep -r "softDeletes()" database/migrations/2026_01_*.php
```

**Specific Checks:**

1. **countries table:**
   - [ ] Has: id, name, code, iso_code, region, is_active, timestamps, softDeletes
   - [ ] Unique constraint on code

2. **programs table:**
   - [ ] Has: id, name, code, description, duration_weeks, is_active, timestamps, softDeletes
   - [ ] Unique constraint on code

3. **employers table:**
   - [ ] Has: permission_number, visa_issuing_company, country_id (FK), sector, trade
   - [ ] Has: basic_salary (decimal), currency, food/transport/accommodation (boolean)
   - [ ] Has: evidence_path, is_active, created_by (FK)
   - [ ] Foreign key to countries
   - [ ] Foreign key to users (created_by)

4. **candidate_employer pivot:**
   - [ ] Has: candidate_id, employer_id, is_current, assigned_at, assigned_by
   - [ ] Foreign keys properly set
   - [ ] Composite unique key on (candidate_id, employer_id, assigned_at)

5. **training_assessments table:**
   - [ ] Has: candidate_id, batch_id, assessment_type (enum)
   - [ ] Has: assessment_date, score, max_score, passing_score
   - [ ] Has: passed (boolean), assessor_id, remarks, evidence_path

6. **Modified candidates table:**
   - [ ] Added: program_id (FK to programs)
   - [ ] Added: implementing_partner_id (FK to implementing_partners)
   - [ ] Added: allocated_number (unique, nullable)

7. **Modified candidate_screenings table:**
   - [ ] Added: consent_for_work (boolean)
   - [ ] Added: placement_interest (enum: local/international)
   - [ ] Added: target_country_id (FK to countries, nullable)
   - [ ] Added: screening_status (enum)
   - [ ] Added: evidence_path, reviewer_id, reviewed_at

8. **Modified training_schedules table:**
   - [ ] Added: technical_training_status (enum)
   - [ ] Added: soft_skills_status (enum)

9. **Modified departures table:**
   - [ ] Added: ptn_status, ptn_issued_at, ptn_deferred_reason
   - [ ] Added: protector_status, protector_applied_at, protector_done_at
   - [ ] Added: ticket_date, ticket_time, departure_platform, landing_platform
   - [ ] Added: pre_departure_doc_path, pre_departure_video_path
   - [ ] Added: final_departure_status

10. **Modified complaints table:**
    - [ ] Added: current_issue, support_steps_taken, suggestions, conclusion
    - [ ] Added: evidence_type, evidence_path

---

## Phase 2: Models & Relationships Audit

### 2.1 Model Files Verification

**Objective:** Verify all 13 new WASL v3 models exist.

**Commands:**

```bash
# List v3 models
ls -1 app/Models/ | grep -E "(Country|PaymentMethod|Program|ImplementingPartner|Employer|DocumentChecklist|PreDepartureDocument|Course|CandidateCourse|TrainingAssessment|PostDepartureDetail|EmploymentHistory|SuccessStory)"
```

**Expected Models:**

1. `Country.php`
2. `PaymentMethod.php`
3. `Program.php`
4. `ImplementingPartner.php`
5. `Employer.php`
6. `DocumentChecklist.php`
7. `PreDepartureDocument.php`
8. `Course.php`
9. `CandidateCourse.php`
10. `TrainingAssessment.php`
11. `PostDepartureDetail.php`
12. `EmploymentHistory.php`
13. `SuccessStory.php`

**Verification:**

```bash
# Check each model exists
for model in \
  "Country" \
  "PaymentMethod" \
  "Program" \
  "ImplementingPartner" \
  "Employer" \
  "DocumentChecklist" \
  "PreDepartureDocument" \
  "Course" \
  "CandidateCourse" \
  "TrainingAssessment" \
  "PostDepartureDetail" \
  "EmploymentHistory" \
  "SuccessStory"
do
  if [ -f "app/Models/$model.php" ]; then
    echo "✅ $model.php"
  else
    echo "❌ MISSING: $model.php"
  fi
done
```

### 2.2 Model Structure Audit

**For each model, verify:**

**Using Tinker:**

```bash
php artisan tinker
```

**Check Country Model:**

```php
$country = new App\Models\Country;

// Check fillable
$country->getFillable();
// Expected: ['name', 'code', 'iso_code', 'region', 'is_active']

// Check casts
$country->getCasts();
// Expected: is_active => boolean

// Check relationships exist
method_exists($country, 'employers'); // true
method_exists($country, 'candidates'); // true

// Check soft deletes
in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($country)); // true
```

**Check Program Model:**

```php
$program = new App\Models\Program;
$program->getFillable();
// Expected: ['name', 'code', 'description', 'duration_weeks', 'is_active']

method_exists($program, 'candidates'); // true
$program->candidates()->getRelated(); // Should return Candidate model
```

**Check Employer Model:**

```php
$employer = new App\Models\Employer;
$employer->getFillable();
// Expected: permission_number, visa_issuing_company, company_name, country_id,
//           sector, trade, basic_salary, currency, food_by_company,
//           transport_by_company, accommodation_by_company, other_conditions,
//           evidence_path, is_active

$employer->getCasts();
// Expected: basic_salary => decimal:2, is_active => boolean,
//           food_by_company => boolean, transport_by_company => boolean,
//           accommodation_by_company => boolean

method_exists($employer, 'country'); // true (belongsTo)
method_exists($employer, 'candidates'); // true (belongsToMany)
method_exists($employer, 'currentCandidates'); // true (belongsToMany with where)
method_exists($employer, 'createdBy'); // true (belongsTo User)
```

**Check Course Model:**

```php
$course = new App\Models\Course;
$course->getFillable();
// Expected: name, code, description, duration_days, training_type, is_active

$course->getCasts();
// Expected: training_type => TrainingType::class, is_active => boolean

method_exists($course, 'candidates'); // true (belongsToMany via candidate_courses)
```

**Check TrainingAssessment Model:**

```php
$assessment = new App\Models\TrainingAssessment;
$assessment->getFillable();
// Expected: candidate_id, batch_id, assessment_type, assessment_date,
//           score, max_score, passing_score, assessor_id, remarks, evidence_path

$assessment->getCasts();
// Expected: assessment_type => AssessmentType::class,
//           assessment_date => date,
//           score => decimal:2, max_score => decimal:2, passing_score => decimal:2,
//           passed => boolean

method_exists($assessment, 'candidate'); // true (belongsTo)
method_exists($assessment, 'batch'); // true (belongsTo)
method_exists($assessment, 'assessor'); // true (belongsTo User)
```

### 2.3 Relationship Verification

**Objective:** Verify all relationships are bidirectional and correct.

**Commands:**

```bash
php artisan tinker
```

**Test Relationships:**

```php
// Country -> Employers
$country = App\Models\Country::first();
$country->employers; // Should return HasMany relation

// Employer -> Country
$employer = App\Models\Employer::first();
$employer->country; // Should return BelongsTo relation

// Program -> Candidates
$program = App\Models\Program::first();
$program->candidates; // Should return HasMany relation

// Candidate -> Program
$candidate = App\Models\Candidate::first();
$candidate->program; // Should return BelongsTo relation

// Employer -> Candidates (many-to-many)
$employer = App\Models\Employer::first();
$employer->candidates; // Should return BelongsToMany
$employer->currentCandidates; // Should return BelongsToMany with where clause

// Candidate -> Employers (many-to-many)
$candidate = App\Models\Candidate::first();
$candidate->employers; // Should return BelongsToMany

// Course -> Candidates (many-to-many via candidate_courses)
$course = App\Models\Course::first();
$course->candidates; // Should return BelongsToMany

// TrainingAssessment -> Candidate
$assessment = App\Models\TrainingAssessment::first();
$assessment->candidate; // Should return BelongsTo
$assessment->batch; // Should return BelongsTo
$assessment->assessor; // Should return BelongsTo User
```

**Checklist:**

- [ ] All relationships are defined
- [ ] Bidirectional relationships work correctly
- [ ] Many-to-many pivot tables include timestamps
- [ ] Foreign keys properly named
- [ ] Eager loading defined where appropriate
- [ ] No N+1 query issues

---

## Phase 3: Enums Audit

### 3.1 Enum Files Verification

**Objective:** Verify all 14 enums exist.

**Commands:**

```bash
# List all enums
ls -1 app/Enums/

# Count enums
ls -1 app/Enums/ | wc -l
# Expected: 14+
```

**Expected Enums:**

1. `CandidateStatus.php` (updated)
2. `ScreeningStatus.php` (updated)
3. `PlacementInterest.php` (new)
4. `TrainingType.php` (new)
5. `TrainingProgress.php` (new)
6. `AssessmentType.php` (new)
7. `PTNStatus.php` (new)
8. `ProtectorStatus.php` (new)
9. `FlightType.php` (new)
10. `DepartureStatus.php` (new)
11. `VisaApplicationStatus.php` (new)
12. `VisaIssuedStatus.php` (new)
13. `VisaStageResult.php` (new)
14. `EvidenceType.php` (new)

**Verification:**

```bash
# Check each enum exists
for enum in \
  "CandidateStatus" \
  "ScreeningStatus" \
  "PlacementInterest" \
  "TrainingType" \
  "TrainingProgress" \
  "AssessmentType" \
  "PTNStatus" \
  "ProtectorStatus" \
  "FlightType" \
  "DepartureStatus" \
  "VisaApplicationStatus" \
  "VisaIssuedStatus" \
  "VisaStageResult" \
  "EvidenceType"
do
  if [ -f "app/Enums/$enum.php" ]; then
    echo "✅ $enum.php"
  else
    echo "❌ MISSING: $enum.php"
  fi
done
```

### 3.2 Enum Values Verification

**Using Tinker:**

```bash
php artisan tinker
```

**Check CandidateStatus (17 values):**

```php
use App\Enums\CandidateStatus;

$cases = CandidateStatus::cases();
count($cases); // Expected: 17

// Check specific cases exist
CandidateStatus::LISTED->value; // 'listed'
CandidateStatus::PRE_DEPARTURE_DOCS->value; // 'pre_departure_docs'
CandidateStatus::SCREENING->value; // 'screening'
CandidateStatus::SCREENED->value; // 'screened'
CandidateStatus::REGISTERED->value; // 'registered'
CandidateStatus::ALLOCATED->value; // 'allocated'
CandidateStatus::TRAINING->value; // 'training'
CandidateStatus::TRAINING_COMPLETE->value; // 'training_complete'
CandidateStatus::VISA_PROCESS->value; // 'visa_process'
CandidateStatus::VISA_APPROVED->value; // 'visa_approved'
CandidateStatus::DEPARTURE_PROCESSING->value; // 'departure_processing'
CandidateStatus::READY_TO_DEPART->value; // 'ready_to_depart'
CandidateStatus::DEPARTED->value; // 'departed'
CandidateStatus::POST_DEPARTURE->value; // 'post_departure'
// Terminal statuses:
CandidateStatus::DEFERRED->value; // 'deferred'
CandidateStatus::REJECTED->value; // 'rejected'
CandidateStatus::WITHDRAWN->value; // 'withdrawn'
```

**Check ScreeningStatus:**

```php
use App\Enums\ScreeningStatus;

$cases = ScreeningStatus::cases();
count($cases); // Expected: 3

ScreeningStatus::SCREENED->value; // 'screened'
ScreeningStatus::PENDING->value; // 'pending'
ScreeningStatus::DEFERRED->value; // 'deferred'
```

**Check PlacementInterest:**

```php
use App\Enums\PlacementInterest;

PlacementInterest::LOCAL->value; // 'local'
PlacementInterest::INTERNATIONAL->value; // 'international'
```

**Check TrainingType:**

```php
use App\Enums\TrainingType;

TrainingType::TECHNICAL->value; // 'technical'
TrainingType::SOFT_SKILLS->value; // 'soft_skills'
TrainingType::BOTH->value; // 'both'
```

**Check TrainingProgress:**

```php
use App\Enums\TrainingProgress;

TrainingProgress::NOT_STARTED->value; // 'not_started'
TrainingProgress::IN_PROGRESS->value; // 'in_progress'
TrainingProgress::COMPLETED->value; // 'completed'
```

**Check AssessmentType:**

```php
use App\Enums\AssessmentType;

AssessmentType::INTERIM->value; // 'interim'
AssessmentType::FINAL->value; // 'final'
```

**Check PTNStatus (6 values):**

```php
use App\Enums\PTNStatus;

PTNStatus::NOT_APPLIED->value;
PTNStatus::ISSUED->value;
PTNStatus::DONE->value;
PTNStatus::PENDING->value;
PTNStatus::NOT_ISSUED->value;
PTNStatus::REFUSED->value;
```

**Check EvidenceType:**

```php
use App\Enums\EvidenceType;

EvidenceType::AUDIO->value; // 'audio'
EvidenceType::VIDEO->value; // 'video'
EvidenceType::WRITTEN->value; // 'written'
EvidenceType::SCREENSHOT->value; // 'screenshot'
EvidenceType::DOCUMENT->value; // 'document'
EvidenceType::OTHER->value; // 'other'
```

**Checklist:**

- [ ] All 14 enums exist
- [ ] All enums are backed by string
- [ ] All enum cases have correct values
- [ ] No typos in enum values
- [ ] Enums properly used in model casts

---

## Phase 4: Controllers Audit

### 4.1 Controller Files Verification

**Objective:** Verify all 7 new WASL v3 controllers exist.

**Commands:**

```bash
# List v3 controllers
ls -1 app/Http/Controllers/ | grep -E "(Program|ImplementingPartner|Employer|Course|DocumentChecklist|PreDepartureDocument|SuccessStory|TrainingAssessment)"
```

**Expected Controllers:**

1. `ProgramController.php`
2. `ImplementingPartnerController.php`
3. `EmployerController.php`
4. `CourseController.php`
5. `DocumentChecklistController.php`
6. `PreDepartureDocumentController.php`
7. `SuccessStoryController.php`

**Verification:**

```bash
for controller in \
  "ProgramController" \
  "ImplementingPartnerController" \
  "EmployerController" \
  "CourseController" \
  "DocumentChecklistController" \
  "PreDepartureDocumentController" \
  "SuccessStoryController"
do
  if [ -f "app/Http/Controllers/$controller.php" ]; then
    echo "✅ $controller.php"
  else
    echo "❌ MISSING: $controller.php"
  fi
done
```

### 4.2 Controller Methods Verification

**For each controller, verify standard CRUD methods exist:**

```bash
# Check ProgramController methods
grep -E "public function (index|create|store|show|edit|update|destroy)" app/Http/Controllers/ProgramController.php

# Expected output:
# public function index()
# public function create()
# public function store(StoreProgramRequest $request)
# public function show(Program $program)
# public function edit(Program $program)
# public function update(UpdateProgramRequest $request, Program $program)
# public function destroy(Program $program)
```

**Check each controller:**

```bash
for controller in \
  "ProgramController" \
  "ImplementingPartnerController" \
  "EmployerController" \
  "CourseController" \
  "DocumentChecklistController" \
  "SuccessStoryController"
do
  echo "=== $controller ==="
  grep -E "public function (index|create|store|show|edit|update|destroy)" "app/Http/Controllers/$controller.php"
  echo ""
done
```

**Checklist:**

- [ ] All 7 controllers exist
- [ ] Each controller has all CRUD methods (index, create, store, show, edit, update, destroy)
- [ ] Controllers use Form Request validation
- [ ] Controllers use Policy authorization
- [ ] Controllers use Route Model Binding
- [ ] Controllers log activity (Spatie)
- [ ] Controllers handle file uploads where applicable
- [ ] Controllers return appropriate responses (view, redirect, JSON)

### 4.3 Special Controller Methods

**EmployerController:**

```bash
grep -E "public function (assignCandidates|downloadEvidence|currentCandidates)" app/Http/Controllers/EmployerController.php
```

Expected:
- [ ] `assignCandidates()` - Assign candidates to employer
- [ ] `downloadEvidence()` - Download evidence document
- [ ] `currentCandidates()` - View current assigned candidates

**PreDepartureDocumentController:**

```bash
grep -E "public function (bulkUpload|verify|download|statusReport)" app/Http/Controllers/PreDepartureDocumentController.php
```

Expected:
- [ ] `bulkUpload()` - Bulk document upload
- [ ] `verify()` - Verify document
- [ ] `download()` - Download document
- [ ] `statusReport()` - Document status report

**SuccessStoryController:**

```bash
grep -E "public function (publish|unpublish|feature|unfeature)" app/Http/Controllers/SuccessStoryController.php
```

Expected:
- [ ] `publish()` / `unpublish()` - Toggle publication
- [ ] `feature()` / `unfeature()` - Toggle featured

---

## Phase 5: Services & Business Logic Audit

### 5.1 Service Files Verification

**Objective:** Verify all 5 WASL v3 services exist.

**Commands:**

```bash
ls -1 app/Services/ | grep -E "(AutoBatch|Allocation|TrainingAssessment|Screening|Registration)"
```

**Expected Services:**

1. `AutoBatchService.php` (NEW)
2. `AllocationService.php` (NEW)
3. `TrainingAssessmentService.php` (NEW)
4. `ScreeningService.php` (UPDATED)
5. `RegistrationService.php` (UPDATED)

**Verification:**

```bash
for service in \
  "AutoBatchService" \
  "AllocationService" \
  "TrainingAssessmentService" \
  "ScreeningService" \
  "RegistrationService"
do
  if [ -f "app/Services/$service.php" ]; then
    echo "✅ $service.php"
  else
    echo "❌ MISSING: $service.php"
  fi
done
```

### 5.2 Service Methods Verification

**AutoBatchService:**

```bash
grep -E "public function" app/Services/AutoBatchService.php
```

Expected methods:
- [ ] `assignOrCreateBatch(Candidate $candidate)`
- [ ] `generateBatchNumber(Campus $campus, Program $program, Trade $trade)`
- [ ] `generateAllocatedNumber(Batch $batch, int $position)`
- [ ] `getOrCreateBatch(Campus $campus, Program $program, Trade $trade)`

**AllocationService:**

```bash
grep -E "public function" app/Services/AllocationService.php
```

Expected methods:
- [ ] `allocateCandidate(Candidate $candidate, array $allocationData)`
- [ ] `validateAllocation(array $allocationData)`
- [ ] `bulkAllocate(array $candidateIds, array $allocationData)`
- [ ] `getAllocationSummary()`

**TrainingAssessmentService:**

```bash
grep -E "public function" app/Services/TrainingAssessmentService.php
```

Expected methods:
- [ ] `recordAssessment(Candidate $candidate, array $assessmentData)`
- [ ] `checkTrainingCompletion(Candidate $candidate)`
- [ ] `getBatchStatistics(Batch $batch)`
- [ ] `getAssessmentResults(Candidate $candidate)`

### 5.3 Service Business Logic Audit

**Check AutoBatchService logic:**

```bash
php artisan tinker
```

```php
$service = app(\App\Services\AutoBatchService::class);

// Test batch number generation format
$campus = App\Models\Campus::where('code', 'ISB')->first();
$program = App\Models\Program::where('code', 'TEC')->first();
$trade = App\Models\Trade::where('code', 'WLD')->first();

$batchNumber = $service->generateBatchNumber($campus, $program, $trade);
// Expected format: ISB-TEC-WLD-2026-0001

// Verify format matches: {CAMPUS}-{PROGRAM}-{TRADE}-{YEAR}-{SEQUENCE}
preg_match('/^[A-Z]+-[A-Z]+-[A-Z]+-\d{4}-\d{4}$/', $batchNumber); // Should return 1
```

**Check AllocationService transactions:**

```bash
# Verify service uses DB transactions
grep -n "DB::beginTransaction" app/Services/AllocationService.php
grep -n "DB::commit" app/Services/AllocationService.php
grep -n "DB::rollBack" app/Services/AllocationService.php
```

Expected:
- [ ] Uses `DB::beginTransaction()`
- [ ] Commits on success with `DB::commit()`
- [ ] Rolls back on error with `DB::rollBack()`

**Checklist:**

- [ ] All 5 services exist
- [ ] Services use dependency injection
- [ ] Services properly documented (docblocks)
- [ ] Services use database transactions where needed
- [ ] Services log activities
- [ ] Services throw appropriate exceptions
- [ ] No hardcoded values (use config)

---

## Phase 6: Routes Audit

### 6.1 Web Routes Verification

**Objective:** Verify all WASL v3 routes are registered.

**Commands:**

```bash
# List all routes
php artisan route:list

# Filter v3 routes
php artisan route:list | grep -E "(program|implementing-partner|employer|course|document-checklist|pre-departure-document|success-story|training-assessment)"

# Count v3 routes
php artisan route:list | grep -E "(program|implementing-partner|employer)" | wc -l
```

**Expected Route Groups:**

1. **Programs** - 7 routes (index, create, store, show, edit, update, destroy)
2. **Implementing Partners** - 7 routes
3. **Employers** - 10 routes (CRUD + assignCandidates, downloadEvidence, currentCandidates)
4. **Courses** - 7 routes
5. **Document Checklists** - 8 routes (CRUD + reorder)
6. **Pre-Departure Documents** - 10 routes (CRUD + bulkUpload, verify, download, statusReport)
7. **Success Stories** - 9 routes (CRUD + publish, unpublish, feature, unfeature)

**Verification:**

```bash
# Check Programs routes
php artisan route:list --path=programs

# Check Employers routes
php artisan route:list --path=employers

# Check Success Stories routes
php artisan route:list --path=success-stories
```

**Checklist:**

- [ ] All resource routes registered
- [ ] Custom routes for special actions registered
- [ ] Routes use correct HTTP methods (GET, POST, PUT, DELETE)
- [ ] Routes have middleware (auth, permission)
- [ ] Routes use Route Model Binding
- [ ] Route names follow convention (resource.action)

### 6.2 API Routes Verification

**Commands:**

```bash
# List API routes
php artisan route:list --path=api

# Check v3 API routes
php artisan route:list --path=api/v1
```

**Expected API Routes:**

- [ ] `GET /api/v1/employers` - List employers
- [ ] `GET /api/v1/employers/{id}` - Get single employer
- [ ] `POST /api/v1/employers` - Create employer
- [ ] `PUT /api/v1/employers/{id}` - Update employer
- [ ] `DELETE /api/v1/employers/{id}` - Delete employer

**Checklist:**

- [ ] API routes versioned (/api/v1/)
- [ ] API routes use correct HTTP methods
- [ ] API routes return JSON responses
- [ ] API routes have throttling middleware
- [ ] API routes have authentication

---

## Phase 7: Views Audit

### 7.1 View Files Verification

**Objective:** Verify all WASL v3 view files exist.

**Commands:**

```bash
# List all v3 views
find resources/views -type f -name "*.blade.php" | grep -E "(employer|success-stor|pre-departure|training-assessment)"

# Check employers views
ls -1 resources/views/admin/employers/
```

**Expected View Files:**

**Employers:**
1. `resources/views/admin/employers/index.blade.php`
2. `resources/views/admin/employers/create.blade.php`
3. `resources/views/admin/employers/edit.blade.php`
4. `resources/views/admin/employers/show.blade.php`

**Success Stories:**
5. `resources/views/success-stories/index.blade.php`
6. `resources/views/success-stories/create.blade.php`
7. `resources/views/success-stories/edit.blade.php`
8. `resources/views/success-stories/show.blade.php`
9. `resources/views/success-stories/form.blade.php`

**Enhanced Forms:**
10. `resources/views/screenings/form.blade.php` (updated)
11. `resources/views/registration/form.blade.php` (updated)
12. `resources/views/departures/form.blade.php` (updated)
13. `resources/views/post-departure/form.blade.php` (updated)
14. `resources/views/complaints/form.blade.php` (updated)

**Verification:**

```bash
# Check each view file
for view in \
  "admin/employers/index.blade.php" \
  "admin/employers/create.blade.php" \
  "admin/employers/edit.blade.php" \
  "admin/employers/show.blade.php" \
  "success-stories/index.blade.php" \
  "success-stories/create.blade.php" \
  "success-stories/edit.blade.php" \
  "success-stories/show.blade.php" \
  "success-stories/form.blade.php" \
  "screenings/form.blade.php" \
  "registration/form.blade.php" \
  "departures/form.blade.php" \
  "post-departure/form.blade.php" \
  "complaints/form.blade.php"
do
  if [ -f "resources/views/$view" ]; then
    echo "✅ $view"
  else
    echo "❌ MISSING: $view"
  fi
done
```

### 7.2 View Content Audit

**Check for v3-specific fields in views:**

**Registration form:**

```bash
# Check for allocation fields
grep -n "program_id" resources/views/registration/form.blade.php
grep -n "implementing_partner_id" resources/views/registration/form.blade.php
grep -n "course" resources/views/registration/form.blade.php
```

Expected:
- [ ] Program dropdown exists
- [ ] Implementing Partner dropdown exists
- [ ] Course assignment section exists

**Screening form:**

```bash
# Check for new screening fields
grep -n "consent_for_work" resources/views/screenings/form.blade.php
grep -n "placement_interest" resources/views/screenings/form.blade.php
grep -n "target_country" resources/views/screenings/form.blade.php
```

Expected:
- [ ] Consent checkbox exists
- [ ] Placement interest radio buttons exist
- [ ] Target country dropdown exists (conditional)

**Departure form:**

```bash
# Check for enhanced departure fields
grep -n "ptn_status" resources/views/departures/form.blade.php
grep -n "protector_status" resources/views/departures/form.blade.php
grep -n "ticket_date" resources/views/departures/form.blade.php
grep -n "pre_departure_doc_path" resources/views/departures/form.blade.php
grep -n "pre_departure_video_path" resources/views/departures/form.blade.php
```

Expected:
- [ ] PTN status dropdown exists
- [ ] Protector status dropdown exists
- [ ] Ticket details fields exist
- [ ] Pre-departure document upload exists
- [ ] Pre-departure video upload exists

**Checklist:**

- [ ] All view files exist
- [ ] Views extend correct layout
- [ ] Views use @csrf tokens
- [ ] Views use @method for PUT/DELETE
- [ ] Views display validation errors
- [ ] Views use old() for form repopulation
- [ ] Views have proper authorization checks (@can)
- [ ] No hardcoded values in views
- [ ] JavaScript properly included

---

## Phase 8: Form Requests Audit

### 8.1 Form Request Files Verification

**Objective:** Verify all 13 WASL v3 Form Requests exist.

**Commands:**

```bash
# List Form Requests
ls -1 app/Http/Requests/ | grep -E "(Program|ImplementingPartner|Employer|Course|DocumentChecklist|PreDepartureDocument|SuccessStory|TrainingAssessment)"
```

**Expected Form Requests:**

1. `StoreProgramRequest.php`
2. `UpdateProgramRequest.php`
3. `StoreImplementingPartnerRequest.php`
4. `UpdateImplementingPartnerRequest.php`
5. `StoreEmployerRequest.php`
6. `UpdateEmployerRequest.php`
7. `StoreCourseRequest.php`
8. `UpdateCourseRequest.php`
9. `StoreDocumentChecklistRequest.php`
10. `UpdateDocumentChecklistRequest.php`
11. `StoreSuccessStoryRequest.php`
12. `UpdateSuccessStoryRequest.php`
13. `StoreTrainingAssessmentRequest.php`

**Verification:**

```bash
for request in \
  "StoreProgramRequest" \
  "UpdateProgramRequest" \
  "StoreImplementingPartnerRequest" \
  "UpdateImplementingPartnerRequest" \
  "StoreEmployerRequest" \
  "UpdateEmployerRequest" \
  "StoreCourseRequest" \
  "UpdateCourseRequest" \
  "StoreDocumentChecklistRequest" \
  "UpdateDocumentChecklistRequest" \
  "StoreSuccessStoryRequest" \
  "UpdateSuccessStoryRequest" \
  "StoreTrainingAssessmentRequest"
do
  if [ -f "app/Http/Requests/$request.php" ]; then
    echo "✅ $request.php"
  else
    echo "❌ MISSING: $request.php"
  fi
done
```

### 8.2 Validation Rules Audit

**Check validation rules in Form Requests:**

**StoreProgramRequest:**

```bash
grep -A 20 "public function rules()" app/Http/Requests/StoreProgramRequest.php
```

Expected rules:
- [ ] `name` - required, string, max:255, unique
- [ ] `code` - required, string, max:10, unique
- [ ] `description` - nullable, string
- [ ] `duration_weeks` - required, integer, min:1, max:52
- [ ] `is_active` - boolean

**StoreEmployerRequest:**

```bash
grep -A 30 "public function rules()" app/Http/Requests/StoreEmployerRequest.php
```

Expected rules:
- [ ] `permission_number` - required, string, max:255, unique
- [ ] `visa_issuing_company` - required, string, max:255
- [ ] `country_id` - required, exists:countries,id
- [ ] `basic_salary` - required, numeric, min:0
- [ ] `currency` - required, string, max:10
- [ ] `food_by_company` - boolean
- [ ] `transport_by_company` - boolean
- [ ] `accommodation_by_company` - boolean
- [ ] `evidence_document` - nullable, file, mimes:pdf,jpg,jpeg,png, max:5120

**Checklist:**

- [ ] All Form Requests exist
- [ ] Each has authorize() method
- [ ] Each has rules() method
- [ ] Validation rules appropriate for field types
- [ ] Required fields marked as required
- [ ] Unique constraints on unique fields
- [ ] Foreign key validation (exists:)
- [ ] File upload validation where applicable
- [ ] Custom error messages defined

---

## Phase 9: Policies Audit

### 9.1 Policy Files Verification

**Objective:** Verify all 7 WASL v3 policies exist.

**Commands:**

```bash
# List policies
ls -1 app/Policies/ | grep -E "(Program|ImplementingPartner|Employer|Course|DocumentChecklist|SuccessStory)"
```

**Expected Policies:**

1. `ProgramPolicy.php`
2. `ImplementingPartnerPolicy.php`
3. `EmployerPolicy.php`
4. `CoursePolicy.php`
5. `DocumentChecklistPolicy.php`
6. `SuccessStoryPolicy.php`
7. `TrainingAssessmentPolicy.php`

**Verification:**

```bash
for policy in \
  "ProgramPolicy" \
  "ImplementingPartnerPolicy" \
  "EmployerPolicy" \
  "CoursePolicy" \
  "DocumentChecklistPolicy" \
  "SuccessStoryPolicy" \
  "TrainingAssessmentPolicy"
do
  if [ -f "app/Policies/$policy.php" ]; then
    echo "✅ $policy.php"
  else
    echo "❌ MISSING: $policy.php"
  fi
done
```

### 9.2 Policy Methods Verification

**Check standard policy methods:**

```bash
# Check ProgramPolicy methods
grep -E "public function (viewAny|view|create|update|delete|restore|forceDelete)" app/Policies/ProgramPolicy.php
```

Expected methods for each policy:
- [ ] `viewAny(User $user)` - View list
- [ ] `view(User $user, Model $model)` - View single
- [ ] `create(User $user)` - Create new
- [ ] `update(User $user, Model $model)` - Update existing
- [ ] `delete(User $user, Model $model)` - Delete
- [ ] `restore(User $user, Model $model)` - Restore soft-deleted (optional)
- [ ] `forceDelete(User $user, Model $model)` - Permanent delete (optional)

**Checklist:**

- [ ] All 7 policies exist
- [ ] Each policy registered in AuthServiceProvider
- [ ] Standard CRUD methods defined
- [ ] Permission checks use Spatie permission system
- [ ] Policies return boolean or Response

---

## Phase 10: Configuration Audit

### 10.1 WASL Configuration File

**Objective:** Verify config/wasl.php exists and is complete.

**Commands:**

```bash
# Check file exists
ls -la config/wasl.php

# Check configuration structure
cat config/wasl.php
```

**Expected Configuration Sections:**

1. **Batch Configuration**
   - [ ] `default_size` - Default batch size (20/25/30)
   - [ ] `available_sizes` - Array of allowed sizes
   - [ ] `number_format` - Batch number format string
   - [ ] `sequence_padding` - Sequence number padding (4)

2. **Assessment Configuration**
   - [ ] `passing_percentage` - Default passing percentage (70)
   - [ ] `types` - Array of assessment types
   - [ ] `require_both` - Require both interim and final (true)

3. **Screening Configuration**
   - [ ] `require_consent` - Require consent for work (true)
   - [ ] `allow_international` - Allow international placement (true)
   - [ ] `enforce_gate` - Enforce screening gate (true)

4. **Document Upload Configuration**
   - [ ] `max_size` - Array of max file sizes by type
   - [ ] `allowed_types` - Array of allowed file types by category
   - [ ] `mandatory_documents` - Array of mandatory document types

5. **Employer Configuration**
   - [ ] `require_evidence` - Require evidence document (true)
   - [ ] `default_currency` - Default currency (SAR)
   - [ ] `currencies` - Array of available currencies

6. **Success Stories Configuration**
   - [ ] `auto_publish` - Auto-publish stories (false)
   - [ ] `evidence_types` - Array of evidence types
   - [ ] `video_processing` - Video processing settings

7. **Complaint Configuration**
   - [ ] `sla_hours` - Array of SLA hours by priority
   - [ ] `sla_notifications` - Send SLA notifications (true)

8. **Post-Departure Configuration**
   - [ ] `max_company_switches` - Max switches to track (2)
   - [ ] `required_fields` - Array of required fields

**Verification:**

```bash
php artisan tinker
```

```php
// Test configuration loading
config('wasl.batch.default_size'); // Should return 25 (or configured value)
config('wasl.assessment.passing_percentage'); // Should return 70
config('wasl.documents.max_size.video'); // Should return 51200 (50MB)
config('wasl.employer.currencies'); // Should return array
```

**Checklist:**

- [ ] config/wasl.php exists
- [ ] All configuration sections present
- [ ] Configuration values are sensible
- [ ] No hardcoded values in code (use config())
- [ ] Environment variables used where appropriate

---

## Phase 11: Tests Audit

### 11.1 Test Files Verification

**Objective:** Verify all 86+ WASL v3 tests exist and pass.

**Commands:**

```bash
# List test files
find tests -name "*Test.php" | grep -E "(Employer|Enum|AutoBatch|Allocation|TrainingAssessment|WASLv3Workflow)"

# Count test files
find tests -name "*Test.php" | grep -E "(Employer|Enum|AutoBatch|Allocation|WASLv3)" | wc -l
```

**Expected Test Files:**

1. `tests/Unit/EmployerModelTest.php` (18 tests)
2. `tests/Unit/WASLv3EnumsTest.php` (27 tests)
3. `tests/Unit/AutoBatchServiceTest.php` (15 tests)
4. `tests/Unit/AllocationServiceTest.php` (15 tests)
5. `tests/Integration/WASLv3WorkflowIntegrationTest.php` (6 tests)

**Verification:**

```bash
for test in \
  "Unit/EmployerModelTest" \
  "Unit/WASLv3EnumsTest" \
  "Unit/AutoBatchServiceTest" \
  "Unit/AllocationServiceTest" \
  "Integration/WASLv3WorkflowIntegrationTest"
do
  if [ -f "tests/$test.php" ]; then
    echo "✅ $test.php"
  else
    echo "❌ MISSING: $test.php"
  fi
done
```

### 11.2 Run All Tests

**Run complete test suite:**

```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run only v3 tests
php artisan test --testsuite=Unit --filter=WASL
php artisan test --testsuite=Integration --filter=WASL

# Run specific test files
php artisan test tests/Unit/EmployerModelTest.php
php artisan test tests/Unit/WASLv3EnumsTest.php
php artisan test tests/Unit/AutoBatchServiceTest.php
php artisan test tests/Unit/AllocationServiceTest.php
php artisan test tests/Integration/WASLv3WorkflowIntegrationTest.php
```

**Expected Results:**

```
Tests:    86 passed (268 assertions)
Duration: XX.XXs
```

**Checklist:**

- [ ] All 5 test files exist
- [ ] Total test count: 86+
- [ ] All tests pass
- [ ] No skipped tests
- [ ] No warnings
- [ ] Test coverage > 80%

### 11.3 Individual Test Verification

**EmployerModelTest (18 tests):**

```bash
php artisan test tests/Unit/EmployerModelTest.php --filter=it_can_be_created_with_factory
php artisan test tests/Unit/EmployerModelTest.php --filter=it_belongs_to_a_country
php artisan test tests/Unit/EmployerModelTest.php --filter=it_has_many_candidates_through_pivot
php artisan test tests/Unit/EmployerModelTest.php --filter=it_can_get_current_candidates_only
```

**WASLv3EnumsTest (27 tests):**

```bash
php artisan test tests/Unit/WASLv3EnumsTest.php --filter=candidate_status_enum_has_17_statuses
php artisan test tests/Unit/WASLv3EnumsTest.php --filter=screening_status_enum_has_correct_values
php artisan test tests/Unit/WASLv3EnumsTest.php --filter=training_progress_enum_works_correctly
```

**AutoBatchServiceTest (15 tests):**

```bash
php artisan test tests/Unit/AutoBatchServiceTest.php --filter=it_generates_correct_batch_number_format
php artisan test tests/Unit/AutoBatchServiceTest.php --filter=it_handles_batch_overflow_correctly
php artisan test tests/Unit/AutoBatchServiceTest.php --filter=it_generates_sequential_allocated_numbers
```

**WASLv3WorkflowIntegrationTest (6 tests):**

```bash
php artisan test tests/Integration/WASLv3WorkflowIntegrationTest.php --filter=complete_wasl_v3_candidate_journey
php artisan test tests/Integration/WASLv3WorkflowIntegrationTest.php --filter=screening_gate_prevents_unscreened_registration
php artisan test tests/Integration/WASLv3WorkflowIntegrationTest.php --filter=auto_batch_creates_new_batch_when_full
```

---

## Phase 12: Security Audit

### 12.1 Authentication & Authorization

**Check middleware on routes:**

```bash
# Check auth middleware
php artisan route:list | grep "auth" | wc -l

# Check permission middleware
php artisan route:list | grep "permission" | wc -l

# Check specific v3 routes have protection
php artisan route:list --path=employers | grep -E "(auth|permission)"
php artisan route:list --path=success-stories | grep -E "(auth|permission)"
```

**Checklist:**

- [ ] All admin routes protected with auth middleware
- [ ] Sensitive routes have permission middleware
- [ ] API routes have throttle middleware
- [ ] CSRF protection enabled
- [ ] XSS protection in place

### 12.2 File Upload Security

**Check file upload validation:**

```bash
# Check file validation in Form Requests
grep -r "mimes:" app/Http/Requests/ | grep -E "(Employer|SuccessStory|PreDeparture)"

# Check max file sizes
grep -r "max:" app/Http/Requests/ | grep -E "file|document|video|audio"
```

**Checklist:**

- [ ] File types restricted (mimes validation)
- [ ] File sizes limited (max validation)
- [ ] Files stored outside public directory
- [ ] File downloads use signed URLs or authorization
- [ ] No executable files allowed

### 12.3 SQL Injection Protection

**Check for raw queries:**

```bash
# Find any raw SQL queries
grep -r "DB::raw" app/ | grep -v ".blade.php"
grep -r "DB::statement" app/
grep -r "\$" app/Models/ | grep "where" | grep "'"
```

**Checklist:**

- [ ] Using Eloquent ORM (prevents SQL injection)
- [ ] No raw SQL with user input
- [ ] Parameterized queries used
- [ ] Input validation on all user input

### 12.4 Mass Assignment Protection

**Check fillable/guarded in models:**

```bash
# Check all models have $fillable or $guarded
for model in app/Models/*.php; do
  if ! grep -q "protected \$fillable\|protected \$guarded" "$model"; then
    echo "⚠️  No mass assignment protection: $model"
  fi
done
```

**Checklist:**

- [ ] All models define $fillable or $guarded
- [ ] Sensitive fields not in $fillable
- [ ] No models using $guarded = []

---

## Phase 13: Code Quality Audit

### 13.1 Code Standards

**Run PHP CodeSniffer (if installed):**

```bash
# Check PSR-12 compliance
./vendor/bin/phpcs --standard=PSR12 app/

# Check specific v3 files
./vendor/bin/phpcs --standard=PSR12 app/Models/Employer.php
./vendor/bin/phpcs --standard=PSR12 app/Services/AutoBatchService.php
```

**Run PHPStan (if installed):**

```bash
# Static analysis
./vendor/bin/phpstan analyse app/

# Check specific directories
./vendor/bin/phpstan analyse app/Models/
./vendor/bin/phpstan analyse app/Services/
```

### 13.2 Hardcoded Values Audit

**Check for hardcoded values:**

```bash
# Check for hardcoded numbers (potential magic numbers)
grep -rn "[^a-zA-Z_]20[^0-9]" app/Services/ | grep -v "2026"
grep -rn "[^a-zA-Z_]25[^0-9]" app/Services/
grep -rn "[^a-zA-Z_]30[^0-9]" app/Services/

# Should use config('wasl.batch.default_size') instead

# Check for hardcoded URLs
grep -rn "http://" app/ | grep -v "example"
grep -rn "https://" app/ | grep -v "example"

# Check for hardcoded paths
grep -rn "/var/www" app/
grep -rn "/home/" app/
```

**Checklist:**

- [ ] No magic numbers (use constants or config)
- [ ] No hardcoded URLs (use config or env)
- [ ] No hardcoded paths
- [ ] No hardcoded credentials
- [ ] Use config() for configurable values

### 13.3 Code Duplication

**Check for duplicated code:**

```bash
# Find similar method names (potential duplication)
grep -rh "public function" app/Services/ | sort | uniq -d

# Check for duplicate validation rules
grep -A 5 "public function rules()" app/Http/Requests/*.php | grep -E "required|string|max:" | sort | uniq -c | sort -rn | head -20
```

**Checklist:**

- [ ] No significant code duplication
- [ ] Common logic extracted to services/helpers
- [ ] Validation rules not duplicated excessively

---

## Phase 14: Documentation Cross-Reference

### 14.1 API Documentation Accuracy

**Verify API endpoints documented match actual routes:**

```bash
# Get all API routes
php artisan route:list --path=api/v1 > /tmp/api_routes.txt

# Compare with documented endpoints in docs/v3/API_DOCUMENTATION.md
# Manual verification required
```

**Checklist:**

- [ ] All API endpoints in code are documented
- [ ] Request/response examples accurate
- [ ] Validation rules match Form Requests
- [ ] No undocumented endpoints

### 14.2 User Manual Accuracy

**Verify features documented match implementation:**

**Check Pre-Departure Documents workflow:**

```bash
# Verify mandatory documents in code match manual
grep "mandatory_documents" config/wasl.php
# Compare with docs/v3/USER_MANUAL.md section on Pre-Departure Documents
```

**Check Auto-Batch feature:**

```bash
# Verify batch number format in code matches manual
grep "number_format" config/wasl.php
# Compare with docs/v3/USER_MANUAL.md section on Registration & Allocation
```

**Checklist:**

- [ ] All features in code are documented in User Manual
- [ ] Step-by-step instructions accurate
- [ ] Screenshots/examples match current UI
- [ ] No documented features missing from code

### 14.3 Admin Guide Accuracy

**Verify configuration documented matches actual config:**

```bash
# Compare config/wasl.php with docs/v3/ADMIN_GUIDE.md
diff <(grep -E "^    '" config/wasl.php | sort) <(grep "^\- " docs/v3/ADMIN_GUIDE.md | grep "wasl\." | sort)
```

**Checklist:**

- [ ] All configuration options documented
- [ ] Installation steps accurate
- [ ] Commands correct and tested
- [ ] Troubleshooting scenarios valid

---

## Phase 15: Integration Testing

### 15.1 End-to-End Workflow Testing

**Test complete candidate journey:**

```bash
php artisan tinker
```

```php
// Create test candidate
$candidate = \App\Models\Candidate::factory()->create([
    'status' => \App\Enums\CandidateStatus::LISTED,
]);

// Step 1: Pre-Departure Documents
$document = \App\Models\PreDepartureDocument::create([
    'candidate_id' => $candidate->id,
    'document_type' => 'CNIC',
    'file_path' => 'test/cnic.pdf',
    'uploaded_at' => now(),
    'is_mandatory' => true,
]);

// Step 2: Initial Screening
$screening = \App\Models\CandidateScreening::create([
    'candidate_id' => $candidate->id,
    'consent_for_work' => true,
    'placement_interest' => 'international',
    'screening_status' => 'screened',
    'reviewed_at' => now(),
    'reviewer_id' => 1,
]);

$candidate->update(['status' => \App\Enums\CandidateStatus::SCREENED]);

// Step 3: Registration with Auto-Batch
$registrationService = app(\App\Services\RegistrationService::class);
$campus = \App\Models\Campus::first();
$program = \App\Models\Program::first();
$trade = \App\Models\Trade::first();
$partner = \App\Models\ImplementingPartner::first();

$result = $registrationService->registerCandidateWithAllocation($candidate, [
    'campus_id' => $campus->id,
    'program_id' => $program->id,
    'trade_id' => $trade->id,
    'implementing_partner_id' => $partner->id,
]);

// Verify batch created
$result['success']; // Should be true
$result['batch']; // Should be Batch instance
$candidate->refresh();
$candidate->allocated_number; // Should be set
$candidate->batch_id; // Should be set

// Step 4: Training Assessments
$assessmentService = app(\App\Services\TrainingAssessmentService::class);

$assessmentService->recordAssessment($candidate, [
    'assessment_type' => 'interim',
    'assessment_date' => now(),
    'score' => 85,
    'max_score' => 100,
    'passing_score' => 70,
    'assessor_id' => 1,
]);

$assessmentService->recordAssessment($candidate, [
    'assessment_type' => 'final',
    'assessment_date' => now()->addDays(30),
    'score' => 88,
    'max_score' => 100,
    'passing_score' => 70,
    'assessor_id' => 1,
]);

// Check training completion
$assessmentService->checkTrainingCompletion($candidate);

// Step 5: Employer Assignment
$employer = \App\Models\Employer::first();
$employer->candidates()->attach($candidate->id, [
    'is_current' => true,
    'assigned_at' => now(),
    'assigned_by' => 1,
]);

// Step 6: Success Story
$successStory = \App\Models\SuccessStory::create([
    'candidate_id' => $candidate->id,
    'story_text' => 'Test success story',
    'evidence_type' => 'written',
    'is_published' => true,
    'recorded_at' => now(),
    'recorded_by' => 1,
]);

echo "✅ Complete workflow test passed!";
```

**Checklist:**

- [ ] Complete workflow executes without errors
- [ ] Screening gate enforced
- [ ] Auto-batch created correctly
- [ ] Assessments recorded
- [ ] Training completion detected
- [ ] Employer assignment works
- [ ] Success story created

### 15.2 Performance Testing

**Test database query performance:**

```bash
php artisan tinker
```

```php
// Enable query log
DB::enableQueryLog();

// Test expensive operation (e.g., candidate list with relationships)
$candidates = \App\Models\Candidate::with([
    'campus', 'program', 'implementingPartner', 'batch', 'employers'
])->paginate(50);

// Check query count
$queries = DB::getQueryLog();
count($queries); // Should be reasonable (< 10 for this operation)

// Check for N+1 queries
// Should not see repeated queries for same table
```

**Checklist:**

- [ ] No N+1 query issues
- [ ] Eager loading used appropriately
- [ ] Indexes on foreign keys
- [ ] Query count reasonable for operations

---

## Audit Checklist Summary

### Database (Phase 1)
- [ ] 20 migrations exist
- [ ] All migrations have up() and down()
- [ ] Foreign keys properly defined
- [ ] Migrations can be rolled back

### Models & Relationships (Phase 2)
- [ ] 13 new models exist
- [ ] All relationships defined
- [ ] Bidirectional relationships work
- [ ] Fillable/casts properly set
- [ ] Soft deletes where needed

### Enums (Phase 3)
- [ ] 14 enums exist
- [ ] All enum values correct
- [ ] Enums properly used in casts

### Controllers (Phase 4)
- [ ] 7 new controllers exist
- [ ] All CRUD methods implemented
- [ ] Form Request validation used
- [ ] Policy authorization used
- [ ] Special methods implemented

### Services (Phase 5)
- [ ] 5 services exist
- [ ] Business logic correct
- [ ] Database transactions used
- [ ] No hardcoded values

### Routes (Phase 6)
- [ ] All web routes registered
- [ ] All API routes registered
- [ ] Middleware applied correctly
- [ ] Route names follow convention

### Views (Phase 7)
- [ ] All view files exist
- [ ] Forms have v3 fields
- [ ] CSRF protection present
- [ ] Validation errors displayed

### Form Requests (Phase 8)
- [ ] 13 Form Requests exist
- [ ] Validation rules appropriate
- [ ] File upload validation correct

### Policies (Phase 9)
- [ ] 7 policies exist
- [ ] All CRUD methods defined
- [ ] Registered in AuthServiceProvider

### Configuration (Phase 10)
- [ ] config/wasl.php exists
- [ ] All sections present
- [ ] Values sensible

### Tests (Phase 11)
- [ ] 86+ tests exist
- [ ] All tests pass
- [ ] Coverage > 80%

### Security (Phase 12)
- [ ] Authentication on all routes
- [ ] Authorization policies enforced
- [ ] File uploads secured
- [ ] SQL injection prevented
- [ ] Mass assignment protected

### Code Quality (Phase 13)
- [ ] No hardcoded values
- [ ] No code duplication
- [ ] PSR-12 compliant

### Documentation (Phase 14)
- [ ] API docs match implementation
- [ ] User manual accurate
- [ ] Admin guide accurate

### Integration (Phase 15)
- [ ] End-to-end workflow works
- [ ] No performance issues
- [ ] No N+1 queries

---

## Audit Report Template

```markdown
# WASL v3 Audit Report

**Date:** [Date]
**Auditor:** [Name]
**Version:** 3.0.0

## Executive Summary
- Overall Status: [PASS/FAIL/PARTIAL]
- Issues Found: [Count]
- Critical Issues: [Count]
- Recommendations: [Count]

## Phase Results

### Phase 1: Database - [PASS/FAIL]
- Migrations: [X/20] ✅
- Issues: [List any issues]

### Phase 2: Models - [PASS/FAIL]
- Models: [X/13] ✅
- Relationships: [Status]
- Issues: [List any issues]

[Continue for all phases...]

## Critical Issues
1. [Issue description]
   - Severity: CRITICAL
   - Location: [File:Line]
   - Recommendation: [Fix]

## Non-Critical Issues
1. [Issue description]
   - Severity: LOW
   - Location: [File:Line]
   - Recommendation: [Fix]

## Recommendations
1. [Recommendation]
2. [Recommendation]

## Conclusion
[Overall assessment and sign-off]
```

---

## Running the Complete Audit

**Execute all phases:**

```bash
# Create audit script
cat > audit_wasl_v3.sh << 'EOF'
#!/bin/bash

echo "🔍 Starting WASL v3 Comprehensive Audit"
echo "======================================"

# Phase 1: Database
echo ""
echo "Phase 1: Database Audit"
ls -1 database/migrations/ | grep "2026_01" | wc -l

# Phase 2: Models
echo ""
echo "Phase 2: Models Audit"
ls -1 app/Models/ | grep -E "(Country|Program|Employer)" | wc -l

# Phase 3: Enums
echo ""
echo "Phase 3: Enums Audit"
ls -1 app/Enums/ | wc -l

# Phase 4: Controllers
echo ""
echo "Phase 4: Controllers Audit"
ls -1 app/Http/Controllers/ | grep -E "(Program|Employer)" | wc -l

# Phase 5: Services
echo ""
echo "Phase 5: Services Audit"
ls -1 app/Services/ | grep -E "(AutoBatch|Allocation)" | wc -l

# Phase 6: Routes
echo ""
echo "Phase 6: Routes Audit"
php artisan route:list | grep -E "(employer|success-stor)" | wc -l

# Phase 7: Views
echo ""
echo "Phase 7: Views Audit"
find resources/views -name "*.blade.php" | grep -E "(employer|success)" | wc -l

# Phase 11: Tests
echo ""
echo "Phase 11: Running Tests"
php artisan test

echo ""
echo "✅ Audit Complete"
EOF

chmod +x audit_wasl_v3.sh
./audit_wasl_v3.sh
```

---

**Document End**

*This audit guide should be executed before production deployment.*
