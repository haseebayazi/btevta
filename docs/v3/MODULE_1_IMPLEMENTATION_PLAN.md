# Module 1: Pre-Departure Document Collection - Complete Implementation Plan

## Executive Summary

This plan provides a step-by-step implementation guide for Module 1 of the WASL Change Impact Analysis. Module 1 introduces a mandatory pre-departure document collection workflow that must be completed before candidates can proceed to screening.

**Duration Estimate:** Sequential implementation recommended
**Complexity:** HIGH - Introduces critical workflow gate mechanism
**Testing Coverage Required:** 90%+ (Feature, Unit, Integration, Policy)

---

## Table of Contents

1. [Requirements Breakdown](#requirements-breakdown)
2. [Architecture Overview](#architecture-overview)
3. [Implementation Tasks](#implementation-tasks)
4. [Testing Strategy](#testing-strategy)
5. [Deployment Checklist](#deployment-checklist)

---

## Requirements Breakdown

### CL-001: Pre-Departure Documents Section ⭐ HIGH PRIORITY
**Type:** New Feature
**Impact:** HIGH - Introduces mandatory workflow step
**Description:** Add dedicated section after candidate listing, positioned before initial screening module

**Acceptance Criteria:**
- ✅ New route section `/candidates/{candidate}/pre-departure-documents`
- ✅ Accessible only for candidates in 'new' status
- ✅ Section appears in candidate navigation menu
- ✅ Blocks progression to screening until documents complete
- ✅ Visual indicator showing completion status (0/5 mandatory, 0/3 optional)

---

### CL-002: Mandatory Document Checklist ⭐ HIGH PRIORITY
**Type:** New Feature
**Impact:** HIGH - Compliance requirement
**Description:** Five mandatory documents that MUST be uploaded before screening

**Required Documents:**
1. **CNIC** (Computerized National Identity Card)
   - Validation: 13-digit format with checksum
   - Required fields: Document number, issue date, expiry date
   - File types: PDF, JPG, PNG (max 5MB)

2. **Passport**
   - Validation: Valid passport number format
   - Required fields: Passport number, issue date, expiry date (must be > 6 months future)
   - File types: PDF, JPG, PNG (max 5MB)

3. **Domicile Certificate**
   - Required fields: Certificate number, issue date
   - File types: PDF, JPG, PNG (max 5MB)

4. **FRC** (Fingerprint Record Certificate)
   - Required fields: FRC number, issue date, expiry date
   - File types: PDF, JPG, PNG (max 5MB)

5. **PCC** (Police Clearance Certificate)
   - Required fields: PCC number, issue date, expiry date
   - Validation: Expiry must be valid (within 6 months)
   - File types: PDF, JPG, PNG (max 5MB)

**Acceptance Criteria:**
- ✅ All 5 documents must be uploaded
- ✅ All documents must pass validation
- ✅ System prevents screening until all mandatory docs uploaded
- ✅ Document metadata stored (number, dates, file info)
- ✅ Verification status tracking (pending, verified, rejected)

---

### CL-003: Optional Document Checklist
**Type:** New Feature
**Impact:** MEDIUM
**Description:** Three optional documents for enhanced candidate profile

**Optional Documents:**
1. **Pre-Medical Results**
   - File types: PDF (max 10MB)
   - Fields: Test date, medical center name

2. **Professional Certifications**
   - File types: PDF, JPG, PNG (max 5MB each)
   - Fields: Certification name, issuing body, issue date, expiry date (optional)
   - Multiple files allowed

3. **Resume/CV**
   - File types: PDF, DOC, DOCX (max 5MB)
   - Fields: Last updated date

**Acceptance Criteria:**
- ✅ Documents can be uploaded but not required for progression
- ✅ UI clearly marks these as "Optional"
- ✅ Same verification workflow as mandatory docs
- ✅ Multiple certifications can be uploaded

---

### CL-004: Licenses Field
**Type:** New Feature
**Impact:** LOW - Optional enhancement
**Description:** Professional and driving licenses collection

**License Types:**
1. **Driving Licenses**
   - Categories: Car, Motorcycle, HGV, PSV
   - Fields: License number, category, issue date, expiry date

2. **Professional Licenses** (e.g., RN Nurse License)
   - Fields: License number, type, issuing authority, issue date, expiry date
   - Multiple licenses allowed

**Acceptance Criteria:**
- ✅ Dynamic form allowing multiple license entries
- ✅ License data stored in separate `candidate_licenses` table
- ✅ File upload for each license (optional)
- ✅ Validation for expiry dates

---

### CL-005: Document Visibility Control ⭐ HIGH PRIORITY
**Type:** New Feature
**Impact:** HIGH - Workflow integrity
**Description:** Enforce read-only access after document submission

**Rules:**
1. **Edit Permissions:**
   - Candidate owner (campus admin/OEP): Can edit ONLY if status = 'new'
   - After screening starts: Read-only for all except Super Admin
   - Super Admin: Always can edit (with audit log)

2. **Version Control:**
   - If document needs re-upload after screening: Create new version
   - Old versions retained with timestamp
   - Audit trail of all changes

3. **Verification Lock:**
   - Once verified: Document locked from editing
   - Rejection allows re-upload
   - All actions logged

**Acceptance Criteria:**
- ✅ Policy enforcement in PreDepartureDocumentPolicy
- ✅ UI disables edit/upload buttons based on permissions
- ✅ API returns 403 for unauthorized edit attempts
- ✅ Version history tracked
- ✅ Activity log integration via Spatie Activity Log

---

### CL-006: Document Fetch Reporting
**Type:** New Feature
**Impact:** MEDIUM - Operational efficiency
**Description:** Generate reports for document retrieval tracking

**Report Types:**

1. **Individual Candidate Report**
   - Input: Candidate ID
   - Output: PDF/Excel with all documents + metadata
   - Includes: Document name, upload date, verified status, file link

2. **Bulk Document Report**
   - Input: Date range, campus, status filter
   - Output: Excel with columns:
     - Candidate Name, BTEVTA ID, Campus, Trade
     - Each mandatory doc (Yes/No/Verified)
     - Each optional doc (Yes/No/Verified)
     - Overall completion %
     - Last updated

3. **Missing Documents Report**
   - Input: Campus, date range
   - Output: List of candidates with incomplete documents
   - Highlights which specific documents missing

**Acceptance Criteria:**
- ✅ Three report types accessible from Documents section
- ✅ Export to PDF and Excel formats
- ✅ Reports respect role-based access (campus admins see only their campus)
- ✅ Performance optimized for bulk reports (chunk processing)

---

## Architecture Overview

### Database Schema

#### New Migration: `add_licenses_to_candidates`
```php
Schema::table('candidates', function (Blueprint $table) {
    // Add licenses field (optional - for simple storage)
    $table->json('licenses')->nullable()->after('qualification');
});
```

#### New Table: `candidate_licenses`
```php
Schema::create('candidate_licenses', function (Blueprint $table) {
    $table->id();
    $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
    $table->enum('license_type', ['driving', 'professional'])->default('professional');
    $table->string('license_name', 100); // e.g., "RN Nurse License", "HGV License"
    $table->string('license_number', 50);
    $table->string('license_category', 50)->nullable(); // For driving: Car, Motorcycle, etc.
    $table->string('issuing_authority', 150)->nullable();
    $table->date('issue_date')->nullable();
    $table->date('expiry_date')->nullable();
    $table->string('file_path', 500)->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->index('candidate_id');
});
```

#### Seeder: `DocumentChecklistSeeder`
Populate `document_checklists` table with predefined items:

```php
// Mandatory documents
['name' => 'CNIC', 'code' => 'CNIC', 'category' => 'mandatory', 'is_mandatory' => true, 'display_order' => 1]
['name' => 'Passport', 'code' => 'PASSPORT', 'category' => 'mandatory', 'is_mandatory' => true, 'display_order' => 2]
['name' => 'Domicile Certificate', 'code' => 'DOMICILE', 'category' => 'mandatory', 'is_mandatory' => true, 'display_order' => 3]
['name' => 'FRC (Fingerprint Record)', 'code' => 'FRC', 'category' => 'mandatory', 'is_mandatory' => true, 'display_order' => 4]
['name' => 'PCC (Police Clearance)', 'code' => 'PCC', 'category' => 'mandatory', 'is_mandatory' => true, 'display_order' => 5]

// Optional documents
['name' => 'Pre-Medical Results', 'code' => 'PRE_MEDICAL', 'category' => 'optional', 'is_mandatory' => false, 'display_order' => 6]
['name' => 'Professional Certifications', 'code' => 'CERTIFICATIONS', 'category' => 'optional', 'is_mandatory' => false, 'display_order' => 7]
['name' => 'Resume/CV', 'code' => 'RESUME', 'category' => 'optional', 'is_mandatory' => false, 'display_order' => 8]
```

### Models

#### New Model: `CandidateLicense`
```php
namespace App\Models;

class CandidateLicense extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'candidate_id', 'license_type', 'license_name', 'license_number',
        'license_category', 'issuing_authority', 'issue_date', 'expiry_date', 'file_path'
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function candidate() {
        return $this->belongsTo(Candidate::class);
    }

    public function isExpired(): bool {
        return $this->expiry_date && $this->expiry_date->isPast();
    }
}
```

#### Update Existing Model: `Candidate`
Add relationship methods:
```php
public function preDepartureDocuments()
{
    return $this->hasMany(PreDepartureDocument::class);
}

public function licenses()
{
    return $this->hasMany(CandidateLicense::class);
}

// Helper method to check document completion
public function hasCompletedPreDepartureDocuments(): bool
{
    $mandatoryDocs = DocumentChecklist::mandatory()->active()->count();
    $uploadedMandatory = $this->preDepartureDocuments()
        ->whereHas('documentChecklist', fn($q) => $q->mandatory())
        ->count();

    return $uploadedMandatory >= $mandatoryDocs;
}

// Helper to get document completion status
public function getPreDepartureDocumentStatus(): array
{
    $mandatory = DocumentChecklist::mandatory()->active()->get();
    $optional = DocumentChecklist::optional()->active()->get();

    $uploaded = $this->preDepartureDocuments()->with('documentChecklist')->get();

    return [
        'mandatory_total' => $mandatory->count(),
        'mandatory_uploaded' => $uploaded->whereIn('document_checklist_id', $mandatory->pluck('id'))->count(),
        'optional_total' => $optional->count(),
        'optional_uploaded' => $uploaded->whereIn('document_checklist_id', $optional->pluck('id'))->count(),
        'is_complete' => $this->hasCompletedPreDepartureDocuments(),
    ];
}
```

### Controllers

#### New Controller: `PreDepartureDocumentController`
Location: `/app/Http/Controllers/PreDepartureDocumentController.php`

**Methods:**
- `index(Candidate $candidate)` - Show document collection page for a candidate
- `store(Candidate $candidate, StorePreDepartureDocumentRequest $request)` - Upload document
- `destroy(Candidate $candidate, PreDepartureDocument $document)` - Delete document (if allowed)
- `download(Candidate $candidate, PreDepartureDocument $document)` - Download document file
- `verify(Candidate $candidate, PreDepartureDocument $document)` - Mark document as verified
- `reject(Candidate $candidate, PreDepartureDocument $document)` - Reject document with reason

#### New API Controller: `Api/PreDepartureDocumentApiController`
Location: `/app/Http/Controllers/Api/PreDepartureDocumentApiController.php`

**Endpoints:**
- `GET /api/v1/candidates/{candidate}/pre-departure-documents` - List documents
- `POST /api/v1/candidates/{candidate}/pre-departure-documents` - Upload document
- `GET /api/v1/candidates/{candidate}/pre-departure-documents/{document}` - Show document details
- `DELETE /api/v1/candidates/{candidate}/pre-departure-documents/{document}` - Delete document
- `GET /api/v1/candidates/{candidate}/pre-departure-documents/{document}/download` - Download file
- `POST /api/v1/candidates/{candidate}/pre-departure-documents/{document}/verify` - Verify document
- `POST /api/v1/candidates/{candidate}/pre-departure-documents/{document}/reject` - Reject document

#### New Controller: `CandidateLicenseController`
Location: `/app/Http/Controllers/CandidateLicenseController.php`

**Methods:**
- `store(Candidate $candidate, Request $request)` - Add license
- `update(Candidate $candidate, CandidateLicense $license, Request $request)` - Update license
- `destroy(Candidate $candidate, CandidateLicense $license)` - Delete license

#### New Controller: `PreDepartureReportController`
Location: `/app/Http/Controllers/PreDepartureReportController.php`

**Methods:**
- `individualReport(Candidate $candidate, $format = 'pdf')` - Generate individual document report
- `bulkReport(Request $request)` - Generate bulk document status report
- `missingDocumentsReport(Request $request)` - Generate missing documents report

### Services

#### New Service: `PreDepartureDocumentService`
Location: `/app/Services/PreDepartureDocumentService.php`

**Responsibilities:**
- File upload handling and validation
- Document verification workflow
- Version control for document updates
- Document retrieval and download
- Report generation logic
- Bulk operations

**Key Methods:**
```php
public function uploadDocument(Candidate $candidate, DocumentChecklist $checklist, UploadedFile $file, array $metadata): PreDepartureDocument
public function verifyDocument(PreDepartureDocument $document, User $verifier, ?string $notes = null): PreDepartureDocument
public function rejectDocument(PreDepartureDocument $document, User $verifier, string $reason): PreDepartureDocument
public function canEditDocuments(Candidate $candidate, User $user): bool
public function getCompletionStatus(Candidate $candidate): array
public function generateIndividualReport(Candidate $candidate, string $format): string
public function generateBulkReport(array $filters, string $format): string
public function generateMissingDocumentsReport(array $filters): Collection
```

### Policies

#### New Policy: `PreDepartureDocumentPolicy`
Location: `/app/Policies/PreDepartureDocumentPolicy.php`

**Authorization Rules:**
```php
viewAny(User $user, Candidate $candidate): bool
    - Super Admin: Yes
    - Campus Admin: Only their campus candidates
    - OEP: Only their assigned candidates
    - Others: No

view(User $user, PreDepartureDocument $document): bool
    - Same as viewAny but for specific document

create(User $user, Candidate $candidate): bool
    - Super Admin: Yes (always)
    - Campus Admin/OEP: Only if candidate status = 'new' AND it's their candidate
    - Others: No

update(User $user, PreDepartureDocument $document): bool
    - Super Admin: Yes (always, creates audit log)
    - Campus Admin/OEP: Only if:
        - Candidate status = 'new'
        - Document verification_status = 'rejected' OR 'pending'
        - It's their candidate
    - Others: No

delete(User $user, PreDepartureDocument $document): bool
    - Super Admin: Yes
    - Campus Admin/OEP: Only if candidate status = 'new' AND it's their candidate
    - Others: No

verify(User $user, PreDepartureDocument $document): bool
    - Super Admin: Yes
    - Project Director: Yes
    - Campus Admin: Only their campus candidates
    - Others: No

reject(User $user, PreDepartureDocument $document): bool
    - Same as verify
```

### Form Requests

#### New Request: `StorePreDepartureDocumentRequest`
Location: `/app/Http/Requests/StorePreDepartureDocumentRequest.php`

**Validation Rules:**
```php
public function rules(): array
{
    return [
        'document_checklist_id' => 'required|exists:document_checklists,id',
        'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB
        'notes' => 'nullable|string|max:500',

        // Conditional metadata based on document type
        'document_number' => 'required_if:document_type,CNIC,PASSPORT,FRC,PCC,DOMICILE|string|max:50',
        'issue_date' => 'nullable|date|before_or_equal:today',
        'expiry_date' => 'nullable|date|after:today',
    ];
}

public function authorize(): bool
{
    return Gate::allows('create', [PreDepartureDocument::class, $this->route('candidate')]);
}
```

### Routes

#### Web Routes (`routes/web.php`)
```php
// Pre-Departure Documents
Route::middleware(['auth'])->prefix('candidates/{candidate}')->group(function () {
    Route::get('pre-departure-documents', [PreDepartureDocumentController::class, 'index'])
        ->name('candidates.pre-departure-documents.index');
    Route::post('pre-departure-documents', [PreDepartureDocumentController::class, 'store'])
        ->name('candidates.pre-departure-documents.store');
    Route::delete('pre-departure-documents/{document}', [PreDepartureDocumentController::class, 'destroy'])
        ->name('candidates.pre-departure-documents.destroy');
    Route::get('pre-departure-documents/{document}/download', [PreDepartureDocumentController::class, 'download'])
        ->name('candidates.pre-departure-documents.download');
    Route::post('pre-departure-documents/{document}/verify', [PreDepartureDocumentController::class, 'verify'])
        ->name('candidates.pre-departure-documents.verify');
    Route::post('pre-departure-documents/{document}/reject', [PreDepartureDocumentController::class, 'reject'])
        ->name('candidates.pre-departure-documents.reject');

    // Licenses
    Route::post('licenses', [CandidateLicenseController::class, 'store'])
        ->name('candidates.licenses.store');
    Route::put('licenses/{license}', [CandidateLicenseController::class, 'update'])
        ->name('candidates.licenses.update');
    Route::delete('licenses/{license}', [CandidateLicenseController::class, 'destroy'])
        ->name('candidates.licenses.destroy');
});

// Reports
Route::middleware(['auth'])->prefix('reports/pre-departure')->group(function () {
    Route::get('individual/{candidate}', [PreDepartureReportController::class, 'individualReport'])
        ->name('reports.pre-departure.individual');
    Route::get('bulk', [PreDepartureReportController::class, 'bulkReport'])
        ->name('reports.pre-departure.bulk');
    Route::get('missing', [PreDepartureReportController::class, 'missingDocumentsReport'])
        ->name('reports.pre-departure.missing');
});
```

#### API Routes (`routes/api.php`)
```php
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Pre-Departure Documents
    Route::prefix('candidates/{candidate}')->group(function () {
        Route::get('pre-departure-documents', [Api\PreDepartureDocumentApiController::class, 'index']);
        Route::post('pre-departure-documents', [Api\PreDepartureDocumentApiController::class, 'store']);
        Route::get('pre-departure-documents/{document}', [Api\PreDepartureDocumentApiController::class, 'show']);
        Route::delete('pre-departure-documents/{document}', [Api\PreDepartureDocumentApiController::class, 'destroy']);
        Route::get('pre-departure-documents/{document}/download', [Api\PreDepartureDocumentApiController::class, 'download']);
        Route::post('pre-departure-documents/{document}/verify', [Api\PreDepartureDocumentApiController::class, 'verify']);
        Route::post('pre-departure-documents/{document}/reject', [Api\PreDepartureDocumentApiController::class, 'reject']);
    });
});
```

### Views

#### New Blade View: `candidates/pre-departure-documents/index.blade.php`
**Components:**
1. Header with candidate info and completion status badge
2. Mandatory documents section (5 cards with upload areas)
3. Optional documents section (3 cards)
4. Licenses section (dynamic form)
5. Action buttons (Save, Continue to Screening)

**Key Features:**
- Drag-and-drop file upload
- Progress indicator (5/8 documents uploaded)
- Document preview thumbnails
- Verification status badges
- Edit/Delete buttons (conditional on permissions)
- Inline validation messages

---

## Implementation Tasks

### Phase 1: Database Setup (CL-002, CL-003, CL-004)

#### Task 1.1: Create Migration for Candidate Licenses
**File:** `database/migrations/YYYY_MM_DD_HHMMSS_create_candidate_licenses_table.php`

**Steps:**
1. Create migration: `php artisan make:migration create_candidate_licenses_table`
2. Define schema as per architecture (above)
3. Add indexes for performance
4. Test rollback

**Validation:**
- [ ] Migration runs without errors
- [ ] Table structure matches specification
- [ ] Foreign keys properly constrained
- [ ] Rollback works correctly

---

#### Task 1.2: Create Document Checklist Seeder
**File:** `database/seeders/DocumentChecklistSeeder.php`

**Steps:**
1. Create seeder: `php artisan make:seeder DocumentChecklistSeeder`
2. Add 8 predefined checklist items (5 mandatory + 3 optional)
3. Include proper display_order and descriptions
4. Call seeder in `DatabaseSeeder.php`

**Validation:**
- [ ] Seeder creates all 8 checklist items
- [ ] Items properly categorized (mandatory/optional)
- [ ] Display order correct
- [ ] Seeder is idempotent (can run multiple times)

---

#### Task 1.3: Run Migrations and Seeders
**Commands:**
```bash
php artisan migrate
php artisan db:seed --class=DocumentChecklistSeeder
```

**Validation:**
- [ ] `document_checklists` table has 8 records
- [ ] `candidate_licenses` table exists and is empty
- [ ] No database errors

---

### Phase 2: Models and Relationships (CL-002, CL-004)

#### Task 2.1: Create CandidateLicense Model
**File:** `app/Models/CandidateLicense.php`

**Steps:**
1. Create model: `php artisan make:model CandidateLicense`
2. Add fillable fields, casts, relationships
3. Add helper methods: `isExpired()`, `isExpiringSoon()`

**Validation:**
- [ ] Model properly configured
- [ ] Relationship to Candidate works
- [ ] Date casting works correctly
- [ ] Helper methods return expected values

**Test File:** `tests/Unit/CandidateLicenseTest.php`

---

#### Task 2.2: Update Candidate Model
**File:** `app/Models/Candidate.php`

**Steps:**
1. Add `preDepartureDocuments()` relationship
2. Add `licenses()` relationship
3. Add `hasCompletedPreDepartureDocuments()` method
4. Add `getPreDepartureDocumentStatus()` method

**Validation:**
- [ ] Relationships return correct data
- [ ] Completion check logic accurate
- [ ] Status method returns proper array structure

**Test File:** `tests/Unit/CandidatePreDepartureTest.php`

---

### Phase 3: Services (CL-001, CL-005, CL-006)

#### Task 3.1: Create PreDepartureDocumentService
**File:** `app/Services/PreDepartureDocumentService.php`

**Steps:**
1. Create service class
2. Implement file upload with validation
3. Implement verification workflow methods
4. Implement permission checking
5. Implement report generation methods
6. Add comprehensive error handling

**Methods to Implement:**
```php
uploadDocument(Candidate $candidate, DocumentChecklist $checklist, UploadedFile $file, array $metadata)
verifyDocument(PreDepartureDocument $document, User $verifier, ?string $notes)
rejectDocument(PreDepartureDocument $document, User $verifier, string $reason)
canEditDocuments(Candidate $candidate, User $user)
getCompletionStatus(Candidate $candidate)
generateIndividualReport(Candidate $candidate, string $format)
generateBulkReport(array $filters, string $format)
generateMissingDocumentsReport(array $filters)
```

**Validation:**
- [ ] File upload stores files securely
- [ ] Verification updates timestamps correctly
- [ ] Permission checks align with policies
- [ ] Reports generate with correct data

**Test File:** `tests/Unit/PreDepartureDocumentServiceTest.php`

---

### Phase 4: Policies (CL-005)

#### Task 4.1: Create PreDepartureDocumentPolicy
**File:** `app/Policies/PreDepartureDocumentPolicy.php`

**Steps:**
1. Create policy: `php artisan make:policy PreDepartureDocumentPolicy --model=PreDepartureDocument`
2. Implement all authorization methods (viewAny, view, create, update, delete, verify, reject)
3. Follow role-based access rules from architecture
4. Register policy in `AuthServiceProvider`

**Validation:**
- [ ] Super Admin has full access
- [ ] Campus Admin restricted to their campus
- [ ] OEP restricted to their candidates
- [ ] Edit permissions respect candidate status
- [ ] Verified documents cannot be edited (except Super Admin)

**Test File:** `tests/Unit/PreDepartureDocumentPolicyTest.php`

---

#### Task 4.2: Create CandidateLicensePolicy
**File:** `app/Policies/CandidateLicensePolicy.php`

**Steps:**
1. Create policy
2. Implement create, update, delete methods
3. Similar rules to PreDepartureDocumentPolicy

**Test File:** `tests/Unit/CandidateLicensePolicyTest.php`

---

### Phase 5: Form Requests (CL-002, CL-003)

#### Task 5.1: Create StorePreDepartureDocumentRequest
**File:** `app/Http/Requests/StorePreDepartureDocumentRequest.php`

**Steps:**
1. Create request: `php artisan make:request StorePreDepartureDocumentRequest`
2. Add validation rules for file upload
3. Add conditional validation based on document type
4. Implement `authorize()` method using policy
5. Add custom error messages

**Validation Rules:**
```php
'document_checklist_id' => 'required|exists:document_checklists,id',
'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
'notes' => 'nullable|string|max:500',
'document_number' => 'required_if:needs_number,true|string|max:50',
'issue_date' => 'nullable|date|before_or_equal:today',
'expiry_date' => 'nullable|date|after:today',
```

**Validation:**
- [ ] File validation works (type, size)
- [ ] Conditional rules trigger correctly
- [ ] Authorization check works
- [ ] Error messages clear

**Test File:** `tests/Feature/StorePreDepartureDocumentRequestTest.php`

---

#### Task 5.2: Create StoreCandidateLicenseRequest
**File:** `app/Http/Requests/StoreCandidateLicenseRequest.php`

**Validation Rules:**
```php
'license_type' => 'required|in:driving,professional',
'license_name' => 'required|string|max:100',
'license_number' => 'required|string|max:50',
'license_category' => 'nullable|string|max:50',
'issuing_authority' => 'nullable|string|max:150',
'issue_date' => 'nullable|date|before_or_equal:today',
'expiry_date' => 'nullable|date|after:issue_date',
'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
```

---

### Phase 6: Controllers (CL-001, CL-002, CL-003, CL-004, CL-005)

#### Task 6.1: Create PreDepartureDocumentController
**File:** `app/Http/Controllers/PreDepartureDocumentController.php`

**Methods to Implement:**

1. **index(Candidate $candidate)**
   - Show document collection page
   - Load all checklist items with uploaded documents
   - Calculate completion status
   - Return view with data

2. **store(Candidate $candidate, StorePreDepartureDocumentRequest $request)**
   - Authorize action
   - Use service to upload document
   - Log activity
   - Return JSON success response

3. **destroy(Candidate $candidate, PreDepartureDocument $document)**
   - Authorize action
   - Delete file from storage
   - Delete database record
   - Log activity
   - Return JSON success

4. **download(Candidate $candidate, PreDepartureDocument $document)**
   - Authorize action
   - Log download activity
   - Return file download response

5. **verify(Candidate $candidate, PreDepartureDocument $document)**
   - Authorize action (verify permission)
   - Use service to verify
   - Log activity
   - Return JSON success

6. **reject(Candidate $candidate, PreDepartureDocument $document)**
   - Authorize action
   - Use service to reject
   - Log activity
   - Return JSON success

**Validation:**
- [ ] All methods check authorization
- [ ] File operations work correctly
- [ ] Activity logging functional
- [ ] Error handling comprehensive
- [ ] JSON responses properly formatted

**Test File:** `tests/Feature/PreDepartureDocumentControllerTest.php`

---

#### Task 6.2: Create API Controller
**File:** `app/Http/Controllers/Api/PreDepartureDocumentApiController.php`

**Methods:**
- Same as web controller but return API resources
- Use `PreDepartureDocumentResource` for transformations
- Include pagination for index method
- Add query filters (verified, pending, etc.)

**Test File:** `tests/Feature/Api/PreDepartureDocumentApiTest.php`

---

#### Task 6.3: Create CandidateLicenseController
**File:** `app/Http/Controllers/CandidateLicenseController.php`

**Methods:**
1. `store()` - Add new license
2. `update()` - Update existing license
3. `destroy()` - Delete license

**Test File:** `tests/Feature/CandidateLicenseControllerTest.php`

---

#### Task 6.4: Create PreDepartureReportController (CL-006)
**File:** `app/Http/Controllers/PreDepartureReportController.php`

**Methods:**

1. **individualReport(Candidate $candidate, $format = 'pdf')**
   - Authorize access to candidate
   - Use service to generate report
   - Return PDF or Excel download

2. **bulkReport(Request $request)**
   - Validate filters (campus, date range, status)
   - Apply role-based filtering
   - Use service to generate report
   - Return Excel download

3. **missingDocumentsReport(Request $request)**
   - Validate filters
   - Use service to generate report
   - Return view or Excel

**Validation:**
- [ ] Reports generate correctly
- [ ] Role-based filtering works
- [ ] PDF formatting correct
- [ ] Excel export includes all data
- [ ] Performance acceptable for large datasets

**Test File:** `tests/Feature/PreDepartureReportControllerTest.php`

---

### Phase 7: API Resources

#### Task 7.1: Create PreDepartureDocumentResource
**File:** `app/Http/Resources/PreDepartureDocumentResource.php`

**Structure:**
```php
public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'candidate_id' => $this->candidate_id,
        'document_checklist' => [
            'id' => $this->documentChecklist->id,
            'name' => $this->documentChecklist->name,
            'code' => $this->documentChecklist->code,
            'is_mandatory' => $this->documentChecklist->is_mandatory,
        ],
        'file_name' => $this->original_filename,
        'file_size' => $this->file_size,
        'mime_type' => $this->mime_type,
        'uploaded_at' => $this->uploaded_at,
        'uploaded_by' => $this->whenLoaded('uploader', fn() => [
            'id' => $this->uploader->id,
            'name' => $this->uploader->name,
        ]),
        'is_verified' => $this->isVerified(),
        'verified_at' => $this->verified_at,
        'verified_by' => $this->whenLoaded('verifier', fn() => [
            'id' => $this->verifier?->id,
            'name' => $this->verifier?->name,
        ]),
        'verification_notes' => $this->when($this->isVerified(), $this->verification_notes),
        'download_url' => route('api.candidates.pre-departure-documents.download', [
            'candidate' => $this->candidate_id,
            'document' => $this->id
        ]),
        'created_at' => $this->created_at,
        'updated_at' => $this->updated_at,
    ];
}
```

---

#### Task 7.2: Create CandidateLicenseResource
**File:** `app/Http/Resources/CandidateLicenseResource.php`

---

### Phase 8: Routes Registration

#### Task 8.1: Register Web Routes
**File:** `routes/web.php`

**Steps:**
1. Add pre-departure documents routes
2. Add candidate licenses routes
3. Add report routes
4. Test all routes accessible

**Validation:**
- [ ] `php artisan route:list` shows all new routes
- [ ] Route names consistent with naming convention
- [ ] Middleware applied correctly

---

#### Task 8.2: Register API Routes
**File:** `routes/api.php`

---

### Phase 9: Views and UI (CL-001, CL-002, CL-003, CL-004)

#### Task 9.1: Create Main Pre-Departure Documents Page
**File:** `resources/views/candidates/pre-departure-documents/index.blade.php`

**Components:**
1. **Header Section**
   - Candidate name, BTEVTA ID, Photo
   - Completion progress bar
   - Status badge (Complete/Incomplete)

2. **Mandatory Documents Section**
   - Card for each of 5 mandatory documents
   - Upload area with drag-and-drop
   - Document metadata form (number, dates)
   - Preview thumbnail
   - Verification status badge
   - Edit/Delete buttons (conditional)

3. **Optional Documents Section**
   - Similar cards for 3 optional documents
   - Clearly marked as "Optional"

4. **Licenses Section**
   - Dynamic form to add multiple licenses
   - File upload for each license
   - Edit/Delete functionality

5. **Action Buttons**
   - "Save Draft" button
   - "Submit for Screening" button (enabled only when all mandatory docs uploaded)

**JavaScript Requirements:**
- Drag-and-drop file upload
- Client-side file validation
- Progress indicators
- AJAX form submission
- Dynamic license form rows

**Validation:**
- [ ] UI renders correctly
- [ ] File upload works
- [ ] Form validation shows errors
- [ ] Permissions hide/show elements correctly
- [ ] Mobile responsive

---

#### Task 9.2: Add Navigation Menu Item
**File:** Update candidate detail sidebar navigation

**Steps:**
1. Add "Pre-Departure Documents" menu item
2. Show completion badge (5/8)
3. Highlight if incomplete

---

#### Task 9.3: Create Report Views
**Files:**
- `resources/views/reports/pre-departure/bulk.blade.php`
- `resources/views/reports/pre-departure/missing.blade.php`

---

### Phase 10: Workflow Integration (CL-005)

#### Task 10.1: Update Candidate Status Transition Logic
**File:** `app/Models/Candidate.php` or `app/Services/CandidateWorkflowService.php`

**Steps:**
1. Modify `canTransitionToScreening()` method
2. Add check: `if (!$this->hasCompletedPreDepartureDocuments()) return false;`
3. Update screening controller to enforce this check
4. Add validation error message

**Validation:**
- [ ] Cannot move to screening without complete documents
- [ ] Error message displayed clearly
- [ ] Workflow enforced in both web and API

**Test File:** `tests/Feature/CandidateWorkflowTest.php`

---

#### Task 10.2: Update CandidateScreening Logic
**File:** `app/Http/Controllers/CandidateScreeningController.php`

**Steps:**
1. In `create()` or `initiate()` method, add check before creating screening
2. Return validation error if documents incomplete
3. Redirect to documents page with message

---

### Phase 11: Testing (All Requirements)

#### Task 11.1: Unit Tests - Models

**File:** `tests/Unit/CandidateLicenseTest.php`
```php
test_creates_license_with_valid_data()
test_belongs_to_candidate()
test_is_expired_returns_true_for_past_date()
test_is_expired_returns_false_for_future_date()
```

**File:** `tests/Unit/CandidatePreDepartureTest.php`
```php
test_has_pre_departure_documents_relationship()
test_has_licenses_relationship()
test_has_completed_pre_departure_documents_returns_true_when_complete()
test_has_completed_pre_departure_documents_returns_false_when_incomplete()
test_get_pre_departure_document_status_returns_correct_counts()
```

**File:** `tests/Unit/PreDepartureDocumentTest.php`
```php
test_belongs_to_candidate()
test_belongs_to_document_checklist()
test_is_verified_returns_true_when_verified()
test_scope_verified_filters_correctly()
```

---

#### Task 11.2: Unit Tests - Services

**File:** `tests/Unit/PreDepartureDocumentServiceTest.php`
```php
test_upload_document_stores_file()
test_upload_document_creates_database_record()
test_verify_document_sets_verified_at()
test_verify_document_sets_verifier()
test_reject_document_clears_verified_at()
test_can_edit_documents_returns_true_for_new_status()
test_can_edit_documents_returns_false_for_screening_status()
test_get_completion_status_returns_correct_counts()
```

---

#### Task 11.3: Unit Tests - Policies

**File:** `tests/Unit/PreDepartureDocumentPolicyTest.php`
```php
test_super_admin_can_view_any()
test_campus_admin_can_view_only_their_campus()
test_oep_can_view_only_their_candidates()
test_super_admin_can_always_create()
test_campus_admin_can_create_only_for_new_status()
test_cannot_create_for_screening_status()
test_super_admin_can_always_update()
test_campus_admin_cannot_update_verified_documents()
test_campus_admin_can_update_rejected_documents()
test_super_admin_can_verify()
test_project_director_can_verify()
test_campus_admin_can_verify_their_campus()
test_oep_cannot_verify()
```

---

#### Task 11.4: Feature Tests - Controllers

**File:** `tests/Feature/PreDepartureDocumentControllerTest.php`
```php
test_index_displays_document_collection_page()
test_index_shows_completion_status()
test_store_uploads_document_successfully()
test_store_fails_with_invalid_file_type()
test_store_fails_with_file_too_large()
test_store_requires_document_checklist_id()
test_destroy_deletes_document()
test_destroy_removes_file_from_storage()
test_destroy_fails_for_unauthorized_user()
test_download_returns_file()
test_download_logs_activity()
test_verify_marks_document_as_verified()
test_verify_requires_verify_permission()
test_reject_clears_verification()
test_reject_allows_re_upload()
```

**File:** `tests/Feature/CandidateLicenseControllerTest.php`
```php
test_store_creates_license()
test_store_uploads_file_if_provided()
test_update_modifies_license()
test_destroy_deletes_license()
test_unauthorized_user_cannot_manage_licenses()
```

**File:** `tests/Feature/PreDepartureReportControllerTest.php`
```php
test_individual_report_generates_pdf()
test_individual_report_generates_excel()
test_individual_report_includes_all_documents()
test_bulk_report_filters_by_campus()
test_bulk_report_filters_by_date_range()
test_bulk_report_respects_role_permissions()
test_missing_documents_report_shows_incomplete_candidates()
test_missing_documents_report_excludes_complete_candidates()
```

---

#### Task 11.5: Feature Tests - API

**File:** `tests/Feature/Api/PreDepartureDocumentApiTest.php`
```php
test_api_index_returns_documents()
test_api_index_paginates_results()
test_api_index_filters_by_verified_status()
test_api_store_uploads_document()
test_api_store_returns_resource()
test_api_show_returns_document_details()
test_api_destroy_deletes_document()
test_api_download_returns_file()
test_api_verify_marks_verified()
test_api_reject_clears_verification()
test_api_requires_authentication()
test_api_enforces_authorization()
```

---

#### Task 11.6: Integration Tests - Workflow

**File:** `tests/Feature/CandidateWorkflowIntegrationTest.php`
```php
test_cannot_transition_to_screening_without_documents()
test_can_transition_to_screening_with_complete_documents()
test_screening_controller_blocks_incomplete_documents()
test_screening_controller_allows_complete_documents()
test_documents_become_read_only_after_screening_starts()
test_super_admin_can_edit_documents_after_screening_starts()
```

---

#### Task 11.7: Browser Tests (Optional but Recommended)

**File:** `tests/Browser/PreDepartureDocumentUploadTest.php`
```php
test_user_can_upload_document_via_drag_and_drop()
test_user_sees_completion_progress()
test_submit_to_screening_button_disabled_until_complete()
test_submit_to_screening_button_enabled_when_complete()
test_user_can_verify_document()
test_user_can_reject_document()
```

---

### Phase 12: Documentation and Deployment

#### Task 12.1: Update API Documentation
**File:** `docs/api/pre-departure-documents.md`

**Content:**
- Endpoint reference
- Request/response examples
- Error codes
- Authentication requirements

---

#### Task 12.2: Update User Manual
**File:** `docs/user-manual/pre-departure-documents.md`

**Content:**
- Step-by-step guide for uploading documents
- Document requirements (formats, sizes)
- Verification process explanation
- Screenshots

---

#### Task 12.3: Create Migration Guide
**File:** `docs/deployment/module-1-migration.md`

**Content:**
- Pre-deployment checklist
- Database migration steps
- Seeding instructions
- Rollback procedures
- Data migration for existing candidates (if applicable)

---

#### Task 12.4: Run Final Tests
```bash
# Run all tests
php artisan test

# Run specific test suites
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run with coverage
php artisan test --coverage

# Run browser tests
php artisan dusk
```

**Validation:**
- [ ] All tests pass
- [ ] Code coverage > 90% for new code
- [ ] No breaking changes to existing features

---

## Testing Strategy

### Test Coverage Requirements

**Target:** 90%+ coverage for all new code

**Test Pyramid:**
1. **Unit Tests (60%)** - Models, Services, Policies, Requests
2. **Feature Tests (30%)** - Controllers, API endpoints, Workflow integration
3. **Browser Tests (10%)** - Critical user flows

### Test Data Setup

**Factories to Create:**
- `CandidateLicenseFactory`
- `PreDepartureDocumentFactory` (if not exists)

**Seeders for Testing:**
- `TestDocumentChecklistSeeder` - Seed test database with checklist items

### Test Scenarios Matrix

| Scenario | Unit | Feature | Browser |
|----------|------|---------|---------|
| Document upload | ✓ Service | ✓ Controller | ✓ UI |
| Document verification | ✓ Service | ✓ Controller | ✗ |
| Document rejection | ✓ Service | ✓ Controller | ✗ |
| Permission checks | ✓ Policy | ✓ Controller | ✗ |
| Workflow gate | ✓ Model | ✓ Integration | ✓ UI |
| License management | ✓ Model | ✓ Controller | ✗ |
| Report generation | ✓ Service | ✓ Controller | ✗ |
| API endpoints | ✗ | ✓ API | ✗ |

### CI/CD Integration

**GitHub Actions Workflow:**
```yaml
- name: Run Tests
  run: php artisan test --parallel

- name: Check Code Coverage
  run: php artisan test --coverage --min=90

- name: Run Static Analysis
  run: ./vendor/bin/phpstan analyse
```

---

## Deployment Checklist

### Pre-Deployment

- [ ] All tests passing locally
- [ ] Code review completed
- [ ] Database migrations tested (up and down)
- [ ] Seeders tested
- [ ] API documentation updated
- [ ] User manual updated
- [ ] Rollback plan documented

### Deployment Steps

1. **Backup Database**
   ```bash
   php artisan backup:run --only-db
   ```

2. **Enable Maintenance Mode**
   ```bash
   php artisan down --message="Module 1 Deployment" --retry=60
   ```

3. **Pull Latest Code**
   ```bash
   git pull origin claude/implement-module-1-Nz3SL
   ```

4. **Install Dependencies**
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

5. **Run Migrations**
   ```bash
   php artisan migrate --force
   ```

6. **Run Seeders**
   ```bash
   php artisan db:seed --class=DocumentChecklistSeeder --force
   ```

7. **Clear Caches**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

8. **Run Tests on Production (if staging)**
   ```bash
   php artisan test --env=staging
   ```

9. **Disable Maintenance Mode**
   ```bash
   php artisan up
   ```

### Post-Deployment

- [ ] Verify all routes accessible
- [ ] Test document upload functionality
- [ ] Test verification workflow
- [ ] Test report generation
- [ ] Monitor error logs
- [ ] Verify performance metrics
- [ ] Notify users of new feature

### Rollback Procedure

If issues occur:
```bash
php artisan down
git revert <commit-hash>
composer install --no-dev --optimize-autoloader
php artisan migrate:rollback --step=1
php artisan config:cache
php artisan route:cache
php artisan up
```

---

## Success Criteria

### Functional Requirements
- ✅ All 5 mandatory documents can be uploaded
- ✅ All 3 optional documents can be uploaded
- ✅ Licenses can be added, edited, deleted
- ✅ Documents cannot be edited after screening starts (except Super Admin)
- ✅ Candidates cannot proceed to screening without complete mandatory documents
- ✅ Verification workflow functional
- ✅ All 3 report types generate correctly
- ✅ API endpoints functional and documented

### Non-Functional Requirements
- ✅ Page load time < 2 seconds
- ✅ File upload success rate > 99%
- ✅ Report generation < 5 seconds (individual), < 30 seconds (bulk)
- ✅ Test coverage > 90%
- ✅ No security vulnerabilities (OWASP Top 10)
- ✅ Mobile responsive design
- ✅ Accessibility compliance (WCAG 2.1 Level A)

### User Acceptance Criteria
- ✅ Campus admins can upload documents for their candidates
- ✅ OEPs can upload documents for their assigned candidates
- ✅ Super Admin can verify/reject documents
- ✅ Users receive clear error messages for validation failures
- ✅ Progress indicators show completion status
- ✅ Reports are accurate and well-formatted

---

## Risk Mitigation

### Identified Risks

1. **Large File Uploads**
   - Risk: Timeouts, memory issues
   - Mitigation: Implement chunked uploads, progress indicators, file size limits

2. **Concurrent Document Edits**
   - Risk: Data loss, conflicts
   - Mitigation: Optimistic locking, version control, clear UI warnings

3. **Storage Capacity**
   - Risk: Disk space exhaustion
   - Mitigation: Monitor storage, implement retention policies, cloud storage option

4. **Performance Degradation**
   - Risk: Slow report generation with large datasets
   - Mitigation: Query optimization, eager loading, caching, background jobs

5. **Permission Bypass**
   - Risk: Unauthorized access
   - Mitigation: Comprehensive policy tests, middleware enforcement, audit logging

---

## Appendix A: File Checklist

### New Files to Create

**Migrations:**
- [ ] `YYYY_MM_DD_HHMMSS_create_candidate_licenses_table.php`

**Seeders:**
- [ ] `DocumentChecklistSeeder.php`

**Models:**
- [ ] `CandidateLicense.php`

**Services:**
- [ ] `PreDepartureDocumentService.php`

**Policies:**
- [ ] `PreDepartureDocumentPolicy.php`
- [ ] `CandidateLicensePolicy.php`

**Form Requests:**
- [ ] `StorePreDepartureDocumentRequest.php`
- [ ] `UpdatePreDepartureDocumentRequest.php`
- [ ] `StoreCandidateLicenseRequest.php`
- [ ] `UpdateCandidateLicenseRequest.php`

**Controllers:**
- [ ] `PreDepartureDocumentController.php`
- [ ] `Api/PreDepartureDocumentApiController.php`
- [ ] `CandidateLicenseController.php`
- [ ] `PreDepartureReportController.php`

**API Resources:**
- [ ] `PreDepartureDocumentResource.php`
- [ ] `CandidateLicenseResource.php`

**Views:**
- [ ] `candidates/pre-departure-documents/index.blade.php`
- [ ] `candidates/pre-departure-documents/_mandatory-section.blade.php`
- [ ] `candidates/pre-departure-documents/_optional-section.blade.php`
- [ ] `candidates/pre-departure-documents/_licenses-section.blade.php`
- [ ] `reports/pre-departure/bulk.blade.php`
- [ ] `reports/pre-departure/missing.blade.php`

**Tests - Unit:**
- [ ] `CandidateLicenseTest.php`
- [ ] `CandidatePreDepartureTest.php`
- [ ] `PreDepartureDocumentTest.php`
- [ ] `PreDepartureDocumentServiceTest.php`
- [ ] `PreDepartureDocumentPolicyTest.php`
- [ ] `CandidateLicensePolicyTest.php`

**Tests - Feature:**
- [ ] `PreDepartureDocumentControllerTest.php`
- [ ] `CandidateLicenseControllerTest.php`
- [ ] `PreDepartureReportControllerTest.php`
- [ ] `Api/PreDepartureDocumentApiTest.php`
- [ ] `CandidateWorkflowIntegrationTest.php`
- [ ] `StorePreDepartureDocumentRequestTest.php`

**Tests - Browser:**
- [ ] `PreDepartureDocumentUploadTest.php`

**Documentation:**
- [ ] `docs/api/pre-departure-documents.md`
- [ ] `docs/user-manual/pre-departure-documents.md`
- [ ] `docs/deployment/module-1-migration.md`

### Files to Modify

**Models:**
- [ ] `app/Models/Candidate.php` - Add relationships and helper methods

**Existing Controllers:**
- [ ] `app/Http/Controllers/CandidateScreeningController.php` - Add document check

**Routes:**
- [ ] `routes/web.php` - Add new routes
- [ ] `routes/api.php` - Add API routes

**Providers:**
- [ ] `app/Providers/AuthServiceProvider.php` - Register policies

**Views:**
- [ ] Candidate detail sidebar navigation - Add menu item

**Seeders:**
- [ ] `database/seeders/DatabaseSeeder.php` - Call DocumentChecklistSeeder

---

## Appendix B: Code Snippets

### Helper Method: Check Document Completion

```php
// In Candidate model
public function hasCompletedPreDepartureDocuments(): bool
{
    $mandatory = DocumentChecklist::mandatory()->active()->pluck('id');
    $uploaded = $this->preDepartureDocuments()
        ->whereIn('document_checklist_id', $mandatory)
        ->count();

    return $uploaded >= $mandatory->count();
}
```

### Helper Method: Get Completion Status

```php
// In Candidate model
public function getPreDepartureDocumentStatus(): array
{
    $mandatory = DocumentChecklist::mandatory()->active()->get();
    $optional = DocumentChecklist::optional()->active()->get();

    $uploadedIds = $this->preDepartureDocuments()
        ->pluck('document_checklist_id')
        ->toArray();

    return [
        'mandatory_total' => $mandatory->count(),
        'mandatory_uploaded' => $mandatory->filter(fn($doc) => in_array($doc->id, $uploadedIds))->count(),
        'optional_total' => $optional->count(),
        'optional_uploaded' => $optional->filter(fn($doc) => in_array($doc->id, $uploadedIds))->count(),
        'is_complete' => $this->hasCompletedPreDepartureDocuments(),
    ];
}
```

### Workflow Gate Check

```php
// In Candidate model or CandidateWorkflowService
public function canTransitionToScreening(): bool
{
    // Existing checks...
    if ($this->status !== 'new') {
        return false;
    }

    // NEW CHECK: Must have complete pre-departure documents
    if (!$this->hasCompletedPreDepartureDocuments()) {
        return false;
    }

    return true;
}
```

---

## Appendix C: Database Schema Diagram

```
candidates (existing)
    id
    btevta_id
    name
    cnic
    status
    ...
    └── hasMany: pre_departure_documents
    └── hasMany: candidate_licenses

document_checklists (existing)
    id
    name
    code (CNIC, PASSPORT, etc.)
    category (mandatory/optional)
    is_mandatory
    display_order
    └── hasMany: pre_departure_documents

pre_departure_documents (existing)
    id
    candidate_id (FK → candidates)
    document_checklist_id (FK → document_checklists)
    file_path
    original_filename
    mime_type
    file_size
    uploaded_at
    uploaded_by (FK → users)
    verified_at
    verified_by (FK → users)
    verification_notes
    └── belongsTo: candidate
    └── belongsTo: document_checklist
    └── belongsTo: uploader (User)
    └── belongsTo: verifier (User)

candidate_licenses (NEW)
    id
    candidate_id (FK → candidates)
    license_type (driving/professional)
    license_name
    license_number
    license_category
    issuing_authority
    issue_date
    expiry_date
    file_path
    └── belongsTo: candidate
```

---

## Appendix D: UI Mockup Description

### Pre-Departure Documents Page Layout

```
┌─────────────────────────────────────────────────────────┐
│ Candidate: Ali Khan | BTEVTA ID: TLP-2024-00123-4      │
│ Progress: [████████░░] 8/8 Documents | ✓ COMPLETE      │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│ MANDATORY DOCUMENTS (5/5) ✓                            │
├─────────────────────────────────────────────────────────┤
│ ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐    │
│ │  CNIC   │  │Passport │  │Domicile │  │   FRC   │    │
│ │    ✓    │  │    ✓    │  │    ✓    │  │    ✓    │    │
│ │Verified │  │Verified │  │Verified │  │Pending  │    │
│ └─────────┘  └─────────┘  └─────────┘  └─────────┘    │
│                                                         │
│ ┌─────────┐                                            │
│ │   PCC   │                                            │
│ │    ✓    │                                            │
│ │Verified │                                            │
│ └─────────┘                                            │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│ OPTIONAL DOCUMENTS (3/3) ✓                             │
├─────────────────────────────────────────────────────────┤
│ ┌─────────────┐  ┌──────────────┐  ┌─────────┐        │
│ │Pre-Medical  │  │Certifications│  │Resume/CV│        │
│ │      ✓      │  │      ✓       │  │    ✓    │        │
│ │  Verified   │  │   Pending    │  │Verified │        │
│ └─────────────┘  └──────────────┘  └─────────┘        │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│ LICENSES (2)                            [+ Add License] │
├─────────────────────────────────────────────────────────┤
│ • HGV Driving License | #DL123456 | Expires: 2026-05   │
│ • RN Nurse License | #RN789012 | Expires: 2027-12      │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│ [Save Draft]          [Proceed to Screening] (enabled) │
└─────────────────────────────────────────────────────────┘
```

---

**End of Implementation Plan**

This comprehensive plan provides all necessary details for a Sonnet AI or human developer to implement Module 1 completely without skipping any features or functions.
