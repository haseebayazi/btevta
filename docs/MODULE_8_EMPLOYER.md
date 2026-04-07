# Module 8: Employer Information Enhancement - Documentation

**Project:** BTEVTA WASL
**Module:** Module 8 - Employer Information (Enhancement)
**Version:** 1.0.0
**Status:** Implemented
**Date:** April 2026

---

## Overview

Module 8 enhances the existing Employer module with comprehensive employer management capabilities. Building on the basic CRUD that already existed, Module 8 introduces **permission number tracking**, **visa issuing company details**, **employment package breakdowns**, **document management**, **candidate assignment workflows**, and **employer verification**. A new **Employer tab** is added to the candidate detail view providing full employer context without leaving the candidate record, and the **sidebar navigation** gains a direct Employers link for admin users.

---

## Features

### Core Enhancements (EP-001 through EP-007)

1. **Permission Number Tracking (EP-002)**
   - Ministry of Labor permission number with issue and expiry dates
   - Expiry alerts: "Expiring Soon" (≤30 days) and "Expired" badges on employer profile
   - Permission document upload and secure storage
   - Dashboard highlights employers with expiring permissions

2. **Visa Issuing Company Details (EP-003)**
   - Dedicated fields for visa issuing company name and license number
   - Separate from the employer entity for regulatory tracking

3. **Employment Package Breakdown (EP-004)**
   - `EmploymentPackage` value object storing: base salary, currency, housing, food, transport, and other allowances
   - Per-employer **default package** stored as JSON
   - Per-candidate **custom package** override on the pivot table
   - Package total calculation and percentage breakdown
   - In-kind benefits flags: food, transport, accommodation provided by company

4. **Country and Sector/Trade Linkage (EP-005)**
   - `country_id` FK to `countries` table (destination country)
   - `trade_id` FK to `trades` table (required trade/skill)
   - Free-text `sector` and `city` fields
   - Filters in employer index by country

5. **Document Management (EP-006)**
   - `employer_documents` table with five document types: Business License, Company Registration, Work Permission, Contract Template, Other
   - Each document stores: type, name, path, number, issue/expiry dates, notes
   - Secure storage under `storage/app/private/employers/{id}/documents/`
   - Expiry tracking per document with `isExpiring()` and `isExpired()` helpers

6. **Enhanced Candidate Linking (EP-007)**
   - `candidate_employer` pivot enhanced with: employment type (initial/transfer/switch), assignment date, custom package, status (pending/active/completed/cancelled), is_current flag, assigned_by
   - `assignCandidate()` model method attaches with full pivot data
   - `getPackageForCandidate()` resolves custom or default package per candidate

7. **Employer Verification Workflow**
   - `verified`, `verified_at`, `verified_by` fields on the employer
   - One-click verify action for admins
   - Verified badge shown on employer profile, employer index, and candidate employer tab
   - Activity log entry on verification

8. **Employer Tab in Candidate View (EP-001)**
   - Separate "Employer Information" tab added to `candidates/show.blade.php`
   - Shows: current employer card with permission details, package breakdown, benefits, assignment info
   - Full employer history table for candidates with multiple employers
   - Tab badge shows linked employer count (highlighted when active employer exists)
   - Sidebar quick-card scrolls to the tab instead of navigating away

9. **Employer Dashboard**
   - Summary cards: Total, Active, Verified, Unverified, Expiring Permissions, Expired Permissions
   - By-country and by-sector breakdowns
   - Top employers by active candidate count
   - Expiring permission alerts table

10. **Sidebar Navigation**
    - Employers link added to Process Management section (admin-only)
    - Active highlight on all `admin.employers.*` routes

---

## Architecture

### Enums

| Enum | Location | Values |
|------|----------|--------|
| `EmployerSize` | `app/Enums/EmployerSize.php` | small, medium, large, enterprise |
| `EmploymentType` | `app/Enums/EmploymentType.php` | initial, transfer, switch |

### Value Object

| Class | Location | Purpose |
|-------|----------|---------|
| `EmploymentPackage` | `app/ValueObjects/EmploymentPackage.php` | Immutable salary + allowances structure |

### Models

| Model | Table | Description |
|-------|-------|-------------|
| `Employer` | `employers` | Employer entity with full company info |
| `EmployerDocument` | `employer_documents` | Documents per employer (soft-deleted) |

### Service

`EmployerService` (`app/Services/EmployerService.php`) handles all business logic:

- `createEmployer()` - Transaction-safe employer creation with documents
- `updateEmployer()` - Update with activity logging
- `addDocument()` - Store file and create `EmployerDocument` record
- `deleteDocument()` - Soft-delete document record
- `setDefaultPackage()` - Build and save `EmploymentPackage` to employer
- `assignCandidate()` - Attach candidate with full pivot data (guards against duplicates)
- `verifyEmployer()` - Set verification fields and log
- `getEmployerCandidates()` - Return candidates filtered by pivot status
- `getDashboard()` - Aggregated statistics for dashboard view

### Controller

`EmployerController` (`app/Http/Controllers/EmployerController.php`)

Full method list:

| Method | Route | Description |
|--------|-------|-------------|
| `index` | GET `/admin/employers` | List with search/filter |
| `create` | GET `/admin/employers/create` | Create form |
| `store` | POST `/admin/employers` | Store new employer |
| `show` | GET `/admin/employers/{id}` | Full detail view |
| `edit` | GET `/admin/employers/{id}/edit` | Edit form |
| `update` | PUT `/admin/employers/{id}` | Update employer |
| `destroy` | DELETE `/admin/employers/{id}` | Delete (checks no active candidates) |
| `toggleStatus` | POST `/admin/employers/{id}/toggle-status` | Toggle active/inactive |
| `downloadEvidence` | GET `/admin/employers/{id}/download-evidence` | Download evidence file |
| `dashboard` | GET `/admin/employers/dashboard` | Statistics dashboard |
| `verify` | POST `/admin/employers/{id}/verify` | Verify employer |
| `setPackage` | POST `/admin/employers/{id}/package` | Set default package |
| `uploadDocument` | POST `/admin/employers/{id}/documents` | Upload document |
| `deleteDocument` | DELETE `/admin/employers/documents/{doc}` | Delete document |
| `assignCandidate` | POST `/admin/employers/{id}/assign-candidate` | Assign candidate |
| `candidates` | GET `/admin/employers/{id}/candidates` | List linked candidates |

### Routes

All routes under `admin.` name prefix and `middleware(['role:admin'])` group:

```php
Route::get('employers/dashboard', [EmployerController::class, 'dashboard'])
    ->name('employers.dashboard');
Route::resource('employers', EmployerController::class);
Route::post('employers/{employer}/toggle-status', ...)
    ->name('employers.toggle-status');
Route::get('employers/{employer}/download-evidence', ...)
    ->name('employers.download-evidence');
Route::post('employers/{employer}/verify', ...)
    ->name('employers.verify');
Route::post('employers/{employer}/package', ...)
    ->name('employers.set-package');
Route::post('employers/{employer}/documents', ...)
    ->name('employers.upload-document');
Route::delete('employers/documents/{document}', ...)
    ->name('employers.delete-document');
Route::post('employers/{employer}/assign-candidate', ...)
    ->name('employers.assign-candidate');
Route::get('employers/{employer}/candidates', ...)
    ->name('employers.candidates');
```

Full route names are prefixed with `admin.` → e.g., `admin.employers.show`.

### Views

| View | Description |
|------|-------------|
| `admin/employers/index.blade.php` | Employer listing with search/filter |
| `admin/employers/create.blade.php` | Create form with all sections |
| `admin/employers/edit.blade.php` | Edit form with existing document display |
| `admin/employers/show.blade.php` | Full employer detail with package, documents, candidates |
| `admin/employers/dashboard.blade.php` | Statistics dashboard |
| `admin/employers/candidates.blade.php` | Candidate table for an employer |
| `candidates/partials/employer-tab.blade.php` | Employer tab content for candidate detail view |

---

## Database Schema

### `employers` Table (Enhanced Columns)

| Column | Type | Description |
|--------|------|-------------|
| `permission_number` | varchar(50) unique | Ministry of Labor permission number |
| `permission_issue_date` | date | Permission issue date |
| `permission_expiry_date` | date | Permission expiry date (indexed) |
| `permission_document_path` | varchar(500) | Path to uploaded permission document |
| `visa_issuing_company` | varchar(200) | Visa issuing company name (required, indexed) |
| `visa_company_license` | varchar(100) | Visa company license number |
| `country_id` | FK → countries | Destination country (indexed) |
| `city` | varchar(100) | City/region |
| `sector` | varchar(100) | Industry sector (indexed) |
| `trade` | varchar(100) | Legacy free-text trade |
| `trade_id` | FK → trades | Trade linkage |
| `basic_salary` | decimal(12,2) | Base salary amount |
| `salary_currency` | char(3) | Currency code (default: SAR) |
| `food_by_company` | boolean | Food provided by company |
| `transport_by_company` | boolean | Transport provided by company |
| `accommodation_by_company` | boolean | Accommodation provided by company |
| `other_conditions` | text | Additional employment conditions |
| `default_package` | json | `EmploymentPackage` serialized |
| `company_size` | enum | small, medium, large, enterprise |
| `verified` | boolean | Employer verified flag (indexed) |
| `verified_at` | timestamp | Verification timestamp |
| `verified_by` | FK → users | Admin who verified |
| `notes` | text | Internal notes |
| `evidence_path` | varchar | Legacy evidence file path |
| `is_active` | boolean | Active/inactive status (indexed) |
| `created_by` | FK → users | Record creator |

### `candidate_employer` Pivot Table

| Column | Type | Description |
|--------|------|-------------|
| `candidate_id` | FK → candidates | Candidate reference |
| `employer_id` | FK → employers | Employer reference |
| `employment_type` | enum | initial, transfer, switch |
| `assignment_date` | date | Date of assignment |
| `custom_package` | json | Override `EmploymentPackage` for this candidate |
| `status` | enum | pending, active, completed, cancelled |
| `is_current` | boolean | Current active employer flag |
| `assigned_at` | timestamp | Assignment timestamp |
| `assigned_by` | FK → users | Admin who assigned |

Indexes: `[candidate_id, is_current]`, `[candidate_id, status]`

### `employer_documents` Table

| Column | Type | Description |
|--------|------|-------------|
| `employer_id` | FK → employers | Parent employer |
| `document_type` | varchar(50) | license, registration, permission, contract_template, other |
| `document_name` | varchar(200) | Display name |
| `document_path` | varchar(500) | Storage path (private disk) |
| `document_number` | varchar(100) | Official document number |
| `issue_date` | date | Document issue date |
| `expiry_date` | date | Document expiry date |
| `notes` | text | Additional notes |
| `uploaded_by` | FK → users | Uploader |
| `deleted_at` | timestamp | Soft delete |

Index: `[employer_id, document_type]`

---

## Workflows

### Employer Creation Workflow

```
Admin fills form (Basic Info + Permission + Package + Documents)
           |
           v
StoreEmployerRequest validates all fields
           |
           v
EmployerService::createEmployer()
├── DB::transaction
├── Employer::create() with all fields
├── Store permission document if uploaded → permission_document_path
├── Store evidence file → evidence_path
├── Build EmploymentPackage from package_* fields → default_package JSON
└── Activity log: "Employer created"
           |
           v
Redirect to employer show page
```

### Candidate Assignment Workflow

```
Admin selects candidate from employer show page
           |
           v
POST /admin/employers/{id}/assign-candidate
           |
           v
EmployerService::assignCandidate()
├── Guard: check not already active for this employer
├── employer->candidates()->attach() with pivot data:
│   - employment_type, assignment_date, custom_package
│   - status = 'active', is_current = true
│   - assigned_at = now(), assigned_by = auth()->id()
└── Activity log: "Candidate assigned to employer"
           |
           v
Redirect to employer candidates view
```

### Verification Workflow

```
Admin clicks "Verify Now" on employer show page
           |
           v
POST /admin/employers/{id}/verify
           |
           v
EmployerService::verifyEmployer()
└── employer->verify():
    ├── verified = true
    ├── verified_at = now()
    ├── verified_by = auth()->id()
    ├── save()
    └── Activity log: "Employer verified"
           |
           v
Verified badge shown on employer profile
```

### Employment Package Flow

```
Default Package (employer level)
  → Stored as default_package JSON on Employer
  → Rendered via EmploymentPackage::fromArray($employer->default_package)
  → Used when no custom package exists for candidate

Custom Package (candidate level)
  → Stored as custom_package JSON on candidate_employer pivot
  → Overrides default package for that specific candidate
  → Resolved via employer->getPackageForCandidate($candidate)

Package Resolution in Candidate Employer Tab:
  pivot->custom_package ? EmploymentPackage::fromArray(pivot) : employer->default_package_object
```

### Permission Expiry Tracking

```
permission_expiry_date set on employer
           |
           v
Employer::getPermissionExpiringAttribute()
  → Returns true if expiry_date is future AND within 30 days
Employer::getPermissionExpiredAttribute()
  → Returns true if expiry_date is past

Dashboard: expiringPermissions() scope filters employers
Employer show page: warning/danger alert banners
Candidate employer tab: colored expiry date with badge
```

---

## Candidate View Integration

The candidate detail page (`candidates/show.blade.php`) uses Alpine.js tabs:

```
┌─────────────────────────────────────────┐
│  [Candidate Details] [Employer Info ●1] │  ← Tab bar
├─────────────────────────────────────────┤
│                                         │
│  Tab: Candidate Details                 │
│  ├── Personal Information               │
│  ├── Contact Information                │
│  ├── Trade / Skill                      │
│  └── Remarks                            │
│                                         │
│  Tab: Employer Information              │
│  ├── Current Employer Card              │
│  │   ├── Company name + badges          │
│  │   ├── Location + sector              │
│  │   ├── Permission details / alerts    │
│  │   ├── Employment package breakdown   │
│  │   ├── In-kind benefits               │
│  │   └── Assignment type/date/status    │
│  └── Employer History Table             │
│      └── All linked employers           │
│                                         │
└─────────────────────────────────────────┘
```

The sidebar **Employer quick-card** (shown when an employer is linked) activates the Employer tab rather than navigating away.

Data is **eager-loaded** in `CandidateController::show()`:
```php
$candidate->load([
    // ...existing relations...
    'employers.country',
    'employers.tradeRelation',
    'employers.documents',
]);
```

---

## Access Control (RBAC)

All employer routes are under `middleware(['role:admin'])` — Admin only.

| Action | Admin | Campus Admin | OEP | Viewer |
|--------|:-----:|:------------:|:---:|:------:|
| View Employer List | Y | N | N | N |
| View Employer Details | Y | N | N | N |
| Create Employer | Y | N | N | N |
| Edit Employer | Y | N | N | N |
| Delete Employer | Y | N | N | N |
| Verify Employer | Y | N | N | N |
| Set Package | Y | N | N | N |
| Upload Documents | Y | N | N | N |
| Assign Candidate | Y | N | N | N |
| View Dashboard | Y | N | N | N |
| View Employer Tab (candidate) | Y | Y | Y | Y |

The `EmployerPolicy` governs individual record-level access via `$this->authorize()` in the controller.

---

## EmploymentPackage Value Object

```php
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

    public static function fromArray(?array $data): self;  // null-safe factory
    public function toArray(): array;                       // JSON serialization
    public function getTotal(): float;                      // sum of all components
    public function getFormattedTotal(): string;            // "5,000.00 SAR"
    public function getBreakdown(): array;                  // per-component with %
}
```

### JSON Storage Format

```json
{
    "base_salary": 2000,
    "currency": "SAR",
    "housing_allowance": 800,
    "food_allowance": 400,
    "transport_allowance": 300,
    "other_allowance": 0,
    "benefits": null,
    "notes": "Standard package for construction sector"
}
```

---

## Validation

### StoreEmployerRequest / UpdateEmployerRequest

| Field | Rule |
|-------|------|
| `visa_issuing_company` | required, string, max:200 |
| `permission_number` | nullable, unique (scoped to employer on update) |
| `permission_issue_date` | nullable, date |
| `permission_expiry_date` | nullable, date, after permission_issue_date |
| `permission_document` | nullable, file, mimes:pdf,jpg,jpeg,png, max:5120 |
| `country_id` | required, exists:countries,id |
| `trade_id` | nullable, exists:trades,id |
| `basic_salary` | nullable, numeric, min:0 |
| `salary_currency` | nullable, string, size:3 |
| `company_size` | nullable, in:small,medium,large,enterprise |
| `package_base_salary` | nullable, numeric, min:0 |
| `package_currency` | nullable, string, size:3 |
| `package_housing_allowance` | nullable, numeric, min:0 |
| `package_food_allowance` | nullable, numeric, min:0 |
| `package_transport_allowance` | nullable, numeric, min:0 |
| `evidence` | nullable, file, mimes:pdf,jpg,jpeg,png, max:5120 |

---

## Activity Logging

All significant actions are logged via Spatie Activity Log:

| Event | Log Message |
|-------|-------------|
| Employer created | "Employer created" |
| Employer updated | "Employer updated" |
| Employer verified | "Employer verified" |
| Candidate assigned | "Candidate assigned to employer" (with candidate_id) |
| Default package set | (via update log) |
| Document uploaded | (via update log) |

---

## Testing

### Unit Tests: `EmployerModelTest.php` (12 tests)

| Test | Description |
|------|-------------|
| `it_can_create_an_employer` | Factory creates model |
| `it_belongs_to_a_country` | country() relation |
| `it_can_have_many_candidates` | candidates() pivot relation |
| `it_can_get_current_candidates_only` | currentCandidates() scope |
| `it_belongs_to_a_creator` | creator() relation |
| `it_can_scope_active_employers` | active() scope |
| `it_casts_salary_to_decimal` | basic_salary cast |
| `it_casts_benefits_to_boolean` | food/transport/accommodation cast |
| `it_has_fillable_attributes` | All 26 fillable fields present |
| `it_soft_deletes` | deleted_at populated |
| `it_can_store_evidence_path` | evidence_path fillable |
| `it_can_store_other_conditions` | other_conditions fillable |

### Unit Tests: `EmployerEnhancedTest.php` (15 tests)

| Test | Description |
|------|-------------|
| `test_employer_size_enum_has_correct_cases` | 4 cases: small/medium/large/enterprise |
| `test_employer_size_labels` | Label strings with employee ranges |
| `test_employer_size_colors` | Color values for UI badges |
| `test_employment_type_enum_has_correct_cases` | 3 cases: initial/transfer/switch |
| `test_employment_type_labels` | Label strings |
| `test_employment_type_colors` | Color values |
| `test_employment_package_from_null_returns_default` | Null-safe factory |
| `test_employment_package_from_array` | Hydration from array |
| `test_employment_package_get_total` | Sum of all allowances |
| `test_employment_package_formatted_total` | Formatted with currency |
| `test_employment_package_to_array` | Serialization |
| `test_employment_package_breakdown` | Per-component with percentages |
| `test_employment_package_zero_total_percentage` | Zero-division guard |
| `test_employer_document_types` | 5 document type constants |
| `test_employment_package_roundtrip` | toArray → fromArray fidelity |

**Run all employer tests:**
```bash
php artisan test tests/Unit/EmployerModelTest.php tests/Unit/EmployerEnhancedTest.php
```

---

## File Structure

### Pre-existing Files (Enhanced)

```
app/
├── Http/Controllers/
│   └── EmployerController.php         (16 methods)
├── Http/Requests/
│   ├── StoreEmployerRequest.php
│   └── UpdateEmployerRequest.php
├── Models/
│   └── Employer.php                   (full enhanced model)
└── Services/
    └── EmployerService.php            (9 service methods)

resources/views/admin/employers/
├── index.blade.php
├── create.blade.php
├── edit.blade.php
├── show.blade.php
├── dashboard.blade.php
└── candidates.blade.php
```

### New Files (Module 8 Enhancement)

```
app/
├── Enums/
│   ├── EmployerSize.php               (small/medium/large/enterprise)
│   └── EmploymentType.php             (initial/transfer/switch)
├── Models/
│   └── EmployerDocument.php           (soft-deleted document model)
├── ValueObjects/
│   └── EmploymentPackage.php          (immutable salary structure)

resources/views/candidates/
└── partials/
    └── employer-tab.blade.php         (employer tab for candidate view)
```

### Modified Files (This Session)

```
app/
└── Http/Controllers/
    └── CandidateController.php        (added employer eager-loads)

resources/views/
├── candidates/
│   └── show.blade.php                 (added Alpine.js tabs + employer tab)
└── layouts/
    └── app.blade.php                  (added Employers to sidebar)

tests/Unit/
└── EmployerModelTest.php              (updated fillable list to match model)
```

---

## Validation Checklist

- [x] Enhanced columns on `employers` table (permission, visa, country, package, etc.)
- [x] `candidate_employer` pivot enhanced with employment_type, custom_package, status, is_current
- [x] `employer_documents` table with soft deletes
- [x] `EmployerSize` enum (small/medium/large/enterprise with labels and colors)
- [x] `EmploymentType` enum (initial/transfer/switch with labels and colors)
- [x] `EmploymentPackage` value object (fromArray, toArray, getTotal, getBreakdown)
- [x] `Employer` model enhanced (fillable, casts, relations, accessors, methods)
- [x] `EmployerDocument` model (types, isExpiring, isExpired)
- [x] `EmployerService` (9 service methods with transactions and activity logging)
- [x] `EmployerController` (16 methods with full authorization)
- [x] All employer routes registered under `admin.` prefix
- [x] Employers link added to sidebar (admin-only, active state on `admin.employers.*`)
- [x] Employer tab added to `candidates/show.blade.php` (Alpine.js tabs)
- [x] `candidates/partials/employer-tab.blade.php` with full employer detail
- [x] Employer relations eager-loaded in `CandidateController::show()`
- [x] Dashboard view shows statistics, expiring permissions, top employers
- [x] Package management works (default + custom per-candidate override)
- [x] Document uploads work (multiple types, private storage)
- [x] Candidate assignment works (guards against duplicate active assignments)
- [x] Verification workflow works (verified badge, timestamps, activity log)
- [x] `EmployerModelTest` updated for enhanced fillable attributes
- [x] All 27 employer unit tests pass

---

## Known Limitations

1. **No bulk candidate assignment** — Candidates must be assigned one at a time
2. **Permission document download** — Only the `evidence_path` field has a dedicated download route; permission documents use secure-file viewer
3. **No employer notification system** — No email alerts for expiring permissions (future: add to scheduled commands)
4. **Visa issuing company field is the display name** — The `visa_issuing_company` field doubles as the company display name; a separate `name` field does not exist on the model

---

## Future Enhancements

- [ ] Scheduled command: alert admins 30 days before permission expiry
- [ ] Bulk candidate assignment interface
- [ ] Employer-facing portal for self-service document uploads
- [ ] API endpoints for mobile/third-party integration
- [ ] PDF export of employer profile
- [ ] Employer rating/compliance scoring
- [ ] Integration with Ministry of Labor APIs for permission number validation

---

## Troubleshooting

**Problem:** Employer tab is blank on candidate page
**Solution:** The tab requires the `employers` relation to be eager-loaded. Ensure `CandidateController::show()` includes `employers.country`, `employers.tradeRelation`, `employers.documents` in the `load()` call.

**Problem:** "View Employer Details" button in sidebar card does not work
**Solution:** The button uses Alpine.js `$el.parentElement` traversal to click the Employer tab. Ensure Alpine.js is loaded and the candidate show page is not inside a context that breaks the tab selector.

**Problem:** Package total shows 0 even after setting values
**Solution:** Check that `package_*` field names (e.g., `package_base_salary`) are submitted in the form. The `setPackage` controller method reads prefixed fields. Alternatively, the `default_package` JSON column may be null — verify the employer's `default_package` attribute is not empty.

**Problem:** Cannot assign candidate — "already assigned" error
**Solution:** The service checks for an existing active (`status = 'active'`) pivot row. Deactivate the existing assignment first or use employment type "transfer" via a new assignment.

**Problem:** Permission expiry alert not showing
**Solution:** The `permission_expiring` accessor returns true only when the date is in the future and ≤30 days away. Dates that have already passed trigger `permission_expired` instead.

---

## Support & Maintenance

**Documentation:** `/docs/MODULE_8_EMPLOYER.md`
**Source Code:** `haseebayazi/btevta` repository, branch `claude/add-dashboard-tab-sync-xKejo`
**Test Coverage:** 27 unit tests passing (EmployerModelTest + EmployerEnhancedTest)

---

## Change Log

### Version 1.0.0 (April 2026)

- `EmployerSize` enum (4 cases with label and color)
- `EmploymentType` enum (3 cases with label and color)
- `EmploymentPackage` value object (immutable, fromArray/toArray/getTotal/getBreakdown)
- `EmployerDocument` model with 5 document types and expiry helpers
- `Employer` model fully enhanced (26 fillable, casts, relations, accessors, verify/assign methods)
- `EmployerService` with 9 transaction-safe service methods
- `EmployerController` with 16 methods covering full employer lifecycle
- All 10 employer routes registered under `admin.` prefix group
- Employers sidebar link added to Process Management section (admin-only)
- `candidates/partials/employer-tab.blade.php` created — permission details, package breakdown, benefits, assignment info, employer history
- `candidates/show.blade.php` converted to Alpine.js tabs with new Employer Information tab
- `CandidateController::show()` updated to eager-load employer relations
- `EmployerModelTest` updated to reflect enhanced fillable attributes
- All UI consistent with Tailwind CSS + Alpine.js (matching all other modules)
- 27/27 employer unit tests passing

---

*Last Updated: April 2026*
