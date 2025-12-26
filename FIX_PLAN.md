# BTEVTA Laravel Application - Fix Plan

**Date:** December 21, 2025
**Based On:** INCOMPLETENESS_REPORT.md
**Priority:** Critical fixes first, then High, Medium, Low

---

## PHASE 1: CRITICAL FIXES (Must Complete)

### Fix #1: Implement Missing NotificationService Methods
**Files to modify:** `app/Services/NotificationService.php`
**New files to create:** None

**Implementation:**
Add 20 missing notification methods:
1. sendDocumentUploaded($document)
2. sendTrainingAssigned($candidate, $batch)
3. sendCertificateIssued($candidate)
4. sendTrainingCompleted($candidate)
5. sendBriefingCompleted($candidate)
6. sendDepartureConfirmed($candidate)
7. sendIqamaRecorded($candidate)
8. sendFirstSalaryConfirmed($candidate)
9. sendComplianceAchieved($candidate)
10. sendIssueReported($candidate, $issue)
11. sendComplaintRegistered($complaint)
12. sendComplaintAssigned($complaint, $user)
13. sendComplaintEscalated($complaint)
14. sendComplaintResolved($complaint)
15. sendComplaintClosed($complaint)
16. sendVisaProcessInitiated($candidate)
17. sendVisaStageCompleted($candidate, $stage)
18. sendVisaIssued($candidate)
19. sendTicketUploaded($candidate)
20. sendVisaProcessCompleted($candidate)

---

### Fix #2: Add Missing Relationship Aliases in Candidate Model
**Files to modify:** `app/Models/Candidate.php`
**New files to create:** None

**Implementation:**
Add alias relationships:
```php
public function certificate()
{
    return $this->hasOne(TrainingCertificate::class)->latest();
}

public function attendances()
{
    return $this->trainingAttendances();
}

public function assessments()
{
    return $this->trainingAssessments();
}
```

---

### Fix #3: Fix RemittanceBeneficiary Relationship
**Files to modify:** `app/Models/RemittanceBeneficiary.php`
**New files to create:** None
**Database schema changes:** None required (use existing beneficiary_id column in remittances)

**Implementation:**
Remove or fix the broken relationship - remittances should link TO beneficiary, not FROM it.

---

### Fix #4: Fix CandidateScreening->undertaking Relationship
**Files to modify:** `app/Models/CandidateScreening.php`
**New files to create:** None
**Database schema changes:** Add `screening_id` to undertakings table OR remove relationship

**Implementation:**
Option A: Create migration to add `screening_id` column
Option B: Remove the relationship if not needed

---

### Fix #5: Fix Campus Model $fillable
**Files to modify:** `app/Models/Campus.php`
**New files to create:** None

**Implementation:**
Remove non-existent fields from $fillable: `location`, `province`, `district`

---

### Fix #6: Fix Trade Model $fillable
**Files to modify:** `app/Models/Trade.php`
**New files to create:** None

**Implementation:**
Remove non-existent field from $fillable: `duration_weeks`

---

## PHASE 2: HIGH PRIORITY FIXES

### Fix #7: Correct Field Reference in CandidateController
**Files to modify:** `app/Http/Controllers/CandidateController.php:237`

**Implementation:**
Change `call_date` to `screened_at`

---

### Fix #8: Add Missing TrainingPolicy Methods
**Files to modify:** `app/Policies/TrainingPolicy.php` (or create if missing)
**New files to create:** `app/Policies/TrainingPolicy.php` if not exists

**Implementation:**
Add policy methods:
- viewAttendance()
- markAttendance()
- createAssessment()
- updateAssessment()
- generateCertificate()
- downloadCertificate()
- completeTraining()
- viewAttendanceReport()
- viewAssessmentReport()
- viewBatchPerformance()

---

### Fix #9: Add Missing Audit Relationships in RemittanceReceipt
**Files to modify:** `app/Models/RemittanceReceipt.php`

**Implementation:**
```php
public function creator()
{
    return $this->belongsTo(User::class, 'created_by');
}

public function updater()
{
    return $this->belongsTo(User::class, 'updated_by');
}
```

---

### Fix #10: Remove Duplicate Relationship Methods
**Files to modify:**
- `app/Models/Complaint.php` - remove `complainant()`
- `app/Models/Correspondence.php` - remove `createdBy()`

---

### Fix #11: Create scheduled_notifications Migration
**New files to create:** `database/migrations/xxxx_create_scheduled_notifications_table.php`

**Implementation:**
```php
Schema::create('scheduled_notifications', function (Blueprint $table) {
    $table->id();
    $table->string('recipient_type');
    $table->unsignedBigInteger('recipient_id')->nullable();
    $table->string('recipient_value')->nullable();
    $table->string('type');
    $table->json('data');
    $table->json('channels');
    $table->timestamp('scheduled_for');
    $table->string('status')->default('pending');
    $table->timestamp('sent_at')->nullable();
    $table->text('error_message')->nullable();
    $table->timestamp('failed_at')->nullable();
    $table->timestamps();
});
```

---

## PHASE 3: IMPLEMENTATION ORDER

### Step 1: Fix Critical Model Issues
1. Update Candidate.php with relationship aliases
2. Fix Campus.php $fillable
3. Fix Trade.php $fillable
4. Fix RemittanceBeneficiary.php relationship
5. Fix CandidateScreening.php relationship

### Step 2: Add All Missing NotificationService Methods
Single file update with 20 new methods

### Step 3: Fix Controller References
1. Fix CandidateController.php field reference
2. Verify all other controllers work with fixed models

### Step 4: Add Missing Policy Methods
Create or update TrainingPolicy.php

### Step 5: Create Missing Database Migration
Add scheduled_notifications table

### Step 6: Clean Up
1. Remove duplicate relationship methods
2. Remove development comments
3. Add missing audit relationships

---

## DATABASE MIGRATIONS NEEDED

```php
// Migration 1: scheduled_notifications table
php artisan make:migration create_scheduled_notifications_table

// Migration 2 (optional): Add screening_id to undertakings
php artisan make:migration add_screening_id_to_undertakings_table
```

---

## UI CHANGES REQUIRED

1. Statistics views - ensure Chart.js is properly loaded
2. Auth views - add password strength indicator (optional)
3. Form views - add loading states (optional)

---

## APIS NEEDED

No new APIs required - all existing API endpoints should work once models are fixed.

---

## COMMANDS TO RUN AFTER FIXES

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Run new migrations
php artisan migrate

# Regenerate autoload
composer dump-autoload

# Run tests
php artisan test

# Cache for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## ESTIMATED IMPLEMENTATION

| Phase | Time Estimate |
|-------|---------------|
| Phase 1: Critical Fixes | 2-3 hours |
| Phase 2: High Priority | 1-2 hours |
| Testing & Validation | 1 hour |
| **Total** | **4-6 hours** |

---

**Ready for Implementation:** YES

---
---

# PART 2: Tabs 6-10 & Campus Management Gap Analysis

**Date:** December 26, 2025
**Scope:** Missing functions for Departure, Correspondence, Complaints, Document Archive, Reporting Module, and Campus Management Integration

---

## Executive Summary

After analyzing the codebase against the requirements for Tabs 6-10 and Campus Management, the application is **~80% complete**. Most core CRUD operations and workflows exist. The primary gaps are:
1. **Missing specialized reports** (departure, correspondence, documents)
2. **PDF/CSV export capabilities** (only Excel exists)
3. **Equipment/trainer utilization tracking** (no models)
4. **Role-specific dashboard views** (filtered but not customized per role)

---

## Tab 6: Departure - Status: 85% Complete

### Existing Functions ✅
| Function | Status | Location |
|----------|--------|----------|
| Pre-Departure Briefing Tracking | ✅ Complete | `DepartureController::recordBriefing()` |
| Iqama Number Recording | ✅ Complete | `DepartureController::recordIqama()` |
| Medical Report (Post-arrival) | ✅ Complete | Stored as `post_arrival_medical_path` |
| Absher Registration | ✅ Complete | `DepartureController::recordAbsher()` |
| Qiwa ID Activation | ✅ Complete | `DepartureController::recordWps()` |
| Salary Confirmation | ✅ Complete | `DepartureController::recordFirstSalary()` |
| Post-departure communication | ✅ Complete | `DepartureService::addCommunicationLog()` |
| Issue tracking | ✅ Complete | `DepartureController::reportIssue()` |
| 90-day compliance report | ✅ Complete | `DepartureController::complianceReport()` |

### Missing Reports ❌

#### 1. Departure List by Date, Trade, and OEP
**Gap**: Service method `getDepartureList($filters)` exists but no dedicated controller method/view
**Fix Required**:
- Add `departureListReport()` method in `DepartureController`
- Add route: `GET /departure/reports/list`
- Create view: `resources/views/departure/reports/list.blade.php`

#### 2. Pending Iqama or Absher Activation Report
**Gap**: Logic exists in `DepartureService::getPendingComplianceItems()` but no dedicated route/view
**Fix Required**:
- Add `pendingActivationsReport()` method in `DepartureController`
- Add route: `GET /departure/reports/pending-activations`
- Create view: `resources/views/departure/reports/pending-activations.blade.php`

#### 3. Salary Disbursement Status Report
**Gap**: No dedicated salary status report
**Fix Required**:
- Add `salaryStatusReport()` method in `DepartureController`
- Add route: `GET /departure/reports/salary-status`
- Create view: `resources/views/departure/reports/salary-status.blade.php`

---

## Tab 7: Correspondence - Status: 85% Complete

### Existing Functions ✅
| Function | Status | Location |
|----------|--------|----------|
| Maintain official communications | ✅ Complete | `CorrespondenceController::store()` |
| File reference number | ✅ Complete | Auto-generated `COR-YYYYMM-XXXXX` |
| Date, subject, sender/recipient | ✅ Complete | Model fields |
| Upload PDF copies | ✅ Complete | `file_path` field |
| Pending reply tracker | ✅ Complete | `CorrespondenceController::pendingReply()` |
| Register view | ✅ Complete | `CorrespondenceController::register()` |

### Missing Reports ❌

#### 1. Communication Summary (Outgoing/Incoming Ratio)
**Gap**: Dashboard shows counts but no ratio analysis report
**Fix Required**:
- Add `summary()` method in `CorrespondenceController`
- Add route: `GET /correspondence/reports/summary`
- Create view: `resources/views/correspondence/reports/summary.blade.php`

---

## Tab 8: Complaints Redressal Mechanism - Status: 100% Complete ✅

All required functions and reports are implemented:
- ✅ In-app complaint registration
- ✅ Category-based tagging (screening, training, visa, salary, conduct)
- ✅ Review, assignment, closure tracking
- ✅ Escalation matrix and SLA (3-5 working days based on category)
- ✅ Total complaints received/resolved/pending
- ✅ Average resolution time (`ComplaintController::statistics()`)
- ✅ Campus-wise and category-wise trends (`ComplaintController::analytics()`)
- ✅ Grievance response compliance rate (`ComplaintController::slaReport()`)

---

## Tab 9: Document Archive - Status: 80% Complete

### Existing Functions ✅
| Function | Status | Location |
|----------|--------|----------|
| Document repository | ✅ Complete | `DocumentArchiveController` |
| Version control | ✅ Complete | `uploadVersion()`, `versions()` |
| Smart filters | ✅ Complete | `index()` filters |
| Secure access & audit log | ✅ Complete | `logAccess()`, `accessLogs()` |
| Expiry alerts | ✅ Complete | `expiring()`, `expired()` |
| Storage utilization | ✅ Complete | `statistics()` |

### Missing Reports ❌

#### 1. Missing Document Summary
**Gap**: No report showing candidates with incomplete documents
**Fix Required**:
- Add `missingDocuments()` method in `DocumentArchiveController`
- Add route: `GET /document-archive/reports/missing`
- Create view: `resources/views/document-archive/reports/missing.blade.php`

#### 2. Document Verification Status by OEP and Campus
**Gap**: No cross-reference report for document verification
**Fix Required**:
- Add `verificationStatus()` method in `DocumentArchiveController`
- Add route: `GET /document-archive/reports/verification-status`
- Create view: `resources/views/document-archive/reports/verification-status.blade.php`

---

## Tab 10: Reporting Module - Status: 70% Complete

### Existing Reports ✅
| Report | Status | Location |
|--------|--------|----------|
| Individual candidate profile | ✅ Complete | `ReportController::candidateProfile()` |
| Batch summary | ✅ Complete | `ReportController::batchSummary()` |
| Campus performance | ✅ Complete | `ReportController::campusPerformance()` |
| OEP performance | ✅ Complete | `ReportController::oepPerformance()` |
| Visa timeline | ✅ Complete | `ReportController::visaTimeline()` |
| Training statistics | ✅ Complete | `ReportController::trainingStatistics()` |
| Complaint analysis | ✅ Complete | `ReportController::complaintAnalysis()` |
| Custom report builder | ✅ Complete | `ReportController::customReport()` |
| Excel export | ✅ Complete | `ReportController::exportToExcel()` |

### Missing Features ❌

#### 1. Individual Profile PDF Export
**Gap**: Profile view exists but no PDF generation
**Fix Required**:
- Install: `composer require barryvdh/laravel-dompdf`
- Add `exportProfilePdf()` method in `ReportController`
- Add route: `GET /reports/candidate-profile/{candidate}/pdf`

#### 2. Salary & Post-Departure Updates Report
**Gap**: No dedicated report in ReportController
**Fix Required**:
- Add `departureUpdatesReport()` method in `ReportController`
- Add route: `GET /reports/departure-updates`
- Create view: `resources/views/reports/departure-updates.blade.php`

#### 3. Trainer Performance Report
**Gap**: No trainer performance tracking/report
**Fix Required**:
- Add `trainerPerformance()` method in `ReportController`
- Add route: `GET /reports/trainer-performance`
- Create view: `resources/views/reports/trainer-performance.blade.php`

#### 4. CSV Export Support
**Gap**: Only Excel export exists
**Fix Required**:
- Add `exportToCsv()` method in `ReportController`
- Update custom report builder to support CSV format

#### 5. Role-Specific Dashboard Views
**Gap**: Dashboard filters by role but doesn't show different layouts/widgets
**Fix Required**:
- Create role-specific dashboard views (admin, campus_admin, oep, visa_partner, instructor)
- Modify `DashboardController::index()` to select view by role

---

## Campus Management Integration - Status: 75% Complete

### Existing Features ✅
| Feature | Status | Location |
|---------|--------|----------|
| Campus as separate data node | ✅ Complete | `Campus` model |
| Batch history | ✅ Complete | `Campus::batches()` |
| Candidate database | ✅ Complete | `Campus::candidates()` |
| Attendance, assessment, certification | ✅ Complete | Training module |
| Central admin dashboard | ✅ Complete | `DashboardController` |
| Comparative analysis | ✅ Complete | `ReportController::campusPerformance()` |

### Missing Features ❌

#### 1. Equipment Utilization Records
**Gap**: No equipment tracking model/functionality
**Fix Required**:
- Create migration: `campus_equipment` table
- Create migration: `equipment_usage_logs` table
- Create model: `CampusEquipment`
- Create model: `EquipmentUsageLog`
- Create controller: `EquipmentController`

#### 2. Trainer Utilization Records
**Gap**: Instructors exist but no utilization tracking
**Fix Required**:
- Add migration: `max_batches_capacity`, `max_hours_per_week` to instructors table
- Add `instructorUtilization()` method in `ReportController`
- Create view: `resources/views/reports/instructor-utilization.blade.php`

#### 3. Performance-Based Funding Metrics
**Gap**: No funding tracking or performance KPIs
**Fix Required**:
- Create migration: `campus_kpis` table
- Create model: `CampusKpi`
- Add funding reports to `ReportController`

#### 4. Compliance Monitoring Dashboard
**Gap**: Basic stats exist but no dedicated compliance view
**Fix Required**:
- Add `complianceMonitoring()` method in `DashboardController`
- Create view: `resources/views/dashboard/compliance-monitoring.blade.php`

---

## Implementation Priority

### Phase 1: Critical Reports (Effort: 4-6 hours)
1. Departure list by date/trade/OEP
2. Pending Iqama/Absher activation report
3. Salary disbursement status
4. Missing document summary
5. Communication summary (outgoing/incoming ratio)

### Phase 2: Enhanced Reports (Effort: 8-12 hours)
1. Individual profile PDF export
2. CSV export support
3. Document verification status by OEP/campus
4. Trainer performance report
5. Salary & post-departure updates report

### Phase 3: Dashboard & Management (Effort: 8-10 hours)
1. Role-specific dashboard views
2. Compliance monitoring dashboard
3. Trainer utilization tracking

### Phase 4: Advanced Features (Effort: 12-16 hours)
1. Equipment utilization model & tracking
2. Performance-based funding metrics
3. Dashboard charts/visualizations

---

## Route Additions Required

```php
// routes/web.php additions

// Departure Reports
Route::get('/departure/reports/list', [DepartureController::class, 'departureListReport'])->name('departure.reports.list');
Route::get('/departure/reports/pending-activations', [DepartureController::class, 'pendingActivationsReport'])->name('departure.reports.pending-activations');
Route::get('/departure/reports/salary-status', [DepartureController::class, 'salaryStatusReport'])->name('departure.reports.salary-status');

// Correspondence Reports
Route::get('/correspondence/reports/summary', [CorrespondenceController::class, 'summary'])->name('correspondence.reports.summary');

// Document Archive Reports
Route::get('/document-archive/reports/missing', [DocumentArchiveController::class, 'missingDocuments'])->name('document-archive.reports.missing');
Route::get('/document-archive/reports/verification-status', [DocumentArchiveController::class, 'verificationStatus'])->name('document-archive.reports.verification-status');

// Additional Reports
Route::get('/reports/candidate-profile/{candidate}/pdf', [ReportController::class, 'exportProfilePdf'])->name('reports.candidate-profile-pdf');
Route::get('/reports/departure-updates', [ReportController::class, 'departureUpdatesReport'])->name('reports.departure-updates');
Route::get('/reports/trainer-performance', [ReportController::class, 'trainerPerformance'])->name('reports.trainer-performance');
Route::get('/reports/instructor-utilization', [ReportController::class, 'instructorUtilization'])->name('reports.instructor-utilization');

// Compliance Monitoring
Route::get('/dashboard/compliance-monitoring', [DashboardController::class, 'complianceMonitoring'])->name('dashboard.compliance-monitoring');
```

---

## New Dependencies

```bash
# PDF Generation (for profile PDF export)
composer require barryvdh/laravel-dompdf
```

---

## View Files to Create

```
resources/views/
├── departure/reports/
│   ├── list.blade.php
│   ├── pending-activations.blade.php
│   └── salary-status.blade.php
├── correspondence/reports/
│   └── summary.blade.php
├── document-archive/reports/
│   ├── missing.blade.php
│   └── verification-status.blade.php
├── reports/
│   ├── departure-updates.blade.php
│   ├── trainer-performance.blade.php
│   ├── instructor-utilization.blade.php
│   └── pdf/
│       └── candidate-profile.blade.php
├── dashboard/
│   ├── admin.blade.php
│   ├── campus-admin.blade.php
│   ├── oep.blade.php
│   ├── visa-partner.blade.php
│   ├── instructor.blade.php
│   └── compliance-monitoring.blade.php
```

---

## Summary Table

| Tab | Current Status | Missing Items | Effort |
|-----|---------------|---------------|--------|
| Tab 6: Departure | 85% | 3 reports | 4-6 hours |
| Tab 7: Correspondence | 85% | 1 report | 2-3 hours |
| Tab 8: Complaints | 100% | None | 0 hours |
| Tab 9: Document Archive | 80% | 2 reports | 4-6 hours |
| Tab 10: Reporting | 70% | 5 features | 8-12 hours |
| Campus Management | 75% | 4 features | 12-16 hours |

**Total Estimated Effort for Tabs 6-10 & Campus: 30-43 hours**
