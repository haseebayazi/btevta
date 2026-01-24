# Laravel Test Failures Fix Plan

## Executive Summary

**Test Report Analysis**: Test_Report_v6.txt
- **Total Tests**: 1,324
- **Passing**: 615 (46.4%)
- **Errors**: 399
- **Failures**: 310
- **Total Failing**: 709 (53.6%)
- **Risky**: 3

This document provides a comprehensive, phased approach to fixing all 709 test failures in the BTEVTA Overseas Employment System (WASL).

---

## Root Cause Analysis

### Critical Issues (Blocking Many Tests)

| Issue | Impact | Tests Affected |
|-------|--------|----------------|
| ComplaintFactory Parse Error | Syntax error blocks all complaint tests | ~50+ tests |
| Missing Rate Limiter `App\Models\User::api` | All API tests return 500 | ~200+ tests |
| BatchFactory missing `start_date` | AutoBatchService tests fail | ~15 tests |
| VisaProcessFactory invalid column `takamol_remarks` | VisaProcessingService tests fail | ~45 tests |
| RemittanceFactory missing `receiver_name` | Remittance tests fail | ~30 tests |
| TrainingCertificateFactory missing `batch_id` | Certificate tests fail | ~10 tests |

### Logic/Business Rule Issues

| Issue | Tests Affected |
|-------|----------------|
| CandidateModel Luhn check digit calculation incorrect | ~5 tests |
| CandidateStateMachine status_remarks not persisting | ~3 tests |
| canTransitionTo() returns array instead of boolean | ~3 tests |
| AllocationService program active validation | ~4 tests |
| CandidatePolicy inactive user check not working | ~5 tests |

### Authorization/Route Issues

| Issue | Tests Affected |
|-------|----------------|
| Campus admin batches access - 404 instead of 403 | ~3 tests |
| Super admin campus access - 404 | ~2 tests |
| System settings access returning 200 instead of 403 | ~3 tests |

---

## Phase 1: Critical Infrastructure Fixes

**Priority**: HIGHEST
**Estimated Impact**: Fixes ~300+ tests
**Files to Modify**: 4

### Task 1.1: Fix ComplaintFactory Parse Error

**File**: `database/factories/ComplaintFactory.php`
**Error**: `Unclosed '{' on line 12`
**Impact**: ~50+ tests

**Problem**: The factory has a syntax error with an unclosed brace.

**Action**:
1. Open `database/factories/ComplaintFactory.php`
2. Locate the unclosed brace around line 12
3. Fix the PHP syntax error
4. Verify with `php -l database/factories/ComplaintFactory.php`

---

### Task 1.2: Define Missing API Rate Limiter

**File**: `app/Providers/AppServiceProvider.php` or new `RouteServiceProvider.php`
**Error**: `Rate limiter [App\Models\User::api] is not defined`
**Impact**: ~200+ API tests

**Problem**: The API routes use `throttle:App\Models\User::api` but this rate limiter is not defined.

**Action**:
1. Add rate limiter definition in `AppServiceProvider.php` boot method:
```php
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

public function boot(): void
{
    RateLimiter::for('api', function (Request $request) {
        return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
    });

    // Also define User::api if used
    RateLimiter::for('App\Models\User::api', function (Request $request) {
        return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
    });
}
```

**Alternative**: Check if the Kernel.php api middleware is using a limiter that doesn't exist and fix the reference.

---

### Task 1.3: Fix BatchFactory - Add Missing `start_date`

**File**: `database/factories/BatchFactory.php`
**Error**: `NOT NULL constraint failed: batches.start_date`
**Impact**: ~15 tests (AutoBatchService, BatchController)

**Problem**: The factory doesn't provide `start_date` which is a required field.

**Action**:
```php
public function definition(): array
{
    return [
        // existing fields...
        'start_date' => $this->faker->dateTimeBetween('now', '+1 month'),
        'end_date' => $this->faker->dateTimeBetween('+2 months', '+4 months'),
        // ...
    ];
}
```

---

### Task 1.4: Fix VisaProcessFactory - Remove/Fix Invalid Column

**File**: `database/factories/VisaProcessFactory.php`
**Error**: `table visa_processes has no column named takamol_remarks`
**Impact**: ~45 tests (VisaProcessingService tests)

**Problem**: The factory includes `takamol_remarks` but the column doesn't exist in the migration.

**Action**:
Option A: Remove `takamol_remarks` from factory if not needed
Option B: Add migration to add the column if it's required

Check the migration first:
```bash
grep -r "takamol_remarks" database/migrations/
```

If column is intended:
```php
Schema::table('visa_processes', function (Blueprint $table) {
    $table->text('takamol_remarks')->nullable()->after('takamol_status');
});
```

---

## Phase 2: Factory Fixes

**Priority**: HIGH
**Estimated Impact**: Fixes ~50+ tests
**Files to Modify**: 5

### Task 2.1: Fix RemittanceFactory - Add Missing `receiver_name`

**File**: `database/factories/RemittanceFactory.php`
**Error**: `NOT NULL constraint failed: remittances.receiver_name`
**Impact**: ~30 tests

**Action**:
```php
public function definition(): array
{
    return [
        // existing fields...
        'receiver_name' => $this->faker->name(),
        // ...
    ];
}
```

---

### Task 2.2: Fix TrainingCertificateFactory - Add Missing `batch_id`

**File**: `database/factories/TrainingCertificateFactory.php`
**Error**: `NOT NULL constraint failed: training_certificates.batch_id`
**Impact**: ~10 tests

**Action**:
```php
public function definition(): array
{
    return [
        // existing fields...
        'batch_id' => Batch::factory(),
        // ...
    ];
}
```

---

### Task 2.3: Fix ProgramFactory - Set `is_active` to true by default

**File**: `database/factories/ProgramFactory.php`
**Error**: AllocationService tests fail with "Selected program is not active"
**Impact**: ~4 tests

**Action**:
```php
public function definition(): array
{
    return [
        // existing fields...
        'is_active' => true,  // Default to active
        // ...
    ];
}
```

---

### Task 2.4: Verify/Fix DocumentArchiveFactory

**File**: `database/factories/DocumentArchiveFactory.php`
**Error**: Various NOT NULL constraints
**Impact**: ~12 tests

**Action**: Review and ensure all required fields are provided.

---

### Task 2.5: Review All Factories for Required Fields

Run audit:
```bash
grep -r "NOT NULL constraint failed" Test_Report_v6.txt | cut -d: -f3 | sort -u
```

Review each table mentioned and ensure corresponding factory provides all required fields.

---

## Phase 3: Model Logic Fixes

**Priority**: HIGH
**Estimated Impact**: Fixes ~20+ tests
**Files to Modify**: 3

### Task 3.1: Fix Candidate Model - Luhn Check Digit Calculation

**File**: `app/Models/Candidate.php`
**Error**: `Failed asserting that 3 matches expected 0`
**Impact**: ~5 tests

**Problem**: The `calculateLuhnCheckDigit()` method produces incorrect check digits.

**Action**:
Review and fix the Luhn algorithm implementation:
```php
public static function calculateLuhnCheckDigit(string $number): int
{
    $sum = 0;
    $length = strlen($number);
    $parity = $length % 2;

    for ($i = $length - 1; $i >= 0; $i--) {
        $digit = (int) $number[$i];

        if (($length - 1 - $i) % 2 === 0) {
            $digit *= 2;
            if ($digit > 9) {
                $digit -= 9;
            }
        }

        $sum += $digit;
    }

    return (10 - ($sum % 10)) % 10;
}
```

Also fix `validateBtevtaId()` method.

---

### Task 3.2: Fix Candidate State Machine - Status Remarks Persistence

**File**: `app/Models/Candidate.php` (or dedicated state machine class)
**Error**: `Failed asserting that null matches expected 'Passed initial screening call'`
**Impact**: ~3 tests

**Problem**: `updateStatus()` method doesn't persist the `status_remarks` field.

**Action**:
```php
public function updateStatus(string $newStatus, ?string $remarks = null): bool
{
    $this->status = $newStatus;

    if ($remarks !== null) {
        $this->status_remarks = $remarks;
    }

    return $this->save();
}
```

---

### Task 3.3: Fix canTransitionTo() Return Type

**File**: `app/Models/Candidate.php`
**Error**: `Failed asserting that Array [...] is true`
**Impact**: ~3 tests

**Problem**: Method returns array `['can_transition' => true, 'issues' => []]` but tests expect boolean.

**Action**:
Option A: Fix the method to return boolean:
```php
public function canTransitionTo(string $status): bool
{
    $result = $this->checkTransitionRules($status);
    return $result['can_transition'];
}
```

Option B: If array return is intentional, update tests to use:
```php
$this->assertTrue($candidate->canTransitionTo(...)['can_transition']);
```

---

## Phase 4: Service Logic Fixes

**Priority**: MEDIUM
**Estimated Impact**: Fixes ~30+ tests
**Files to Modify**: 8

### Task 4.1: Fix AllocationService - Allocation Summary Return Type

**File**: `app/Services/AllocationService.php`
**Error**: `[...] does not match expected type "string"`
**Impact**: ~4 tests

**Problem**: The `getAllocationSummary()` method returns objects instead of strings for campus/program names.

**Action**:
```php
public function getAllocationSummary($allocation): array
{
    return [
        'campus' => $allocation->campus->name ?? null,  // Extract name string
        'program' => $allocation->program->name ?? null,
        'trade' => $allocation->trade->name ?? null,
        'implementing_partner' => $allocation->oep->name ?? null,
    ];
}
```

---

### Task 4.2: Fix AllocationService - bulkAllocate Return Type

**File**: `app/Services/AllocationService.php`
**Error**: `Argument #2 ($haystack) must be of type Countable|Traversable|array, bool given`
**Impact**: ~2 tests

**Problem**: `bulkAllocate()` returns `false` on error instead of expected array structure.

**Action**:
```php
public function bulkAllocate(array $candidateIds, array $data): array
{
    $results = ['success' => [], 'failed' => []];

    // Always return array structure, never bool
    foreach ($candidateIds as $id) {
        try {
            $this->allocate($id, $data);
            $results['success'][] = $id;
        } catch (\Exception $e) {
            $results['failed'][] = ['id' => $id, 'error' => $e->getMessage()];
        }
    }

    return $results;
}
```

---

### Task 4.3: Fix DepartureService - Pre-Departure Briefing

**File**: `app/Services/DepartureService.php`
**Error**: Various briefing-related test failures
**Impact**: ~3 tests

**Action**: Review and fix `recordPreDepartureBriefing()` method.

---

### Task 4.4: Fix DocumentArchiveService - Upload and Statistics

**File**: `app/Services/DocumentArchiveService.php`
**Error**: Multiple document-related test failures
**Impact**: ~12 tests

**Action**: Review upload, version increment, and statistics calculation methods.

---

### Task 4.5: Fix RegistrationService - All Methods

**File**: `app/Services/RegistrationService.php`
**Error**: All 14 tests failing
**Impact**: ~14 tests

**Action**: Complete review - methods may not be implemented or returning unexpected results.

---

### Task 4.6: Fix TrainingService Methods

**File**: `app/Services/TrainingService.php`
**Error**: Multiple training-related failures
**Impact**: ~15 tests

**Action**: Review `startBatchTraining()`, `calculateAttendanceStats()`, `recordAssessment()`, `generateCertificate()`.

---

### Task 4.7: Fix RemittanceAlertService Methods

**File**: `app/Services/RemittanceAlertService.php`
**Error**: Multiple alert-related failures
**Impact**: ~12 tests

**Action**: Review alert generation and auto-resolve methods.

---

### Task 4.8: Fix RemittanceAnalyticsService Methods

**File**: `app/Services/RemittanceAnalyticsService.php`
**Error**: All analytics methods failing
**Impact**: ~7 tests

**Action**: Review dashboard stats, monthly trends, purpose/method analysis.

---

## Phase 5: Policy/Authorization Fixes

**Priority**: MEDIUM
**Estimated Impact**: Fixes ~25+ tests
**Files to Modify**: 10

### Task 5.1: Fix CandidatePolicy - Inactive User Check

**File**: `app/Policies/CandidatePolicy.php`
**Error**: `Failed asserting that true is false` - inactive user can still access
**Impact**: ~5 tests

**Action**:
```php
public function before(User $user, string $ability): ?bool
{
    // Check if user is inactive first
    if (!$user->is_active) {
        return false;
    }

    // Super admin bypass
    if ($user->role === 'super_admin') {
        return true;
    }

    return null;
}
```

---

### Task 5.2: Fix Batch Access - 404 vs 403 Response

**File**: `app/Http/Controllers/BatchController.php` or route definition
**Error**: Expected 403 but received 404
**Impact**: ~3 tests

**Problem**: Campus admin trying to access other campus batch gets 404 instead of 403.

**Action**: Add policy check before fetching batch:
```php
public function show(Batch $batch)
{
    $this->authorize('view', $batch);
    // ...
}
```

---

### Task 5.3: Fix Campus Show Route

**File**: Route configuration or CampusController
**Error**: Super admin gets 404 on `/campuses/{id}`
**Impact**: ~2 tests

**Action**: Verify route exists and returns proper response.

---

### Task 5.4: Fix System Settings Access

**File**: `app/Http/Controllers/Admin/SettingsController.php`
**Error**: Admin should get 403 but gets 200
**Impact**: ~3 tests

**Action**: Add proper authorization middleware/policy.

---

### Task 5.5: Fix ComplaintPolicies

**File**: `app/Policies/ComplaintEvidencePolicy.php`, `app/Policies/ComplaintUpdatePolicy.php`
**Error**: Various authorization failures
**Impact**: ~6 tests

**Action**: Review and fix campus admin evidence/update access policies.

---

### Task 5.6: Fix RegistrationPolicies

**File**: `app/Policies/NextOfKinPolicy.php`, `app/Policies/UndertakingPolicy.php`
**Error**: Various authorization failures
**Impact**: ~4 tests

**Action**: Review campus admin access to next of kin and undertaking records.

---

### Task 5.7: Fix RemittancePolicies

**File**: `app/Policies/RemittanceReceiptPolicy.php`, `app/Policies/RemittanceBreakdownPolicy.php`
**Error**: OEP and super admin authorization issues
**Impact**: ~8 tests

---

### Task 5.8: Fix TrainingPolicies

**File**: `app/Policies/TrainingAssessmentPolicy.php`, `app/Policies/TrainingAttendancePolicy.php`, etc.
**Error**: Various training authorization issues
**Impact**: ~7 tests

---

### Task 5.9: Fix SystemPolicies

**File**: `app/Policies/SystemSettingPolicy.php`, `app/Policies/VisaPartnerPolicy.php`, etc.
**Error**: Super admin create/update/delete authorization
**Impact**: ~10 tests

---

### Task 5.10: Fix GlobalSearchService Authorization

**File**: `app/Services/GlobalSearchService.php`
**Error**: Campus admin search scope issues
**Impact**: ~5 tests

---

## Phase 6: Test-Specific Fixes

**Priority**: LOWER
**Estimated Impact**: Fixes ~30+ tests
**Files to Modify**: Various test files

### Task 6.1: Fix DataValidationEdgeCasesTest Route Issues

**File**: `tests/Unit/DataValidationEdgeCasesTest.php`
**Error**: Tests hitting API routes without proper setup
**Impact**: ~23 tests

**Action**: Tests need to either:
- Use proper API route setup with authentication
- Or use direct model/validation testing instead of HTTP requests

---

### Task 6.2: Fix CandidateDeduplicationServiceTest

**File**: `tests/Unit/CandidateDeduplicationServiceTest.php`
**Error**: Name similarity calculation returning higher scores than expected
**Impact**: ~5 tests

**Action**: Review test expectations vs actual algorithm behavior.

---

### Task 6.3: Fix DocumentTagTest Model Setup

**File**: `tests/Unit/DocumentTagTest.php`
**Error**: Multiple factory/relationship issues
**Impact**: ~18 tests

**Action**: Review DocumentTag model and factory configuration.

---

### Task 6.4: Fix StateTransitionEdgeCasesTest

**File**: `tests/Unit/StateTransitionEdgeCasesTest.php`
**Error**: Transition prerequisite checks not working
**Impact**: ~8 tests

**Action**: Review transition rules and prerequisite validation.

---

### Task 6.5: Fix EnumDatabaseConsistencyTest

**File**: `tests/Unit/EnumDatabaseConsistencyTest.php`
**Error**: Enum storage issues
**Impact**: ~4 tests

**Action**: Review enum values vs database constraints.

---

### Task 6.6: Fix LoadTest Performance Tests

**File**: `tests/Feature/LoadTest.php`
**Error**: Various load test failures
**Impact**: ~8 tests

**Action**: Review test data setup and performance expectations.

---

## Phase 7: Feature Test Fixes

**Priority**: LOWER
**Estimated Impact**: Fixes ~150+ tests
**Files to Modify**: Various controllers

### Task 7.1: Fix BatchApiController Tests

**File**: `app/Http/Controllers/Api/BatchApiController.php`
**Error**: All 27 tests failing (rate limiter issue + logic)
**Impact**: ~27 tests

**Action**: After Phase 1 rate limiter fix, review remaining logic issues.

---

### Task 7.2: Fix CandidateApiController Tests

**File**: `app/Http/Controllers/Api/CandidateApiController.php`
**Error**: 20+ tests failing
**Impact**: ~20 tests

---

### Task 7.3: Fix ComplaintController Tests

**File**: `app/Http/Controllers/ComplaintController.php`
**Error**: 33+ tests failing
**Impact**: ~33 tests

---

### Task 7.4: Fix CorrespondenceRestApiTest

**File**: `app/Http/Controllers/Api/CorrespondenceApiController.php`
**Error**: 15 tests failing
**Impact**: ~15 tests

---

### Task 7.5: Fix DepartureApiController Tests

**File**: `app/Http/Controllers/Api/DepartureApiController.php`
**Error**: 12 tests failing
**Impact**: ~12 tests

---

### Task 7.6: Fix RemittanceController Tests

**File**: `app/Http/Controllers/RemittanceController.php`
**Error**: 14 tests failing
**Impact**: ~14 tests

---

### Task 7.7: Fix ScreeningController Tests

**File**: Multiple screening controllers
**Error**: 25+ tests failing
**Impact**: ~25 tests

---

### Task 7.8: Fix VisaProcessingController Tests

**File**: `app/Http/Controllers/VisaProcessingController.php`
**Error**: 25+ tests failing
**Impact**: ~25 tests

---

### Task 7.9: Fix UserController Tests

**File**: `app/Http/Controllers/UserController.php`
**Error**: 12 tests failing
**Impact**: ~12 tests

---

### Task 7.10: Fix Remaining Feature Tests

Review and fix:
- BatchImportIntegrationTest
- BulkOperationsControllerTest
- CandidateLifecycleIntegrationTest
- DocumentArchiveAdvancedSearchTest
- DocumentArchiveVersionComparisonTest
- RegistrationApiTest
- RegistrationControllerTest
- TrainingApiTest
- ValidationApiTest

---

## Implementation Order & Dependencies

```
Phase 1 (CRITICAL - Do First)
├── Task 1.1: ComplaintFactory syntax fix
├── Task 1.2: Rate Limiter definition
├── Task 1.3: BatchFactory start_date
└── Task 1.4: VisaProcessFactory column fix

Phase 2 (HIGH - After Phase 1)
├── Task 2.1: RemittanceFactory receiver_name
├── Task 2.2: TrainingCertificateFactory batch_id
├── Task 2.3: ProgramFactory is_active
├── Task 2.4: DocumentArchiveFactory review
└── Task 2.5: Audit all factories

Phase 3 (HIGH - After Phase 2)
├── Task 3.1: Luhn check digit fix
├── Task 3.2: Status remarks persistence
└── Task 3.3: canTransitionTo return type

Phase 4 (MEDIUM - After Phase 3)
├── Task 4.1-4.8: Service logic fixes
└── (Can run in parallel)

Phase 5 (MEDIUM - After Phase 3)
├── Task 5.1-5.10: Policy/authorization fixes
└── (Can run in parallel with Phase 4)

Phase 6 (LOWER - After Phase 4 & 5)
├── Task 6.1-6.6: Test-specific fixes
└── (Some may be resolved by earlier phases)

Phase 7 (LOWER - After all above)
├── Task 7.1-7.10: Feature test fixes
└── (Many will be auto-resolved by earlier phases)
```

---

## Testing Strategy

### After Each Phase

Run targeted tests to verify fixes:

```bash
# Phase 1 verification
php artisan test --filter=ComplaintServiceTest
php artisan test --filter=BatchApiControllerTest
php artisan test --filter=AutoBatchServiceTest
php artisan test --filter=VisaProcessingServiceTest

# Phase 2 verification
php artisan test --filter=RemittanceTest
php artisan test --filter=TrainingCertificate
php artisan test --filter=AllocationServiceTest

# Full suite after all phases
php artisan test
```

### Expected Results Per Phase

| Phase | Expected Tests Fixed | Cumulative Pass Rate |
|-------|---------------------|---------------------|
| Phase 1 | ~300 | ~70% |
| Phase 2 | ~50 | ~75% |
| Phase 3 | ~20 | ~77% |
| Phase 4 | ~30 | ~80% |
| Phase 5 | ~25 | ~82% |
| Phase 6 | ~30 | ~85% |
| Phase 7 | ~150 | ~95%+ |

---

## Quick Reference: Files to Modify

### Critical Files (Phase 1)
- `database/factories/ComplaintFactory.php`
- `app/Providers/AppServiceProvider.php`
- `database/factories/BatchFactory.php`
- `database/factories/VisaProcessFactory.php`

### Factory Files (Phase 2)
- `database/factories/RemittanceFactory.php`
- `database/factories/TrainingCertificateFactory.php`
- `database/factories/ProgramFactory.php`
- `database/factories/DocumentArchiveFactory.php`

### Model Files (Phase 3)
- `app/Models/Candidate.php`

### Service Files (Phase 4)
- `app/Services/AllocationService.php`
- `app/Services/DepartureService.php`
- `app/Services/DocumentArchiveService.php`
- `app/Services/RegistrationService.php`
- `app/Services/TrainingService.php`
- `app/Services/RemittanceAlertService.php`
- `app/Services/RemittanceAnalyticsService.php`
- `app/Services/ComplaintService.php`

### Policy Files (Phase 5)
- `app/Policies/CandidatePolicy.php`
- `app/Policies/ComplaintEvidencePolicy.php`
- `app/Policies/ComplaintUpdatePolicy.php`
- `app/Policies/NextOfKinPolicy.php`
- `app/Policies/UndertakingPolicy.php`
- `app/Policies/RemittanceReceiptPolicy.php`
- `app/Policies/RemittanceBreakdownPolicy.php`
- `app/Policies/TrainingAssessmentPolicy.php`
- `app/Policies/SystemSettingPolicy.php`
- `app/Policies/VisaPartnerPolicy.php`

---

## Notes

1. **Run tests frequently** after each task to catch regressions
2. **Phase 1 is blocking** - must be completed before other phases will show improvement
3. **Some tests may pass automatically** after earlier phases complete
4. **Consider creating branches** for each phase for easier rollback
5. **Document any intentional test changes** in commit messages

---

## Version History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2026-01-23 | Claude | Initial comprehensive plan |

