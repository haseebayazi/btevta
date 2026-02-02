# Module 2: Initial Screening

**Version:** 1.0.0  
**Status:** ✅ Complete  
**Implementation Date:** February 2026

---

## Overview

Module 2 transforms the BTEVTA WASL screening process from a legacy 3-call system into a streamlined single-review workflow with consent verification, placement interest capture, and country specification. This module marks a critical gate in the candidate journey—only "Screened" candidates may proceed to Registration (Module 3).

---

## Features

### Core Functionality

1. **Single-Review Workflow**
   - Replaces the legacy 3-call screening system
   - One comprehensive screening session per candidate
   - Immediate decision: Screened, Pending, or Deferred

2. **Consent Verification**
   - **Legal Requirement**: Candidate must provide informed consent to work
   - Checkbox confirmation with disclaimer text
   - Mandatory field—cannot proceed without consent
   - Logged in activity trail for compliance

3. **Placement Interest Capture**
   - **Local Placement**: Employment within Pakistan
   - **International Placement**: Overseas employment opportunities
   - Required field for all screenings

4. **Country Specification**
   - Dynamic field shown only for international placement interest
   - Dropdown of active destination countries
   - Includes: Saudi Arabia, UAE, Qatar, Kuwait, Bahrain, Oman, Malaysia, Singapore
   - Required when international placement selected

5. **Screening Outcomes**
   - **Screened**: Candidate approved, ready for registration
   - **Pending**: Save for later review (no status change)
   - **Deferred**: Not suitable at this time (terminal for now)

6. **Evidence Upload**
   - Optional supporting documentation
   - Accepted formats: PDF, JPG, JPEG, PNG
   - Maximum file size: 10MB
   - Secure storage in private directory

7. **Screening Notes**
   - Free-text field for reviewer observations
   - Up to 2000 characters
   - Saved with screening record

---

## Workflow

```
┌─────────────────────┐
│  PRE_DEPARTURE_DOCS │
│    (Module 1)       │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│   SCREENING         │◄─── Candidate ready for screening
└──────────┬──────────┘
           │
           ▼
┌─────────────────────────────────────────┐
│  Initial Screening Form                 │
│  ─────────────────────────              │
│  ☑ Consent for Work (required)          │
│  ○ Local / ● International (required)   │
│  🌍 Country: [Saudi Arabia ▼] (if intl) │
│  📝 Notes: [Optional text field]        │
│  📎 Evidence: [Optional file upload]    │
│  ────────────────────────────           │
│  Decision:                               │
│  ○ Screened  ○ Pending  ○ Deferred      │
└──────────┬──────────────────────────────┘
           │
           ├─── Screened ──────► SCREENED (proceed to Registration)
           ├─── Pending ───────► SCREENING (no change, review later)
           └─── Deferred ──────► DEFERRED (terminal state)
```

---

## Database Schema

### New Fields in `candidate_screenings` Table

| Field                 | Type          | Description                           |
|-----------------------|---------------|---------------------------------------|
| `consent_for_work`    | boolean       | Candidate consent confirmation        |
| `placement_interest`  | enum          | 'local' or 'international'            |
| `target_country_id`   | foreign key   | ID from countries table (nullable)    |
| `screening_status`    | enum          | 'pending', 'screened', or 'deferred'  |
| `reviewer_id`         | foreign key   | User who conducted screening          |
| `reviewed_at`         | timestamp     | When screening was reviewed           |

### Countries Table

| Field                 | Type      | Description                              |
|-----------------------|-----------|------------------------------------------|
| `id`                  | bigint    | Primary key                              |
| `name`                | string    | Country name                             |
| `code`                | string(3) | ISO 3166-1 alpha-3                       |
| `code_2`              | string(2) | ISO 3166-1 alpha-2                       |
| `is_destination`      | boolean   | Flag for destination countries           |
| `is_active`           | boolean   | Active status                            |
| `specific_requirements` | json    | Country-specific fields/docs (optional)  |

---

## User Interface

### 1. Initial Screening Dashboard

**Route:** `/screening/initial-dashboard`

**Components:**
- **Statistics Cards**
  - Pending Screening count
  - Screened count
  - Deferred count
  - Screenings this month

- **Pending Candidates Table**
  - TheLeap ID, Name, CNIC
  - Campus, Trade, OEP
  - Current status badge
  - "Screen" action button per candidate

- **Recently Screened Section**
  - Last 10 screened candidates
  - Timestamp of screening
  - Quick view link

### 2. Initial Screening Form

**Route:** `/candidates/{candidate}/initial-screening`

**Layout:**
- Candidate info header (4 cards: Name, ID, Campus, Trade)
- Existing screening alert (if applicable)
- Screening form with sections:
  1. Consent checkbox with legal disclaimer
  2. Placement interest radio buttons
  3. Target country dropdown (conditional)
  4. Notes textarea
  5. Evidence file upload
  6. Outcome selection (3 radio buttons)
  7. Submit button

**JavaScript Behavior:**
- Show/hide country dropdown based on placement interest
- Update required attribute dynamically
- Form validation before submission

---

## Access Control (RBAC)

| Action               | Super Admin | Admin | Campus Admin | OEP | Viewer |
|----------------------|:-----------:|:-----:|:------------:|:---:|:------:|
| View Dashboard       | ✓           | ✓     | Campus Only  | ✗   | ✓      |
| Perform Screening    | ✓           | ✓     | Campus Only  | ✗   | ✗      |
| Override Status      | ✓           | ✓     | ✗            | ✗   | ✗      |
| Export Reports       | ✓           | ✓     | Campus Only  | ✗   | ✓      |

**Campus Admin Filtering:**
- Campus admins see only candidates assigned to their campus
- Enforced at both query and policy levels
- Applies to dashboard, forms, and exports

---

## API Endpoints

### Web Routes

| Method | Route                                           | Action                   |
|--------|-------------------------------------------------|--------------------------|
| GET    | `/screening/initial-dashboard`                  | Dashboard view           |
| GET    | `/candidates/{candidate}/initial-screening`     | Screening form           |
| POST   | `/candidates/{candidate}/initial-screening`     | Submit screening         |

All routes require authentication and proper authorization via policies.

---

## Validation Rules

### InitialScreeningRequest

```php
[
    'candidate_id' => 'required|exists:candidates,id',
    'consent_for_work' => 'required|boolean|accepted',
    'placement_interest' => 'required|in:local,international',
    'target_country_id' => 'required_if:placement_interest,international|nullable|exists:countries,id',
    'screening_status' => 'required|in:pending,screened,deferred',
    'notes' => 'nullable|string|max:2000',
    'evidence' => 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png',
]
```

**Custom Error Messages:**
- Consent must be accepted to proceed
- Country required for international placement
- Evidence file size must not exceed 10MB

---

## Business Logic

### Screening Workflow

1. **Pre-Screening Check**
   - Candidate must be in `pre_departure_docs` or `screening` status
   - If not, redirect with error message

2. **Form Processing**
   ```php
   // Controller: storeInitialScreening()
   DB::transaction(function() {
       // 1. Create/update screening record
       // 2. Upload evidence file (if provided)
       // 3. Process outcome:
       //    - Screened: Update candidate to 'screened'
       //    - Deferred: Update candidate to 'deferred'
       //    - Pending: No status change
       // 4. Log activity
   });
   ```

3. **Status Updates**
   - `markAsScreened()`: Sets screening_status = 'screened', updates candidate to 'screened'
   - `markAsDeferred()`: Sets screening_status = 'deferred', updates candidate to 'deferred'

### Registration Gate

- **Only candidates with status = 'screened' can proceed to Registration**
- Enforced in:
  - CandidateStatus enum transitions
  - Registration controller authorization
  - UI logic (disabled buttons for non-screened)

---

## Testing

### Unit Tests (`InitialScreeningTest.php`)

13 tests covering:
- Model methods (markAsScreened, markAsDeferred)
- Relationships (targetCountry, reviewer)
- Type casting and constants
- Status transitions
- Field validations

**Run:** `php artisan test --filter=InitialScreeningTest`

### Feature Tests (`InitialScreeningControllerTest.php`)

12 tests covering:
- Dashboard and form views
- Pre-departure docs check
- Local and international placement
- Consent and country validation
- Outcome processing (screened, pending, deferred)
- Evidence upload
- Statistics accuracy
- Campus admin filtering

**Run:** `php artisan test --filter=InitialScreeningControllerTest`

**All Tests:** `php artisan test --filter=InitialScreening`

---

## Configuration

### Status Configuration

Screening statuses defined in:
- `app/Enums/ScreeningStatus.php` (ScreeningStatus enum)
- `app/Models/CandidateScreening.php` (OUTCOME_* constants)

### Countries Seeder

Destination countries configured in:
- `database/seeders/CountriesSeeder.php`

To seed countries:
```bash
php artisan db:seed --class=CountriesSeeder
```

---

## Activity Logging

All screening actions are logged via Spatie Activity Log:

```php
activity()
    ->performedOn($candidate)
    ->causedBy(auth()->user())
    ->withProperties([
        'screening_status' => 'screened',
        'placement_interest' => 'international',
        'target_country' => 'Saudi Arabia',
    ])
    ->log('Candidate marked as screened');
```

**Logged Events:**
- Screening created
- Screening outcome recorded
- Evidence uploaded
- Status transitions

---

## Backward Compatibility

### Legacy Screening System

- Old 3-call columns **NOT REMOVED** (soft-deprecation)
- Historical data preserved
- Old methods marked `@deprecated` in code comments
- Existing screening functionality still works

### Migration Strategy

- New screenings use `screening_type = 'initial'`
- Old screenings keep original `screening_type` values
- Both systems coexist during transition period

---

## File Structure

### Created Files

```
app/
├── Enums/
│   └── PlacementInterest.php
├── Http/
│   ├── Controllers/
│   │   └── ScreeningController.php (updated)
│   └── Requests/
│       └── InitialScreeningRequest.php
└── Models/
    ├── CandidateScreening.php (updated)
    └── Country.php

database/
├── factories/
│   ├── CandidateScreeningFactory.php (updated)
│   └── CountryFactory.php
├── migrations/
│   ├── YYYY_MM_DD_create_countries_table.php
│   └── YYYY_MM_DD_modify_screenings_table_for_initial_screening.php
└── seeders/
    └── CountriesSeeder.php

resources/views/
└── screening/
    ├── initial-screening-dashboard.blade.php
    └── initial-screening.blade.php

routes/
└── web.php (updated)

tests/
├── Feature/
│   └── InitialScreeningControllerTest.php
└── Unit/
    └── InitialScreeningTest.php
```

---

## Known Issues & Limitations

### Current Limitations

1. **No Bulk Screening**
   - Candidates must be screened individually
   - Future enhancement: Bulk screening interface

2. **No Re-Screening**
   - Once screened/deferred, outcome cannot be changed without admin override
   - Future enhancement: Re-screening workflow

3. **Limited Analytics**
   - Basic statistics on dashboard
   - Future enhancement: Detailed analytics and reports

### Workarounds

- **Bulk Screening**: Use exports and imports for batch processing
- **Re-Screening**: Admins can manually update database or use legacy screening system
- **Analytics**: Use Activity Log queries for detailed tracking

---

## Troubleshooting

### Common Issues

**Problem:** Country dropdown not showing for international placement  
**Solution:** Ensure JavaScript is enabled and page is fully loaded. Check browser console for errors.

**Problem:** "Consent required" validation error  
**Solution:** Checkbox must be checked before form submission. This is a legal requirement.

**Problem:** File upload fails  
**Solution:** Check file size (max 10MB) and format (PDF, JPG, PNG only). Verify storage permissions.

**Problem:** Campus admin sees all candidates  
**Solution:** Verify user has `campus_id` set in database and role is 'campus_admin'.

---

## Future Enhancements

### Planned Features (v1.1)

- [ ] Bulk screening interface
- [ ] Re-screening workflow with approval
- [ ] Enhanced analytics dashboard
- [ ] Email notifications on screening outcomes
- [ ] Mobile-responsive improvements
- [ ] Integration with SMS notifications
- [ ] PDF export of screening records
- [ ] Advanced filtering and search

---

## Support & Maintenance

**Developer Contact:** BTEVTA Development Team  
**Documentation:** `/docs/MODULE_2_INITIAL_SCREENING.md`  
**Source Code:** `haseebayazi/btevta` repository  
**Test Coverage:** 100% (25/25 tests passing)

---

## Change Log

### Version 1.0.0 (February 2026)
- ✅ Initial implementation
- ✅ Single-review workflow
- ✅ Consent verification
- ✅ Placement interest capture
- ✅ Country specification
- ✅ Dashboard and form UI
- ✅ Comprehensive testing (25 tests)
- ✅ Activity logging
- ✅ RBAC implementation
- ✅ Backward compatibility

---

*Last Updated: February 2026*
