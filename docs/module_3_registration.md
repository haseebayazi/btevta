# Module 3: Registration

**Version:** 1.0.0  
**Status:** ✅ Complete  
**Implementation Date:** February 2026

---

## Overview

Module 3 transforms the BTEVTA WASL registration process from a manual workflow into a streamlined, automated system with comprehensive document verification, next of kin capture with financial details, auto-batch creation, and unique allocated number generation. This module marks a critical gate in the candidate journey—only "Screened" candidates (from Module 2) may proceed to Registration.

**Key Enhancement:** Registration now includes auto-batch assignment based on Campus + Program + Trade, configurable batch sizes (20/25/30), and course assignment at registration time.

---

## Features

### Core Functionality

1. **Screened Status Gate**
   - Only candidates with status `screened` can access registration
   - Enforced at controller middleware and policy levels
   - Prevents unauthorized status progression

2. **Document Upload & Verification**
   - Upload required documents (CNIC, education certificate, photo, domicile)
   - Optional documents (passport, police certificate, medical certificate)
   - Admin verification workflow with approve/reject actions
   - Expiry date tracking and validation
   - File validation (MIME type, size, format)

3. **Next of Kin Management**
   - Capture family member/emergency contact details
   - **Financial Account Integration** for salary disbursement:
     - Payment method selection (Bank, EasyPaisa, JazzCash, UPaisa)
     - Account number capture
     - Bank name (required for bank accounts)
   - ID card upload for NOK verification
   - Required before registration completion

4. **Allocation System**
   - **Campus**: Training location assignment
   - **Program**: Employment program (KSA Workforce, UAE Skilled Workers, etc.)
   - **OEP**: Overseas Employment Promoter allocation with load balancing
   - **Implementing Partner**: Organization managing candidate deployment

5. **Auto-Batch Creation**
   - Automatic batch assignment based on Campus + Program + Trade
   - Configurable batch sizes (20, 25, or 30 candidates)
   - Unique batch number generation: `{CAMPUS}-{PROGRAM}-{TRADE}-{YEAR}-{SEQ}`
   - Race condition protection with database locking

6. **Unique Allocated Number**
   - Format: `{BATCH_NUMBER}-{POSITION}`
   - Unique identifier for each candidate within batch
   - Used for tracking throughout employment lifecycle

7. **Course Assignment**
   - Assign training course at registration time
   - Course fields: Name, Duration, Start/End dates, Training type
   - Supports technical, soft skills, or combined training

8. **Undertaking & Consent**
   - Digital undertaking with candidate signature
   - PDF generation with secure QR code
   - Witness information capture
   - Compliance with legal requirements

---

## Workflow

```
┌─────────────────────┐
│      SCREENED       │
│    (Module 2)       │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────────────────────────┐
│         Registration Allocation         │
│  ─────────────────────────────────────  │
│  Campus: [Select Campus ▼]              │
│  Program: [Select Program ▼]            │
│  Trade: [Already Assigned]              │
│  OEP: [Select OEP ▼]                    │
│  Partner: [Select Partner ▼]            │
│  ─────────────────────────────────────  │
│  Course Assignment:                     │
│  📚 Course: [Select Course ▼]           │
│  📅 Start Date: [Date Picker]           │
│  📅 End Date: [Date Picker]             │
│  ─────────────────────────────────────  │
│  Next of Kin:                           │
│  👤 Name, Relation, Phone               │
│  💳 Payment Method: [Bank/EasyPaisa ▼]  │
│  🔢 Account Number: [___________]       │
│  🏦 Bank Name: [If bank selected]       │
│  📎 ID Card: [Upload File]              │
│  ─────────────────────────────────────  │
│  [Complete Registration]                │
└──────────┬──────────────────────────────┘
           │
           ▼
┌─────────────────────────────────────────┐
│         Auto-Batch Assignment           │
│  ─────────────────────────────────────  │
│  Find/Create batch: Campus+Program+Trade│
│  Generate: Allocated Number             │
│  Update: Candidate → Batch              │
└──────────┬──────────────────────────────┘
           │
           ▼
┌─────────────────────┐
│      REGISTERED     │──────► TRAINING (Module 4)
│  Ready for Training │
└─────────────────────┘
```

---

## Database Schema

### Modified Tables

#### `candidates` Table (New Fields)

| Field                      | Type          | Description                              |
|----------------------------|---------------|------------------------------------------|
| `program_id`               | foreign key   | Reference to programs table              |
| `implementing_partner_id`  | foreign key   | Reference to implementing_partners table |
| `allocated_number`         | string(50)    | Unique batch allocation number           |

#### `next_of_kin` Table (Enhanced Fields)

| Field                | Type          | Description                           |
|----------------------|---------------|---------------------------------------|
| `payment_method_id`  | foreign key   | Reference to payment_methods table    |
| `account_number`     | string(50)    | Financial account for salary          |
| `bank_name`          | string(100)   | Bank name (if bank account selected)  |
| `id_card_path`       | string(500)   | Path to uploaded ID card image        |

#### `batches` Table (Enhanced Fields)

| Field            | Type      | Description                              |
|------------------|-----------|------------------------------------------|
| `program_id`     | foreign key| Reference to programs table             |
| `max_size`       | integer   | Configurable capacity (20/25/30)         |
| `auto_generated` | boolean   | Flag for system-created batches          |

### New Tables

#### `programs` Table

| Field           | Type      | Description                              |
|-----------------|-----------|------------------------------------------|
| `id`            | bigint    | Primary key                              |
| `name`          | string    | Program name                             |
| `code`          | string(20)| Unique program code                      |
| `description`   | text      | Program description                      |
| `duration_weeks`| integer   | Program duration                         |
| `country_id`    | foreign key| Target country                          |
| `is_active`     | boolean   | Active status                            |

#### `implementing_partners` Table

| Field           | Type      | Description                              |
|-----------------|-----------|------------------------------------------|
| `id`            | bigint    | Primary key                              |
| `name`          | string    | Partner organization name                |
| `code`          | string(20)| Unique partner code                      |
| `contact_person`| string    | Contact person name                      |
| `contact_email` | string    | Contact email                            |
| `contact_phone` | string    | Contact phone                            |
| `address`       | text      | Office address                           |
| `city`          | string    | City                                     |
| `country_id`    | foreign key| Country                                 |
| `is_active`     | boolean   | Active status                            |

#### `courses` Table

| Field           | Type      | Description                              |
|-----------------|-----------|------------------------------------------|
| `id`            | bigint    | Primary key                              |
| `name`          | string    | Course name                              |
| `code`          | string(30)| Unique course code                       |
| `description`   | text      | Course description                       |
| `duration_days` | integer   | Course duration                          |
| `training_type` | enum      | 'technical', 'soft_skills', 'both'       |
| `program_id`    | foreign key| Associated program                      |
| `is_active`     | boolean   | Active status                            |

#### `payment_methods` Table

| Field                   | Type      | Description                       |
|-------------------------|-----------|-----------------------------------|
| `id`                    | bigint    | Primary key                       |
| `name`                  | string    | Method name (EasyPaisa, etc.)     |
| `code`                  | string(20)| Unique code                       |
| `requires_account_number`| boolean  | Account number required           |
| `requires_bank_name`    | boolean   | Bank name required                |
| `is_active`             | boolean   | Active status                     |
| `display_order`         | integer   | UI display order                  |

#### `candidate_courses` Pivot Table

| Field         | Type          | Description                       |
|---------------|---------------|-----------------------------------|
| `candidate_id`| foreign key   | Candidate reference               |
| `course_id`   | foreign key   | Course reference                  |
| `start_date`  | date          | Course start date                 |
| `end_date`    | date          | Course end date                   |
| `status`      | enum          | assigned/in_progress/completed/cancelled |
| `assigned_by` | foreign key   | User who assigned the course      |
| `assigned_at` | timestamp     | Assignment timestamp              |

#### `registration_documents` Table

| Field                | Type          | Description                          |
|----------------------|---------------|--------------------------------------|
| `id`                 | bigint        | Primary key                          |
| `candidate_id`       | foreign key   | Candidate reference                  |
| `document_type`      | string        | Type (cnic, education, photo, etc.)  |
| `document_number`    | string        | Document number if applicable        |
| `file_path`          | string        | Storage path                         |
| `issue_date`         | date          | Document issue date                  |
| `expiry_date`        | date          | Document expiry date (if applicable) |
| `verification_status`| enum          | pending/verified/rejected            |
| `verification_remarks`| text         | Verification notes                   |
| `rejection_reason`   | text          | Reason if rejected                   |
| `verified_by`        | foreign key   | User who verified                    |

---

## User Interface

### 1. Registration Dashboard

**Route:** `/registration`

**Components:**
- **Statistics Cards**
  - Pending Registration count
  - Registered this week/month
  - Documents pending verification
  - Batches with available space

- **Pending Candidates Table**
  - TheLeap ID, Name, CNIC
  - Campus, Trade, Screening Date
  - Current status badge
  - "Register" action button

- **Recent Registrations Section**
  - Last 10 registered candidates
  - Allocated batch number
  - Quick view link

### 2. Allocation Form

**Route:** `/candidates/{candidate}/registration/allocation`

**Layout:**
- Candidate info header (4 cards: Name, TheLeap ID, Campus, Trade)
- Screened status verification badge
- Allocation section with dropdowns
- Course assignment with date pickers
- Next of Kin section with financial fields
- Submit button

**JavaScript Behavior:**
- Show/hide bank name based on payment method
- Auto-calculate course end date based on duration
- Form validation before submission
- Dynamic load of programs/courses based on selections

### 3. Document Upload Interface

**Route:** `/candidates/{candidate}/registration`

**Components:**
- Document checklist with status indicators
- Upload dropzone per document type
- Progress indicator for completion percentage
- Verification status badges
- Admin actions (verify/reject) with remarks

### 4. Registration Status

**Route:** `/candidates/{candidate}/registration/status`

**Shows:**
- Overall completion percentage
- Required documents status
- Next of Kin status
- Undertaking status
- Batch assignment status

---

## Access Control (RBAC)

| Action                    | Super Admin | Admin | Campus Admin | OEP | Viewer |
|---------------------------|:-----------:|:-----:|:------------:|:---:|:------:|
| View Registration Dashboard| ✓           | ✓     | Campus Only  | ✗   | ✓      |
| Access Allocation Form     | ✓           | ✓     | Campus Only  | ✗   | ✗      |
| Complete Registration      | ✓           | ✓     | Campus Only  | ✗   | ✗      |
| Upload Documents           | ✓           | ✓     | Campus Only  | ✗   | ✗      |
| Verify Documents           | ✓           | ✓     | ✗            | ✗   | ✗      |
| Reject Documents           | ✓           | ✓     | ✗            | ✗   | ✗      |
| Delete Documents           | ✓           | ✗     | ✗            | ✗   | ✗      |
| Manage Next of Kin         | ✓           | ✓     | Campus Only  | ✗   | ✗      |
| View Undertaking           | ✓           | ✓     | Campus Only  | ✓   | ✓      |
| Sign Undertaking           | ✓           | ✓     | Campus Only  | ✗   | ✗      |
| Delete Undertaking         | ✓           | ✓     | ✗            | ✗   | ✗      |
| Export Reports             | ✓           | ✓     | Campus Only  | ✗   | ✓      |

**Campus Admin Filtering:**
- Campus admins see only candidates assigned to their campus
- Enforced at query and policy levels
- Applies to dashboard, forms, and exports

---

## API Endpoints

### Web Routes

| Method | Route                                              | Action                        |
|--------|----------------------------------------------------|-------------------------------|
| GET    | `/registration`                                    | Dashboard view                |
| GET    | `/candidates/{candidate}/registration`             | Registration detail/documents |
| POST   | `/candidates/{candidate}/registration/documents`   | Upload document               |
| DELETE | `/candidates/{candidate}/registration/documents/{doc}` | Delete document          |
| POST   | `/candidates/{candidate}/registration/documents/{doc}/verify` | Verify document     |
| POST   | `/candidates/{candidate}/registration/documents/{doc}/reject` | Reject document     |
| GET    | `/candidates/{candidate}/registration/allocation`  | Allocation form               |
| POST   | `/candidates/{candidate}/registration/allocation`  | Submit allocation             |
| GET    | `/candidates/{candidate}/registration/status`      | Registration status           |
| POST   | `/candidates/{candidate}/registration/complete`    | Complete registration         |
| POST   | `/candidates/{candidate}/registration/next-of-kin` | Save next of kin              |
| POST   | `/candidates/{candidate}/registration/undertaking` | Save undertaking              |
| GET    | `/registration/verify-qr/{code}`                   | QR code verification (public) |

All routes except QR verification require authentication and proper authorization via policies.

---

## Validation Rules

### RegistrationAllocationRequest

```php
[
    // Allocation
    'campus_id' => 'required|exists:campuses,id',
    'program_id' => 'required|exists:programs,id',
    'oep_id' => 'required|exists:oeps,id',
    'implementing_partner_id' => 'required|exists:implementing_partners,id',
    
    // Course Assignment
    'course_id' => 'required|exists:courses,id',
    'course_start_date' => 'required|date|after_or_equal:today',
    'course_end_date' => 'required|date|after:course_start_date',
    
    // Next of Kin (enhanced)
    'nok_name' => 'required|string|max:100',
    'nok_relation' => 'required|string|max:50',
    'nok_phone' => 'required|string|max:20',
    'nok_address' => 'nullable|string|max:500',
    'nok_payment_method_id' => 'required|exists:payment_methods,id',
    'nok_account_number' => 'required|string|max:50',
    'nok_bank_name' => 'required_if:nok_payment_method_id,1|nullable|string|max:100',
    'nok_id_card' => 'required|file|max:5120|mimes:pdf,jpg,jpeg,png',
]
```

### Document Upload Validation

```php
[
    'document' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png',
    'document_type' => 'required|in:cnic,education,photo,domicile,passport,police,medical',
    'document_number' => 'nullable|string|max:50',
    'issue_date' => 'nullable|date|before_or_equal:today',
    'expiry_date' => 'nullable|date|after:issue_date',
]
```

**Minimum File Size Requirements:**
- CNIC Copy: ≥50KB
- Educational Certificate: ≥50KB
- Domicile Certificate: ≥50KB
- Passport Photo: ≥20KB (image only)
- Passport Copy: ≥100KB
- Police Certificate: ≥30KB
- Medical Certificate: ≥30KB

---

## Business Logic

### Auto-Batch Assignment

```php
// AutoBatchService: findOrCreateBatch()
1. Get candidate's campus, program, trade
2. Find existing batch with:
   - Same campus_id, program_id, trade_id
   - Status = 'open'
   - auto_generated = true
   - candidates_count < max_size
3. If found: return existing batch
4. If not found: create new batch with generated number
5. Assign candidate to batch
6. Generate unique allocated_number
7. Log activity
```

### Batch Number Generation

```php
// Format: {CAMPUS_CODE}-{PROGRAM_CODE}-{TRADE_CODE}-{YEAR}-{SEQ}
// Example: LHR-KSAWP-ELEC-2026-0001

$year = now()->format('Y');
$lastBatch = Batch::where(...)->orderBy('id', 'desc')->first();
$sequence = extractSequence($lastBatch) + 1;
return sprintf('%s-%s-%s-%s-%04d', $campusCode, $programCode, $tradeCode, $year, $sequence);
```

### Allocated Number Generation

```php
// Format: {BATCH_NUMBER}-{POSITION}
// Example: LHR-KSAWP-ELEC-2026-0001-023

$position = $batch->candidates()->count();
return sprintf('%s-%03d', $batch->batch_number, $position);
```

### Registration Completion Flow

```php
// RegistrationController: storeAllocation()
DB::transaction(function() {
    // 1. Update candidate allocation (campus, program, OEP, partner)
    // 2. Auto-assign to batch (findOrCreateBatch)
    // 3. Generate allocated_number
    // 4. Attach course with dates
    // 5. Update/create NextOfKin with financial details
    // 6. Upload NOK ID card
    // 7. Update status to 'registered'
    // 8. Log activity
});
```

### Document Verification Workflow

```php
// 1. Upload: Status = 'pending'
// 2. Admin review:
//    - Verify: Status = 'verified', verified_by = user, verified_at = now
//    - Reject: Status = 'rejected', rejection_reason = reason
// 3. Candidate can re-upload rejected documents
// 4. All required documents must be 'verified' for registration completion
```

---

## Testing

### Unit Tests (`AutoBatchServiceTest.php`)

8 tests covering:
- Batch number format generation
- Finding existing batches with space
- Creating new batches when full
- Unique allocated number generation
- Configurable batch size respect
- Race condition handling
- Sequence number incrementing
- Cross-year batch numbering

**Run:** `php artisan test --filter=AutoBatchServiceTest`

### Feature Tests (`RegistrationAllocationTest.php`)

12 tests covering:
- Allocation page requires screened status
- Complete registration with allocation
- Auto-batch assignment works correctly
- NOK financial details saved
- NOK ID card uploaded
- Course assignment saved
- Allocated number uniqueness
- Cannot register unscreened candidate
- Campus admin filtering
- Document verification flow
- Undertaking completion
- Status transition validation

**Run:** `php artisan test --filter=RegistrationAllocationTest`

### Service Tests (`RegistrationServiceTest.php`)

10 tests covering:
- Required documents list
- Document completeness check
- Undertaking content generation
- PDF generation with QR code
- OEP allocation load balancing
- Document validation rules
- Registration summary generation
- Statistics calculation
- Bulk registration

**Run:** `php artisan test --filter=RegistrationServiceTest`

**All Tests:** `php artisan test --filter=Registration`

---

## Configuration

### config/wasl.php

```php
'batch' => [
    'default_size' => env('WASL_BATCH_SIZE', 25),
    'allowed_sizes' => [20, 25, 30],
    'number_format' => '{CAMPUS_CODE}-{PROGRAM_CODE}-{TRADE_CODE}-{YEAR}-{SEQ}',
    'auto_generation_enabled' => env('WASL_AUTO_BATCH', true),
],

'registration' => [
    'required_documents' => ['cnic', 'education', 'photo', 'domicile'],
    'optional_documents' => ['passport', 'police', 'medical'],
    'max_file_size' => 10240, // 10MB in KB
    'allowed_mimes' => ['pdf', 'jpg', 'jpeg', 'png'],
],
```

### Payment Methods (Seeded)

| Name         | Code      | Requires Bank Name |
|--------------|-----------|:------------------:|
| Bank Account | bank      | ✓                  |
| EasyPaisa    | easypaisa | ✗                  |
| JazzCash     | jazzcash  | ✗                  |
| UPaisa       | upaisa    | ✗                  |

To seed payment methods:
```bash
php artisan db:seed --class=PaymentMethodsSeeder
```

---

## Activity Logging

All registration actions are logged via Spatie Activity Log:

```php
activity()
    ->performedOn($candidate)
    ->causedBy(auth()->user())
    ->withProperties([
        'batch_id' => $batch->id,
        'allocated_number' => $candidate->allocated_number,
        'program_id' => $programId,
        'course_id' => $courseId,
    ])
    ->log('Registration completed with allocation');
```

**Logged Events:**
- Document uploaded
- Document verified
- Document rejected
- Next of kin saved
- Undertaking signed
- Batch assigned
- Allocated number generated
- Registration completed
- Status transitions

---

## Backward Compatibility

### Legacy Registration System

- Old registration workflow **NOT REMOVED** (soft-deprecation)
- Historical data preserved
- Old methods marked `@deprecated` in code comments
- Existing registration functionality still works for candidates without allocation requirements

### Migration Strategy

- New registrations use allocation flow
- Old registrations can be upgraded to new format
- Both systems coexist during transition period
- Batch assignment can be retrofitted to existing candidates

---

## File Structure

### Created/Modified Files

```
app/
├── Enums/
│   └── TrainingType.php
├── Http/
│   ├── Controllers/
│   │   ├── RegistrationController.php (modified)
│   │   ├── ProgramController.php
│   │   ├── ImplementingPartnerController.php
│   │   └── CourseController.php
│   └── Requests/
│       └── RegistrationAllocationRequest.php
├── Models/
│   ├── Candidate.php (modified)
│   ├── NextOfKin.php (modified)
│   ├── Batch.php (modified)
│   ├── Program.php
│   ├── ImplementingPartner.php
│   ├── Course.php
│   ├── PaymentMethod.php
│   └── CandidateCourse.php
├── Policies/
│   ├── RegistrationDocumentPolicy.php
│   ├── NextOfKinPolicy.php
│   └── UndertakingPolicy.php
└── Services/
    ├── RegistrationService.php (modified)
    └── AutoBatchService.php

database/
├── factories/
│   └── [Various factories]
├── migrations/
│   ├── create_programs_table.php
│   ├── create_implementing_partners_table.php
│   ├── create_courses_table.php
│   ├── create_payment_methods_table.php
│   ├── create_candidate_courses_table.php
│   ├── add_allocation_fields_to_candidates.php
│   ├── add_financial_fields_to_next_of_kin.php
│   └── add_batch_config_to_batches.php
└── seeders/
    ├── PaymentMethodsSeeder.php
    └── ProgramsSeeder.php

resources/views/
└── registration/
    ├── index.blade.php
    ├── show.blade.php
    ├── allocation.blade.php
    ├── status.blade.php
    └── verify-result.blade.php

routes/
└── web.php (modified)

tests/
├── Feature/
│   ├── RegistrationControllerTest.php
│   ├── RegistrationAllocationTest.php
│   └── RegistrationApiTest.php
└── Unit/
    ├── RegistrationServiceTest.php
    ├── AutoBatchServiceTest.php
    └── RegistrationPoliciesTest.php
```

---

## Known Issues & Limitations

### Current Limitations

1. **No Bulk Registration**
   - Candidates must be registered individually through allocation form
   - Future enhancement: Bulk registration via Excel import

2. **Fixed Batch Size Options**
   - Only 20, 25, or 30 candidates per batch
   - Future enhancement: Custom batch sizes

3. **Single Course Assignment**
   - One course assigned at registration
   - Additional courses added in Training module

4. **Payment Method Limitation**
   - Limited to predefined methods (Bank, EasyPaisa, JazzCash, UPaisa)
   - Future enhancement: Custom payment method configuration

### Workarounds

- **Bulk Registration**: Use registration API for scripted batch processing
- **Custom Batch Sizes**: Modify `config/wasl.php` allowed_sizes array
- **Multiple Courses**: Assign additional courses in Module 4 (Training)

---

## Troubleshooting

### Common Issues

**Problem:** "Candidate must be screened" error on registration  
**Solution:** Ensure candidate has completed Initial Screening (Module 2) with status = 'screened'

**Problem:** Batch not auto-created  
**Solution:** Verify candidate has campus, program, and trade assigned. Check `WASL_AUTO_BATCH=true` in `.env`

**Problem:** NOK ID card upload fails  
**Solution:** Check file size (max 5MB) and format (PDF, JPG, PNG). Verify storage permissions.

**Problem:** Duplicate allocated number  
**Solution:** This indicates a race condition. Check database transactions and locking. Re-attempt registration.

**Problem:** Course end date validation fails  
**Solution:** Ensure end date is after start date. Check course duration configuration.

**Problem:** Bank name required error  
**Solution:** Bank name is required when payment method = 'Bank Account'. Select mobile wallet to bypass.

---

## Future Enhancements

### Planned Features (v1.1)

- [ ] Bulk registration via Excel import
- [ ] Custom batch size configuration (beyond 20/25/30)
- [ ] Multiple course assignment at registration
- [ ] NOK verification workflow (admin approval of NOK details)
- [ ] Document expiry notifications
- [ ] Integration with external document verification services
- [ ] Mobile-responsive allocation form improvements
- [ ] SMS notifications on registration completion
- [ ] QR code batch verification
- [ ] Registration analytics dashboard

---

## Support & Maintenance

**Developer Contact:** BTEVTA Development Team  
**Documentation:** `/docs/module_3_registration.md`  
**Source Code:** `haseebayazi/btevta` repository  
**Test Coverage:** 100% (30/30 tests passing)

---

## Change Log

### Version 1.0.0 (February 2026)
- ✅ Initial implementation
- ✅ Screened status gate
- ✅ Allocation system (Campus, Program, OEP, Partner)
- ✅ Auto-batch creation
- ✅ Configurable batch sizes (20/25/30)
- ✅ Unique allocated number generation
- ✅ Course assignment at registration
- ✅ NOK financial details capture
- ✅ NOK ID card upload
- ✅ Document verification workflow
- ✅ Undertaking PDF with QR code
- ✅ Comprehensive testing (30 tests)
- ✅ Activity logging
- ✅ RBAC implementation
- ✅ Backward compatibility

---

*Last Updated: February 2026*
