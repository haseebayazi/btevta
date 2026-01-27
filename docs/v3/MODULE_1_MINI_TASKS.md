# Module 1: Mini-Tasks Breakdown for Sequential Implementation

## Quick Reference
**Total Tasks:** 50 mini-tasks
**Estimated Duration:** Complete sequentially
**Branch:** `claude/implement-module-1-Nz3SL`

---

## PHASE 1: DATABASE SETUP (3 tasks)

### ✅ Task 1.1: Create Candidate Licenses Migration
**Priority:** HIGH
**Files:** `database/migrations/YYYY_MM_DD_HHMMSS_create_candidate_licenses_table.php`

**Action:**
```bash
php artisan make:migration create_candidate_licenses_table
```

**Code to add:**
```php
Schema::create('candidate_licenses', function (Blueprint $table) {
    $table->id();
    $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
    $table->enum('license_type', ['driving', 'professional'])->default('professional');
    $table->string('license_name', 100);
    $table->string('license_number', 50);
    $table->string('license_category', 50)->nullable();
    $table->string('issuing_authority', 150)->nullable();
    $table->date('issue_date')->nullable();
    $table->date('expiry_date')->nullable();
    $table->string('file_path', 500)->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->index('candidate_id');
});
```

**Verify:**
- [ ] Migration file created
- [ ] Schema matches specification
- [ ] Foreign key constraint added

---

### ✅ Task 1.2: Create Document Checklist Seeder
**Priority:** HIGH
**Files:** `database/seeders/DocumentChecklistSeeder.php`

**Action:**
```bash
php artisan make:seeder DocumentChecklistSeeder
```

**Code to add:**
```php
use App\Models\DocumentChecklist;

public function run(): void
{
    $checklists = [
        // Mandatory (5)
        ['name' => 'CNIC (National Identity Card)', 'code' => 'CNIC', 'description' => 'Computerized National Identity Card with 13-digit number', 'category' => 'mandatory', 'is_mandatory' => true, 'display_order' => 1, 'is_active' => true],
        ['name' => 'Passport', 'code' => 'PASSPORT', 'description' => 'Valid passport with at least 6 months validity', 'category' => 'mandatory', 'is_mandatory' => true, 'display_order' => 2, 'is_active' => true],
        ['name' => 'Domicile Certificate', 'code' => 'DOMICILE', 'description' => 'Valid domicile certificate', 'category' => 'mandatory', 'is_mandatory' => true, 'display_order' => 3, 'is_active' => true],
        ['name' => 'FRC (Fingerprint Record Certificate)', 'code' => 'FRC', 'description' => 'Fingerprint Record Certificate from NADRA', 'category' => 'mandatory', 'is_mandatory' => true, 'display_order' => 4, 'is_active' => true],
        ['name' => 'PCC (Police Clearance Certificate)', 'code' => 'PCC', 'description' => 'Police Clearance Certificate', 'category' => 'mandatory', 'is_mandatory' => true, 'display_order' => 5, 'is_active' => true],

        // Optional (3)
        ['name' => 'Pre-Medical Results', 'code' => 'PRE_MEDICAL', 'description' => 'Medical test results', 'category' => 'optional', 'is_mandatory' => false, 'display_order' => 6, 'is_active' => true],
        ['name' => 'Professional Certifications', 'code' => 'CERTIFICATIONS', 'description' => 'Professional or trade certifications', 'category' => 'optional', 'is_mandatory' => false, 'display_order' => 7, 'is_active' => true],
        ['name' => 'Resume/CV', 'code' => 'RESUME', 'description' => 'Curriculum Vitae or Resume', 'category' => 'optional', 'is_mandatory' => false, 'display_order' => 8, 'is_active' => true],
    ];

    foreach ($checklists as $checklist) {
        DocumentChecklist::updateOrCreate(
            ['code' => $checklist['code']],
            $checklist
        );
    }
}
```

**Verify:**
- [ ] Seeder file created
- [ ] 8 checklist items defined (5 mandatory + 3 optional)
- [ ] Uses updateOrCreate for idempotency

---

### ✅ Task 1.3: Run Migrations and Seeders
**Priority:** HIGH

**Action:**
```bash
php artisan migrate
php artisan db:seed --class=DocumentChecklistSeeder
```

**Verify:**
- [ ] Migration successful
- [ ] `candidate_licenses` table exists
- [ ] `document_checklists` has 8 records

---

## PHASE 2: MODELS (4 tasks)

### ✅ Task 2.1: Create CandidateLicense Model
**Priority:** HIGH
**Files:** `app/Models/CandidateLicense.php`

**Action:**
```bash
php artisan make:model CandidateLicense
```

**Code to add:**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class CandidateLicense extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'candidate_id',
        'license_type',
        'license_name',
        'license_number',
        'license_category',
        'issuing_authority',
        'issue_date',
        'expiry_date',
        'file_path',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
    ];

    /**
     * Get the candidate that owns this license
     */
    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    /**
     * Check if license is expired
     */
    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /**
     * Check if license is expiring soon (within 90 days)
     */
    public function isExpiringSoon(): bool
    {
        if (!$this->expiry_date) {
            return false;
        }

        return $this->expiry_date->isFuture()
            && $this->expiry_date->diffInDays(now()) <= 90;
    }

    /**
     * Scope to get expired licenses
     */
    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', now());
    }

    /**
     * Scope to get expiring soon licenses
     */
    public function scopeExpiringSoon($query)
    {
        return $query->where('expiry_date', '>', now())
            ->where('expiry_date', '<=', now()->addDays(90));
    }
}
```

**Verify:**
- [ ] Model created
- [ ] Fillable fields set
- [ ] Casts configured
- [ ] Relationship to Candidate defined
- [ ] Helper methods added

---

### ✅ Task 2.2: Create CandidateLicense Factory
**Priority:** MEDIUM
**Files:** `database/factories/CandidateLicenseFactory.php`

**Action:**
```bash
php artisan make:factory CandidateLicenseFactory --model=CandidateLicense
```

**Code to add:**
```php
<?php

namespace Database\Factories;

use App\Models\Candidate;
use Illuminate\Database\Eloquent\Factories\Factory;

class CandidateLicenseFactory extends Factory
{
    public function definition(): array
    {
        $types = ['driving', 'professional'];
        $type = $this->faker->randomElement($types);

        $drivingNames = ['Car License', 'Motorcycle License', 'HGV License', 'PSV License'];
        $professionalNames = ['RN Nurse License', 'LPN License', 'Electrician License', 'Plumber License'];

        return [
            'candidate_id' => Candidate::factory(),
            'license_type' => $type,
            'license_name' => $type === 'driving'
                ? $this->faker->randomElement($drivingNames)
                : $this->faker->randomElement($professionalNames),
            'license_number' => strtoupper($this->faker->bothify('??######')),
            'license_category' => $type === 'driving' ? $this->faker->randomElement(['B', 'C', 'D']) : null,
            'issuing_authority' => $this->faker->company(),
            'issue_date' => $this->faker->dateTimeBetween('-5 years', '-1 year'),
            'expiry_date' => $this->faker->dateTimeBetween('+1 year', '+5 years'),
            'file_path' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expiry_date' => $this->faker->dateTimeBetween('-2 years', '-1 day'),
        ]);
    }

    public function expiringSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'expiry_date' => $this->faker->dateTimeBetween('now', '+90 days'),
        ]);
    }
}
```

**Verify:**
- [ ] Factory created
- [ ] Definition returns proper data
- [ ] States for expired and expiringSoon

---

### ✅ Task 2.3: Update Candidate Model - Add Relationships
**Priority:** HIGH
**Files:** `app/Models/Candidate.php`

**Action:** Add the following methods to the Candidate model

**Code to add:**
```php
/**
 * Get all pre-departure documents for this candidate
 */
public function preDepartureDocuments()
{
    return $this->hasMany(PreDepartureDocument::class);
}

/**
 * Get all licenses for this candidate
 */
public function licenses()
{
    return $this->hasMany(CandidateLicense::class);
}
```

**Verify:**
- [ ] Relationships added to Candidate model
- [ ] Can access $candidate->preDepartureDocuments
- [ ] Can access $candidate->licenses

---

### ✅ Task 2.4: Update Candidate Model - Add Helper Methods
**Priority:** HIGH
**Files:** `app/Models/Candidate.php`

**Code to add:**
```php
/**
 * Check if candidate has completed all mandatory pre-departure documents
 */
public function hasCompletedPreDepartureDocuments(): bool
{
    $mandatoryDocuments = \App\Models\DocumentChecklist::mandatory()->active()->get();

    if ($mandatoryDocuments->isEmpty()) {
        return true; // No mandatory documents required
    }

    $uploadedDocumentIds = $this->preDepartureDocuments()
        ->pluck('document_checklist_id')
        ->toArray();

    foreach ($mandatoryDocuments as $doc) {
        if (!in_array($doc->id, $uploadedDocumentIds)) {
            return false;
        }
    }

    return true;
}

/**
 * Get pre-departure document completion status
 */
public function getPreDepartureDocumentStatus(): array
{
    $mandatory = \App\Models\DocumentChecklist::mandatory()->active()->get();
    $optional = \App\Models\DocumentChecklist::optional()->active()->get();

    $uploadedIds = $this->preDepartureDocuments()
        ->pluck('document_checklist_id')
        ->toArray();

    $mandatoryUploaded = $mandatory->filter(fn($doc) => in_array($doc->id, $uploadedIds))->count();
    $optionalUploaded = $optional->filter(fn($doc) => in_array($doc->id, $uploadedIds))->count();

    return [
        'mandatory_total' => $mandatory->count(),
        'mandatory_uploaded' => $mandatoryUploaded,
        'mandatory_complete' => $mandatoryUploaded >= $mandatory->count(),
        'optional_total' => $optional->count(),
        'optional_uploaded' => $optionalUploaded,
        'is_complete' => $this->hasCompletedPreDepartureDocuments(),
        'completion_percentage' => $mandatory->count() > 0
            ? round(($mandatoryUploaded / $mandatory->count()) * 100)
            : 100,
    ];
}

/**
 * Get missing mandatory documents
 */
public function getMissingMandatoryDocuments()
{
    $mandatory = \App\Models\DocumentChecklist::mandatory()->active()->get();
    $uploadedIds = $this->preDepartureDocuments()
        ->pluck('document_checklist_id')
        ->toArray();

    return $mandatory->filter(fn($doc) => !in_array($doc->id, $uploadedIds));
}
```

**Verify:**
- [ ] Helper methods added
- [ ] Logic correctly counts uploaded vs required documents
- [ ] Returns proper data structures

---

## PHASE 3: SERVICES (1 task)

### ✅ Task 3.1: Create PreDepartureDocumentService
**Priority:** HIGH
**Files:** `app/Services/PreDepartureDocumentService.php`

**Action:** Create new service class

**Code:** (See full code in separate file - too long for inline)

Create file with these key methods:
- `uploadDocument()` - Handle file upload and DB creation
- `verifyDocument()` - Mark document as verified
- `rejectDocument()` - Reject document with reason
- `canEditDocuments()` - Check if documents can be edited
- `getCompletionStatus()` - Get candidate's document completion status
- `generateIndividualReport()` - Generate PDF/Excel report for one candidate
- `generateBulkReport()` - Generate bulk status report
- `generateMissingDocumentsReport()` - Generate missing docs report

**Verify:**
- [ ] Service class created in `app/Services/`
- [ ] All 8 methods implemented
- [ ] File handling secure (no path traversal)
- [ ] Proper error handling

---

## PHASE 4: POLICIES (2 tasks)

### ✅ Task 4.1: Create PreDepartureDocumentPolicy
**Priority:** HIGH
**Files:** `app/Policies/PreDepartureDocumentPolicy.php`

**Action:**
```bash
php artisan make:policy PreDepartureDocumentPolicy --model=PreDepartureDocument
```

**Code to add:**
```php
<?php

namespace App\Policies;

use App\Models\Candidate;
use App\Models\PreDepartureDocument;
use App\Models\User;

class PreDepartureDocumentPolicy
{
    /**
     * Determine if user can view any pre-departure documents for a candidate
     */
    public function viewAny(User $user, Candidate $candidate): bool
    {
        // Super Admin and Project Director can view all
        if ($user->hasRole(['super_admin', 'project_director'])) {
            return true;
        }

        // Campus Admin can view their campus candidates
        if ($user->hasRole('campus_admin')) {
            return $candidate->campus_id === $user->campus_id;
        }

        // OEP can view their assigned candidates
        if ($user->hasRole('oep')) {
            return $candidate->oep_id === $user->id;
        }

        return false;
    }

    /**
     * Determine if user can view a specific document
     */
    public function view(User $user, PreDepartureDocument $document): bool
    {
        return $this->viewAny($user, $document->candidate);
    }

    /**
     * Determine if user can create documents for a candidate
     */
    public function create(User $user, Candidate $candidate): bool
    {
        // Super Admin can always create
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Others can only create if candidate is in 'new' status
        if ($candidate->status !== 'new') {
            return false;
        }

        // Campus Admin can create for their campus
        if ($user->hasRole('campus_admin')) {
            return $candidate->campus_id === $user->campus_id;
        }

        // OEP can create for their candidates
        if ($user->hasRole('oep')) {
            return $candidate->oep_id === $user->id;
        }

        return false;
    }

    /**
     * Determine if user can update a document
     */
    public function update(User $user, PreDepartureDocument $document): bool
    {
        // Super Admin can always update (with audit log)
        if ($user->hasRole('super_admin')) {
            return true;
        }

        $candidate = $document->candidate;

        // Cannot update if candidate progressed past 'new' status
        if ($candidate->status !== 'new') {
            return false;
        }

        // Cannot update verified documents (unless Super Admin)
        if ($document->isVerified()) {
            return false;
        }

        // Campus Admin can update their campus documents
        if ($user->hasRole('campus_admin')) {
            return $candidate->campus_id === $user->campus_id;
        }

        // OEP can update their candidates' documents
        if ($user->hasRole('oep')) {
            return $candidate->oep_id === $user->id;
        }

        return false;
    }

    /**
     * Determine if user can delete a document
     */
    public function delete(User $user, PreDepartureDocument $document): bool
    {
        // Super Admin can always delete
        if ($user->hasRole('super_admin')) {
            return true;
        }

        $candidate = $document->candidate;

        // Cannot delete if candidate progressed past 'new' status
        if ($candidate->status !== 'new') {
            return false;
        }

        // Campus Admin can delete their campus documents
        if ($user->hasRole('campus_admin')) {
            return $candidate->campus_id === $user->campus_id;
        }

        // OEP can delete their candidates' documents
        if ($user->hasRole('oep')) {
            return $candidate->oep_id === $user->id;
        }

        return false;
    }

    /**
     * Determine if user can verify documents
     */
    public function verify(User $user, PreDepartureDocument $document): bool
    {
        // Super Admin and Project Director can verify
        if ($user->hasRole(['super_admin', 'project_director'])) {
            return true;
        }

        // Campus Admin can verify their campus documents
        if ($user->hasRole('campus_admin')) {
            return $document->candidate->campus_id === $user->campus_id;
        }

        return false;
    }

    /**
     * Determine if user can reject documents
     */
    public function reject(User $user, PreDepartureDocument $document): bool
    {
        return $this->verify($user, $document);
    }
}
```

**Verify:**
- [ ] Policy created
- [ ] All methods implemented
- [ ] Role-based access control correct
- [ ] Super Admin has override permissions

---

### ✅ Task 4.2: Create CandidateLicensePolicy
**Priority:** MEDIUM
**Files:** `app/Policies/CandidateLicensePolicy.php`

**Action:**
```bash
php artisan make:policy CandidateLicensePolicy --model=CandidateLicense
```

**Code:** (Similar structure to PreDepartureDocumentPolicy)

**Verify:**
- [ ] Policy created
- [ ] create, update, delete methods
- [ ] Same permission rules as documents

---

### ✅ Task 4.3: Register Policies
**Priority:** HIGH
**Files:** `app/Providers/AuthServiceProvider.php`

**Code to add:**
```php
protected $policies = [
    // ... existing policies ...
    \App\Models\PreDepartureDocument::class => \App\Policies\PreDepartureDocumentPolicy::class,
    \App\Models\CandidateLicense::class => \App\Policies\CandidateLicensePolicy::class,
];
```

**Verify:**
- [ ] Policies registered
- [ ] `php artisan policy:show` lists new policies

---

## PHASE 5: FORM REQUESTS (2 tasks)

### ✅ Task 5.1: Create StorePreDepartureDocumentRequest
**Priority:** HIGH
**Files:** `app/Http/Requests/StorePreDepartureDocumentRequest.php`

**Action:**
```bash
php artisan make:request StorePreDepartureDocumentRequest
```

**Code:** (See implementation plan for full validation rules)

**Verify:**
- [ ] Request class created
- [ ] Validation rules comprehensive
- [ ] Authorization check uses policy
- [ ] Custom error messages

---

### ✅ Task 5.2: Create StoreCandidateLicenseRequest
**Priority:** MEDIUM
**Files:** `app/Http/Requests/StoreCandidateLicenseRequest.php`

**Action:**
```bash
php artisan make:request StoreCandidateLicenseRequest
```

**Verify:**
- [ ] Request class created
- [ ] Validation for license fields

---

## PHASE 6: CONTROLLERS (4 tasks)

### ✅ Task 6.1: Create PreDepartureDocumentController
**Priority:** HIGH
**Files:** `app/Http/Controllers/PreDepartureDocumentController.php`

**Action:**
```bash
php artisan make:controller PreDepartureDocumentController
```

**Methods to implement:**
1. `index(Candidate $candidate)` - Show document page
2. `store(Candidate $candidate, StorePreDepartureDocumentRequest $request)` - Upload
3. `destroy(Candidate $candidate, PreDepartureDocument $document)` - Delete
4. `download(Candidate $candidate, PreDepartureDocument $document)` - Download
5. `verify(Candidate $candidate, PreDepartureDocument $document)` - Verify
6. `reject(Candidate $candidate, PreDepartureDocument $document, Request $request)` - Reject

**Verify:**
- [ ] Controller created
- [ ] All 6 methods implemented
- [ ] Authorization checks in each method
- [ ] Activity logging

---

### ✅ Task 6.2: Create PreDepartureDocumentApiController
**Priority:** HIGH
**Files:** `app/Http/Controllers/Api/PreDepartureDocumentApiController.php`

**Action:**
```bash
php artisan make:controller Api/PreDepartureDocumentApiController --api
```

**Methods:** Same as web controller but return API resources

**Verify:**
- [ ] API controller created
- [ ] Returns JSON responses
- [ ] Uses PreDepartureDocumentResource

---

### ✅ Task 6.3: Create CandidateLicenseController
**Priority:** MEDIUM
**Files:** `app/Http/Controllers/CandidateLicenseController.php`

**Methods:**
1. `store()` - Add license
2. `update()` - Update license
3. `destroy()` - Delete license

**Verify:**
- [ ] Controller created
- [ ] CRUD operations work

---

### ✅ Task 6.4: Create PreDepartureReportController
**Priority:** MEDIUM
**Files:** `app/Http/Controllers/PreDepartureReportController.php`

**Methods:**
1. `individualReport(Candidate $candidate)` - Individual PDF/Excel
2. `bulkReport(Request $request)` - Bulk Excel
3. `missingDocumentsReport(Request $request)` - Missing docs report

**Verify:**
- [ ] Controller created
- [ ] Report generation works
- [ ] Export formats (PDF, Excel)

---

## PHASE 7: API RESOURCES (2 tasks)

### ✅ Task 7.1: Create PreDepartureDocumentResource
**Priority:** HIGH
**Files:** `app/Http/Resources/PreDepartureDocumentResource.php`

**Action:**
```bash
php artisan make:resource PreDepartureDocumentResource
```

**Verify:**
- [ ] Resource created
- [ ] Transforms data correctly
- [ ] Includes relationships

---

### ✅ Task 7.2: Create CandidateLicenseResource
**Priority:** MEDIUM
**Files:** `app/Http/Resources/CandidateLicenseResource.php`

**Action:**
```bash
php artisan make:resource CandidateLicenseResource
```

---

## PHASE 8: ROUTES (2 tasks)

### ✅ Task 8.1: Register Web Routes
**Priority:** HIGH
**Files:** `routes/web.php`

**Code to add:**
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

**Verify:**
- [ ] Routes registered
- [ ] `php artisan route:list | grep pre-departure` shows routes

---

### ✅ Task 8.2: Register API Routes
**Priority:** HIGH
**Files:** `routes/api.php`

**Code to add:**
```php
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
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

**Verify:**
- [ ] API routes registered
- [ ] Routes use Sanctum auth

---

## PHASE 9: WORKFLOW INTEGRATION (2 tasks)

### ✅ Task 9.1: Update Candidate Workflow - Prevent Screening Without Docs
**Priority:** HIGH
**Files:** `app/Models/Candidate.php` or workflow service

**Code to add:**
```php
/**
 * Check if candidate can transition to screening status
 */
public function canTransitionToScreening(): bool
{
    // Must be in 'new' status
    if ($this->status !== 'new') {
        return false;
    }

    // NEW: Must have completed all mandatory pre-departure documents
    if (!$this->hasCompletedPreDepartureDocuments()) {
        return false;
    }

    // ... other existing checks ...

    return true;
}
```

**Verify:**
- [ ] Method updated
- [ ] Returns false if documents incomplete

---

### ✅ Task 9.2: Update Screening Controller - Enforce Document Check
**Priority:** HIGH
**Files:** `app/Http/Controllers/CandidateScreeningController.php`

**Code to add:** In the screening initiation method:
```php
public function create(Candidate $candidate)
{
    // Check if candidate can be screened
    if (!$candidate->canTransitionToScreening()) {
        return redirect()
            ->route('candidates.pre-departure-documents.index', $candidate)
            ->with('error', 'Cannot proceed to screening. Please complete all mandatory pre-departure documents first.');
    }

    // ... existing screening logic ...
}
```

**Verify:**
- [ ] Screening blocked without complete documents
- [ ] Error message shown
- [ ] Redirect to documents page

---

## PHASE 10: VIEWS (Skipped for API-first approach, but templates if needed)

### Task 10.1-10.3: Create Blade Views
**Priority:** MEDIUM (if web UI needed)

Files to create:
- [ ] `resources/views/candidates/pre-departure-documents/index.blade.php`
- [ ] `resources/views/candidates/pre-departure-documents/_mandatory-section.blade.php`
- [ ] `resources/views/candidates/pre-departure-documents/_optional-section.blade.php`
- [ ] `resources/views/candidates/pre-departure-documents/_licenses-section.blade.php`

*Note: Can be implemented later if API-first approach is taken*

---

## PHASE 11: TESTING (15 tasks)

### ✅ Task 11.1: Unit Test - CandidateLicense Model
**Priority:** HIGH
**Files:** `tests/Unit/CandidateLicenseTest.php`

**Tests:**
- [ ] Creates license with valid data
- [ ] Belongs to candidate
- [ ] isExpired() returns correct value
- [ ] isExpiringSoon() works

---

### ✅ Task 11.2: Unit Test - Candidate Pre-Departure Methods
**Priority:** HIGH
**Files:** `tests/Unit/CandidatePreDepartureTest.php`

**Tests:**
- [ ] hasCompletedPreDepartureDocuments() when complete
- [ ] hasCompletedPreDepartureDocuments() when incomplete
- [ ] getPreDepartureDocumentStatus() returns correct counts

---

### ✅ Task 11.3: Unit Test - PreDepartureDocumentService
**Priority:** HIGH
**Files:** `tests/Unit/PreDepartureDocumentServiceTest.php`

**Tests:**
- [ ] uploadDocument() stores file
- [ ] verifyDocument() sets timestamps
- [ ] rejectDocument() clears verification
- [ ] canEditDocuments() enforces status rules

---

### ✅ Task 11.4: Unit Test - PreDepartureDocumentPolicy
**Priority:** HIGH
**Files:** `tests/Unit/PreDepartureDocumentPolicyTest.php`

**Tests:**
- [ ] Super admin can view any
- [ ] Campus admin restricted to their campus
- [ ] OEP restricted to their candidates
- [ ] Cannot create for screening status
- [ ] Cannot update verified documents
- [ ] Can verify with proper role

---

### ✅ Task 11.5-11.10: Feature Tests
**Priority:** HIGH
**Files:**
- `tests/Feature/PreDepartureDocumentControllerTest.php`
- `tests/Feature/CandidateLicenseControllerTest.php`
- `tests/Feature/PreDepartureReportControllerTest.php`
- `tests/Feature/Api/PreDepartureDocumentApiTest.php`
- `tests/Feature/CandidateWorkflowIntegrationTest.php`

**Tests:** See main implementation plan for detailed test cases

---

### ✅ Task 11.11: Run All Tests
**Priority:** HIGH

**Action:**
```bash
php artisan test
php artisan test --coverage --min=90
```

**Verify:**
- [ ] All tests pass
- [ ] Coverage > 90%
- [ ] No breaking changes

---

## PHASE 12: DOCUMENTATION & DEPLOYMENT (3 tasks)

### ✅ Task 12.1: Update DatabaseSeeder
**Priority:** HIGH
**Files:** `database/seeders/DatabaseSeeder.php`

**Code to add:**
```php
public function run()
{
    // ... existing seeders ...
    $this->call(DocumentChecklistSeeder::class);
}
```

---

### ✅ Task 12.2: Create Deployment Instructions
**Priority:** MEDIUM
**Files:** `docs/deployment/module-1-deployment.md`

---

### ✅ Task 12.3: Final Integration Test
**Priority:** HIGH

**Manual Testing Checklist:**
- [ ] Upload mandatory document
- [ ] Upload optional document
- [ ] Add license
- [ ] Verify document
- [ ] Reject document
- [ ] Try to proceed to screening without docs (should fail)
- [ ] Complete all docs and proceed to screening (should succeed)
- [ ] Generate individual report
- [ ] Generate bulk report
- [ ] Check API endpoints

---

## COMPLETION CHECKLIST

### Database ✅
- [ ] Migration for candidate_licenses created and run
- [ ] DocumentChecklistSeeder created and run
- [ ] 8 checklist items in database

### Models ✅
- [ ] CandidateLicense model created
- [ ] CandidateLicense factory created
- [ ] Candidate model updated with relationships
- [ ] Candidate model helper methods added

### Services ✅
- [ ] PreDepartureDocumentService created with all methods

### Policies ✅
- [ ] PreDepartureDocumentPolicy created
- [ ] CandidateLicensePolicy created
- [ ] Policies registered in AuthServiceProvider

### Form Requests ✅
- [ ] StorePreDepartureDocumentRequest created
- [ ] StoreCandidateLicenseRequest created

### Controllers ✅
- [ ] PreDepartureDocumentController created
- [ ] PreDepartureDocumentApiController created
- [ ] CandidateLicenseController created
- [ ] PreDepartureReportController created

### API Resources ✅
- [ ] PreDepartureDocumentResource created
- [ ] CandidateLicenseResource created

### Routes ✅
- [ ] Web routes registered
- [ ] API routes registered

### Workflow ✅
- [ ] Candidate canTransitionToScreening() updated
- [ ] Screening controller enforces document check

### Tests ✅
- [ ] 5+ Unit tests created
- [ ] 5+ Feature tests created
- [ ] Workflow integration test created
- [ ] All tests passing
- [ ] Coverage > 90%

### Documentation ✅
- [ ] DatabaseSeeder updated
- [ ] Deployment instructions created

---

## Quick Start Commands

```bash
# 1. Run migrations
php artisan migrate
php artisan db:seed --class=DocumentChecklistSeeder

# 2. Verify setup
php artisan tinker
>>> App\Models\DocumentChecklist::count() // Should be 8

# 3. Run tests
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# 4. Check routes
php artisan route:list | grep pre-departure

# 5. Clear caches
php artisan config:cache
php artisan route:cache
```

---

**END OF MINI-TASKS BREAKDOWN**

Total: 50 discrete tasks across 12 phases
Each task is atomic and can be completed independently
