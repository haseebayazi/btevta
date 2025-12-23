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
