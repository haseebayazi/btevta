# Module 3: Registration Enhancement - Implementation Prompt for Claude

**Project:** BTEVTA WASL
**Module:** Module 3 - Registration (Enhancement)
**Status:** Existing Module - Requires Modifications
**Date:** February 2026

---

## Executive Summary

Module 3 (Registration) **ALREADY EXISTS** with comprehensive functionality including document verification, undertaking PDFs with QR codes, OEP allocation, and validation. This prompt focuses on **ENHANCEMENTS** required per the new WASL specifications.

**CRITICAL:** This is NOT a new module. You must understand existing code before making changes.

---

## Pre-Implementation Analysis

### Step 1: Read Existing Implementation

Before ANY changes, read these files completely:

```
# Controllers (ALREADY EXISTS - 84 methods)
app/Http/Controllers/RegistrationController.php

# Services (ALREADY EXISTS - 19 methods)
app/Services/RegistrationService.php

# Models
app/Models/Candidate.php
app/Models/NextOfKin.php
app/Models/Undertaking.php
app/Models/Batch.php
app/Models/RegistrationDocument.php

# Database Migrations
database/migrations/*next_of_kin*
database/migrations/*batches*
database/migrations/*candidates*

# Views
resources/views/registration/

# Existing Tests
tests/Feature/RegistrationControllerTest.php
tests/Unit/RegistrationServiceTest.php
```

### Step 2: Understand Current Registration Flow

Current flow:
1. Candidate selected for registration
2. Documents uploaded and verified
3. Next of kin details captured
4. Undertaking signed and PDF generated with QR code
5. OEP allocated via load balancing
6. Registration completed

New requirements ADD to this flow - don't replace it.

---

## Required Changes (from WASL_CHANGE_IMPACT_ANALYSIS.md)

| Change ID | Type | Description | Priority |
|-----------|------|-------------|----------|
| RG-001 | NEW | Entry gate: Only Screened candidates can be Registered | CRITICAL |
| RG-002 | MODIFIED | Next of Kin details restructured with financial account | HIGH |
| RG-003 | NEW | ID card copy of next of kin | HIGH |
| RG-004 | NEW | Allocation section (Campus, Program, OEP, Implementing Partner) | CRITICAL |
| RG-005 | NEW | Auto Batch Creation at registration (not manual) | CRITICAL |
| RG-006 | NEW | Configurable batch size (20/25/30 admin-defined) | HIGH |
| RG-007 | NEW | Batch generation based on Campus + Program + Trade | HIGH |
| RG-008 | NEW | Unique allocated number per batch | HIGH |
| RG-009 | NEW | Course Assignment at registration | HIGH |
| RG-010 | NEW | Course fields: Name, Duration, Start/End dates, Training type | HIGH |
| RG-011 | MODIFIED | Move batch creation from Training to Registration | CRITICAL |

---

## Phase 1: Database Changes

### 1.1 Check/Create Programs Table

```bash
php artisan tinker --execute="Schema::hasTable('programs')"
```

If not exists, create:

```php
// database/migrations/YYYY_MM_DD_create_programs_table.php
Schema::create('programs', function (Blueprint $table) {
    $table->id();
    $table->string('name', 150);
    $table->string('code', 20)->unique();
    $table->text('description')->nullable();
    $table->integer('duration_weeks')->nullable();
    $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();

    $table->index('is_active');
});
```

### 1.2 Check/Create Implementing Partners Table

```php
// database/migrations/YYYY_MM_DD_create_implementing_partners_table.php
Schema::create('implementing_partners', function (Blueprint $table) {
    $table->id();
    $table->string('name', 200);
    $table->string('code', 20)->unique();
    $table->string('contact_person', 100)->nullable();
    $table->string('contact_email', 150)->nullable();
    $table->string('contact_phone', 20)->nullable();
    $table->text('address')->nullable();
    $table->string('city', 100)->nullable();
    $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();

    $table->index('is_active');
});
```

### 1.3 Check/Create Courses Table

```php
// database/migrations/YYYY_MM_DD_create_courses_table.php
Schema::create('courses', function (Blueprint $table) {
    $table->id();
    $table->string('name', 150);
    $table->string('code', 30)->unique();
    $table->text('description')->nullable();
    $table->integer('duration_days');
    $table->enum('training_type', ['technical', 'soft_skills', 'both'])->default('both');
    $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();

    $table->index('training_type');
    $table->index('is_active');
});
```

### 1.4 Check/Create Payment Methods Table

```php
// database/migrations/YYYY_MM_DD_create_payment_methods_table.php
Schema::create('payment_methods', function (Blueprint $table) {
    $table->id();
    $table->string('name', 50); // EasyPaisa, JazzCash, Bank Account
    $table->string('code', 20)->unique();
    $table->string('icon')->nullable();
    $table->boolean('requires_account_number')->default(true);
    $table->boolean('requires_bank_name')->default(false);
    $table->boolean('is_active')->default(true);
    $table->integer('display_order')->default(0);
    $table->timestamps();
});
```

### 1.5 Create Candidate Courses Pivot Table

```php
// database/migrations/YYYY_MM_DD_create_candidate_courses_table.php
Schema::create('candidate_courses', function (Blueprint $table) {
    $table->id();
    $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
    $table->foreignId('course_id')->constrained()->cascadeOnDelete();
    $table->date('start_date');
    $table->date('end_date');
    $table->enum('status', ['assigned', 'in_progress', 'completed', 'cancelled'])->default('assigned');
    $table->foreignId('assigned_by')->constrained('users');
    $table->timestamp('assigned_at');
    $table->timestamps();

    $table->unique(['candidate_id', 'course_id']);
    $table->index('status');
});
```

### 1.6 Add Allocation Fields to Candidates Table

```php
// database/migrations/YYYY_MM_DD_add_allocation_fields_to_candidates.php
Schema::table('candidates', function (Blueprint $table) {
    // Check if columns exist before adding
    if (!Schema::hasColumn('candidates', 'program_id')) {
        $table->foreignId('program_id')->nullable()->after('campus_id')
            ->constrained()->nullOnDelete();
    }
    if (!Schema::hasColumn('candidates', 'implementing_partner_id')) {
        $table->foreignId('implementing_partner_id')->nullable()->after('oep_id')
            ->constrained()->nullOnDelete();
    }
    if (!Schema::hasColumn('candidates', 'allocated_number')) {
        $table->string('allocated_number', 50)->nullable()->unique()->after('batch_id');
    }

    $table->index('program_id');
    $table->index('implementing_partner_id');
});
```

### 1.7 Enhance Next of Kin Table

```php
// database/migrations/YYYY_MM_DD_add_financial_fields_to_next_of_kin.php
Schema::table('next_of_kin', function (Blueprint $table) {
    // Financial account fields for NOK
    if (!Schema::hasColumn('next_of_kin', 'payment_method_id')) {
        $table->foreignId('payment_method_id')->nullable()->after('relation')
            ->constrained('payment_methods')->nullOnDelete();
    }
    if (!Schema::hasColumn('next_of_kin', 'account_number')) {
        $table->string('account_number', 50)->nullable()->after('payment_method_id');
    }
    if (!Schema::hasColumn('next_of_kin', 'bank_name')) {
        $table->string('bank_name', 100)->nullable()->after('account_number');
    }
    if (!Schema::hasColumn('next_of_kin', 'id_card_path')) {
        $table->string('id_card_path', 500)->nullable()->after('bank_name');
    }
});
```

### 1.8 Add Batch Configuration to Config

```php
// database/migrations/YYYY_MM_DD_add_batch_config_to_batches.php
Schema::table('batches', function (Blueprint $table) {
    if (!Schema::hasColumn('batches', 'program_id')) {
        $table->foreignId('program_id')->nullable()->after('campus_id')
            ->constrained()->nullOnDelete();
    }
    if (!Schema::hasColumn('batches', 'max_size')) {
        $table->integer('max_size')->default(25)->after('capacity');
    }
    if (!Schema::hasColumn('batches', 'auto_generated')) {
        $table->boolean('auto_generated')->default(false)->after('max_size');
    }
});
```

---

## Phase 2: Create New Models

### 2.1 Program Model

```php
// app/Models/Program.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Program extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'code', 'description', 'duration_weeks',
        'country_id', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'duration_weeks' => 'integer',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function candidates()
    {
        return $this->hasMany(Candidate::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    public function batches()
    {
        return $this->hasMany(Batch::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
```

### 2.2 ImplementingPartner Model

```php
// app/Models/ImplementingPartner.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ImplementingPartner extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'code', 'contact_person', 'contact_email',
        'contact_phone', 'address', 'city', 'country_id', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function candidates()
    {
        return $this->hasMany(Candidate::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
```

### 2.3 Course Model

```php
// app/Models/Course.php
<?php

namespace App\Models;

use App\Enums\TrainingType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Course extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'code', 'description', 'duration_days',
        'training_type', 'program_id', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'duration_days' => 'integer',
    ];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function candidates()
    {
        return $this->belongsToMany(Candidate::class, 'candidate_courses')
            ->withPivot(['start_date', 'end_date', 'status', 'assigned_by', 'assigned_at'])
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getTrainingTypeLabelAttribute()
    {
        return match($this->training_type) {
            'technical' => 'Technical Training',
            'soft_skills' => 'Soft Skills Training',
            'both' => 'Technical & Soft Skills',
            default => 'Unknown',
        };
    }
}
```

### 2.4 PaymentMethod Model

```php
// app/Models/PaymentMethod.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $fillable = [
        'name', 'code', 'icon', 'requires_account_number',
        'requires_bank_name', 'is_active', 'display_order',
    ];

    protected $casts = [
        'requires_account_number' => 'boolean',
        'requires_bank_name' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('display_order');
    }
}
```

### 2.5 CandidateCourse Pivot Model

```php
// app/Models/CandidateCourse.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class CandidateCourse extends Pivot
{
    protected $table = 'candidate_courses';

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'assigned_at' => 'datetime',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
```

---

## Phase 3: Create Enums

### 3.1 TrainingType Enum

```php
// app/Enums/TrainingType.php
<?php

namespace App\Enums;

enum TrainingType: string
{
    case TECHNICAL = 'technical';
    case SOFT_SKILLS = 'soft_skills';
    case BOTH = 'both';

    public function label(): string
    {
        return match($this) {
            self::TECHNICAL => 'Technical Training',
            self::SOFT_SKILLS => 'Soft Skills Training',
            self::BOTH => 'Technical & Soft Skills',
        };
    }

    public static function toArray(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn($case) => $case->label(), self::cases())
        );
    }
}
```

---

## Phase 4: Create Seeders

### 4.1 Payment Methods Seeder

```php
// database/seeders/PaymentMethodsSeeder.php
<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodsSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            ['name' => 'Bank Account', 'code' => 'bank', 'requires_bank_name' => true, 'display_order' => 1],
            ['name' => 'EasyPaisa', 'code' => 'easypaisa', 'requires_bank_name' => false, 'display_order' => 2],
            ['name' => 'JazzCash', 'code' => 'jazzcash', 'requires_bank_name' => false, 'display_order' => 3],
            ['name' => 'UPaisa', 'code' => 'upaisa', 'requires_bank_name' => false, 'display_order' => 4],
        ];

        foreach ($methods as $method) {
            PaymentMethod::updateOrCreate(
                ['code' => $method['code']],
                array_merge($method, ['is_active' => true, 'requires_account_number' => true])
            );
        }
    }
}
```

### 4.2 Programs Seeder

```php
// database/seeders/ProgramsSeeder.php
<?php

namespace Database\Seeders;

use App\Models\Program;
use App\Models\Country;
use Illuminate\Database\Seeder;

class ProgramsSeeder extends Seeder
{
    public function run(): void
    {
        $saudiArabia = Country::where('code', 'SAU')->first();

        $programs = [
            ['name' => 'KSA Workforce Program', 'code' => 'KSAWP', 'duration_weeks' => 12, 'country_id' => $saudiArabia?->id],
            ['name' => 'UAE Skilled Workers', 'code' => 'UAESW', 'duration_weeks' => 8, 'country_id' => null],
            ['name' => 'Qatar Construction', 'code' => 'QATCON', 'duration_weeks' => 10, 'country_id' => null],
            ['name' => 'Malaysia Hospitality', 'code' => 'MYHOSP', 'duration_weeks' => 6, 'country_id' => null],
        ];

        foreach ($programs as $program) {
            Program::updateOrCreate(
                ['code' => $program['code']],
                array_merge($program, ['is_active' => true])
            );
        }
    }
}
```

---

## Phase 5: Create Auto-Batch Service

### 5.1 AutoBatchService

```php
// app/Services/AutoBatchService.php
<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Campus;
use App\Models\Candidate;
use App\Models\Program;
use App\Models\Trade;
use Illuminate\Support\Facades\DB;

class AutoBatchService
{
    /**
     * Get configurable batch size from settings or config
     */
    public function getBatchSize(): int
    {
        // Check admin-configured setting first, then fallback to config
        return (int) (setting('batch_size') ?? config('wasl.batch.default_size', 25));
    }

    /**
     * Get allowed batch sizes
     */
    public function getAllowedBatchSizes(): array
    {
        return config('wasl.batch.allowed_sizes', [20, 25, 30]);
    }

    /**
     * Generate unique batch number
     * Format: {CAMPUS_CODE}-{PROGRAM_CODE}-{TRADE_CODE}-{YYYY}-{SEQ}
     */
    public function generateBatchNumber(Campus $campus, Program $program, Trade $trade): string
    {
        $year = now()->format('Y');

        // Get the next sequence number for this combination
        $lastBatch = Batch::where('campus_id', $campus->id)
            ->where('program_id', $program->id)
            ->where('trade_id', $trade->id)
            ->whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = 1;
        if ($lastBatch && preg_match('/-(\d{4})$/', $lastBatch->batch_number, $matches)) {
            $sequence = (int) $matches[1] + 1;
        }

        return sprintf(
            '%s-%s-%s-%s-%04d',
            strtoupper($campus->code ?? 'XXX'),
            strtoupper($program->code ?? 'XXX'),
            strtoupper($trade->code ?? 'XXX'),
            $year,
            $sequence
        );
    }

    /**
     * Generate allocated number for candidate within batch
     * Format: {BATCH_NUMBER}-{POSITION}
     */
    public function generateAllocatedNumber(Candidate $candidate, Batch $batch): string
    {
        // Get current position in batch
        $position = $batch->candidates()->count();

        return sprintf(
            '%s-%03d',
            $batch->batch_number,
            $position
        );
    }

    /**
     * Find or create batch for candidate based on Campus + Program + Trade
     */
    public function findOrCreateBatch(Candidate $candidate): Batch
    {
        $campus = $candidate->campus;
        $program = $candidate->program;
        $trade = $candidate->trade;

        if (!$campus || !$program || !$trade) {
            throw new \Exception('Candidate must have campus, program, and trade assigned before batch allocation.');
        }

        $batchSize = $this->getBatchSize();

        // Find existing batch with space
        $existingBatch = Batch::where('campus_id', $campus->id)
            ->where('program_id', $program->id)
            ->where('trade_id', $trade->id)
            ->where('auto_generated', true)
            ->where('status', 'open')
            ->withCount('candidates')
            ->having('candidates_count', '<', $batchSize)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($existingBatch) {
            return $existingBatch;
        }

        // Create new batch
        return DB::transaction(function () use ($campus, $program, $trade, $batchSize) {
            $batchNumber = $this->generateBatchNumber($campus, $program, $trade);

            return Batch::create([
                'batch_number' => $batchNumber,
                'name' => "Auto Batch - {$batchNumber}",
                'campus_id' => $campus->id,
                'program_id' => $program->id,
                'trade_id' => $trade->id,
                'max_size' => $batchSize,
                'capacity' => $batchSize,
                'auto_generated' => true,
                'status' => 'open',
                'start_date' => now()->addDays(14), // Default start in 2 weeks
                'created_by' => auth()->id(),
            ]);
        });
    }

    /**
     * Assign candidate to batch and generate allocated number
     */
    public function assignCandidateToBatch(Candidate $candidate): Candidate
    {
        return DB::transaction(function () use ($candidate) {
            $batch = $this->findOrCreateBatch($candidate);

            // Check if batch is full (race condition protection)
            $currentCount = $batch->candidates()->lockForUpdate()->count();
            if ($currentCount >= $batch->max_size) {
                // Batch filled while we were processing, create new one
                $batch = $this->findOrCreateBatch($candidate);
            }

            // Assign to batch
            $candidate->batch_id = $batch->id;

            // Generate allocated number
            $candidate->allocated_number = $this->generateAllocatedNumber($candidate, $batch);

            $candidate->save();

            // Log activity
            activity()
                ->performedOn($candidate)
                ->causedBy(auth()->user())
                ->withProperties([
                    'batch_id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'allocated_number' => $candidate->allocated_number,
                ])
                ->log('Auto-assigned to batch');

            return $candidate;
        });
    }
}
```

---

## Phase 6: Update Existing Models

### 6.1 Update Candidate Model

Add to `app/Models/Candidate.php`:

```php
// Add to $fillable array:
'program_id',
'implementing_partner_id',
'allocated_number',

// Add relationships:
public function program()
{
    return $this->belongsTo(Program::class);
}

public function implementingPartner()
{
    return $this->belongsTo(ImplementingPartner::class);
}

public function courses()
{
    return $this->belongsToMany(Course::class, 'candidate_courses')
        ->withPivot(['start_date', 'end_date', 'status', 'assigned_by', 'assigned_at'])
        ->withTimestamps();
}

public function activeCourse()
{
    return $this->courses()
        ->wherePivot('status', 'assigned')
        ->orWherePivot('status', 'in_progress')
        ->first();
}
```

### 6.2 Update NextOfKin Model

Add to `app/Models/NextOfKin.php`:

```php
// Add to $fillable array:
'payment_method_id',
'account_number',
'bank_name',
'id_card_path',

// Add relationships:
public function paymentMethod()
{
    return $this->belongsTo(PaymentMethod::class);
}

// Add accessor for ID card URL
public function getIdCardUrlAttribute()
{
    if (empty($this->id_card_path)) {
        return null;
    }
    return route('secure-file.view', ['path' => $this->id_card_path]);
}
```

### 6.3 Update Batch Model

Add to `app/Models/Batch.php`:

```php
// Add to $fillable array:
'program_id',
'max_size',
'auto_generated',

// Add to $casts:
'auto_generated' => 'boolean',
'max_size' => 'integer',

// Add relationship:
public function program()
{
    return $this->belongsTo(Program::class);
}

// Add scope:
public function scopeWithSpace($query)
{
    return $query->where('status', 'open')
        ->withCount('candidates')
        ->having('candidates_count', '<', DB::raw('max_size'));
}
```

---

## Phase 7: Create Form Requests

### 7.1 RegistrationAllocationRequest

```php
// app/Http/Requests/RegistrationAllocationRequest.php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistrationAllocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
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
        ];
    }

    public function messages(): array
    {
        return [
            'nok_bank_name.required_if' => 'Bank name is required when payment method is Bank Account.',
            'nok_id_card.required' => 'Next of Kin ID card copy is required.',
            'course_end_date.after' => 'Course end date must be after start date.',
        ];
    }
}
```

---

## Phase 8: Update Registration Controller

Add/modify methods in `app/Http/Controllers/RegistrationController.php`:

### 8.1 Add Screened Status Gate

```php
/**
 * Middleware to ensure candidate is screened before registration
 */
public function __construct()
{
    $this->middleware(function ($request, $next) {
        $candidate = $request->route('candidate');
        if ($candidate && !in_array($candidate->status, ['screened', 'registered'])) {
            return redirect()->back()
                ->with('error', 'Candidate must be screened before registration. Current status: ' . $candidate->status);
        }
        return $next($request);
    })->only(['show', 'allocation', 'storeAllocation', 'completeRegistration']);
}
```

### 8.2 Add Allocation Method

```php
/**
 * Show allocation form for registration
 */
public function allocation(Candidate $candidate)
{
    $this->authorize('update', $candidate);

    // Verify candidate is screened
    if ($candidate->status !== 'screened') {
        return redirect()->route('candidates.show', $candidate)
            ->with('error', 'Only screened candidates can be registered.');
    }

    $campuses = Campus::active()->orderBy('name')->get();
    $programs = Program::active()->orderBy('name')->get();
    $oeps = Oep::active()->orderBy('name')->get();
    $partners = ImplementingPartner::active()->orderBy('name')->get();
    $courses = Course::active()->orderBy('name')->get();
    $paymentMethods = PaymentMethod::active()->get();
    $batchSizes = app(AutoBatchService::class)->getAllowedBatchSizes();

    return view('registration.allocation', compact(
        'candidate', 'campuses', 'programs', 'oeps', 'partners',
        'courses', 'paymentMethods', 'batchSizes'
    ));
}

/**
 * Store allocation and complete registration
 */
public function storeAllocation(RegistrationAllocationRequest $request, Candidate $candidate)
{
    $this->authorize('update', $candidate);

    $validated = $request->validated();

    try {
        DB::beginTransaction();

        // 1. Update candidate allocation
        $candidate->update([
            'campus_id' => $validated['campus_id'],
            'program_id' => $validated['program_id'],
            'oep_id' => $validated['oep_id'],
            'implementing_partner_id' => $validated['implementing_partner_id'],
        ]);

        // 2. Auto-assign to batch
        $autoBatchService = app(AutoBatchService::class);
        $candidate = $autoBatchService->assignCandidateToBatch($candidate);

        // 3. Assign course
        $candidate->courses()->attach($validated['course_id'], [
            'start_date' => $validated['course_start_date'],
            'end_date' => $validated['course_end_date'],
            'status' => 'assigned',
            'assigned_by' => auth()->id(),
            'assigned_at' => now(),
        ]);

        // 4. Update/Create Next of Kin with financial details
        $nokData = [
            'name' => $validated['nok_name'],
            'relation' => $validated['nok_relation'],
            'phone' => $validated['nok_phone'],
            'address' => $validated['nok_address'] ?? null,
            'payment_method_id' => $validated['nok_payment_method_id'],
            'account_number' => $validated['nok_account_number'],
            'bank_name' => $validated['nok_bank_name'] ?? null,
        ];

        // Handle ID card upload
        if ($request->hasFile('nok_id_card')) {
            $file = $request->file('nok_id_card');
            $path = $file->store('next-of-kin/id-cards/' . $candidate->id, 'private');
            $nokData['id_card_path'] = $path;
        }

        $candidate->nextOfKin()->updateOrCreate(
            ['candidate_id' => $candidate->id],
            $nokData
        );

        // 5. Update candidate status to registered
        $candidate->update(['status' => 'registered']);

        // Log activity
        activity()
            ->performedOn($candidate)
            ->causedBy(auth()->user())
            ->withProperties([
                'batch_id' => $candidate->batch_id,
                'allocated_number' => $candidate->allocated_number,
                'program_id' => $validated['program_id'],
                'course_id' => $validated['course_id'],
            ])
            ->log('Registration completed with allocation');

        DB::commit();

        return redirect()->route('candidates.show', $candidate)
            ->with('success', 'Registration completed successfully. Allocated Number: ' . $candidate->allocated_number);

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->withInput()
            ->with('error', 'Registration failed: ' . $e->getMessage());
    }
}
```

---

## Phase 9: Create Views

### 9.1 Allocation Form View

Create `resources/views/registration/allocation.blade.php`:

**Required Sections:**
1. Candidate Info Header (name, TheLeap ID, status)
2. Allocation Section (Campus, Program, OEP, Implementing Partner dropdowns)
3. Course Assignment Section (Course dropdown, Start Date, End Date)
4. Next of Kin Section (Name, Relation, Phone, Address, Payment Method, Account Number, Bank Name, ID Card Upload)
5. Submit Button

**JavaScript:**
- Show/hide bank name field based on payment method selection
- Calculate end date based on course duration when course selected
- Form validation

**Styling:** Match Module 1 & 2 Tailwind CSS design.

---

## Phase 10: Admin Configuration

### 10.1 Batch Size Configuration

Add to admin settings page ability to configure:
- Default batch size (dropdown: 20, 25, 30)
- Auto-batch enabled (toggle)

Store in `settings` table or `config/wasl.php`.

### 10.2 Admin CRUD for New Entities

Create basic CRUD controllers for:
- `ProgramController` - Manage programs
- `ImplementingPartnerController` - Manage partners
- `CourseController` - Manage courses
- `PaymentMethodController` - Manage payment methods

---

## Phase 11: Testing

### 11.1 Unit Tests

```php
// tests/Unit/AutoBatchServiceTest.php
public function test_generates_correct_batch_number_format()
public function test_finds_existing_batch_with_space()
public function test_creates_new_batch_when_full()
public function test_generates_unique_allocated_numbers()
public function test_respects_configurable_batch_size()
```

### 11.2 Feature Tests

```php
// tests/Feature/RegistrationAllocationTest.php
public function test_allocation_page_requires_screened_status()
public function test_can_complete_registration_with_allocation()
public function test_auto_batch_assignment_works()
public function test_next_of_kin_with_financial_details_saved()
public function test_course_assignment_saved()
public function test_allocated_number_is_unique()
public function test_cannot_register_unscreened_candidate()
```

---

## Phase 12: Update Configuration

### 12.1 config/wasl.php

```php
'batch' => [
    'default_size' => env('WASL_BATCH_SIZE', 25),
    'allowed_sizes' => [20, 25, 30],
    'number_format' => '{CAMPUS_CODE}-{PROGRAM_CODE}-{TRADE_CODE}-{YEAR}-{SEQ}',
    'auto_generation_enabled' => env('WASL_AUTO_BATCH', true),
],
```

---

## Validation Checklist

After implementation, verify:

- [ ] Programs table exists with seed data
- [ ] Implementing Partners table exists
- [ ] Courses table exists
- [ ] Payment Methods table exists with EasyPaisa, JazzCash, Bank
- [ ] Candidates table has program_id, implementing_partner_id, allocated_number columns
- [ ] Next of Kin table has payment_method_id, account_number, bank_name, id_card_path
- [ ] Batches table has program_id, max_size, auto_generated columns
- [ ] AutoBatchService generates correct batch numbers
- [ ] AutoBatchService finds existing batches with space
- [ ] AutoBatchService creates new batches when needed
- [ ] Allocated numbers are unique per batch
- [ ] Screened status gate works
- [ ] Allocation form loads with all dropdowns
- [ ] Course assignment saves correctly
- [ ] NOK financial details save correctly
- [ ] NOK ID card uploads correctly
- [ ] Candidate status changes to 'registered'
- [ ] All tests pass
- [ ] Activity logging works

---

## Files to Create

```
app/Models/Program.php
app/Models/ImplementingPartner.php
app/Models/Course.php
app/Models/PaymentMethod.php
app/Models/CandidateCourse.php
app/Enums/TrainingType.php
app/Services/AutoBatchService.php
app/Http/Requests/RegistrationAllocationRequest.php
app/Http/Controllers/ProgramController.php
app/Http/Controllers/ImplementingPartnerController.php
app/Http/Controllers/CourseController.php
database/migrations/YYYY_MM_DD_create_programs_table.php
database/migrations/YYYY_MM_DD_create_implementing_partners_table.php
database/migrations/YYYY_MM_DD_create_courses_table.php
database/migrations/YYYY_MM_DD_create_payment_methods_table.php
database/migrations/YYYY_MM_DD_create_candidate_courses_table.php
database/migrations/YYYY_MM_DD_add_allocation_fields_to_candidates.php
database/migrations/YYYY_MM_DD_add_financial_fields_to_next_of_kin.php
database/migrations/YYYY_MM_DD_add_batch_config_to_batches.php
database/seeders/PaymentMethodsSeeder.php
database/seeders/ProgramsSeeder.php
resources/views/registration/allocation.blade.php
tests/Unit/AutoBatchServiceTest.php
tests/Feature/RegistrationAllocationTest.php
docs/MODULE_3_REGISTRATION.md
```

## Files to Modify

```
app/Models/Candidate.php
app/Models/NextOfKin.php
app/Models/Batch.php
app/Http/Controllers/RegistrationController.php
app/Services/RegistrationService.php
routes/web.php
config/wasl.php
CLAUDE.md
README.md
```

---

## Success Criteria

Module 3 Enhancement is complete when:

1. Only screened candidates can access registration
2. Allocation form captures Campus, Program, OEP, Implementing Partner
3. Auto-batch creation works based on Campus + Program + Trade
4. Configurable batch sizes (20/25/30) work
5. Unique allocated numbers generated per batch
6. Course assignment at registration works
7. NOK financial details captured with ID card upload
8. All tests pass
9. No regression in existing registration functionality
10. Documentation updated

---

*End of Module 3 Implementation Prompt*
