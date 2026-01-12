# BTEVTA WASL - COMPLETE EXECUTION ROADMAP
## All Phases Summary & Implementation Order

**Version:** 2.0  
**Document Type:** Executive Implementation Summary  
**Purpose:** Reference guide for complete system implementation  

---

## 📊 IMPLEMENTATION STATUS DASHBOARD

| Phase | Name | Files | Duration | Dependencies | Guide Status |
|-------|------|-------|----------|--------------|--------------|
| 0 | Foundation & Setup | 15+ | 1-2 days | None | ✅ Complete |
| 1 | Database Architecture | 27 | 2-3 days | Phase 0 | ✅ Complete |
| 2 | Auth & Authorization | 25+ | 2-3 days | Phase 0, 1 | 📝 Below |
| 3 | Candidate Listing | 20+ | 3-4 days | Phase 0-2 | 📝 Below |
| 4 | Screening Module | 15+ | 2-3 days | Phase 3 | 📝 Below |
| 5 | Registration Module | 18+ | 2-3 days | Phase 3,4 | 📝 Below |
| 6 | Training Module | 25+ | 3-4 days | Phase 3-5 | 📝 Below |
| 7 | Visa Processing | 22+ | 3-4 days | Phase 3-5 | 📝 Below |
| 8 | Departure Module | 18+ | 2-3 days | Phase 3-7 | 📝 Below |
| 9 | Correspondence | 12+ | 2 days | Phase 2 | 📝 Below |
| 10 | Complaints | 15+ | 2-3 days | Phase 2 | 📝 Below |
| 11 | Document Archive | 15+ | 2-3 days | Phase 3-8 | 📝 Below |
| 12 | Remittance | 18+ | 2-3 days | Phase 3,8 | 📝 Below |
| 13 | Advanced Features | 20+ | 4-5 days | All modules | 📝 Below |
| 14 | Testing & QA | 40+ tests | 5-7 days | All modules | 📝 Below |
| 15 | Production Deploy | Config | 2-3 days | Phase 14 pass | 📝 Below |

**Total Estimated Time:** 40-50 days (8-10 weeks)  
**Total Files to Create:** 300+ files

---

## PHASE 2: AUTHENTICATION & AUTHORIZATION

**Duration:** 2-3 Days  
**Prerequisites:** Phase 0, 1 Complete  

### 2.1 Models Creation

**File:** `app/Models/User.php` (Enhanced)

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

    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
```

**Create All Other Models** (18 total):

```bash
# Create models with commands
php artisan make:model Campus
php artisan make:model Trade
php artisan make:model Oep
php artisan make:model Batch
php artisan make:model Candidate
php artisan make:model Screening
php artisan make:model Registration
php artisan make:model Training
php artisan make:model TrainingAttendance
php artisan make:model TrainingAssessment
php artisan make:model VisaProcess
php artisan make:model VisaStage
php artisan make:model Departure
php artisan make:model Correspondence
php artisan make:model Complaint
php artisan make:model DocumentArchive
php artisan make:model Remittance
php artisan make:model PasswordHistory
```

### 2.2 Role & Permission Seeder

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
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'view_candidates',
            'create_candidates',
            'edit_candidates',
            'delete_candidates',
            'import_candidates',
            'export_candidates',
            'view_screening',
            'manage_screening',
            'view_registration',
            'manage_registration',
            'view_training',
            'manage_training',
            'mark_attendance',
            'record_assessment',
            'view_visa',
            'manage_visa',
            'view_departure',
            'manage_departure',
            'view_correspondence',
            'manage_correspondence',
            'view_complaints',
            'manage_complaints',
            'assign_complaints',
            'resolve_complaints',
            'view_documents',
            'upload_documents',
            'verify_documents',
            'delete_documents',
            'view_remittances',
            'record_remittances',
            'view_reports',
            'generate_reports',
            'manage_users',
            'manage_roles',
            'manage_campuses',
            'manage_trades',
            'manage_oeps',
            'view_activity_log',
            'system_settings',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles with permissions
        $superAdmin = Role::create(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());

        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo(Permission::all());

        $campusAdmin = Role::create(['name' => 'campus_admin']);
        $campusAdmin->givePermissionTo([
            'view_candidates', 'create_candidates', 'edit_candidates',
            'view_screening', 'manage_screening',
            'view_registration', 'manage_registration',
            'view_training', 'manage_training', 'mark_attendance', 'record_assessment',
            'view_visa',
            'view_departure',
            'view_correspondence',
            'view_complaints', 'manage_complaints',
            'view_documents', 'upload_documents',
            'view_remittances',
            'view_reports', 'generate_reports',
        ]);

        $instructor = Role::create(['name' => 'instructor']);
        $instructor->givePermissionTo([
            'view_candidates',
            'view_training', 'mark_attendance', 'record_assessment',
            'view_documents',
        ]);

        $oep = Role::create(['name' => 'oep']);
        $oep->givePermissionTo([
            'view_candidates',
            'view_registration',
            'view_visa', 'manage_visa',
            'view_departure', 'manage_departure',
            'view_documents', 'upload_documents',
        ]);

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
            'view_documents',
            'view_remittances',
            'view_reports',
        ]);

        $this->command->info('Roles and permissions created successfully.');
    }
}
```

### 2.3 Seeders for Initial Data

**File:** `database/seeders/CampusSeeder.php`

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Campus;

class CampusSeeder extends Seeder
{
    public function run(): void
    {
        $campuses = [
            ['name' => 'BTEVTA Campus Lahore', 'code' => 'LHR01', 'district' => 'Lahore', 'capacity' => 500],
            ['name' => 'BTEVTA Campus Faisalabad', 'code' => 'FSD01', 'district' => 'Faisalabad', 'capacity' => 300],
            ['name' => 'BTEVTA Campus Rawalpindi', 'code' => 'RWP01', 'district' => 'Rawalpindi', 'capacity' => 400],
            ['name' => 'BTEVTA Campus Multan', 'code' => 'MLT01', 'district' => 'Multan', 'capacity' => 250],
            ['name' => 'BTEVTA Campus Gujranwala', 'code' => 'GJW01', 'district' => 'Gujranwala', 'capacity' => 200],
        ];

        foreach ($campuses as $campus) {
            Campus::create($campus);
        }

        $this->command->info('Campuses created successfully.');
    }
}
```

**File:** `database/seeders/TradeSeeder.php`

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Trade;

class TradeSeeder extends Seeder
{
    public function run(): void
    {
        $trades = [
            ['name' => 'Electrician', 'code' => 'ELEC', 'duration_weeks' => 12],
            ['name' => 'Plumber', 'code' => 'PLUM', 'duration_weeks' => 10],
            ['name' => 'Mason', 'code' => 'MASN', 'duration_weeks' => 10],
            ['name' => 'Carpenter', 'code' => 'CARP', 'duration_weeks' => 12],
            ['name' => 'Welder', 'code' => 'WELD', 'duration_weeks' => 12],
            ['name' => 'HVAC Technician', 'code' => 'HVAC', 'duration_weeks' => 14],
            ['name' => 'Auto Mechanic', 'code' => 'AUTO', 'duration_weeks' => 16],
            ['name' => 'Steel Fixer', 'code' => 'STLF', 'duration_weeks' => 10],
        ];

        foreach ($trades as $trade) {
            Trade::create($trade);
        }

        $this->command->info('Trades created successfully.');
    }
}
```

**File:** `database/seeders/UserSeeder.php`

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Campus;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Super Administrator',
                'email' => 'superadmin@btevta.gov.pk',
                'role' => 'super_admin',
                'campus_id' => null,
            ],
            [
                'name' => 'System Administrator',
                'email' => 'admin@btevta.gov.pk',
                'role' => 'admin',
                'campus_id' => null,
            ],
            [
                'name' => 'Lahore Campus Admin',
                'email' => 'lahore@btevta.gov.pk',
                'role' => 'campus_admin',
                'campus_id' => Campus::where('code', 'LHR01')->first()?->id,
            ],
            [
                'name' => 'Viewer Account',
                'email' => 'viewer@btevta.gov.pk',
                'role' => 'viewer',
                'campus_id' => null,
            ],
        ];

        $credentials = [];

        foreach ($users as $userData) {
            $password = 'Btevta@' . rand(1000, 9999);
            
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make($password),
                'campus_id' => $userData['campus_id'],
                'password_changed_at' => null,
                'must_change_password' => true,
                'is_active' => true,
            ]);

            $user->assignRole($userData['role']);

            $credentials[] = [
                'email' => $userData['email'],
                'password' => $password,
                'role' => $userData['role'],
            ];
        }

        // Log credentials to file
        $logContent = "=== BTEVTA WASL - Initial User Credentials ===\n\n";
        $logContent .= "⚠️  IMPORTANT: Change all passwords after first login!\n";
        $logContent .= "⚠️  Delete this file after noting credentials.\n\n";

        foreach ($credentials as $cred) {
            $logContent .= "Email: {$cred['email']}\n";
            $logContent .= "Password: {$cred['password']}\n";
            $logContent .= "Role: {$cred['role']}\n";
            $logContent .= str_repeat('-', 50) . "\n\n";
        }

        file_put_contents(storage_path('logs/seeder-credentials.log'), $logContent);
        
        $this->command->info('Users created successfully.');
        $this->command->warn('⚠️  Credentials saved to storage/logs/seeder-credentials.log');
        $this->command->warn('⚠️  Delete this file after noting passwords!');
    }
}
```

**File:** `database/seeders/DatabaseSeeder.php`

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            CampusSeeder::class,
            TradeSeeder::class,
            UserSeeder::class,
        ]);
    }
}
```

---

## PHASE 3-12: MODULES IMPLEMENTATION PATTERN

For each module, follow this pattern:

### Step 1: Create Model
### Step 2: Create Policy
### Step 3: Create Controller
### Step 4: Create Form Requests
### Step 5: Create Views
### Step 6: Define Routes
### Step 7: Create Tests
### Step 8: Verify & Commit

---

## QUICK REFERENCE: FILE CREATION COMMANDS

```bash
# Model
php artisan make:model ModelName

# Controller
php artisan make:controller ModuleController --resource

# Policy
php artisan make:policy ModulePolicy --model=Model

# Request
php artisan make:request StoreModuleRequest

# Test
php artisan make:test ModuleTest

# Migration
php artisan make:migration create_table_name_table

# Seeder
php artisan make:seeder TableSeeder

# Factory
php artisan make:factory ModelFactory --model=Model
```

---

## TESTING CHECKLIST

After each module:

```bash
# Run tests
php artisan test

# Check for errors
php artisan route:list
php artisan config:cache
php artisan view:clear

# Verify in browser
php artisan serve
# Navigate to module URL
# Test all CRUD operations
```

---

## DEPLOYMENT PREPARATION

### Production Checklist

```bash
# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Run migrations
php artisan migrate --force

# Seed production data
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=CampusSeeder
php artisan db:seed --class=TradeSeeder

# Create admin user manually (don't use UserSeeder in production)
```

---

## COMPLETE FILE MANIFEST

**Total Files to Create: 300+**

### Configuration & Setup (Phase 0)
- config/btevta.php
- app/Helpers/helpers.php
- app/Traits/* (3 files)
- Setup scripts (3 files)

### Database (Phase 1)
- Migrations (19 files)
- Enums (8 files)

### Models (Phase 1-2)
- app/Models/* (18 files)

### Authentication (Phase 2)
- Seeders (4 files)
- Middleware (2 files)
- Policies (10+ files)

### Controllers (Phases 3-12)
- CandidateController
- ScreeningController
- RegistrationController
- TrainingController
- TrainingAttendanceController
- TrainingAssessmentController
- VisaProcessController
- DepartureController
- CorrespondenceController
- ComplaintController
- DocumentArchiveController
- RemittanceController
- Admin controllers (6 files)

### Views (Phases 3-12)
- Layouts (4 files)
- Candidates (5 files)
- Screening (3 files)
- Registration (3 files)
- Training (4 files)
- Visa (3 files)
- Departure (3 files)
- Correspondence (3 files)
- Complaints (3 files)
- Documents (2 files)
- Remittances (3 files)
- Admin (6 files)

### Tests (Phase 14)
- Unit tests (20+ files)
- Feature tests (30+ files)

---

## EXECUTION ORDER

1. ✅ Phase 0: Foundation
2. ✅ Phase 1: Database
3. 🔄 Phase 2: Authentication (Above)
4. ➡️ Phase 3: Candidates
5. ➡️ Phase 4: Screening
6. ➡️ Phase 5: Registration
7. ➡️ Phase 6: Training
8. ➡️ Phase 7: Visa
9. ➡️ Phase 8: Departure
10. ➡️ Phase 9: Correspondence
11. ➡️ Phase 10: Complaints
12. ➡️ Phase 11: Documents
13. ➡️ Phase 12: Remittances
14. ➡️ Phase 13: Advanced
15. ➡️ Phase 14: Testing
16. ➡️ Phase 15: Deploy

---

## IMPLEMENTATION LOG TEMPLATE

```markdown
# Daily Implementation Log

## Day 1
- Started Phase 2
- Created User model enhancements
- Created RoleSeeder
- Status: 30% complete

## Day 2
- Completed Phase 2
- All seeders working
- Users can login
- Status: Phase 2 ✅
```

---

## EMERGENCY PROCEDURES

### If Migration Fails
```bash
php artisan migrate:rollback
# Fix migration
php artisan migrate
```

### If Tests Fail
```bash
php artisan config:clear
php artisan cache:clear
composer dump-autoload
php artisan test
```

### If Code Not Working
```bash
php artisan optimize:clear
php artisan view:clear
composer dump-autoload
```

---

## SUCCESS METRICS

**Phase Complete When:**
- [ ] All files created
- [ ] All migrations run
- [ ] All tests pass
- [ ] Manual testing succeeds
- [ ] Code committed
- [ ] Tag created

---

**Continue to detailed phase guides for implementation specifics.**
