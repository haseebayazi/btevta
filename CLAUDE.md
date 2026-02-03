# CLAUDE.md - AI Assistant Guide for WASL (BTEVTA)

## Project Overview

**WASL (Workforce Abroad Skills & Linkages)** is a Laravel 11.x enterprise application for TheLeap/BTEVTA (Board of Technical Education & Vocational Training Authority, Punjab) that manages the complete overseas employment lifecycle from candidate listing through deployment, post-departure tracking, and remittance management.

**Version:** 1.5.0 | **Status:** Production Ready | **PHP:** 8.2+

---

## Quick Reference

### Essential Commands

```bash
# Development
php artisan serve              # Start dev server (localhost:8000)
npm run dev                    # Start Vite dev server (hot reload)
npm run build                  # Build production assets

# Testing
php artisan test               # Run all tests
php artisan test --testsuite=Unit      # Unit tests only
php artisan test --testsuite=Feature   # Feature tests only
php artisan test --coverage            # With coverage report

# Database
php artisan migrate            # Run migrations
php artisan db:seed            # Seed database
php artisan migrate:fresh --seed  # Reset and reseed

# Cache (clear when debugging)
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Code Quality
./vendor/bin/pint              # Laravel Pint code formatting
```

### Key File Locations

| Purpose | Location |
|---------|----------|
| Main Model | `app/Models/Candidate.php` |
| State Machine | `app/Enums/CandidateStatus.php` |
| Business Logic | `app/Services/` |
| Authorization | `app/Policies/` |
| Web Routes | `routes/web.php` |
| API Routes | `routes/api.php` |
| Config | `config/wasl.php`, `config/statuses.php` |
| Tests | `tests/Unit/`, `tests/Feature/`, `tests/Integration/` |

---

## Technology Stack

| Component | Technology |
|-----------|------------|
| Framework | Laravel 11.x (PHP 8.2+) |
| Database | MySQL 8.0+ (SQLite for tests) |
| Frontend | Tailwind CSS 3.x, Alpine.js 3.x, Bootstrap 5 |
| Build Tool | Vite |
| Auth | Laravel Sanctum |
| Logging | Spatie Activity Log |
| Permissions | Spatie Laravel Permission |
| Documents | PhpSpreadsheet, DomPDF |
| Testing | PHPUnit 11, Laravel Dusk |

---

## Directory Structure

```
btevta/
├── app/
│   ├── Console/Commands/      # 11 Artisan commands (SLA checks, reminders, exports)
│   ├── Enums/                 # 19 PHP 8.1+ enums (CandidateStatus, VisaStage, etc.)
│   ├── Events/                # Broadcasting events
│   ├── Exports/               # Excel export classes
│   ├── Helpers/               # helpers.php utility functions
│   ├── Http/
│   │   ├── Controllers/       # ~30 controllers
│   │   ├── Middleware/        # Auth, security, role middleware
│   │   ├── Requests/          # Form request validation classes
│   │   └── Resources/         # API response formatting
│   ├── Imports/               # Excel import classes
│   ├── Jobs/                  # Queued background jobs
│   ├── Mail/                  # Email notifications
│   ├── Models/                # 48 Eloquent models
│   ├── Observers/             # Model event listeners
│   ├── Policies/              # 48 authorization policies
│   ├── Rules/                 # Custom validation rules
│   └── Services/              # 19 business logic services
├── config/
│   ├── wasl.php               # WASL-specific settings
│   ├── statuses.php           # Centralized status definitions
│   ├── password.php           # Password policy settings
│   └── remittance.php         # Remittance module config
├── database/
│   ├── migrations/            # Schema definitions
│   ├── seeders/               # Initial data
│   └── factories/             # Test factories
├── resources/views/           # Blade templates
├── routes/
│   ├── web.php                # Web routes
│   └── api.php                # API routes
├── storage/app/private/       # Secure document storage
└── tests/
    ├── Unit/                  # Unit tests (~45 files)
    ├── Feature/               # Feature tests (~37 files)
    ├── Integration/           # Integration tests (4 files)
    └── Browser/               # Dusk browser tests (4 files)
```

---

## Architecture Patterns

### 1. State Machine (CandidateStatus Enum)

The candidate workflow is managed by `app/Enums/CandidateStatus.php`:

```
LISTED → PRE_DEPARTURE_DOCS → SCREENING → SCREENED → REGISTERED →
TRAINING → TRAINING_COMPLETED → VISA_PROCESS → VISA_APPROVED →
DEPARTURE_PROCESSING → READY_TO_DEPART → DEPARTED → POST_DEPARTURE → COMPLETED

Terminal states: COMPLETED, REJECTED, WITHDRAWN (no further transitions)
```

**Usage:**
```php
use App\Enums\CandidateStatus;

// Check if transition is valid
if ($candidate->status->canTransitionTo(CandidateStatus::TRAINING)) {
    $candidate->update(['status' => CandidateStatus::TRAINING->value]);
}

// Get status metadata
$status->label();  // "In Training"
$status->color();  // "warning" (Bootstrap class)
$status->order();  // Workflow order for sorting
```

### 2. Service Layer Pattern

Business logic is separated from controllers into dedicated services:

| Service | Purpose |
|---------|---------|
| `CandidateDeduplicationService` | Multi-strategy duplicate detection |
| `ScreeningService` | Module 2 initial screening workflow |
| `TrainingService` | Attendance, assessments, certificates |
| `VisaProcessingService` | 12-stage visa pipeline |
| `DepartureService` | Flight tracking, post-arrival |
| `RemittanceService` | Money transfer tracking |
| `ComplaintService` | SLA-based complaint management |
| `PreDepartureDocumentService` | Module 1 document collection |

### 3. Policy-Based Authorization

48 authorization policies provide fine-grained access control:

```php
// In controller
$this->authorize('update', $candidate);

// In Blade
@can('update', $candidate)
    <button>Edit</button>
@endcan
```

**Role Hierarchy:** Super Admin > Admin > Campus Admin > OEP > Instructor > Viewer

### 4. Form Request Validation

All input validation uses dedicated FormRequest classes in `app/Http/Requests/`:

```php
// Example: app/Http/Requests/StoreRemittanceRequest.php
public function rules(): array
{
    return [
        'candidate_id' => 'required|exists:candidates,id',
        'amount' => 'required|numeric|min:1',
        'date' => 'required|date|before_or_equal:today',
    ];
}
```

---

## Coding Conventions

### PHP 8.1+ Features Used

- **Enums:** Type-safe status values (see `app/Enums/`)
- **Match expressions:** Instead of switch statements
- **Constructor property promotion**
- **Nullsafe operator:** `$user?->id`
- **Named arguments**

### Eloquent Patterns

```php
// Eager loading (prevent N+1)
$candidates = Candidate::with(['trade', 'campus', 'batch', 'oep'])->paginate();

// Soft deletes (most models)
Candidate::onlyTrashed()->restore();

// Scopes
$candidates = Candidate::active()->byStatus('training')->get();
```

### Naming Conventions

- **Controllers:** `{Resource}Controller.php` (singular)
- **Models:** `{Resource}.php` (singular)
- **Policies:** `{Resource}Policy.php`
- **Services:** `{Resource}Service.php`
- **Requests:** `Store{Resource}Request.php`, `Update{Resource}Request.php`
- **Enums:** `{Name}Status.php`, `{Name}Type.php`

### File Organization Rules

- Controllers handle HTTP only; delegate to services
- Services contain business logic
- Models define relationships and scopes
- Policies handle authorization
- Enums define type-safe constants with metadata

---

## Testing Guidelines

### Test Structure

```
tests/
├── Unit/                      # Isolated unit tests (services, models, policies)
├── Feature/                   # HTTP/integration tests (controllers, API)
├── Integration/               # Multi-module workflow tests
└── Browser/                   # Dusk browser automation
```

### Running Tests

```bash
# All tests (uses SQLite in-memory)
php artisan test

# Specific suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Specific file
php artisan test tests/Unit/CandidateStateMachineTest.php

# With coverage
php artisan test --coverage

# Filter by name
php artisan test --filter=CandidateLifecycle
```

### Key Test Files

| Test | Purpose |
|------|---------|
| `CandidateStateMachineTest` | Status transitions validation |
| `CandidateLifecycleIntegrationTest` | Full workflow tests |
| `ComplaintWorkflowIntegrationTest` | SLA and escalation |
| `AuthenticationTest` | Login, lockout, password policies |
| `BulkOperationsIntegrationTest` | Multi-record operations |

### Writing Tests

```php
// Feature test example
public function test_candidate_can_transition_to_training(): void
{
    $user = User::factory()->admin()->create();
    $candidate = Candidate::factory()
        ->withStatus(CandidateStatus::REGISTERED)
        ->create();

    $response = $this->actingAs($user)
        ->post("/candidates/{$candidate->id}/transition", [
            'status' => CandidateStatus::TRAINING->value,
        ]);

    $response->assertRedirect();
    $this->assertEquals(CandidateStatus::TRAINING, $candidate->fresh()->status);
}
```

---

## Database Schema

### Core Entities

| Model | Description | Key Fields |
|-------|-------------|------------|
| `User` | System users (11 roles) | email, role, force_password_change |
| `Candidate` | Main entity | cnic, status, campus_id, trade_id, batch_id |
| `Campus` | Training locations | name, code, capacity |
| `Trade` | Skill categories | name, code, saudi_name |
| `Batch` | Training cohorts | campus_id, start_date, max_size (20-30) |
| `Oep` | Overseas Employment Promoters | name, license_number |

### Workflow Entities

| Model | Description |
|-------|-------------|
| `CandidateScreening` | Module 2 initial screening with consent & placement |
| `TrainingAttendance` | Daily attendance records |
| `TrainingAssessment` | Midterm, final, practical scores |
| `VisaProcess` | 12-stage visa pipeline |
| `Departure` | Flight info, post-arrival tracking |
| `Complaint` | SLA-based with escalation |
| `PreDepartureDocument` | Module 1 document collection |
| `Country` | Destination countries for placement |

### Common Relationships

```php
// Candidate relationships
$candidate->campus      // belongsTo
$candidate->trade       // belongsTo
$candidate->batch       // belongsTo
$candidate->oep         // belongsTo
$candidate->screenings  // hasMany
$candidate->documents   // hasMany (polymorphic)
$candidate->visaProcess // hasOne
$candidate->departure   // hasOne
```

---

## Common Development Tasks

### Adding a New Status

1. Add case to enum in `app/Enums/CandidateStatus.php`
2. Add label, color, and order in match expressions
3. Update `validNextStatuses()` transitions
4. Update `activeStatuses()` if non-terminal
5. Add migration if database column needs updating
6. Update tests in `CandidateStateMachineTest`

### Adding a New Module

1. Create Model in `app/Models/`
2. Create Migration in `database/migrations/`
3. Create Controller in `app/Http/Controllers/`
4. Create Policy in `app/Policies/` and register in `AuthServiceProvider`
5. Create Service in `app/Services/`
6. Create FormRequests in `app/Http/Requests/`
7. Add routes to `routes/web.php` or `routes/api.php`
8. Create Blade views in `resources/views/{module}/`
9. Add tests in `tests/Feature/` and `tests/Unit/`

### Adding an API Endpoint

1. Add route to `routes/api.php`
2. Create/update controller method
3. Create FormRequest for validation
4. Create API Resource in `app/Http/Resources/`
5. Add policy check in controller
6. Add test in `tests/Feature/`

### Working with File Uploads

Files are stored securely in `storage/app/private/`:

```php
// Storing files
$path = $request->file('document')->store('candidates/documents', 'private');

// Accessing files (through SecureFileController)
Route::get('/secure-file/download/{path}', [SecureFileController::class, 'download'])
    ->where('path', '.*');
```

---

## Security Considerations

### Password Policy (Government Standard)

- Minimum 12 characters
- Requires uppercase, lowercase, number, special character
- Password history (last 5 blocked)
- Expiry: 90 days (60 for admins)
- Force change on first login

### File Upload Validation

- Magic bytes validation (content-based)
- Dangerous extension blocking (PHP, EXE, BAT)
- Double extension prevention (`.php.pdf`)
- Private storage with authorization checks

### Authorization Checklist

When adding features, ensure:
- [ ] Policy exists and is registered
- [ ] Controller uses `$this->authorize()`
- [ ] Blade uses `@can` directives
- [ ] Soft-deleted records handled appropriately
- [ ] Campus-scoped access for campus admins
- [ ] API endpoints protected with Sanctum

---

## Environment Setup

### Required Environment Variables

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=btevta
DB_USERNAME=root
DB_PASSWORD=

# For testing, SQLite is used automatically via phpunit.xml.dist
```

### Initial Setup

```bash
# 1. Install dependencies
composer install
npm install

# 2. Environment setup
cp .env.example .env
php artisan key:generate

# 3. Database setup
php artisan migrate
php artisan db:seed  # Creates admin accounts

# 4. Storage link (for file uploads)
php artisan storage:link

# 5. Build assets
npm run build

# 6. Start server
php artisan serve
```

**Note:** Seeder credentials are saved to `storage/logs/seeder-credentials.log`. Delete after noting passwords.

---

## Scheduled Commands

| Command | Schedule | Purpose |
|---------|----------|---------|
| `check-complaint-sla` | Every 15 min | SLA breach detection |
| `check-document-expiry` | Daily | Document expiry alerts |
| `check-90-day-compliance` | Daily | Post-arrival verification |
| `send-screening-reminders` | Daily | Candidate reminders |
| `generate-remittance-alerts` | Daily | Payment anomaly detection |
| `cleanup-old-logs` | Daily 1 AM | Log rotation |

---

## Important Notes for AI Assistants

### Do's

- Always check `CandidateStatus->canTransitionTo()` before updating status
- Use FormRequest classes for validation, not inline validation
- Delegate business logic to Services, keep controllers thin
- Use Policies for authorization, not manual role checks
- Eager load relationships to prevent N+1 queries
- Add tests for new features
- Use enums for type-safe status values
- Log significant actions via Spatie Activity Log

### Don'ts

- Don't bypass state machine transitions
- Don't store files in `public/` - use `storage/app/private/`
- Don't hardcode role names - use config or constants
- Don't create database queries in Blade views
- Don't skip authorization checks
- Don't use raw SQL when Eloquent suffices
- Don't forget soft delete considerations in queries

### Common Gotchas

1. **Soft Deletes:** Most models use soft deletes. Use `withTrashed()` when needed.
2. **Campus Scoping:** Campus admins see only their campus data - check policies.
3. **Status Validation:** Status changes must go through valid transitions.
4. **File Access:** All private files must go through `SecureFileController`.
5. **Password Policy:** New users have `force_password_change = true`.

---

## Related Documentation

- `README.md` - Full project documentation
- `.github/copilot-instructions.md` - Copilot-specific instructions
- `docs/EVENTS_AND_LISTENERS.md` - Event architecture
- `docs/openapi.yaml` - API specification
- `docs/REMITTANCE_*.md` - Remittance module guides

---

*Last Updated: February 2026*
