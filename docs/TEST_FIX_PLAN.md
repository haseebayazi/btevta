# Comprehensive Test Fix Plan

**Date:** January 23, 2026
**Report Source:** `/Test_Report.md`
**Total Tests:** 1324
**Status:** 1304 failed, 20 passed (84 assertions)

---

## Executive Summary

The test suite is currently experiencing **1,304 test failures** caused by three distinct categories of issues:

| Category | Count | Impact | Priority |
|----------|-------|--------|----------|
| SQLite Compatibility | 1,301 errors | Critical - blocks all tests | P0 |
| Enum Test Mismatches | 3 failures | Medium - outdated tests | P1 |
| PHPUnit Deprecations | 1,203 warnings | Low - future compatibility | P2 |

---

## Phase 1: Critical - SQLite Compatibility Fix (P0)

### Problem Description

The migration file `database/migrations/2025_12_31_000001_phase1_fix_enum_database_mismatches.php` uses MySQL-specific SQL syntax that fails when running tests with SQLite:

```php
// Line 97 - MySQL-specific syntax
$columnType = DB::select("SHOW COLUMNS FROM candidates WHERE Field = 'training_status'");
```

**Error:**
```
SQLSTATE[HY000]: General error: 1 near "SHOW": syntax error
(Connection: sqlite, SQL: SHOW COLUMNS FROM candidates WHERE Field = 'training_status')
```

### Root Cause

- Tests run using SQLite (in-memory) for speed
- `SHOW COLUMNS` is MySQL-specific and has no SQLite equivalent
- The migration attempts to check column type before modifying it

### Solution

**Option A: Database Driver Detection (Recommended)**

```php
// Replace lines 92-102 with:
if (Schema::hasTable('candidates') && Schema::hasColumn('candidates', 'training_status')) {
    $driver = Schema::getConnection()->getDriverName();

    if ($driver === 'mysql') {
        // MySQL: Check if it's an enum and convert to string
        $columnType = DB::select("SHOW COLUMNS FROM candidates WHERE Field = 'training_status'");

        if (!empty($columnType) && str_contains($columnType[0]->Type, 'enum')) {
            DB::statement("ALTER TABLE candidates MODIFY COLUMN training_status VARCHAR(50) DEFAULT 'pending'");
        }
    } elseif ($driver === 'sqlite') {
        // SQLite: Column is already string-compatible, no action needed
        // SQLite doesn't support ENUM types, so columns are already flexible
    }

    // Data updates work for both databases
    DB::table('candidates')
        ->where('training_status', 'ongoing')
        ->update(['training_status' => 'in_progress']);

    // ... rest of logging code
}
```

**Option B: Skip Migration in Testing**

Add a check at the beginning of the migration:

```php
public function up(): void
{
    // Skip MySQL-specific operations in SQLite (testing environment)
    if (Schema::getConnection()->getDriverName() === 'sqlite') {
        return;
    }

    // ... existing migration code
}
```

### Files to Modify

| File | Action |
|------|--------|
| `database/migrations/2025_12_31_000001_phase1_fix_enum_database_mismatches.php` | Add SQLite compatibility |

### Expected Outcome

- All 1,301 SQLite-related test errors will be resolved
- Tests will run successfully in both SQLite and MySQL environments

---

## Phase 2: Enum Test Fixes (P1)

### Problem Description

Three tests in `tests/Unit/WASLv3EnumsTest.php` are failing because the test expectations don't match the current enum definitions.

### Failure 1: ScreeningStatus Count Mismatch

**Test:** `updated_screening_status_enum_has_correct_values()`
**Location:** `tests/Unit/WASLv3EnumsTest.php:167`

**Test Expects:**
```php
$this->assertCount(2, $cases);
// Expected: screened, deferred
```

**Actual Enum (`app/Enums/ScreeningStatus.php`):**
```php
case PENDING = 'pending';    // NEW
case SCREENED = 'screened';
case DEFERRED = 'deferred';
// Total: 3 values
```

**Fix:**
```php
public function updated_screening_status_enum_has_correct_values()
{
    $cases = ScreeningStatus::cases();

    $this->assertCount(3, $cases);
    $this->assertEquals('pending', ScreeningStatus::PENDING->value);
    $this->assertEquals('screened', ScreeningStatus::SCREENED->value);
    $this->assertEquals('deferred', ScreeningStatus::DEFERRED->value);
}
```

### Failure 2: CandidateStatus Active Statuses

**Test:** `candidate_status_has_active_statuses()`
**Location:** `tests/Unit/WASLv3EnumsTest.php:203`

**Test Expects (Outdated Values):**
```php
$activeStatuses = [
    'initial',           // Does NOT exist
    'screening',         // EXISTS
    'screened',          // EXISTS
    'registered',        // EXISTS
    'training',          // EXISTS
    'training_completed',// EXISTS
    'visa_processing',   // Does NOT exist (actual: visa_process)
    'visa_received',     // Does NOT exist (actual: visa_approved)
    'pre_departure',     // Does NOT exist (actual: pre_departure_docs)
    'departed',          // EXISTS
    'post_arrival',      // Does NOT exist (actual: post_departure)
    'employed',          // Does NOT exist
    'remitting',         // Does NOT exist
    'success_story',     // Does NOT exist
];
```

**Actual Enum (`app/Enums/CandidateStatus.php`):**
```php
// Active statuses:
case LISTED = 'listed';
case PRE_DEPARTURE_DOCS = 'pre_departure_docs';
case SCREENING = 'screening';
case SCREENED = 'screened';
case REGISTERED = 'registered';
case TRAINING = 'training';
case TRAINING_COMPLETED = 'training_completed';
case VISA_PROCESS = 'visa_process';
case VISA_APPROVED = 'visa_approved';
case DEPARTURE_PROCESSING = 'departure_processing';
case READY_TO_DEPART = 'ready_to_depart';
case DEPARTED = 'departed';
case POST_DEPARTURE = 'post_departure';
```

**Fix:**
```php
public function candidate_status_has_active_statuses()
{
    $activeStatuses = [
        'listed',
        'pre_departure_docs',
        'screening',
        'screened',
        'registered',
        'training',
        'training_completed',
        'visa_process',
        'visa_approved',
        'departure_processing',
        'ready_to_depart',
        'departed',
        'post_departure',
    ];

    foreach ($activeStatuses as $status) {
        $enum = CandidateStatus::tryFrom($status);
        $this->assertNotNull($enum, "Active status '$status' should exist");
    }
}
```

### Failure 3: CandidateStatus Terminal Statuses

**Test:** `candidate_status_has_terminal_statuses()`
**Location:** `tests/Unit/WASLv3EnumsTest.php:218`

**Test Expects (Outdated Values):**
```php
$terminalStatuses = [
    'rejected',     // EXISTS
    'dropped_out',  // Does NOT exist (actual: withdrawn)
    'returned',     // Does NOT exist
];
```

**Actual Enum Terminal States:**
```php
case DEFERRED = 'deferred';
case REJECTED = 'rejected';
case WITHDRAWN = 'withdrawn';
```

**Fix:**
```php
public function candidate_status_has_terminal_statuses()
{
    $terminalStatuses = [
        'deferred',
        'rejected',
        'withdrawn',
    ];

    foreach ($terminalStatuses as $status) {
        $enum = CandidateStatus::tryFrom($status);
        $this->assertNotNull($enum, "Terminal status '$status' should exist");
    }
}
```

### Also Update: CandidateStatus Count

The test `candidate_status_enum_has_17_statuses()` expects 17 statuses, but actual count may differ:
- 13 active + 3 terminal + 1 completed = 17 total (if count matches, no change needed)

### Files to Modify

| File | Lines | Action |
|------|-------|--------|
| `tests/Unit/WASLv3EnumsTest.php` | 163-170 | Update ScreeningStatus count to 3 |
| `tests/Unit/WASLv3EnumsTest.php` | 182-205 | Update active statuses list |
| `tests/Unit/WASLv3EnumsTest.php` | 207-220 | Update terminal statuses list |

### Expected Outcome

- All 3 enum assertion failures will be resolved
- Test expectations will match actual enum definitions

---

## Phase 3: PHPUnit Deprecation Warnings (P2)

### Problem Description

1,203 deprecation warnings for using `@test` doc-comments instead of PHPUnit attributes:

```
WARN  Metadata found in doc-comment for method Tests\Unit\*Test::*
Metadata in doc-comments is deprecated and will no longer be supported in PHPUnit 12.
Update your test code to use attributes instead.
```

### Solution

Convert all `@test` annotations to `#[Test]` attributes:

**Before:**
```php
/** @test */
public function it_can_allocate_candidate_with_all_fields()
{
    // ...
}
```

**After:**
```php
use PHPUnit\Framework\Attributes\Test;

#[Test]
public function it_can_allocate_candidate_with_all_fields()
{
    // ...
}
```

### Bulk Fix Script

Create a script to automate the conversion:

```bash
#!/bin/bash
# fix-phpunit-annotations.sh

# Add import statement if not present
find tests -name "*.php" -exec grep -l "@test" {} \; | while read file; do
    if ! grep -q "use PHPUnit\\\\Framework\\\\Attributes\\\\Test" "$file"; then
        sed -i 's/^namespace [^;]*;/&\n\nuse PHPUnit\\Framework\\Attributes\\Test;/' "$file"
    fi
done

# Replace @test annotations with #[Test] attribute
find tests -name "*.php" -exec sed -i 's/\/\*\* @test \*\//    #[Test]/' {} \;
find tests -name "*.php" -exec sed -i 's/\/\*\*\n     \* @test\n     \*\//    #[Test]/' {} \;
```

### Files to Modify

All test files in `tests/Unit/` and `tests/Feature/` directories (approximately 50+ files)

### Test Files with Deprecation Warnings

| Test Class | Warning Count |
|------------|---------------|
| AllocationServiceTest | 14 |
| AuthorizationEdgeCasesTest | 25 |
| AutoBatchServiceTest | 13 |
| BatchPolicyTest | 30 |
| CampusEquipmentPolicyTest | 18 |
| CampusPolicyTest | 22 |
| CandidateDeduplicationServiceTest | 20 |
| CandidateModelTest | 22 |
| CandidatePolicyTest | 24 |
| CandidateStateMachineTest | 26 |
| ComplaintPoliciesTest | 14 |
| ComplaintServiceTest | Multiple |
| StateTransitionEdgeCasesTest | Multiple |
| TrainingServiceTest | Multiple |
| VisaProcessingServiceTest | Multiple |
| ... and many more |

### Expected Outcome

- All 1,203 deprecation warnings will be eliminated
- Tests will be compatible with PHPUnit 12

---

## Implementation Checklist

### Phase 1: SQLite Compatibility (Immediate)

- [ ] Update migration to detect database driver
- [ ] Add SQLite-compatible code path
- [ ] Verify migration works in both MySQL and SQLite
- [ ] Run full test suite to confirm errors resolved

### Phase 2: Enum Test Fixes (Short-term)

- [ ] Update `ScreeningStatus` test to expect 3 values
- [ ] Update `CandidateStatus` active statuses list
- [ ] Update `CandidateStatus` terminal statuses list
- [ ] Verify CandidateStatus count (17) is still valid
- [ ] Run enum tests to confirm fixes

### Phase 3: PHPUnit Modernization (Medium-term)

- [ ] Create migration script for annotations
- [ ] Run script on all test files
- [ ] Manually verify complex test classes
- [ ] Update `phpunit.xml` if needed
- [ ] Run full test suite to verify

---

## Success Metrics

| Metric | Current | Target |
|--------|---------|--------|
| Tests Passing | 20 | 1324 |
| Errors | 1301 | 0 |
| Failures | 3 | 0 |
| Deprecation Warnings | 1203 | 0 |

---

## Risk Assessment

| Phase | Risk Level | Mitigation |
|-------|------------|------------|
| Phase 1 | Low | Isolated to one migration file |
| Phase 2 | Low | Test-only changes |
| Phase 3 | Medium | Bulk changes require careful review |

---

## Appendix: Full Error Trace

### SQLite Error Stack

```
SQLSTATE[HY000]: General error: 1 near "SHOW": syntax error
(Connection: sqlite, SQL: SHOW COLUMNS FROM candidates WHERE Field = 'training_status')

at vendor/laravel/framework/src/Illuminate/Database/Connection.php:825
    821|         } catch (Exception $e) {
    822|             if ($this->isUniqueConstraintError($e)) {
    823|                 throw new UniqueConstraintViolationException(
    824|             }
 >  825|             throw new QueryException(
    826|                 $this->getName(), $query, $this->prepareBindings($bindings), $e
    827|             );
    828|         }

Migration: database/migrations/2025_12_31_000001_phase1_fix_enum_database_mismatches.php:97
```

### Enum Test Failure Details

```
FAILED  Tests\Unit\WASLv3EnumsTest > updated screening status enum has correct values
Failed asserting that actual size 3 matches expected size 2.
at tests/Unit/WASLv3EnumsTest.php:167

FAILED  Tests\Unit\WASLv3EnumsTest > candidate status has active statuses
Active status 'initial' should exist
Failed asserting that null is not null.
at tests/Unit/WASLv3EnumsTest.php:203

FAILED  Tests\Unit\WASLv3EnumsTest > candidate status has terminal statuses
Terminal status 'dropped_out' should exist
Failed asserting that null is not null.
at tests/Unit/WASLv3EnumsTest.php:218
```
