# Module 2: Initial Screening - Implementation Prompt for Claude

**Project:** BTEVTA WASL
**Module:** Module 2 - Initial Screening
**Status:** Ready for Implementation
**Date:** February 2026

---

## Task Overview

Implement **Module 2: Initial Screening** for the WASL application. This module transforms the existing 3-call screening system into a simplified single-review workflow with consent verification, placement interest capture, and country specification.

**CRITICAL:** This is a MODIFICATION of existing functionality, not a new module. You must analyze what already exists before making changes.

---

## Pre-Implementation Analysis

### Step 1: Understand What Already Exists

Before writing ANY code, you MUST read and understand these files:

```
# Core Model & Enum (ALREADY EXISTS - MODIFY)
app/Models/CandidateScreening.php
app/Enums/ScreeningStatus.php
app/Enums/CandidateStatus.php

# Controller (ALREADY EXISTS - MODIFY)
app/Http/Controllers/ScreeningController.php
app/Http/Controllers/Api/ScreeningApiController.php

# Service (ALREADY EXISTS - MODIFY)
app/Services/ScreeningService.php

# Policy (ALREADY EXISTS - MODIFY)
app/Policies/CandidateScreeningPolicy.php

# Views (ALREADY EXISTS - MODIFY)
resources/views/screening/
resources/views/screenings/

# Tests (ALREADY EXISTS - MODIFY)
tests/Feature/ScreeningControllerTest.php
tests/Unit/ScreeningServiceTest.php

# Database Migration (CHECK EXISTING)
database/migrations/*screenings*
```

### Step 2: Check for Countries Table

Run: `php artisan tinker --execute="Schema::hasTable('countries')"`

- If countries table exists: use it
- If not: create the countries table migration and model first

---

## Implementation Requirements

### Change Summary (from WASL_CHANGE_IMPACT_ANALYSIS.md)

| Change ID | Type | Description |
|-----------|------|-------------|
| IS-001 | MODIFIED | Rename "Screening" → "Initial Screening" in UI |
| IS-002 | MODIFIED | Replace 3-call system with single review workflow |
| IS-003 | NEW | Consent for work verification step |
| IS-004 | NEW | Area of interest capture (Local/International placement) |
| IS-005 | NEW | Country specification field (if international) |
| IS-006 | NEW | Screening dashboard with status breakdown |
| IS-007 | MODIFIED | Status tags: Screened/Pending/Deferred (not Passed/Failed) |
| IS-008 | NEW | Screening notes and evidence upload |
| IS-009 | NEW | Gate: Only "Screened" candidates can proceed to Registration |
| IS-010 | REMOVED | 3-call tracking system (soft-deprecate, don't delete data) |

---

## Step-by-Step Implementation

### Phase 1: Database Changes

#### 1.1 Create Countries Table (if not exists)

```php
// database/migrations/YYYY_MM_DD_create_countries_table.php
Schema::create('countries', function (Blueprint $table) {
    $table->id();
    $table->string('name', 100);
    $table->string('code', 3)->unique(); // ISO 3166-1 alpha-3
    $table->string('code_2', 2)->unique(); // ISO 3166-1 alpha-2
    $table->boolean('is_destination')->default(false);
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    $table->index(['is_destination', 'is_active']);
});
```

Create seeder for destination countries:
```php
// database/seeders/CountriesSeeder.php
// Include: Saudi Arabia, UAE, Qatar, Kuwait, Bahrain, Oman, Malaysia, etc.
```

#### 1.2 Modify screenings table (ADD columns, don't remove old ones)

```php
// database/migrations/YYYY_MM_DD_add_initial_screening_fields_to_candidate_screenings.php
Schema::table('candidate_screenings', function (Blueprint $table) {
    // New Initial Screening fields
    $table->boolean('consent_for_work')->default(false)->after('candidate_id');
    $table->enum('placement_interest', ['local', 'international'])->nullable()->after('consent_for_work');
    $table->foreignId('target_country_id')->nullable()->after('placement_interest')
        ->constrained('countries')->nullOnDelete();
    $table->enum('screening_outcome', ['pending', 'screened', 'deferred'])
        ->default('pending')->after('target_country_id');
    $table->foreignId('reviewer_id')->nullable()->after('evidence_path')
        ->constrained('users')->nullOnDelete();
    $table->timestamp('reviewed_at')->nullable()->after('reviewer_id');

    // Indexes
    $table->index('screening_outcome');
    $table->index('placement_interest');

    // NOTE: Keep old columns (call_count, call_duration, next_call_date, etc.)
    // for historical data - mark as deprecated in model comments
});
```

### Phase 2: Backend Changes

#### 2.1 Create PlacementInterest Enum

```php
// app/Enums/PlacementInterest.php
<?php

namespace App\Enums;

enum PlacementInterest: string
{
    case LOCAL = 'local';
    case INTERNATIONAL = 'international';

    public function label(): string
    {
        return match($this) {
            self::LOCAL => 'Local Placement',
            self::INTERNATIONAL => 'International Placement',
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

#### 2.2 Create Country Model

```php
// app/Models/Country.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $fillable = ['name', 'code', 'code_2', 'is_destination', 'is_active'];

    protected $casts = [
        'is_destination' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeDestinations($query)
    {
        return $query->where('is_destination', true)->where('is_active', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
```

#### 2.3 Update CandidateScreening Model

Add to `app/Models/CandidateScreening.php`:

```php
// Add to $fillable array:
'consent_for_work',
'placement_interest',
'target_country_id',
'screening_outcome',
'reviewer_id',
'reviewed_at',

// Add to $casts array:
'consent_for_work' => 'boolean',
'reviewed_at' => 'datetime',

// Add relationship:
public function targetCountry()
{
    return $this->belongsTo(Country::class, 'target_country_id');
}

public function reviewer()
{
    return $this->belongsTo(User::class, 'reviewer_id');
}

// Add new screening outcome constants (keep old STATUS_ constants for backwards compatibility)
const OUTCOME_PENDING = 'pending';
const OUTCOME_SCREENED = 'screened';
const OUTCOME_DEFERRED = 'deferred';

// Add helper methods for new workflow
public function markAsScreened($notes = null)
{
    $this->screening_outcome = self::OUTCOME_SCREENED;
    $this->reviewer_id = auth()->id();
    $this->reviewed_at = now();

    if ($notes) {
        $this->remarks = $notes;
    }

    $this->save();

    // Update candidate status to 'screened'
    $this->candidate->update(['status' => \App\Enums\CandidateStatus::SCREENED->value]);

    return true;
}

public function markAsDeferred($reason)
{
    $this->screening_outcome = self::OUTCOME_DEFERRED;
    $this->reviewer_id = auth()->id();
    $this->reviewed_at = now();
    $this->remarks = $reason;

    $this->save();

    // Update candidate status to 'deferred'
    $this->candidate->update(['status' => \App\Enums\CandidateStatus::DEFERRED->value]);

    return true;
}

// DEPRECATED: Mark old 3-call methods as deprecated but keep them
/** @deprecated Use markAsScreened() instead */
public function incrementCallCount() { /* ... existing code ... */ }
```

#### 2.4 Create InitialScreeningRequest

```php
// app/Http/Requests/InitialScreeningRequest.php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitialScreeningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policy handles authorization
    }

    public function rules(): array
    {
        return [
            'candidate_id' => 'required|exists:candidates,id',
            'consent_for_work' => 'required|boolean|accepted',
            'placement_interest' => 'required|in:local,international',
            'target_country_id' => 'required_if:placement_interest,international|nullable|exists:countries,id',
            'screening_outcome' => 'required|in:pending,screened,deferred',
            'notes' => 'nullable|string|max:2000',
            'evidence' => 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png',
        ];
    }

    public function messages(): array
    {
        return [
            'consent_for_work.accepted' => 'Candidate must consent to work before screening.',
            'target_country_id.required_if' => 'Target country is required for international placement.',
        ];
    }
}
```

#### 2.5 Update ScreeningController

Add new methods to `app/Http/Controllers/ScreeningController.php`:

```php
/**
 * Display Initial Screening form for a candidate
 */
public function initialScreening(Candidate $candidate)
{
    $this->authorize('create', CandidateScreening::class);

    // Check if candidate is in correct status (must have completed pre-departure docs)
    if (!in_array($candidate->status, ['pre_departure_docs', 'screening'])) {
        return back()->with('error', 'Candidate must complete Pre-Departure Documents before screening.');
    }

    $countries = Country::destinations()->orderBy('name')->get();
    $existingScreening = $candidate->screenings()->latest()->first();

    return view('screening.initial-screening', compact('candidate', 'countries', 'existingScreening'));
}

/**
 * Store Initial Screening result
 */
public function storeInitialScreening(InitialScreeningRequest $request, Candidate $candidate)
{
    $this->authorize('create', CandidateScreening::class);

    $validated = $request->validated();

    try {
        DB::beginTransaction();

        // Create or update screening record
        $screening = $candidate->screenings()->updateOrCreate(
            ['candidate_id' => $candidate->id, 'screening_type' => 'initial'],
            [
                'consent_for_work' => $validated['consent_for_work'],
                'placement_interest' => $validated['placement_interest'],
                'target_country_id' => $validated['target_country_id'] ?? null,
                'screening_outcome' => $validated['screening_outcome'],
                'remarks' => $validated['notes'] ?? null,
                'screened_by' => auth()->id(),
                'screened_at' => now(),
            ]
        );

        // Handle evidence upload
        if ($request->hasFile('evidence')) {
            $screening->uploadEvidence($request->file('evidence'));
        }

        // Process outcome
        if ($validated['screening_outcome'] === 'screened') {
            $screening->markAsScreened($validated['notes'] ?? null);
            $message = 'Candidate screened successfully. Ready for Registration.';
        } elseif ($validated['screening_outcome'] === 'deferred') {
            $screening->markAsDeferred($validated['notes'] ?? 'Deferred');
            $message = 'Candidate screening deferred.';
        } else {
            $message = 'Screening saved as pending.';
        }

        activity()
            ->performedOn($candidate)
            ->causedBy(auth()->user())
            ->withProperties([
                'screening_outcome' => $validated['screening_outcome'],
                'placement_interest' => $validated['placement_interest'],
            ])
            ->log('Initial screening recorded');

        DB::commit();

        return redirect()->route('candidates.show', $candidate)
            ->with('success', $message);

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->withInput()->with('error', 'Failed to save screening: ' . $e->getMessage());
    }
}

/**
 * Initial Screening Dashboard
 */
public function initialScreeningDashboard()
{
    $this->authorize('viewAny', CandidateScreening::class);

    $user = auth()->user();

    // Base query with campus filtering
    $baseQuery = Candidate::query();
    if ($user->isCampusAdmin() && $user->campus_id) {
        $baseQuery->where('campus_id', $user->campus_id);
    }

    // Statistics
    $stats = [
        'pending' => (clone $baseQuery)->where('status', 'screening')->count(),
        'screened' => (clone $baseQuery)->where('status', 'screened')->count(),
        'deferred' => (clone $baseQuery)->where('status', 'deferred')->count(),
        'total_this_month' => CandidateScreening::whereMonth('reviewed_at', now()->month)
            ->whereNotNull('reviewed_at')->count(),
    ];

    // Pending candidates for screening
    $pendingCandidates = (clone $baseQuery)
        ->whereIn('status', ['pre_departure_docs', 'screening'])
        ->with(['campus', 'trade', 'oep'])
        ->latest()
        ->paginate(20);

    // Recently screened
    $recentlyScreened = (clone $baseQuery)
        ->where('status', 'screened')
        ->with(['campus', 'trade'])
        ->latest('updated_at')
        ->limit(10)
        ->get();

    return view('screening.initial-screening-dashboard', compact('stats', 'pendingCandidates', 'recentlyScreened'));
}
```

#### 2.6 Add Routes

Add to `routes/web.php`:

```php
// Initial Screening routes
Route::middleware(['auth'])->group(function () {
    Route::get('/initial-screening/dashboard', [ScreeningController::class, 'initialScreeningDashboard'])
        ->name('screening.initial-dashboard');
    Route::get('/candidates/{candidate}/initial-screening', [ScreeningController::class, 'initialScreening'])
        ->name('candidates.initial-screening');
    Route::post('/candidates/{candidate}/initial-screening', [ScreeningController::class, 'storeInitialScreening'])
        ->name('candidates.initial-screening.store');
});
```

### Phase 3: Frontend Implementation

#### 3.1 Create Initial Screening Dashboard View

Create `resources/views/screening/initial-screening-dashboard.blade.php`:

Use the same Tailwind CSS styling as Module 1 (Pre-Departure Documents). Include:
- Stats cards (Pending, Screened, Deferred, This Month)
- Table of pending candidates with action buttons
- Recently screened section
- Filter by campus (for admins)

#### 3.2 Create Initial Screening Form View

Create `resources/views/screening/initial-screening.blade.php`:

Include:
- Candidate info header (name, TheLeap ID, status badge)
- Consent for work checkbox (required, with legal disclaimer)
- Placement interest radio buttons (Local / International)
- Target country dropdown (shown only if International selected, required)
- Notes textarea
- Evidence file upload
- Outcome buttons: Screen (success) / Defer (warning) / Save as Pending (secondary)

**JavaScript:** Show/hide country dropdown based on placement interest selection.

#### 3.3 Update Navigation

Add "Initial Screening" to sidebar navigation in appropriate location (after Candidate Listing, before Registration).

### Phase 4: Gate Enforcement

#### 4.1 Update CandidateStatus Enum

Ensure `app/Enums/CandidateStatus.php` has:
- `SCREENED = 'screened'` status
- Valid transition from `SCREENING` to `SCREENED`
- Gate in `canTransitionTo()` that only allows `SCREENED` → `REGISTERED`

#### 4.2 Add Registration Gate Middleware

Create `app/Http/Middleware/RequireScreenedStatus.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireScreenedStatus
{
    public function handle(Request $request, Closure $next)
    {
        $candidate = $request->route('candidate');

        if ($candidate && $candidate->status !== 'screened') {
            return redirect()->back()
                ->with('error', 'Candidate must be screened before registration.');
        }

        return $next($request);
    }
}
```

Apply to registration routes.

### Phase 5: Testing

#### 5.1 Unit Tests

Create/update `tests/Unit/InitialScreeningTest.php`:

```php
public function test_screening_requires_consent()
public function test_international_placement_requires_country()
public function test_screened_status_allows_registration()
public function test_deferred_status_blocks_registration()
public function test_screening_outcome_updates_candidate_status()
```

#### 5.2 Feature Tests

Create/update `tests/Feature/InitialScreeningControllerTest.php`:

```php
public function test_initial_screening_dashboard_loads()
public function test_initial_screening_form_loads()
public function test_can_screen_candidate()
public function test_can_defer_candidate()
public function test_cannot_screen_without_consent()
public function test_cannot_register_unscreened_candidate()
public function test_campus_admin_sees_only_own_campus()
```

#### 5.3 Run All Tests

```bash
php artisan test --filter=Screening
php artisan test --testsuite=Feature
```

### Phase 6: Documentation

#### 6.1 Update CLAUDE.md

Add Initial Screening to the module list and update version.

#### 6.2 Update README.md

Add Module 2 section with feature description.

#### 6.3 Create MODULE_2_INITIAL_SCREENING.md

Similar to MODULE_1_CANDIDATE_LISTING_PRE_DEPARTURE.md with:
- Overview
- Features
- UI wireframes (ASCII)
- Workflow diagram
- Testing checklist

---

## RBAC Requirements

| Action | Super Admin | Admin | Campus Admin | OEP | Viewer |
|--------|:-----------:|:-----:|:------------:|:---:|:------:|
| View Dashboard | ✓ | ✓ | Campus Only | ✗ | ✓ |
| Perform Screening | ✓ | ✓ | Campus Only | ✗ | ✗ |
| Override Status | ✓ | ✓ | ✗ | ✗ | ✗ |
| Export Reports | ✓ | ✓ | Campus Only | ✗ | ✓ |

---

## Validation Checklist

After implementation, verify:

- [ ] Countries table exists with destination countries
- [ ] Migration adds new fields without removing old ones
- [ ] PlacementInterest enum created
- [ ] CandidateScreening model updated with new fields and methods
- [ ] InitialScreeningRequest validates all inputs
- [ ] ScreeningController has new methods
- [ ] Routes added and working
- [ ] Initial Screening Dashboard loads with stats
- [ ] Initial Screening Form works with JS for country toggle
- [ ] Consent is required (validation error if unchecked)
- [ ] International requires country (validation error if missing)
- [ ] Screened status updates candidate to 'screened'
- [ ] Deferred status updates candidate to 'deferred'
- [ ] Only 'screened' candidates can proceed to Registration
- [ ] Evidence upload works
- [ ] Activity logging works
- [ ] Campus admin filtering works
- [ ] All tests pass
- [ ] Documentation updated

---

## Common Pitfalls to Avoid

1. **DO NOT** delete the old 3-call columns - mark them as deprecated
2. **DO NOT** create a new Screening model - update CandidateScreening
3. **DO NOT** break existing screening functionality - ensure backward compatibility
4. **DO NOT** hardcode country list - use database
5. **DO NOT** skip the consent validation - it's a legal requirement
6. **DO NOT** allow registration without screened status - enforce the gate
7. **DO NOT** forget campus admin filtering on all queries

---

## UI Design Guidelines

Match the styling of Module 1 (Pre-Departure Documents):
- Use Tailwind CSS
- Gradient headers (purple/blue theme)
- Card-based layouts
- Status badges with appropriate colors
- Clean, modern look matching the dashboard

---

## Files to Create

```
app/Enums/PlacementInterest.php
app/Models/Country.php
app/Http/Requests/InitialScreeningRequest.php
app/Http/Middleware/RequireScreenedStatus.php
database/migrations/YYYY_MM_DD_create_countries_table.php
database/migrations/YYYY_MM_DD_add_initial_screening_fields.php
database/seeders/CountriesSeeder.php
resources/views/screening/initial-screening.blade.php
resources/views/screening/initial-screening-dashboard.blade.php
tests/Unit/InitialScreeningTest.php
tests/Feature/InitialScreeningControllerTest.php
docs/MODULE_2_INITIAL_SCREENING.md
```

## Files to Modify

```
app/Models/CandidateScreening.php
app/Enums/CandidateStatus.php
app/Http/Controllers/ScreeningController.php
app/Policies/CandidateScreeningPolicy.php
routes/web.php
resources/views/layouts/app.blade.php (navigation)
CLAUDE.md
README.md
```

---

## Success Criteria

Module 2 is complete when:

1. All new features work as specified
2. All tests pass (unit and feature)
3. Old functionality is preserved (backwards compatible)
4. UI matches project design standards
5. Gate enforcement prevents unscreened registration
6. Documentation is updated
7. No regression in existing tests

---

*End of Implementation Prompt*
