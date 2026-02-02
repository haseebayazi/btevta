# Module 8: Employer Information Enhancement - Implementation Prompt for Claude

**Project:** BTEVTA WASL
**Module:** Module 8 - Employer Information (Enhancement)
**Status:** Existing Module - Requires Enhancement
**Date:** February 2026

---

## Executive Summary

Module 8 (Employer) **ALREADY EXISTS** with basic CRUD functionality:
- EmployerController (9 methods)
- Employer model with evidence file uploads
- Active status management
- Basic candidate-employer relationships

This prompt focuses on **ENHANCEMENTS** for comprehensive employer management including permission numbers, visa issuing companies, employment packages, and enhanced candidate linking.

**CRITICAL:** Build on existing infrastructure, don't recreate.

---

## Pre-Implementation Analysis

### Step 1: Read Existing Implementation

```
# Controller
app/Http/Controllers/EmployerController.php

# Model
app/Models/Employer.php

# Request Classes
app/Http/Requests/StoreEmployerRequest.php
app/Http/Requests/UpdateEmployerRequest.php

# Database
php artisan tinker --execute="Schema::getColumnListing('employers')"
php artisan tinker --execute="Schema::hasTable('candidate_employer')"

# Views
resources/views/employers/
```

---

## Required Changes (from WASL_CHANGE_IMPACT_ANALYSIS.md)

| Change ID | Type | Description | Priority |
|-----------|------|-------------|----------|
| EP-001 | MODIFIED | Separate Employer Information tab in candidate view | HIGH |
| EP-002 | NEW | Permission Number field | HIGH |
| EP-003 | NEW | Visa Issuing Company details | HIGH |
| EP-004 | NEW | Employment Package breakdown (salary structure) | HIGH |
| EP-005 | MODIFIED | Country and Sector/Trade linkage | MEDIUM |
| EP-006 | EXISTS | Evidence/document attachment (enhance) | MEDIUM |
| EP-007 | MODIFIED | Enhanced employer-candidate linking | HIGH |

---

## Phase 1: Database Changes

### 1.1 Enhance Employers Table

```php
// database/migrations/YYYY_MM_DD_enhance_employers_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employers', function (Blueprint $table) {
            // Permission Number (Ministry of Labor)
            if (!Schema::hasColumn('employers', 'permission_number')) {
                $table->string('permission_number', 50)->nullable()->after('name');
            }
            if (!Schema::hasColumn('employers', 'permission_issue_date')) {
                $table->date('permission_issue_date')->nullable()->after('permission_number');
            }
            if (!Schema::hasColumn('employers', 'permission_expiry_date')) {
                $table->date('permission_expiry_date')->nullable()->after('permission_issue_date');
            }
            if (!Schema::hasColumn('employers', 'permission_document_path')) {
                $table->string('permission_document_path', 500)->nullable()->after('permission_expiry_date');
            }

            // Visa Issuing Company
            if (!Schema::hasColumn('employers', 'visa_issuing_company')) {
                $table->string('visa_issuing_company', 200)->nullable()->after('permission_document_path');
            }
            if (!Schema::hasColumn('employers', 'visa_company_license')) {
                $table->string('visa_company_license', 100)->nullable()->after('visa_issuing_company');
            }

            // Location
            if (!Schema::hasColumn('employers', 'country_id')) {
                $table->foreignId('country_id')->nullable()->after('address')
                    ->constrained()->nullOnDelete();
            }
            if (!Schema::hasColumn('employers', 'city')) {
                $table->string('city', 100)->nullable()->after('country_id');
            }

            // Sector/Trade
            if (!Schema::hasColumn('employers', 'sector')) {
                $table->string('sector', 100)->nullable()->after('city');
            }
            if (!Schema::hasColumn('employers', 'trade_id')) {
                $table->foreignId('trade_id')->nullable()->after('sector')
                    ->constrained()->nullOnDelete();
            }

            // Employment Package Template
            if (!Schema::hasColumn('employers', 'default_package')) {
                $table->json('default_package')->nullable()->after('trade_id');
                // Contains: base_salary, currency, housing, food, transport, other
            }

            // Additional Fields
            if (!Schema::hasColumn('employers', 'company_size')) {
                $table->enum('company_size', ['small', 'medium', 'large', 'enterprise'])
                    ->nullable()->after('default_package');
            }
            if (!Schema::hasColumn('employers', 'verified')) {
                $table->boolean('verified')->default(false)->after('company_size');
            }
            if (!Schema::hasColumn('employers', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('verified');
            }
            if (!Schema::hasColumn('employers', 'verified_by')) {
                $table->foreignId('verified_by')->nullable()->after('verified_at')
                    ->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('employers', 'notes')) {
                $table->text('notes')->nullable()->after('verified_by');
            }

            // Indexes
            $table->index('permission_number');
            $table->index('country_id');
            $table->index('sector');
            $table->index('verified');
        });
    }
};
```

### 1.2 Enhance Candidate-Employer Pivot Table

```php
// database/migrations/YYYY_MM_DD_enhance_candidate_employer_table.php
Schema::table('candidate_employer', function (Blueprint $table) {
    // If table doesn't exist, create it
    if (!Schema::hasTable('candidate_employer')) {
        Schema::create('candidate_employer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employer_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    // Add enhanced fields
    if (!Schema::hasColumn('candidate_employer', 'employment_type')) {
        $table->enum('employment_type', ['initial', 'transfer', 'switch'])
            ->default('initial')->after('employer_id');
    }
    if (!Schema::hasColumn('candidate_employer', 'assignment_date')) {
        $table->date('assignment_date')->nullable()->after('employment_type');
    }
    if (!Schema::hasColumn('candidate_employer', 'custom_package')) {
        $table->json('custom_package')->nullable()->after('assignment_date');
    }
    if (!Schema::hasColumn('candidate_employer', 'status')) {
        $table->enum('status', ['pending', 'active', 'completed', 'cancelled'])
            ->default('pending')->after('custom_package');
    }
    if (!Schema::hasColumn('candidate_employer', 'assigned_by')) {
        $table->foreignId('assigned_by')->nullable()->after('status')
            ->constrained('users')->nullOnDelete();
    }

    $table->index(['candidate_id', 'status']);
});
```

### 1.3 Create Employer Documents Table

```php
// database/migrations/YYYY_MM_DD_create_employer_documents_table.php
Schema::create('employer_documents', function (Blueprint $table) {
    $table->id();
    $table->foreignId('employer_id')->constrained()->cascadeOnDelete();

    $table->string('document_type', 50); // license, registration, permission, contract_template
    $table->string('document_name', 200);
    $table->string('document_path', 500);
    $table->string('document_number', 100)->nullable();
    $table->date('issue_date')->nullable();
    $table->date('expiry_date')->nullable();
    $table->text('notes')->nullable();

    $table->foreignId('uploaded_by')->constrained('users');
    $table->timestamps();
    $table->softDeletes();

    $table->index(['employer_id', 'document_type']);
});
```

---

## Phase 2: Create Enums

### 2.1 EmployerSize Enum

```php
// app/Enums/EmployerSize.php
<?php

namespace App\Enums;

enum EmployerSize: string
{
    case SMALL = 'small';
    case MEDIUM = 'medium';
    case LARGE = 'large';
    case ENTERPRISE = 'enterprise';

    public function label(): string
    {
        return match($this) {
            self::SMALL => 'Small (1-50)',
            self::MEDIUM => 'Medium (51-200)',
            self::LARGE => 'Large (201-1000)',
            self::ENTERPRISE => 'Enterprise (1000+)',
        };
    }
}
```

### 2.2 EmploymentType Enum

```php
// app/Enums/EmploymentType.php
<?php

namespace App\Enums;

enum EmploymentType: string
{
    case INITIAL = 'initial';
    case TRANSFER = 'transfer';
    case SWITCH = 'switch';

    public function label(): string
    {
        return match($this) {
            self::INITIAL => 'Initial Assignment',
            self::TRANSFER => 'Transfer',
            self::SWITCH => 'Company Switch',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::INITIAL => 'primary',
            self::TRANSFER => 'info',
            self::SWITCH => 'warning',
        };
    }
}
```

---

## Phase 3: Create Value Object

### 3.1 EmploymentPackage Value Object

```php
// app/ValueObjects/EmploymentPackage.php
<?php

namespace App\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;

class EmploymentPackage implements Arrayable
{
    public function __construct(
        public float $baseSalary = 0,
        public string $currency = 'SAR',
        public float $housingAllowance = 0,
        public float $foodAllowance = 0,
        public float $transportAllowance = 0,
        public float $otherAllowance = 0,
        public ?array $benefits = null,
        public ?string $notes = null,
    ) {}

    public static function fromArray(?array $data): self
    {
        if (!$data) return new self();

        return new self(
            baseSalary: (float) ($data['base_salary'] ?? 0),
            currency: $data['currency'] ?? 'SAR',
            housingAllowance: (float) ($data['housing_allowance'] ?? 0),
            foodAllowance: (float) ($data['food_allowance'] ?? 0),
            transportAllowance: (float) ($data['transport_allowance'] ?? 0),
            otherAllowance: (float) ($data['other_allowance'] ?? 0),
            benefits: $data['benefits'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'base_salary' => $this->baseSalary,
            'currency' => $this->currency,
            'housing_allowance' => $this->housingAllowance,
            'food_allowance' => $this->foodAllowance,
            'transport_allowance' => $this->transportAllowance,
            'other_allowance' => $this->otherAllowance,
            'benefits' => $this->benefits,
            'notes' => $this->notes,
        ];
    }

    public function getTotal(): float
    {
        return $this->baseSalary
            + $this->housingAllowance
            + $this->foodAllowance
            + $this->transportAllowance
            + $this->otherAllowance;
    }

    public function getFormattedTotal(): string
    {
        return number_format($this->getTotal(), 2) . ' ' . $this->currency;
    }

    public function getBreakdown(): array
    {
        return [
            ['label' => 'Base Salary', 'amount' => $this->baseSalary, 'percentage' => $this->getPercentage($this->baseSalary)],
            ['label' => 'Housing', 'amount' => $this->housingAllowance, 'percentage' => $this->getPercentage($this->housingAllowance)],
            ['label' => 'Food', 'amount' => $this->foodAllowance, 'percentage' => $this->getPercentage($this->foodAllowance)],
            ['label' => 'Transport', 'amount' => $this->transportAllowance, 'percentage' => $this->getPercentage($this->transportAllowance)],
            ['label' => 'Other', 'amount' => $this->otherAllowance, 'percentage' => $this->getPercentage($this->otherAllowance)],
        ];
    }

    protected function getPercentage(float $amount): float
    {
        $total = $this->getTotal();
        return $total > 0 ? round(($amount / $total) * 100, 1) : 0;
    }
}
```

---

## Phase 4: Update Employer Model

Add to `app/Models/Employer.php`:

```php
use App\Enums\EmployerSize;
use App\ValueObjects\EmploymentPackage;

// Add to $fillable:
'permission_number',
'permission_issue_date',
'permission_expiry_date',
'permission_document_path',
'visa_issuing_company',
'visa_company_license',
'country_id',
'city',
'sector',
'trade_id',
'default_package',
'company_size',
'verified',
'verified_at',
'verified_by',
'notes',

// Add to $casts:
'permission_issue_date' => 'date',
'permission_expiry_date' => 'date',
'default_package' => 'array',
'company_size' => EmployerSize::class,
'verified' => 'boolean',
'verified_at' => 'datetime',

// Add relationships:
public function country()
{
    return $this->belongsTo(Country::class);
}

public function trade()
{
    return $this->belongsTo(Trade::class);
}

public function verifiedByUser()
{
    return $this->belongsTo(User::class, 'verified_by');
}

public function documents()
{
    return $this->hasMany(EmployerDocument::class);
}

public function candidates()
{
    return $this->belongsToMany(Candidate::class, 'candidate_employer')
        ->withPivot(['employment_type', 'assignment_date', 'custom_package', 'status', 'assigned_by'])
        ->withTimestamps();
}

public function activeCandidates()
{
    return $this->candidates()->wherePivot('status', 'active');
}

// Add accessors:
public function getDefaultPackageObjectAttribute(): EmploymentPackage
{
    return EmploymentPackage::fromArray($this->default_package);
}

public function getPermissionExpiringAttribute(): bool
{
    if (!$this->permission_expiry_date) return false;
    return $this->permission_expiry_date->diffInDays(now()) <= 30;
}

public function getActiveCandidateCountAttribute(): int
{
    return $this->activeCandidates()->count();
}

// Add methods:
public function verify(): void
{
    $this->verified = true;
    $this->verified_at = now();
    $this->verified_by = auth()->id();
    $this->save();

    activity()
        ->performedOn($this)
        ->causedBy(auth()->user())
        ->log('Employer verified');
}

public function assignCandidate(
    Candidate $candidate,
    string $employmentType = 'initial',
    ?array $customPackage = null
): void {
    $this->candidates()->attach($candidate->id, [
        'employment_type' => $employmentType,
        'assignment_date' => now(),
        'custom_package' => $customPackage ? json_encode($customPackage) : null,
        'status' => 'active',
        'assigned_by' => auth()->id(),
    ]);

    activity()
        ->performedOn($this)
        ->causedBy(auth()->user())
        ->withProperties(['candidate_id' => $candidate->id])
        ->log('Candidate assigned');
}

public function getPackageForCandidate(Candidate $candidate): EmploymentPackage
{
    $pivot = $this->candidates()->where('candidate_id', $candidate->id)->first()?->pivot;

    if ($pivot && $pivot->custom_package) {
        return EmploymentPackage::fromArray(json_decode($pivot->custom_package, true));
    }

    return $this->default_package_object;
}
```

---

## Phase 5: Create EmployerDocument Model

```php
// app/Models/EmployerDocument.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployerDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employer_id',
        'document_type',
        'document_name',
        'document_path',
        'document_number',
        'issue_date',
        'expiry_date',
        'notes',
        'uploaded_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
    ];

    const TYPE_LICENSE = 'license';
    const TYPE_REGISTRATION = 'registration';
    const TYPE_PERMISSION = 'permission';
    const TYPE_CONTRACT_TEMPLATE = 'contract_template';
    const TYPE_OTHER = 'other';

    public static function documentTypes(): array
    {
        return [
            self::TYPE_LICENSE => 'Business License',
            self::TYPE_REGISTRATION => 'Company Registration',
            self::TYPE_PERMISSION => 'Work Permission',
            self::TYPE_CONTRACT_TEMPLATE => 'Contract Template',
            self::TYPE_OTHER => 'Other Document',
        ];
    }

    public function employer()
    {
        return $this->belongsTo(Employer::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrlAttribute(): string
    {
        return route('secure-file.view', ['path' => $this->document_path]);
    }

    public function isExpiring(): bool
    {
        if (!$this->expiry_date) return false;
        return $this->expiry_date->diffInDays(now()) <= 30;
    }

    public function isExpired(): bool
    {
        if (!$this->expiry_date) return false;
        return $this->expiry_date->isPast();
    }
}
```

---

## Phase 6: Create EmployerService

```php
// app/Services/EmployerService.php
<?php

namespace App\Services;

use App\Models\Employer;
use App\Models\Candidate;
use App\Models\EmployerDocument;
use App\ValueObjects\EmploymentPackage;
use Illuminate\Support\Facades\DB;

class EmployerService
{
    /**
     * Create employer with documents
     */
    public function createEmployer(array $data, array $documents = []): Employer
    {
        return DB::transaction(function () use ($data, $documents) {
            $employer = Employer::create($data);

            foreach ($documents as $doc) {
                $this->addDocument($employer, $doc['file'], $doc['type'], $doc['data'] ?? []);
            }

            activity()
                ->performedOn($employer)
                ->causedBy(auth()->user())
                ->log('Employer created');

            return $employer;
        });
    }

    /**
     * Update employer
     */
    public function updateEmployer(Employer $employer, array $data): Employer
    {
        $employer->update($data);

        activity()
            ->performedOn($employer)
            ->causedBy(auth()->user())
            ->log('Employer updated');

        return $employer;
    }

    /**
     * Add document to employer
     */
    public function addDocument(Employer $employer, $file, string $type, array $data = []): EmployerDocument
    {
        $path = $file->store("employers/{$employer->id}/documents", 'private');

        return EmployerDocument::create([
            'employer_id' => $employer->id,
            'document_type' => $type,
            'document_name' => $data['name'] ?? $file->getClientOriginalName(),
            'document_path' => $path,
            'document_number' => $data['number'] ?? null,
            'issue_date' => $data['issue_date'] ?? null,
            'expiry_date' => $data['expiry_date'] ?? null,
            'notes' => $data['notes'] ?? null,
            'uploaded_by' => auth()->id(),
        ]);
    }

    /**
     * Set default employment package
     */
    public function setDefaultPackage(Employer $employer, array $packageData): void
    {
        $package = new EmploymentPackage(
            baseSalary: $packageData['base_salary'] ?? 0,
            currency: $packageData['currency'] ?? 'SAR',
            housingAllowance: $packageData['housing_allowance'] ?? 0,
            foodAllowance: $packageData['food_allowance'] ?? 0,
            transportAllowance: $packageData['transport_allowance'] ?? 0,
            otherAllowance: $packageData['other_allowance'] ?? 0,
            benefits: $packageData['benefits'] ?? null,
        );

        $employer->default_package = $package->toArray();
        $employer->save();
    }

    /**
     * Assign candidate to employer
     */
    public function assignCandidate(
        Employer $employer,
        Candidate $candidate,
        string $employmentType = 'initial',
        ?array $customPackage = null
    ): void {
        // Check if already assigned
        if ($employer->candidates()->where('candidate_id', $candidate->id)->wherePivot('status', 'active')->exists()) {
            throw new \Exception('Candidate is already assigned to this employer.');
        }

        $employer->assignCandidate($candidate, $employmentType, $customPackage);
    }

    /**
     * Get employer dashboard data
     */
    public function getDashboard(): array
    {
        $employers = Employer::with(['country', 'trade', 'documents'])
            ->withCount(['candidates as active_candidates_count' => function ($query) {
                $query->wherePivot('status', 'active');
            }])
            ->get();

        return [
            'summary' => [
                'total' => $employers->count(),
                'verified' => $employers->where('verified', true)->count(),
                'unverified' => $employers->where('verified', false)->count(),
                'with_expiring_permission' => $employers->filter(fn($e) => $e->permission_expiring)->count(),
            ],
            'by_country' => $employers->groupBy('country.name')->map->count(),
            'by_sector' => $employers->groupBy('sector')->map->count(),
            'top_employers' => $employers->sortByDesc('active_candidates_count')->take(10),
            'expiring_permissions' => $employers->filter(fn($e) => $e->permission_expiring),
        ];
    }

    /**
     * Verify employer
     */
    public function verifyEmployer(Employer $employer): void
    {
        $employer->verify();
    }

    /**
     * Get candidates for employer
     */
    public function getEmployerCandidates(Employer $employer, ?string $status = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = $employer->candidates()->with(['campus', 'trade']);

        if ($status) {
            $query->wherePivot('status', $status);
        }

        return $query->get();
    }
}
```

---

## Phase 7: Update Controller

Update `app/Http/Controllers/EmployerController.php` with enhanced methods for package management, document uploads, candidate assignment, and verification.

---

## Phase 8: Add Routes

```php
// routes/web.php
Route::middleware(['auth'])->prefix('employers')->name('employers.')->group(function () {
    // Existing CRUD routes...

    Route::get('/dashboard', [EmployerController::class, 'dashboard'])->name('dashboard');
    Route::post('/{employer}/verify', [EmployerController::class, 'verify'])->name('verify');
    Route::post('/{employer}/package', [EmployerController::class, 'setPackage'])->name('set-package');
    Route::post('/{employer}/documents', [EmployerController::class, 'uploadDocument'])->name('upload-document');
    Route::delete('/documents/{document}', [EmployerController::class, 'deleteDocument'])->name('delete-document');
    Route::post('/{employer}/assign-candidate', [EmployerController::class, 'assignCandidate'])->name('assign-candidate');
    Route::get('/{employer}/candidates', [EmployerController::class, 'candidates'])->name('candidates');
});
```

---

## Phase 9: Create Views

1. `resources/views/employers/dashboard.blade.php`
2. `resources/views/employers/show.blade.php` (enhanced)
3. `resources/views/employers/partials/package-form.blade.php`
4. `resources/views/employers/partials/documents-section.blade.php`
5. `resources/views/employers/partials/candidates-list.blade.php`
6. `resources/views/candidates/partials/employer-tab.blade.php` (for candidate view)

---

## Validation Checklist

- [ ] Enhanced columns added to employers table
- [ ] candidate_employer pivot enhanced
- [ ] employer_documents table created
- [ ] EmployerSize and EmploymentType enums created
- [ ] EmploymentPackage value object created
- [ ] Employer model enhanced
- [ ] EmployerDocument model created
- [ ] EmployerService created
- [ ] Controller enhanced
- [ ] Routes added
- [ ] Dashboard view created
- [ ] Package management works
- [ ] Document uploads work
- [ ] Candidate assignment works
- [ ] Verification works
- [ ] Employer tab in candidate view works
- [ ] All tests pass

---

## Success Criteria

Module 8 Enhancement is complete when:

1. Permission number tracking with expiry alerts works
2. Visa issuing company details captured
3. Employment package with breakdown works
4. Country and sector/trade linkage works
5. Multiple document uploads work
6. Candidate assignment with custom packages works
7. Employer verification workflow works
8. Employer dashboard shows statistics
9. Employer tab in candidate view works
10. All tests pass

---

*End of Module 8 Implementation Prompt*
