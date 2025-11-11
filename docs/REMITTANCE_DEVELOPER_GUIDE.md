# Remittance Management - Developer Guide

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Database Schema](#database-schema)
3. [Code Structure](#code-structure)
4. [Services Layer](#services-layer)
5. [API Documentation](#api-documentation)
6. [Testing](#testing)
7. [Extending the Module](#extending-the-module)
8. [Performance Considerations](#performance-considerations)
9. [Security](#security)
10. [Troubleshooting](#troubleshooting)

---

## Architecture Overview

### Design Pattern

The Remittance module follows Laravel's MVC pattern with an additional Service Layer:

```
┌─────────────┐
│   Routes    │ (web.php, api.php)
└──────┬──────┘
       │
┌──────▼──────┐
│ Controllers │ (RemittanceController, RemittanceApiController)
└──────┬──────┘
       │
┌──────▼──────┐
│  Services   │ (RemittanceAnalyticsService, RemittanceAlertService)
└──────┬──────┘
       │
┌──────▼──────┐
│   Models    │ (Remittance, RemittanceAlert, RemittanceBeneficiary)
└──────┬──────┘
       │
┌──────▼──────┐
│  Database   │ (MySQL/PostgreSQL)
└─────────────┘
```

### Module Components

1. **Models** - Eloquent models with relationships
2. **Controllers** - Handle HTTP requests (Web + API)
3. **Services** - Business logic and complex operations
4. **Views** - Blade templates for UI
5. **Routes** - Web and API route definitions
6. **Migrations** - Database schema definitions
7. **Factories** - Test data generation
8. **Tests** - Unit and Feature tests

### Key Features

- **RESTful API** - Complete REST API with versioning (/api/v1/)
- **Service Layer** - Separation of business logic
- **Soft Deletes** - Non-destructive data removal
- **Automated Alerts** - Intelligent alert generation
- **Advanced Analytics** - Statistical analysis and reporting
- **Comprehensive Testing** - 93 automated tests

---

## Database Schema

### Main Tables

#### remittances

Primary table storing all remittance records.

```sql
CREATE TABLE remittances (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    candidate_id BIGINT UNSIGNED NOT NULL,
    departure_id BIGINT UNSIGNED,
    recorded_by BIGINT UNSIGNED,
    transaction_reference VARCHAR(255) UNIQUE NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'PKR',
    amount_foreign DECIMAL(12,2),
    foreign_currency VARCHAR(3),
    exchange_rate DECIMAL(10,4),
    transfer_date DATE NOT NULL,
    transfer_method VARCHAR(50),
    sender_name VARCHAR(255),
    sender_location VARCHAR(255),
    receiver_name VARCHAR(255),
    receiver_account VARCHAR(255),
    bank_name VARCHAR(255),
    primary_purpose VARCHAR(50),
    purpose_description TEXT,
    has_proof BOOLEAN DEFAULT FALSE,
    proof_verified_date DATE,
    verified_by BIGINT UNSIGNED,
    status VARCHAR(20) DEFAULT 'pending',
    notes TEXT,
    alert_message TEXT,
    is_first_remittance BOOLEAN DEFAULT FALSE,
    month_number INT,
    year INT,
    month INT,
    quarter INT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,

    FOREIGN KEY (candidate_id) REFERENCES candidates(id),
    FOREIGN KEY (departure_id) REFERENCES departures(id),
    FOREIGN KEY (recorded_by) REFERENCES users(id),
    FOREIGN KEY (verified_by) REFERENCES users(id),
    INDEX idx_candidate (candidate_id),
    INDEX idx_transfer_date (transfer_date),
    INDEX idx_year_month (year, month),
    INDEX idx_status (status)
);
```

**Key Fields:**
- `transaction_reference` - Unique identifier (indexed)
- `year`, `month`, `quarter` - Auto-populated from transfer_date
- `month_number` - Months since deployment
- `is_first_remittance` - Flags first remittance per candidate

#### remittance_alerts

Stores automated alerts for remittance issues.

```sql
CREATE TABLE remittance_alerts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    candidate_id BIGINT UNSIGNED NOT NULL,
    remittance_id BIGINT UNSIGNED,
    alert_type VARCHAR(50) NOT NULL,
    severity VARCHAR(20) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    metadata JSON,
    is_read BOOLEAN DEFAULT FALSE,
    is_resolved BOOLEAN DEFAULT FALSE,
    resolved_by BIGINT UNSIGNED,
    resolved_at TIMESTAMP,
    resolution_notes TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (candidate_id) REFERENCES candidates(id),
    FOREIGN KEY (remittance_id) REFERENCES remittances(id),
    FOREIGN KEY (resolved_by) REFERENCES users(id),
    INDEX idx_candidate (candidate_id),
    INDEX idx_type_severity (alert_type, severity),
    INDEX idx_status (is_resolved, is_read)
);
```

**Alert Types:**
- `missing_remittance` - No remittances in threshold period
- `missing_proof` - Remittance without documentation
- `first_remittance_delay` - First remittance delayed
- `low_frequency` - Infrequent remittances
- `unusual_amount` - Statistical anomaly

**Severities:** `critical`, `warning`, `info`

#### remittance_receipts

Stores proof documents.

```sql
CREATE TABLE remittance_receipts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    remittance_id BIGINT UNSIGNED NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_type VARCHAR(50),
    file_size INT,
    description TEXT,
    is_verified BOOLEAN DEFAULT FALSE,
    verified_by BIGINT UNSIGNED,
    verified_at TIMESTAMP,
    uploaded_by BIGINT UNSIGNED,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (remittance_id) REFERENCES remittances(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id),
    FOREIGN KEY (verified_by) REFERENCES users(id)
);
```

#### remittance_beneficiaries

Manages recipient information.

```sql
CREATE TABLE remittance_beneficiaries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    candidate_id BIGINT UNSIGNED NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    relationship VARCHAR(50),
    cnic VARCHAR(13),
    phone VARCHAR(20),
    email VARCHAR(255),
    address TEXT,
    account_number VARCHAR(50),
    iban VARCHAR(50),
    bank_name VARCHAR(255),
    mobile_wallet VARCHAR(50),
    is_primary BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    notes TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (candidate_id) REFERENCES candidates(id),
    INDEX idx_candidate (candidate_id),
    INDEX idx_primary (is_primary, is_active)
);
```

#### remittance_usage_breakdown

Tracks how remittances are used.

```sql
CREATE TABLE remittance_usage_breakdown (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    remittance_id BIGINT UNSIGNED NOT NULL,
    purpose VARCHAR(50) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    percentage DECIMAL(5,2),
    description TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (remittance_id) REFERENCES remittances(id) ON DELETE CASCADE
);
```

### Relationships

```
Candidate (1) ─┬─> (N) Remittances
               └─> (N) RemittanceBeneficiaries
               └─> (N) RemittanceAlerts

Remittance (1) ─┬─> (N) RemittanceReceipts
                └─> (N) RemittanceUsageBreakdown
                └─> (N) RemittanceAlerts

Departure (1) ──> (N) Remittances

User (1) ──┬──> (N) Remittances (as recorder)
           ├──> (N) Remittances (as verifier)
           └──> (N) RemittanceAlerts (as resolver)
```

---

## Code Structure

### Directory Layout

```
app/
├── Models/
│   ├── Remittance.php
│   ├── RemittanceAlert.php
│   ├── RemittanceBeneficiary.php
│   ├── RemittanceReceipt.php
│   └── RemittanceUsageBreakdown.php
├── Http/
│   └── Controllers/
│       ├── RemittanceController.php
│       ├── RemittanceBeneficiaryController.php
│       ├── RemittanceReportController.php
│       ├── RemittanceAlertController.php
│       └── Api/
│           ├── RemittanceApiController.php
│           ├── RemittanceReportApiController.php
│           └── RemittanceAlertApiController.php
├── Services/
│   ├── RemittanceAnalyticsService.php
│   └── RemittanceAlertService.php
└── Console/
    └── Commands/
        └── GenerateRemittanceAlerts.php

database/
├── migrations/
│   ├── xxxx_create_remittances_table.php
│   ├── xxxx_create_remittance_alerts_table.php
│   ├── xxxx_create_remittance_beneficiaries_table.php
│   ├── xxxx_create_remittance_receipts_table.php
│   └── xxxx_create_remittance_usage_breakdown_table.php
└── factories/
    ├── RemittanceFactory.php
    ├── RemittanceAlertFactory.php
    └── DepartureFactory.php

resources/
└── views/
    └── remittances/
        ├── index.blade.php
        ├── create.blade.php
        ├── edit.blade.php
        ├── show.blade.php
        ├── reports/
        │   ├── dashboard.blade.php
        │   ├── monthly.blade.php
        │   └── ...
        └── alerts/
            ├── index.blade.php
            └── show.blade.php

routes/
├── web.php (Remittance web routes)
└── api.php (Remittance API routes)

tests/
├── Unit/
│   ├── RemittanceAnalyticsServiceTest.php
│   └── RemittanceAlertServiceTest.php
└── Feature/
    ├── RemittanceControllerTest.php
    ├── RemittanceApiControllerTest.php
    ├── RemittanceReportApiControllerTest.php
    └── RemittanceAlertApiControllerTest.php

config/
└── remittance.php

docs/
├── API_REMITTANCE.md
├── REMITTANCE_USER_GUIDE.md
├── REMITTANCE_DEVELOPER_GUIDE.md
├── REMITTANCE_SETUP_GUIDE.md
└── REMITTANCE_ADMIN_MANUAL.md
```

### Model Implementation

#### Remittance Model

**File:** `app/Models/Remittance.php`

Key features:
- **Soft Deletes** - Non-destructive deletion
- **Factories** - Test data generation
- **Relationships** - Eloquent relationships
- **Scopes** - Query scopes for filtering
- **Accessors** - Computed attributes
- **Mutators** - Auto-populate fields

```php
class Remittance extends Model
{
    use HasFactory, SoftDeletes;

    // Fillable fields
    protected $fillable = [...];

    // Type casting
    protected $casts = [
        'transfer_date' => 'date',
        'amount' => 'decimal:2',
        'has_proof' => 'boolean',
        // ...
    ];

    // Relationships
    public function candidate() { return $this->belongsTo(Candidate::class); }
    public function departure() { return $this->belongsTo(Departure::class); }
    public function receipts() { return $this->hasMany(RemittanceReceipt::class); }

    // Scopes
    public function scopeVerified($query) { /* ... */ }
    public function scopeByYear($query, $year) { /* ... */ }

    // Accessors
    public function getFormattedAmountAttribute() { /* ... */ }

    // Mutators
    public function setTransferDateAttribute($value) {
        // Auto-set year, month, quarter
    }

    // Methods
    public function markAsVerified($userId) { /* ... */ }
    public function hasCompleteProof() { /* ... */ }
}
```

**Key Methods:**
- `calculateMonthNumber()` - Months since deployment
- `markAsVerified($userId)` - Verify remittance
- `hasCompleteProof()` - Check for verified receipts

---

## Services Layer

### RemittanceAnalyticsService

**File:** `app/Services/RemittanceAnalyticsService.php`

Handles all analytics and statistical operations.

**Key Methods:**

```php
class RemittanceAnalyticsService
{
    // Dashboard statistics
    public function getDashboardStats(): array

    // Monthly trends with zero-fill
    public function getMonthlyTrends($year = null): array

    // Purpose distribution
    public function getPurposeAnalysis(): array

    // Transfer method breakdown
    public function getTransferMethodAnalysis(): array

    // Country-wise analysis
    public function getCountryAnalysis(): array

    // Proof compliance reporting
    public function getProofComplianceReport(): array

    // Beneficiary analysis
    public function getBeneficiaryReport(): array

    // Economic impact metrics
    public function getImpactAnalytics(): array

    // Top remitting candidates
    public function getTopRemittingCandidates($limit = 10): Collection

    // Date range filtering
    public function getRemittancesByDateRange($startDate, $endDate): Collection
}
```

**Usage Example:**

```php
use App\Services\RemittanceAnalyticsService;

$service = new RemittanceAnalyticsService();

// Get dashboard stats
$stats = $service->getDashboardStats();

// Get monthly trends for 2025
$trends = $service->getMonthlyTrends(2025);

// Get top 10 remitters
$topCandidates = $service->getTopRemittingCandidates(10);
```

### RemittanceAlertService

**File:** `app/Services/RemittanceAlertService.php`

Manages alert generation and resolution.

**Key Methods:**

```php
class RemittanceAlertService
{
    // Generate all alert types
    public function generateAllAlerts(): array

    // Individual alert generators
    public function generateMissingRemittanceAlerts(): int
    public function generateMissingProofAlerts(): int
    public function generateFirstRemittanceDelayAlerts(): int
    public function generateLowFrequencyAlerts(): int
    public function generateUnusualAmountAlerts(): int

    // Alert statistics
    public function getUnresolvedAlertsCount($candidateId = null): int
    public function getCriticalAlertsCount(): int
    public function getAlertStatistics(): array

    // Auto-resolution
    public function autoResolveAlerts(): int

    // Utilities
    public function markOldAlertsAsRead($daysOld = 30): int
    protected function calculateStandardDeviation($values): float
}
```

**Alert Generation Logic:**

```php
// Missing Remittance Alert
$daysThreshold = config('remittance.alert_thresholds.missing_remittance_days', 90);

$candidates = Candidate::whereHas('departure', function($q) use ($daysThreshold) {
        $q->where('departure_date', '<=', now()->subDays($daysThreshold));
    })
    ->whereDoesntHave('remittances', function($q) use ($daysThreshold) {
        $q->where('transfer_date', '>=', now()->subDays($daysThreshold));
    })
    ->get();

// Create alerts for each candidate
foreach ($candidates as $candidate) {
    // Check for existing alert to prevent duplicates
    $existingAlert = RemittanceAlert::where('candidate_id', $candidate->id)
        ->where('alert_type', 'missing_remittance')
        ->where('is_resolved', false)
        ->first();

    if (!$existingAlert) {
        RemittanceAlert::create([...]);
    }
}
```

---

## API Documentation

See `docs/API_REMITTANCE.md` for complete API reference.

### API Structure

All API endpoints are under `/api/v1/` with the following groups:

1. **Remittances** - `/api/v1/remittances/*`
2. **Reports** - `/api/v1/remittance/reports/*`
3. **Alerts** - `/api/v1/remittance/alerts/*`

### Authentication

All API endpoints require authentication via web session.

```php
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    // API routes
});
```

### Rate Limiting

60 requests per minute per authenticated user.

### Response Format

**Success Response:**
```json
{
  "data": [...],
  "current_page": 1,
  "last_page": 10,
  "per_page": 20,
  "total": 200
}
```

**Error Response:**
```json
{
  "error": "Resource not found"
}
```

**Validation Error:**
```json
{
  "errors": {
    "candidate_id": ["The candidate id field is required."],
    "amount": ["The amount must be at least 0."]
  }
}
```

---

## Testing

### Test Structure

93 tests across 6 test classes:

**Unit Tests (32 tests):**
- `RemittanceAnalyticsServiceTest.php` - 13 tests
- `RemittanceAlertServiceTest.php` - 19 tests

**Feature Tests (61 tests):**
- `RemittanceControllerTest.php` - 15 tests
- `RemittanceApiControllerTest.php` - 16 tests
- `RemittanceReportApiControllerTest.php` - 13 tests
- `RemittanceAlertApiControllerTest.php` - 17 tests

### Running Tests

```bash
# All remittance tests
php artisan test --filter=Remittance

# Specific test class
php artisan test tests/Unit/RemittanceAnalyticsServiceTest.php

# Specific test method
php artisan test --filter=test_get_dashboard_stats_returns_comprehensive_statistics

# With coverage
php artisan test --coverage
```

### Factory Usage

```php
use App\Models\Remittance;
use App\Models\Candidate;
use App\Models\Departure;

// Basic remittance
$remittance = Remittance::factory()->create();

// Verified remittance
$remittance = Remittance::factory()->verified()->create();

// Pending without proof
$remittance = Remittance::factory()
    ->pending()
    ->withoutProof()
    ->create();

// First remittance
$remittance = Remittance::factory()->firstRemittance()->create();

// With relationships
$remittance = Remittance::factory()->create([
    'candidate_id' => Candidate::factory(),
    'departure_id' => Departure::factory(),
]);
```

### Writing Tests

**Unit Test Example:**

```php
public function test_get_monthly_trends_returns_data_for_all_months()
{
    $currentYear = date('Y');

    Remittance::factory()->create([
        'transfer_date' => "$currentYear-01-15",
        'year' => $currentYear,
        'month' => 1,
    ]);

    $trends = $this->service->getMonthlyTrends($currentYear);

    $this->assertIsArray($trends);
    $this->assertCount(12, $trends); // All 12 months
    $this->assertEquals(1, $trends[1]['count']);
    $this->assertEquals('January', $trends[1]['month']);
}
```

**Feature Test Example:**

```php
public function test_api_store_creates_new_remittance()
{
    $data = [
        'candidate_id' => $this->candidate->id,
        'transaction_reference' => 'TXN_API_123',
        'amount' => 50000,
        'transfer_date' => '2025-11-01',
        // ...
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/remittances/', $data);

    $response->assertStatus(201)
        ->assertJson(['message' => 'Remittance created successfully']);

    $this->assertDatabaseHas('remittances', [
        'transaction_reference' => 'TXN_API_123',
    ]);
}
```

---

## Extending the Module

### Adding a New Alert Type

1. **Update configuration** (`config/remittance.php`):

```php
'alert_types' => [
    'existing_types' => [...],
    'new_alert_type' => 'New Alert Type',
],

'alert_thresholds' => [
    'new_alert_threshold' => 30, // days
],
```

2. **Add generator method** (`RemittanceAlertService.php`):

```php
public function generateNewAlertTypeAlerts()
{
    $alertsCreated = 0;
    $threshold = config('remittance.alert_thresholds.new_alert_threshold', 30);

    // Your logic here
    $candidates = Candidate::where(/* conditions */)->get();

    foreach ($candidates as $candidate) {
        // Check for existing alert
        $existingAlert = RemittanceAlert::where('candidate_id', $candidate->id)
            ->where('alert_type', 'new_alert_type')
            ->where('is_resolved', false)
            ->first();

        if (!$existingAlert) {
            RemittanceAlert::create([
                'candidate_id' => $candidate->id,
                'alert_type' => 'new_alert_type',
                'severity' => 'warning',
                'title' => 'New Alert Title',
                'message' => 'Alert message...',
                'metadata' => [/* additional data */],
            ]);
            $alertsCreated++;
        }
    }

    return $alertsCreated;
}
```

3. **Update `generateAllAlerts()` method**:

```php
public function generateAllAlerts()
{
    $alerts = [
        'missing_remittances' => $this->generateMissingRemittanceAlerts(),
        'missing_proofs' => $this->generateMissingProofAlerts(),
        'first_remittance_delay' => $this->generateFirstRemittanceDelayAlerts(),
        'low_frequency' => $this->generateLowFrequencyAlerts(),
        'unusual_amount' => $this->generateUnusualAmountAlerts(),
        'new_alert_type' => $this->generateNewAlertTypeAlerts(), // Add this
    ];

    return [
        'total_generated' => array_sum($alerts),
        'breakdown' => $alerts,
    ];
}
```

4. **Write tests**:

```php
public function test_generate_new_alert_type_creates_alerts()
{
    // Setup test data
    $candidate = Candidate::factory()->create();

    // Run alert generation
    $alertsCreated = $this->service->generateNewAlertTypeAlerts();

    // Assert
    $this->assertGreaterThan(0, $alertsCreated);
    $this->assertDatabaseHas('remittance_alerts', [
        'candidate_id' => $candidate->id,
        'alert_type' => 'new_alert_type',
    ]);
}
```

### Adding a New Report

1. **Add method to `RemittanceAnalyticsService.php`**:

```php
public function getNewReport($filters = [])
{
    $query = Remittance::query();

    // Apply filters
    if (isset($filters['year'])) {
        $query->where('year', $filters['year']);
    }

    // Get data
    $data = $query->select(/* fields */)
        ->groupBy(/* grouping */)
        ->get();

    // Process and return
    return $data->map(function($item) {
        return [
            'field1' => $item->field1,
            'field2' => $item->field2,
            // ...
        ];
    })->toArray();
}
```

2. **Add controller method**:

```php
public function newReport(Request $request)
{
    $service = new RemittanceAnalyticsService();
    $data = $service->getNewReport($request->all());

    return view('remittances.reports.new-report', compact('data'));
}
```

3. **Add route**:

```php
Route::get('/remittance/reports/new-report',
    [RemittanceReportController::class, 'newReport'])
    ->name('remittance.reports.new');
```

4. **Create view** (`resources/views/remittances/reports/new-report.blade.php`)

5. **Add API endpoint**:

```php
Route::get('/remittance/reports/new-report',
    [RemittanceReportApiController::class, 'newReport'])
    ->name('remittance.reports.new');
```

6. **Write tests**

### Adding Custom Validation

Add custom validation rules in the controller:

```php
protected function validateRemittance(Request $request)
{
    return $request->validate([
        'candidate_id' => 'required|exists:candidates,id',
        'amount' => [
            'required',
            'numeric',
            'min:0',
            function ($attribute, $value, $fail) {
                if ($value > 1000000) {
                    $fail('Amount exceeds maximum allowed (1,000,000 PKR)');
                }
            },
        ],
        'transfer_date' => [
            'required',
            'date',
            'before_or_equal:today',
        ],
        'transaction_reference' => [
            'required',
            'unique:remittances,transaction_reference,' . $this->remittance?->id,
        ],
    ]);
}
```

---

## Performance Considerations

### Database Optimization

**Indexes:**
- All foreign keys are indexed
- Commonly queried fields (transfer_date, year, month, status) are indexed
- Composite indexes on frequently filtered combinations

**Query Optimization:**
```php
// Bad - N+1 problem
$remittances = Remittance::all();
foreach ($remittances as $remittance) {
    echo $remittance->candidate->name; // N queries
}

// Good - Eager loading
$remittances = Remittance::with('candidate', 'departure')->get();
foreach ($remittances as $remittance) {
    echo $remittance->candidate->name; // 1 query
}
```

**Chunking Large Datasets:**
```php
// Process large datasets in chunks
Remittance::chunk(1000, function ($remittances) {
    foreach ($remittances as $remittance) {
        // Process
    }
});
```

### Caching Strategies

**Cache expensive queries:**

```php
use Illuminate\Support\Facades\Cache;

public function getDashboardStats()
{
    return Cache::remember('remittance_dashboard_stats', 3600, function () {
        // Expensive calculation
        return [
            'total_remittances' => Remittance::count(),
            'total_amount' => Remittance::sum('amount'),
            // ...
        ];
    });
}
```

**Clear cache on updates:**

```php
// In RemittanceController
public function store(Request $request)
{
    $remittance = Remittance::create($validated);

    // Clear cached statistics
    Cache::forget('remittance_dashboard_stats');

    return redirect()->route('remittances.show', $remittance);
}
```

### Background Processing

For heavy operations, use queued jobs:

```php
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class GenerateMonthlyReport implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $service = new RemittanceAnalyticsService();
        $report = $service->getMonthlyReport();

        // Email or store report
    }
}

// Dispatch
GenerateMonthlyReport::dispatch()->delay(now()->addMinutes(5));
```

---

## Security

### Authorization

**Gate Definitions:**

```php
// In AuthServiceProvider.php
Gate::define('verify-remittance', function (User $user) {
    return $user->hasRole(['admin', 'verifier']);
});

Gate::define('delete-remittance', function (User $user) {
    return $user->hasRole('admin');
});
```

**Usage in Controllers:**

```php
public function verify($id)
{
    $this->authorize('verify-remittance');

    $remittance = Remittance::findOrFail($id);
    $remittance->markAsVerified(auth()->id());

    return redirect()->back();
}
```

### Input Validation

Always validate user input:

```php
$validated = $request->validate([
    'candidate_id' => 'required|exists:candidates,id',
    'amount' => 'required|numeric|min:0|max:10000000',
    'transaction_reference' => 'required|unique:remittances',
    'transfer_date' => 'required|date|before_or_equal:today',
]);
```

### SQL Injection Protection

Use Eloquent ORM and query builder (never raw SQL with user input):

```php
// Safe - parameterized query
$remittances = Remittance::where('candidate_id', $candidateId)->get();

// Safe - query builder
$remittances = DB::table('remittances')
    ->where('candidate_id', '=', $candidateId)
    ->get();

// Dangerous - avoid
$remittances = DB::select("SELECT * FROM remittances WHERE candidate_id = $candidateId");
```

### File Upload Security

```php
$request->validate([
    'receipt' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB
]);

$path = $request->file('receipt')->store('receipts', 'private');
```

---

## Troubleshooting

### Common Issues

#### Alerts Not Generating

**Issue:** `generateAllAlerts()` returns 0

**Solutions:**
1. Check alert thresholds in `config/remittance.php`
2. Verify candidates have departures with dates
3. Run command manually: `php artisan remittance:generate-alerts`
4. Check for existing unresolved alerts (prevents duplicates)

#### Performance Issues

**Issue:** Reports loading slowly

**Solutions:**
1. Add database indexes
2. Implement caching
3. Use eager loading
4. Optimize queries (check with `DB::enableQueryLog()`)
5. Consider pagination for large datasets

#### Test Failures

**Issue:** Tests failing with database errors

**Solutions:**
1. Ensure SQLite extension installed
2. Run migrations: `php artisan migrate:fresh`
3. Clear cache: `php artisan cache:clear`
4. Check factory relationships

---

## Development Workflow

### Local Development

1. **Setup:**
```bash
git clone <repository>
cd btevta
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
```

2. **Running:**
```bash
php artisan serve
```

3. **Testing:**
```bash
php artisan test --filter=Remittance
```

### Code Style

Follow PSR-12 coding standards:

```bash
# Format code
./vendor/bin/pint

# Check style
./vendor/bin/pint --test
```

### Git Workflow

1. Create feature branch
2. Make changes
3. Write tests
4. Run tests
5. Commit with clear message
6. Push and create PR

---

## Useful Commands

```bash
# Generate alerts manually
php artisan remittance:generate-alerts

# Generate alerts with auto-resolution
php artisan remittance:generate-alerts --auto-resolve

# Run tests
php artisan test --filter=Remittance

# Clear cache
php artisan cache:clear

# Re-migrate database
php artisan migrate:fresh --seed

# Generate factory data
php artisan tinker
> Remittance::factory()->count(100)->create()
```

---

## API Response Examples

### Successful Response

```json
{
  "data": [
    {
      "id": 1,
      "candidate_id": 123,
      "transaction_reference": "TXN123456",
      "amount": 50000.00,
      "currency": "PKR",
      "transfer_date": "2025-11-01",
      "status": "verified"
    }
  ],
  "current_page": 1,
  "per_page": 20,
  "total": 150
}
```

### Error Response

```json
{
  "error": "Remittance not found"
}
```

### Validation Error

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "amount": ["The amount must be at least 0."],
    "transfer_date": ["The transfer date must be a date before or equal to today."]
  }
}
```

---

**Document Version:** 1.0
**Last Updated:** November 2025
**Maintainers:** BTEVTA Development Team

For questions or contributions, contact: dev-team@btevta.gov.pk
