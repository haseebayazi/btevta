# 💱 Remittance Management Module - Implementation Plan

**Project:** WASL Platform
**Module:** Module 10 - Remittance Management
**Estimated Duration:** 40-60 hours (3-4 weeks)
**Priority:** High
**Status:** Planning Phase

---

## 📋 Table of Contents

1. [Module Overview](#module-overview)
2. [Database Schema](#database-schema)
3. [Models](#models)
4. [Controllers](#controllers)
5. [Routes](#routes)
6. [Views](#views)
7. [Services](#services)
8. [Policies](#policies)
9. [Features Breakdown](#features-breakdown)
10. [Implementation Phases](#implementation-phases)
11. [Testing Plan](#testing-plan)

---

## 🎯 Module Overview

### Purpose
Track remittance inflows from deployed workers, monitor usage patterns, manage beneficiaries, and provide impact analytics for families and the organization.

### Core Features
1. ✅ Track remittance inflows post-deployment with timestamps
2. ✅ Tag each transfer by usage purpose (education, rent, health, savings, family support, etc.)
3. ✅ Upload digital proof (receipts/photos)
4. ✅ Real-time sender visibility with alerts
5. ✅ Beneficiary and family member management
6. ✅ Remittance impact analytics and reports
7. ✅ Monthly tracking and trends
8. ✅ Proof-of-use ratio tracking

### User Roles with Access
- **Admin:** Full access to all remittances, analytics, and reports
- **OEP:** View remittances for their deployed candidates
- **Candidate/Worker:** Record their own remittances and view history
- **Campus Admin:** View remittances for candidates from their campus
- **Family/Beneficiary:** View-only access to designated remittances (future enhancement)

---

## 🗄️ Database Schema

### Migration 1: `remittances` Table

```php
Schema::create('remittances', function (Blueprint $table) {
    $table->id();

    // Foreign Keys
    $table->foreignId('candidate_id')->constrained('candidates')->onDelete('cascade');
    $table->foreignId('departure_id')->nullable()->constrained('departures')->onDelete('set null');
    $table->foreignId('recorded_by')->nullable()->constrained('users')->onDelete('set null');

    // Remittance Details
    $table->string('transaction_reference')->unique(); // Unique transaction ID
    $table->decimal('amount', 12, 2); // Amount in local currency
    $table->string('currency', 3)->default('PKR'); // Currency code (PKR, USD, SAR, etc.)
    $table->decimal('amount_foreign', 12, 2)->nullable(); // Amount in foreign currency
    $table->string('foreign_currency', 3)->nullable(); // Foreign currency code
    $table->decimal('exchange_rate', 10, 4)->nullable(); // Exchange rate used

    // Transfer Information
    $table->date('transfer_date'); // Date of transfer
    $table->string('transfer_method')->nullable(); // Bank, Money Transfer, Mobile Wallet, etc.
    $table->string('sender_name'); // Worker name
    $table->string('sender_location')->nullable(); // Location in foreign country
    $table->string('receiver_name'); // Beneficiary name
    $table->string('receiver_account')->nullable(); // Account/wallet number
    $table->string('bank_name')->nullable(); // Bank or service provider

    // Purpose & Usage
    $table->enum('primary_purpose', [
        'education',
        'health',
        'rent',
        'food',
        'savings',
        'debt_repayment',
        'family_support',
        'business_investment',
        'other'
    ])->default('family_support');
    $table->text('purpose_description')->nullable(); // Detailed description
    $table->boolean('has_proof')->default(false); // Whether proof is uploaded
    $table->date('proof_verified_date')->nullable(); // When proof was verified
    $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');

    // Status & Tracking
    $table->enum('status', ['pending', 'verified', 'flagged', 'completed'])->default('pending');
    $table->text('notes')->nullable(); // Admin/OEP notes
    $table->text('alert_message')->nullable(); // Automated alerts (if any)

    // Metadata
    $table->boolean('is_first_remittance')->default(false); // Track first remittance
    $table->integer('month_number')->nullable(); // Month since deployment (1, 2, 3...)
    $table->year('year'); // Year of remittance
    $table->tinyInteger('month'); // Month (1-12)
    $table->tinyInteger('quarter')->nullable(); // Quarter (1-4)

    $table->timestamps();
    $table->softDeletes();

    // Indexes
    $table->index('candidate_id');
    $table->index('transfer_date');
    $table->index('status');
    $table->index(['year', 'month']);
    $table->index('primary_purpose');
});
```

### Migration 2: `remittance_beneficiaries` Table

```php
Schema::create('remittance_beneficiaries', function (Blueprint $table) {
    $table->id();

    // Foreign Keys
    $table->foreignId('candidate_id')->constrained('candidates')->onDelete('cascade');

    // Beneficiary Details
    $table->string('full_name');
    $table->enum('relationship', [
        'spouse',
        'father',
        'mother',
        'son',
        'daughter',
        'brother',
        'sister',
        'other_relative',
        'self'
    ]);
    $table->string('cnic')->nullable(); // National ID
    $table->string('phone')->nullable();
    $table->string('email')->nullable();
    $table->text('address')->nullable();
    $table->string('city')->nullable();
    $table->string('district')->nullable();

    // Bank Details
    $table->string('bank_name')->nullable();
    $table->string('account_number')->nullable();
    $table->string('iban')->nullable();
    $table->string('mobile_wallet')->nullable(); // JazzCash, Easypaisa, etc.

    // Status
    $table->boolean('is_primary')->default(false); // Primary beneficiary
    $table->boolean('is_active')->default(true);
    $table->text('notes')->nullable();

    $table->timestamps();
    $table->softDeletes();

    // Indexes
    $table->index('candidate_id');
    $table->index('is_primary');
});
```

### Migration 3: `remittance_receipts` Table

```php
Schema::create('remittance_receipts', function (Blueprint $table) {
    $table->id();

    // Foreign Keys
    $table->foreignId('remittance_id')->constrained('remittances')->onDelete('cascade');
    $table->foreignId('uploaded_by')->nullable()->constrained('users')->onDelete('set null');

    // File Details
    $table->string('file_name'); // Original filename
    $table->string('file_path'); // Storage path
    $table->string('file_type')->nullable(); // image/pdf/etc
    $table->bigInteger('file_size')->nullable(); // Size in bytes
    $table->enum('document_type', [
        'bank_receipt',
        'transfer_slip',
        'mobile_screenshot',
        'email_confirmation',
        'other'
    ])->default('bank_receipt');

    // Verification
    $table->boolean('is_verified')->default(false);
    $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
    $table->timestamp('verified_at')->nullable();
    $table->text('verification_notes')->nullable();

    $table->timestamps();
    $table->softDeletes();

    // Indexes
    $table->index('remittance_id');
    $table->index('is_verified');
});
```

### Migration 4: `remittance_usage_breakdown` Table

```php
Schema::create('remittance_usage_breakdown', function (Blueprint $table) {
    $table->id();

    // Foreign Keys
    $table->foreignId('remittance_id')->constrained('remittances')->onDelete('cascade');

    // Usage Details
    $table->enum('usage_category', [
        'education',
        'health',
        'rent',
        'food',
        'savings',
        'debt_repayment',
        'family_support',
        'business_investment',
        'utilities',
        'transportation',
        'clothing',
        'other'
    ]);
    $table->decimal('amount', 10, 2); // Amount allocated to this category
    $table->decimal('percentage', 5, 2)->nullable(); // Percentage of total
    $table->text('description')->nullable(); // Specific usage details
    $table->boolean('has_proof')->default(false); // Has proof for this usage

    $table->timestamps();

    // Indexes
    $table->index('remittance_id');
    $table->index('usage_category');
});
```

### Migration 5: `remittance_alerts` Table

```php
Schema::create('remittance_alerts', function (Blueprint $table) {
    $table->id();

    // Foreign Keys
    $table->foreignId('candidate_id')->constrained('candidates')->onDelete('cascade');
    $table->foreignId('remittance_id')->nullable()->constrained('remittances')->onDelete('cascade');

    // Alert Details
    $table->enum('alert_type', [
        'no_remittance_90_days',
        'first_remittance_received',
        'irregular_pattern',
        'large_amount',
        'missing_proof',
        'beneficiary_change',
        'other'
    ]);
    $table->enum('severity', ['info', 'warning', 'critical'])->default('info');
    $table->string('title');
    $table->text('message');
    $table->json('metadata')->nullable(); // Additional data

    // Status
    $table->boolean('is_read')->default(false);
    $table->boolean('is_resolved')->default(false);
    $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
    $table->timestamp('resolved_at')->nullable();
    $table->text('resolution_notes')->nullable();

    $table->timestamps();

    // Indexes
    $table->index('candidate_id');
    $table->index('alert_type');
    $table->index(['is_read', 'is_resolved']);
});
```

---

## 📦 Models

### 1. `Remittance.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Remittance extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'candidate_id',
        'departure_id',
        'recorded_by',
        'transaction_reference',
        'amount',
        'currency',
        'amount_foreign',
        'foreign_currency',
        'exchange_rate',
        'transfer_date',
        'transfer_method',
        'sender_name',
        'sender_location',
        'receiver_name',
        'receiver_account',
        'bank_name',
        'primary_purpose',
        'purpose_description',
        'has_proof',
        'proof_verified_date',
        'verified_by',
        'status',
        'notes',
        'alert_message',
        'is_first_remittance',
        'month_number',
        'year',
        'month',
        'quarter',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'proof_verified_date' => 'date',
        'amount' => 'decimal:2',
        'amount_foreign' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'has_proof' => 'boolean',
        'is_first_remittance' => 'boolean',
        'year' => 'integer',
        'month' => 'integer',
        'quarter' => 'integer',
    ];

    // Relationships
    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function departure()
    {
        return $this->belongsTo(Departure::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function receipts()
    {
        return $this->hasMany(RemittanceReceipt::class);
    }

    public function usageBreakdown()
    {
        return $this->hasMany(RemittanceUsageBreakdown::class);
    }

    // Scopes
    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByYear($query, $year)
    {
        return $query->where('year', $year);
    }

    public function scopeByMonth($query, $year, $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }

    public function scopeByPurpose($query, $purpose)
    {
        return $query->where('primary_purpose', $purpose);
    }

    // Accessors
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }

    public function getMonthNameAttribute()
    {
        return date('F', mktime(0, 0, 0, $this->month, 1));
    }

    // Mutators
    public function setTransferDateAttribute($value)
    {
        $this->attributes['transfer_date'] = $value;

        // Auto-set year, month, quarter
        if ($value) {
            $date = \Carbon\Carbon::parse($value);
            $this->attributes['year'] = $date->year;
            $this->attributes['month'] = $date->month;
            $this->attributes['quarter'] = $date->quarter;
        }
    }

    // Methods
    public function calculateMonthNumber()
    {
        if ($this->departure && $this->departure->departure_date) {
            $deploymentDate = \Carbon\Carbon::parse($this->departure->departure_date);
            $transferDate = \Carbon\Carbon::parse($this->transfer_date);
            return $deploymentDate->diffInMonths($transferDate) + 1;
        }
        return null;
    }

    public function markAsVerified($userId)
    {
        $this->update([
            'status' => 'verified',
            'verified_by' => $userId,
            'proof_verified_date' => now(),
        ]);
    }

    public function hasCompleteProof()
    {
        return $this->has_proof && $this->receipts()->where('is_verified', true)->exists();
    }
}
```

### 2. `RemittanceBeneficiary.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RemittanceBeneficiary extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'candidate_id',
        'full_name',
        'relationship',
        'cnic',
        'phone',
        'email',
        'address',
        'city',
        'district',
        'bank_name',
        'account_number',
        'iban',
        'mobile_wallet',
        'is_primary',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function remittances()
    {
        return $this->hasMany(Remittance::class, 'receiver_name', 'full_name');
    }

    // Scopes
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Methods
    public function setPrimary()
    {
        // Remove primary status from others
        self::where('candidate_id', $this->candidate_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        // Set this as primary
        $this->update(['is_primary' => true]);
    }

    public function getFullContactAttribute()
    {
        $parts = array_filter([
            $this->phone,
            $this->email,
        ]);
        return implode(' | ', $parts);
    }
}
```

### 3. `RemittanceReceipt.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class RemittanceReceipt extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'remittance_id',
        'uploaded_by',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'document_type',
        'is_verified',
        'verified_by',
        'verified_at',
        'verification_notes',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'file_size' => 'integer',
    ];

    // Relationships
    public function remittance()
    {
        return $this->belongsTo(Remittance::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // Accessors
    public function getFileUrlAttribute()
    {
        return Storage::url($this->file_path);
    }

    public function getFileSizeFormattedAttribute()
    {
        if ($this->file_size < 1024) {
            return $this->file_size . ' B';
        } elseif ($this->file_size < 1048576) {
            return round($this->file_size / 1024, 2) . ' KB';
        } else {
            return round($this->file_size / 1048576, 2) . ' MB';
        }
    }

    // Methods
    public function verify($userId, $notes = null)
    {
        $this->update([
            'is_verified' => true,
            'verified_by' => $userId,
            'verified_at' => now(),
            'verification_notes' => $notes,
        ]);

        // Update parent remittance
        $this->remittance->update(['has_proof' => true]);
    }

    public function deleteFile()
    {
        if (Storage::exists($this->file_path)) {
            Storage::delete($this->file_path);
        }
    }

    // Events
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($receipt) {
            $receipt->deleteFile();
        });
    }
}
```

### 4. `RemittanceUsageBreakdown.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RemittanceUsageBreakdown extends Model
{
    use HasFactory;

    protected $fillable = [
        'remittance_id',
        'usage_category',
        'amount',
        'percentage',
        'description',
        'has_proof',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'percentage' => 'decimal:2',
        'has_proof' => 'boolean',
    ];

    // Relationships
    public function remittance()
    {
        return $this->belongsTo(Remittance::class);
    }

    // Accessors
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 2);
    }

    public function getCategoryLabelAttribute()
    {
        return ucwords(str_replace('_', ' ', $this->usage_category));
    }

    // Methods
    public function calculatePercentage()
    {
        if ($this->remittance && $this->remittance->amount > 0) {
            $this->percentage = ($this->amount / $this->remittance->amount) * 100;
            $this->save();
        }
    }
}
```

### 5. `RemittanceAlert.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RemittanceAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_id',
        'remittance_id',
        'alert_type',
        'severity',
        'title',
        'message',
        'metadata',
        'is_read',
        'is_resolved',
        'resolved_by',
        'resolved_at',
        'resolution_notes',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_read' => 'boolean',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    // Relationships
    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function remittance()
    {
        return $this->belongsTo(Remittance::class);
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    // Methods
    public function markAsRead()
    {
        $this->update(['is_read' => true]);
    }

    public function resolve($userId, $notes = null)
    {
        $this->update([
            'is_resolved' => true,
            'resolved_by' => $userId,
            'resolved_at' => now(),
            'resolution_notes' => $notes,
        ]);
    }
}
```

---

## 🎮 Controllers

### 1. `RemittanceController.php`

**Location:** `app/Http/Controllers/RemittanceController.php`

**Methods:**
- `index()` - List all remittances with filters
- `create()` - Show create form
- `store()` - Store new remittance
- `show($id)` - View remittance details
- `edit($id)` - Show edit form
- `update($id)` - Update remittance
- `destroy($id)` - Delete remittance
- `verify($id)` - Verify remittance
- `uploadReceipt($id)` - Upload receipt
- `deleteReceipt($receiptId)` - Delete receipt
- `export()` - Export remittances to Excel/PDF

### 2. `RemittanceBeneficiaryController.php`

**Location:** `app/Http/Controllers/RemittanceBeneficiaryController.php`

**Methods:**
- `index($candidateId)` - List beneficiaries for a candidate
- `create($candidateId)` - Show create form
- `store($candidateId)` - Store new beneficiary
- `edit($id)` - Show edit form
- `update($id)` - Update beneficiary
- `destroy($id)` - Delete beneficiary
- `setPrimary($id)` - Set as primary beneficiary

### 3. `RemittanceReportController.php`

**Location:** `app/Http/Controllers/RemittanceReportController.php`

**Methods:**
- `dashboard()` - Remittance analytics dashboard
- `monthlyReport()` - Monthly remittance report
- `purposeAnalysis()` - Analysis by purpose
- `beneficiaryReport()` - Beneficiary-wise report
- `proofComplianceReport()` - Proof of use compliance
- `impactAnalytics()` - Remittance impact analytics
- `export($type)` - Export various reports

### 4. `RemittanceAlertController.php`

**Location:** `app/Http/Controllers/RemittanceAlertController.php`

**Methods:**
- `index()` - List all alerts
- `show($id)` - View alert details
- `markAsRead($id)` - Mark alert as read
- `resolve($id)` - Resolve alert
- `generateAlerts()` - Manually trigger alert generation

---

## 🛣️ Routes

### Web Routes

**Location:** `routes/web.php`

```php
// Remittance Management Routes
Route::middleware(['auth'])->group(function () {

    // Dashboard Tab
    Route::get('/dashboard/remittance', [DashboardController::class, 'remittance'])
        ->name('dashboard.remittance');

    // Remittances
    Route::resource('remittances', RemittanceController::class);
    Route::post('/remittances/{id}/verify', [RemittanceController::class, 'verify'])
        ->name('remittances.verify');
    Route::post('/remittances/{id}/upload-receipt', [RemittanceController::class, 'uploadReceipt'])
        ->name('remittances.upload-receipt');
    Route::delete('/remittances/receipts/{id}', [RemittanceController::class, 'deleteReceipt'])
        ->name('remittances.delete-receipt');
    Route::get('/remittances/export/{format}', [RemittanceController::class, 'export'])
        ->name('remittances.export');

    // Beneficiaries
    Route::get('/candidates/{candidateId}/beneficiaries', [RemittanceBeneficiaryController::class, 'index'])
        ->name('beneficiaries.index');
    Route::get('/candidates/{candidateId}/beneficiaries/create', [RemittanceBeneficiaryController::class, 'create'])
        ->name('beneficiaries.create');
    Route::post('/candidates/{candidateId}/beneficiaries', [RemittanceBeneficiaryController::class, 'store'])
        ->name('beneficiaries.store');
    Route::get('/beneficiaries/{id}/edit', [RemittanceBeneficiaryController::class, 'edit'])
        ->name('beneficiaries.edit');
    Route::put('/beneficiaries/{id}', [RemittanceBeneficiaryController::class, 'update'])
        ->name('beneficiaries.update');
    Route::delete('/beneficiaries/{id}', [RemittanceBeneficiaryController::class, 'destroy'])
        ->name('beneficiaries.destroy');
    Route::post('/beneficiaries/{id}/set-primary', [RemittanceBeneficiaryController::class, 'setPrimary'])
        ->name('beneficiaries.set-primary');

    // Reports & Analytics
    Route::get('/remittance/reports/dashboard', [RemittanceReportController::class, 'dashboard'])
        ->name('remittance.reports.dashboard');
    Route::get('/remittance/reports/monthly', [RemittanceReportController::class, 'monthlyReport'])
        ->name('remittance.reports.monthly');
    Route::get('/remittance/reports/purpose-analysis', [RemittanceReportController::class, 'purposeAnalysis'])
        ->name('remittance.reports.purpose');
    Route::get('/remittance/reports/beneficiary', [RemittanceReportController::class, 'beneficiaryReport'])
        ->name('remittance.reports.beneficiary');
    Route::get('/remittance/reports/proof-compliance', [RemittanceReportController::class, 'proofComplianceReport'])
        ->name('remittance.reports.proof');
    Route::get('/remittance/reports/impact', [RemittanceReportController::class, 'impactAnalytics'])
        ->name('remittance.reports.impact');
    Route::get('/remittance/reports/export/{type}', [RemittanceReportController::class, 'export'])
        ->name('remittance.reports.export');

    // Alerts
    Route::get('/remittance/alerts', [RemittanceAlertController::class, 'index'])
        ->name('remittance.alerts.index');
    Route::get('/remittance/alerts/{id}', [RemittanceAlertController::class, 'show'])
        ->name('remittance.alerts.show');
    Route::post('/remittance/alerts/{id}/read', [RemittanceAlertController::class, 'markAsRead'])
        ->name('remittance.alerts.read');
    Route::post('/remittance/alerts/{id}/resolve', [RemittanceAlertController::class, 'resolve'])
        ->name('remittance.alerts.resolve');
    Route::post('/remittance/alerts/generate', [RemittanceAlertController::class, 'generateAlerts'])
        ->name('remittance.alerts.generate')
        ->middleware('role:admin');
});
```

### API Routes

**Location:** `routes/api.php`

```php
// Remittance API Routes
Route::middleware(['auth:sanctum'])->prefix('remittances')->group(function () {
    Route::get('/search', [RemittanceController::class, 'search']);
    Route::get('/stats', [RemittanceReportController::class, 'apiStats']);
    Route::get('/candidate/{candidateId}', [RemittanceController::class, 'byCandidate']);
    Route::get('/beneficiaries/{candidateId}', [RemittanceBeneficiaryController::class, 'apiList']);
    Route::get('/alerts/unread', [RemittanceAlertController::class, 'unreadCount']);
});
```

---

## 🎨 Views

### Directory Structure

```
resources/views/remittances/
├── index.blade.php                 # List all remittances
├── create.blade.php                # Create new remittance
├── edit.blade.php                  # Edit remittance
├── show.blade.php                  # View remittance details
├── partials/
│   ├── filters.blade.php           # Filter form
│   ├── remittance-card.blade.php   # Remittance card component
│   ├── receipt-upload.blade.php    # Receipt upload form
│   └── usage-breakdown.blade.php   # Usage breakdown display
├── beneficiaries/
│   ├── index.blade.php             # List beneficiaries
│   ├── create.blade.php            # Add beneficiary
│   ├── edit.blade.php              # Edit beneficiary
│   └── partials/
│       └── beneficiary-card.blade.php
├── reports/
│   ├── dashboard.blade.php         # Analytics dashboard
│   ├── monthly.blade.php           # Monthly report
│   ├── purpose-analysis.blade.php  # Purpose analysis
│   ├── beneficiary.blade.php       # Beneficiary report
│   ├── proof-compliance.blade.php  # Proof compliance
│   └── impact.blade.php            # Impact analytics
└── alerts/
    ├── index.blade.php             # List alerts
    └── show.blade.php              # Alert details
```

### Key Views Details

#### 1. `index.blade.php`
- Data table with pagination
- Filters (date range, status, purpose, candidate, OEP)
- Quick stats cards (total remittances, total amount, avg amount, proof rate)
- Export buttons
- Create new button

#### 2. `create.blade.php` / `edit.blade.php`
- Form with sections:
  - Candidate selection (auto-fill from departure)
  - Transfer details
  - Amount and currency
  - Purpose selection
  - Beneficiary selection
  - Receipt upload
  - Usage breakdown (optional)
  - Notes

#### 3. `show.blade.php`
- Remittance details card
- Transfer information
- Beneficiary details
- Receipts gallery/list
- Usage breakdown chart
- Timeline/audit log
- Action buttons (verify, edit, delete)

#### 4. `reports/dashboard.blade.php`
- KPI cards (total remittances, total value, average, trends)
- Monthly trend chart
- Purpose distribution pie chart
- Top beneficiaries list
- Proof compliance rate
- Recent remittances table
- Alerts summary

---

## ⚙️ Services

### 1. `RemittanceService.php`

**Location:** `app/Services/RemittanceService.php`

**Responsibilities:**
- Business logic for remittance operations
- Validation and data processing
- File upload handling
- Transaction calculations
- Alert generation

**Key Methods:**
```php
public function createRemittance(array $data): Remittance
public function updateRemittance(Remittance $remittance, array $data): Remittance
public function verifyRemittance(Remittance $remittance, User $user): void
public function uploadReceipt(Remittance $remittance, UploadedFile $file, array $data): RemittanceReceipt
public function calculateMonthNumber(Remittance $remittance): int
public function addUsageBreakdown(Remittance $remittance, array $breakdown): void
public function generateAlerts(Candidate $candidate): void
public function checkMissingRemittances(): void
```

### 2. `RemittanceAnalyticsService.php`

**Location:** `app/Services/RemittanceAnalyticsService.php`

**Responsibilities:**
- Generate analytics and reports
- Statistical calculations
- Trend analysis
- Data aggregation

**Key Methods:**
```php
public function getMonthlyTrends(int $year): array
public function getPurposeDistribution(?int $year = null): array
public function getBeneficiaryStats(): array
public function getProofComplianceRate(): float
public function getAverageRemittanceAmount(): float
public function getTopRemittingSectors(): array
public function getCandidateRemittanceHistory(Candidate $candidate): array
public function getImpactMetrics(): array
```

### 3. `RemittanceNotificationService.php`

**Location:** `app/Services/RemittanceNotificationService.php`

**Responsibilities:**
- Send notifications for remittance events
- Email/SMS alerts
- In-app notifications

**Key Methods:**
```php
public function notifyRemittanceReceived(Remittance $remittance): void
public function notifyVerificationRequired(Remittance $remittance): void
public function notifyMissingProof(Remittance $remittance): void
public function notifyNoRemittance90Days(Candidate $candidate): void
```

---

## 🔒 Policies

### `RemittancePolicy.php`

**Location:** `app/Policies/RemittancePolicy.php`

**Authorization Rules:**

```php
public function viewAny(User $user): bool
{
    return in_array($user->role, ['admin', 'campus_admin', 'oep', 'candidate']);
}

public function view(User $user, Remittance $remittance): bool
{
    if ($user->role === 'admin') return true;
    if ($user->role === 'oep') return $remittance->candidate->oep_id === $user->oep_id;
    if ($user->role === 'campus_admin') return $remittance->candidate->campus_id === $user->campus_id;
    if ($user->role === 'candidate') return $remittance->candidate->user_id === $user->id;
    return false;
}

public function create(User $user): bool
{
    return in_array($user->role, ['admin', 'oep', 'candidate']);
}

public function update(User $user, Remittance $remittance): bool
{
    if ($user->role === 'admin') return true;
    if ($user->role === 'candidate') {
        return $remittance->candidate->user_id === $user->id
            && $remittance->status === 'pending';
    }
    return false;
}

public function delete(User $user, Remittance $remittance): bool
{
    return $user->role === 'admin';
}

public function verify(User $user, Remittance $remittance): bool
{
    return in_array($user->role, ['admin', 'oep']);
}
```

---

## 📊 Features Breakdown

### Phase 1: Core Functionality (Week 1)

**Tasks:**
1. ✅ Create database migrations (5 tables)
2. ✅ Create Eloquent models with relationships
3. ✅ Build basic CRUD controllers
4. ✅ Set up routes
5. ✅ Create basic views (index, create, edit, show)
6. ✅ Implement file upload for receipts
7. ✅ Add basic validation

**Deliverables:**
- Working remittance recording system
- Beneficiary management
- Receipt upload functionality

### Phase 2: Analytics & Reports (Week 2)

**Tasks:**
1. ✅ Build RemittanceAnalyticsService
2. ✅ Create analytics dashboard view
3. ✅ Implement monthly trend charts
4. ✅ Purpose distribution analysis
5. ✅ Proof compliance tracking
6. ✅ Export functionality (Excel/PDF)

**Deliverables:**
- Complete analytics dashboard
- Multiple report types
- Export capabilities

### Phase 3: Alerts & Notifications (Week 3)

**Tasks:**
1. ✅ Create alert generation system
2. ✅ Build RemittanceNotificationService
3. ✅ Implement email notifications
4. ✅ Create alert views
5. ✅ Add scheduled jobs for auto-alerts
6. ✅ Real-time notifications (optional)

**Deliverables:**
- Automated alert system
- Notification emails
- Alert management dashboard

### Phase 4: Advanced Features & Testing (Week 4)

**Tasks:**
1. ✅ Usage breakdown tracking
2. ✅ Impact analytics
3. ✅ Advanced filtering and search
4. ✅ Bulk operations
5. ✅ Complete testing (unit, feature, integration)
6. ✅ Bug fixes and optimization
7. ✅ Documentation

**Deliverables:**
- Full feature set complete
- Tested and production-ready
- User documentation

---

## 🧪 Testing Plan

### Unit Tests

**Location:** `tests/Unit/`

1. **RemittanceTest.php**
   - Test model relationships
   - Test scopes
   - Test accessors/mutators
   - Test business methods

2. **RemittanceServiceTest.php**
   - Test remittance creation
   - Test verification logic
   - Test alert generation
   - Test calculations

3. **RemittanceAnalyticsServiceTest.php**
   - Test analytics calculations
   - Test trend analysis
   - Test data aggregation

### Feature Tests

**Location:** `tests/Feature/`

1. **RemittanceManagementTest.php**
   - Test CRUD operations
   - Test authorization
   - Test file uploads
   - Test validation

2. **RemittanceReportTest.php**
   - Test report generation
   - Test export functionality
   - Test data accuracy

3. **RemittanceAlertTest.php**
   - Test alert creation
   - Test notification sending
   - Test alert resolution

### Integration Tests

1. Test complete remittance flow (create → upload → verify)
2. Test candidate-to-remittance linkage
3. Test multi-beneficiary scenarios
4. Test report data consistency

---

## 📋 Database Seeder

**Location:** `database/seeders/RemittanceSeeder.php`

**Generate Sample Data:**
- 100+ sample remittances
- Various statuses and purposes
- Multiple beneficiaries
- Sample receipts
- Test alerts

---

## 🔄 Scheduled Jobs

### `CheckMissingRemittancesJob.php`

**Schedule:** Daily at 9:00 AM
**Purpose:** Check for candidates with no remittances in 90 days

### `GenerateMonthlyRemittanceReportJob.php`

**Schedule:** 1st of every month
**Purpose:** Auto-generate monthly remittance reports

---

## 📚 Additional Files to Create/Update

### 1. Update Navigation

**File:** `resources/views/layouts/app.blade.php`

Add Remittance module to sidebar:
```html
<!-- Tab 11: Remittance Management -->
<a href="{{ route('dashboard.remittance') }}"
   class="sidebar-item flex items-center space-x-3 px-3 py-2 rounded-lg">
    <i class="fas fa-money-bill-transfer text-lg w-6"></i>
    <span x-show="sidebarOpen" class="font-medium">Remittance</span>
</a>
```

### 2. Update DashboardController

Add remittance tab method:
```php
public function remittance()
{
    // Statistics for remittance dashboard
    return view('dashboard.remittance');
}
```

### 3. Update Candidate Model

Add remittance relationship:
```php
public function remittances()
{
    return $this->hasMany(Remittance::class);
}

public function beneficiaries()
{
    return $this->hasMany(RemittanceBeneficiary::class);
}
```

### 4. Config File

**File:** `config/remittance.php`

```php
return [
    'currencies' => ['PKR', 'USD', 'SAR', 'AED', 'EUR', 'GBP'],
    'purposes' => [
        'education' => 'Education',
        'health' => 'Health',
        'rent' => 'Rent/Housing',
        'food' => 'Food & Groceries',
        'savings' => 'Savings',
        'debt_repayment' => 'Debt Repayment',
        'family_support' => 'Family Support',
        'business_investment' => 'Business Investment',
        'other' => 'Other',
    ],
    'max_file_size' => 5120, // KB
    'allowed_file_types' => ['pdf', 'jpg', 'jpeg', 'png'],
    'alert_threshold_days' => 90,
];
```

---

## 🎯 Success Metrics

### KPIs to Track

1. **Adoption Rate:** % of deployed candidates recording remittances
2. **Proof Compliance:** % of remittances with verified receipts
3. **Average Remittance Amount:** Per candidate, per month
4. **Purpose Distribution:** Where money is being used
5. **Alert Resolution Time:** How quickly alerts are addressed
6. **Data Completeness:** % of fields filled in remittance records

### Performance Targets

- ✅ 80%+ of deployed workers recording remittances within 90 days
- ✅ 70%+ proof compliance rate
- ✅ <24 hours alert response time
- ✅ 95%+ data accuracy

---

## 🚀 Deployment Checklist

- [ ] Run all migrations
- [ ] Seed sample data (development only)
- [ ] Run tests (100% pass rate)
- [ ] Update navigation menu
- [ ] Configure file storage
- [ ] Set up scheduled jobs
- [ ] Configure email notifications
- [ ] Update user documentation
- [ ] Train users
- [ ] Monitor performance

---

## 📞 Support & Maintenance

### Post-Launch Tasks

1. Monitor usage and gather feedback
2. Address bugs and issues
3. Optimize queries for performance
4. Add requested features
5. Regular data backups

### Future Enhancements

1. Mobile app integration
2. SMS notifications for beneficiaries
3. Real-time currency conversion API
4. Blockchain verification (optional)
5. Integration with banking APIs
6. Family portal for beneficiaries
7. Predictive analytics (ML-based)

---

## 📖 Documentation

### User Guides to Create

1. **Admin Guide:** Complete remittance management
2. **OEP Guide:** Monitoring and verification
3. **Candidate Guide:** Recording remittances
4. **Report Guide:** Understanding analytics

### API Documentation

If exposing APIs, document:
- Endpoints
- Authentication
- Request/Response formats
- Rate limits

---

## ✅ Summary

This implementation plan provides a complete roadmap for building the Remittance Management Module from scratch. The modular approach ensures each component is well-defined, tested, and integrated seamlessly with the existing WASL platform.

**Estimated Timeline:** 3-4 weeks
**Estimated Effort:** 40-60 hours
**Team Size:** 1-2 developers

**Next Steps:**
1. Review and approve this plan
2. Set up development environment
3. Start with Phase 1 (Core Functionality)
4. Iterate through phases
5. Deploy and monitor

---

**Document Version:** 1.0
**Last Updated:** November 11, 2025
**Author:** Claude AI Assistant
**Status:** Ready for Implementation
