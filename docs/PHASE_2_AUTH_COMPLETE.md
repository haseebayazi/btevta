# PHASE 2: AUTHENTICATION & AUTHORIZATION - COMPLETE
## Full Implementation with All Code

**Duration:** 2-3 Days  
**Prerequisites:** Phase 0, 1 Complete  
**Files to Create:** 25+  

---

## SECTION 2.1: Enhanced User Model

**File:** `app/Models/User.php` (REPLACE EXISTING)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, LogsActivity, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'email',
        'phone',
        'password',
        'campus_id',
        'oep_id',
        'password_changed_at',
        'must_change_password',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password_changed_at' => 'datetime',
        'locked_until' => 'datetime',
        'must_change_password' => 'boolean',
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($user) {
            if (empty($user->uuid)) {
                $user->uuid = (string) Str::uuid();
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relationships
    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function oep()
    {
        return $this->belongsTo(Oep::class);
    }

    public function passwordHistories()
    {
        return $this->hasMany(PasswordHistory::class);
    }

    public function createdCandidates()
    {
        return $this->hasMany(Candidate::class, 'created_by');
    }

    // Helper methods
    public function isLocked(): bool
    {
        return $this->locked_until && now()->lt($this->locked_until);
    }

    public function incrementFailedLogins()
    {
        $this->increment('failed_login_attempts');
        
        if ($this->failed_login_attempts >= 5) {
            $this->update(['locked_until' => now()->addMinutes(15)]);
        }
    }

    public function resetFailedLogins()
    {
        $this->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);
    }

    public function needsPasswordChange(): bool
    {
        return $this->must_change_password || is_password_expired($this);
    }

    public function canChangePassword(string $newPassword): bool
    {
        // Check password history
        $histories = $this->passwordHistories()
            ->latest()
            ->limit(config('btevta.password.history_count', 5))
            ->get();

        foreach ($histories as $history) {
            if (Hash::check($newPassword, $history->password)) {
                return false; // Password was used before
            }
        }

        return true;
    }

    public function updatePassword(string $newPassword)
    {
        // Save to history
        $this->passwordHistories()->create([
            'password' => Hash::make($newPassword),
        ]);

        // Update password
        $this->update([
            'password' => Hash::make($newPassword),
            'password_changed_at' => now(),
            'must_change_password' => false,
        ]);
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
```

**File:** `app/Models/PasswordHistory.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordHistory extends Model
{
    protected $fillable = ['user_id', 'password'];

    protected $hidden = ['password'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

---

## SECTION 2.2: All Remaining Models

**File:** `app/Models/Campus.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasActivityLog;
use App\Traits\HasUuid;

class Campus extends Model
{
    use HasFactory, SoftDeletes, HasActivityLog, HasUuid;

    protected $fillable = [
        'uuid',
        'name',
        'code',
        'district',
        'address',
        'phone',
        'email',
        'capacity',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'capacity' => 'integer',
    ];

    // Relationships
    public function candidates()
    {
        return $this->hasMany(Candidate::class);
    }

    public function batches()
    {
        return $this->hasMany(Batch::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
```

**File:** `app/Models/Trade.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasActivityLog;
use App\Traits\HasUuid;

class Trade extends Model
{
    use HasFactory, SoftDeletes, HasActivityLog, HasUuid;

    protected $fillable = [
        'uuid',
        'name',
        'code',
        'description',
        'duration_weeks',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'duration_weeks' => 'integer',
    ];

    // Relationships
    public function candidates()
    {
        return $this->hasMany(Candidate::class);
    }

    public function batches()
    {
        return $this->hasMany(Batch::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
```

**File:** `app/Models/Oep.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasActivityLog;
use App\Traits\HasUuid;

class Oep extends Model
{
    use HasFactory, SoftDeletes, HasActivityLog, HasUuid;

    protected $fillable = [
        'uuid',
        'name',
        'license_number',
        'contact_person',
        'phone',
        'email',
        'address',
        'capacity',
        'is_active',
        'license_expiry',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'capacity' => 'integer',
        'license_expiry' => 'date',
    ];

    // Relationships
    public function candidates()
    {
        return $this->hasMany(Candidate::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function visaProcesses()
    {
        return $this->hasMany(VisaProcess::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLicenseValid($query)
    {
        return $query->where('license_expiry', '>', now());
    }
}
```

**File:** `app/Models/Batch.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasActivityLog;
use App\Traits\HasUuid;

class Batch extends Model
{
    use HasFactory, SoftDeletes, HasActivityLog, HasUuid;

    protected $fillable = [
        'uuid',
        'batch_number',
        'campus_id',
        'trade_id',
        'start_date',
        'end_date',
        'capacity',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'capacity' => 'integer',
    ];

    // Relationships
    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function trade()
    {
        return $this->belongsTo(Trade::class);
    }

    public function candidates()
    {
        return $this->hasMany(Candidate::class);
    }

    public function trainings()
    {
        return $this->hasMany(Training::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>', now());
    }

    // Helper methods
    public function enrolledCount()
    {
        return $this->candidates()->count();
    }

    public function availableSeats()
    {
        return $this->capacity - $this->enrolledCount();
    }
}
```

**File:** `app/Models/Candidate.php` (COMPLETE VERSION)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasActivityLog;
use App\Traits\HasUuid;
use App\Traits\Searchable;
use App\Enums\CandidateStatus;

class Candidate extends Model
{
    use HasFactory, SoftDeletes, HasActivityLog, HasUuid, Searchable;

    protected $fillable = [
        'uuid',
        'name',
        'cnic',
        'phone',
        'email',
        'father_name',
        'gender',
        'date_of_birth',
        'district',
        'address',
        'trade_id',
        'campus_id',
        'batch_id',
        'oep_id',
        'status',
        'batch_number',
        'education_level',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    protected $searchable = [
        'name',
        'cnic',
        'phone',
        'email',
        'district',
        'batch_number',
    ];

    // Relationships
    public function trade()
    {
        return $this->belongsTo(Trade::class);
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function oep()
    {
        return $this->belongsTo(Oep::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function screenings()
    {
        return $this->hasMany(Screening::class);
    }

    public function registration()
    {
        return $this->hasOne(Registration::class);
    }

    public function training()
    {
        return $this->hasOne(Training::class);
    }

    public function visaProcess()
    {
        return $this->hasOne(VisaProcess::class);
    }

    public function departure()
    {
        return $this->hasOne(Departure::class);
    }

    public function complaints()
    {
        return $this->hasMany(Complaint::class);
    }

    public function documents()
    {
        return $this->morphMany(DocumentArchive::class, 'documentable');
    }

    public function remittances()
    {
        return $this->hasMany(Remittance::class);
    }

    // Scopes
    public function scopeFilter($query, array $filters)
    {
        return $query
            ->when($filters['status'] ?? null, fn($q, $status) => $q->where('status', $status))
            ->when($filters['district'] ?? null, fn($q, $district) => $q->where('district', $district))
            ->when($filters['trade_id'] ?? null, fn($q, $trade) => $q->where('trade_id', $trade))
            ->when($filters['campus_id'] ?? null, fn($q, $campus) => $q->where('campus_id', $campus))
            ->when($filters['oep_id'] ?? null, fn($q, $oep) => $q->where('oep_id', $oep))
            ->when($filters['search'] ?? null, fn($q, $search) => $q->search($search));
    }

    // Helper methods
    public function canTransitionTo(string $newStatus): bool
    {
        $currentStatus = CandidateStatus::from($this->status);
        $targetStatus = CandidateStatus::from($newStatus);
        
        return $currentStatus->canTransitionTo($targetStatus);
    }

    public function updateStatus(string $newStatus)
    {
        if ($this->canTransitionTo($newStatus)) {
            $this->update(['status' => $newStatus]);
            return true;
        }
        return false;
    }
}
```

Continue with remaining models in next file...

**Tasks:**
- [ ] Create User.php (enhanced)
- [ ] Create PasswordHistory.php
- [ ] Create Campus.php
- [ ] Create Trade.php
- [ ] Create Oep.php
- [ ] Create Batch.php
- [ ] Create Candidate.php (complete)

Run: `php artisan optimize:clear` after creating models.

---

## SECTION 2.3: Seeders

**File:** `database/seeders/RoleSeeder.php`

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create all permissions
        $permissions = [
            // Candidates
            'view_candidates',
            'create_candidates',
            'edit_candidates',
            'delete_candidates',
            'import_candidates',
            'export_candidates',
            'bulk_candidates',
            
            // Screening
            'view_screening',
            'manage_screening',
            'log_screening_calls',
            
            // Registration
            'view_registration',
            'manage_registration',
            'complete_registration',
            
            // Training
            'view_training',
            'manage_training',
            'mark_attendance',
            'record_assessment',
            'issue_certificates',
            
            // Visa
            'view_visa',
            'manage_visa',
            'update_visa_stages',
            
            // Departure
            'view_departure',
            'manage_departure',
            'record_departure',
            'track_post_arrival',
            
            // Correspondence
            'view_correspondence',
            'manage_correspondence',
            'create_correspondence',
            
            // Complaints
            'view_complaints',
            'manage_complaints',
            'assign_complaints',
            'resolve_complaints',
            
            // Documents
            'view_documents',
            'upload_documents',
            'verify_documents',
            'delete_documents',
            'download_documents',
            
            // Remittances
            'view_remittances',
            'record_remittances',
            'verify_remittances',
            
            // Reports
            'view_reports',
            'generate_reports',
            'export_reports',
            
            // Admin
            'manage_users',
            'manage_roles',
            'manage_campuses',
            'manage_trades',
            'manage_oeps',
            'manage_batches',
            'view_activity_log',
            'system_settings',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Super Admin - all permissions
        $superAdmin = Role::create(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Admin - all permissions
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo(Permission::all());

        // Project Director - most permissions except user/role management
        $director = Role::create(['name' => 'project_director']);
        $director->givePermissionTo(Permission::whereNotIn('name', [
            'manage_users',
            'manage_roles',
            'system_settings',
        ])->get());

        // Campus Admin - campus-specific operations
        $campusAdmin = Role::create(['name' => 'campus_admin']);
        $campusAdmin->givePermissionTo([
            'view_candidates', 'create_candidates', 'edit_candidates', 'import_candidates', 'export_candidates',
            'view_screening', 'manage_screening', 'log_screening_calls',
            'view_registration', 'manage_registration', 'complete_registration',
            'view_training', 'manage_training', 'mark_attendance', 'record_assessment', 'issue_certificates',
            'view_visa',
            'view_departure',
            'view_correspondence', 'create_correspondence',
            'view_complaints', 'manage_complaints', 'assign_complaints', 'resolve_complaints',
            'view_documents', 'upload_documents', 'verify_documents', 'download_documents',
            'view_remittances',
            'view_reports', 'generate_reports', 'export_reports',
            'manage_batches',
        ]);

        // Instructor - training-focused
        $instructor = Role::create(['name' => 'instructor']);
        $instructor->givePermissionTo([
            'view_candidates',
            'view_training', 'mark_attendance', 'record_assessment',
            'view_documents', 'download_documents',
        ]);

        // OEP - visa and departure focused
        $oep = Role::create(['name' => 'oep']);
        $oep->givePermissionTo([
            'view_candidates',
            'view_registration',
            'view_visa', 'manage_visa', 'update_visa_stages',
            'view_departure', 'manage_departure', 'record_departure', 'track_post_arrival',
            'view_documents', 'upload_documents', 'download_documents',
        ]);

        // Visa Partner
        $visaPartner = Role::create(['name' => 'visa_partner']);
        $visaPartner->givePermissionTo([
            'view_candidates',
            'view_visa', 'manage_visa', 'update_visa_stages',
            'view_documents', 'upload_documents', 'download_documents',
        ]);

        // Viewer - read-only
        $viewer = Role::create(['name' => 'viewer']);
        $viewer->givePermissionTo([
            'view_candidates',
            'view_screening',
            'view_registration',
            'view_training',
            'view_visa',
            'view_departure',
            'view_correspondence',
            'view_complaints',
            'view_documents', 'download_documents',
            'view_remittances',
            'view_reports',
        ]);

        // Staff - basic operations
        $staff = Role::create(['name' => 'staff']);
        $staff->givePermissionTo([
            'view_candidates', 'create_candidates',
            'view_screening', 'log_screening_calls',
            'view_correspondence', 'create_correspondence',
            'view_complaints',
            'view_documents', 'upload_documents', 'download_documents',
        ]);

        $this->command->info('✅ Roles and permissions created successfully.');
    }
}
```

Continuing in next response...
