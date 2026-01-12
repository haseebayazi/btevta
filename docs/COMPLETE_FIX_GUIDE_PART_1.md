# BTEVTA WASL - COMPLETE SYSTEM REBUILD GUIDE
## AI-Executable Implementation Plan

**Version:** 2.0  
**Status:** Complete Rebuild Specification  
**Target:** Production-Ready System  
**Estimated Time:** 12 Weeks (Full Implementation)  

---

## 📋 TABLE OF CONTENTS

- [Phase 0: Environment Setup & Foundation](#phase-0-environment-setup--foundation)
- [Phase 1: Database Architecture (Complete)](#phase-1-database-architecture-complete)
- [Phase 2: Authentication & Authorization](#phase-2-authentication--authorization)
- [Phase 3: Module 1 - Candidate Listing](#phase-3-module-1---candidate-listing)
- [Phase 4: Module 2 - Candidate Screening](#phase-4-module-2---candidate-screening)
- [Phase 5: Module 3 - Registration at Campus](#phase-5-module-3---registration-at-campus)
- [Phase 6: Module 4 - Training Management](#phase-6-module-4---training-management)
- [Phase 7: Module 5 - Visa Processing](#phase-7-module-5---visa-processing)
- [Phase 8: Module 6 - Departure & Post-Deployment](#phase-8-module-6---departure--post-deployment)
- [Phase 9: Module 7 - Correspondence](#phase-9-module-7---correspondence)
- [Phase 10: Module 8 - Complaints & Grievance](#phase-10-module-8---complaints--grievance)
- [Phase 11: Module 9 - Document Archive](#phase-11-module-9---document-archive)
- [Phase 12: Module 10 - Remittance Management](#phase-12-module-10---remittance-management)
- [Phase 13: Advanced Features](#phase-13-advanced-features)
- [Phase 14: Testing & Quality Assurance](#phase-14-testing--quality-assurance)
- [Phase 15: Production Deployment](#phase-15-production-deployment)

---

## 🎯 AI EXECUTION INSTRUCTIONS

**For AI Models (Claude, Copilot, etc.):**

1. **Follow phases sequentially** - Do NOT skip ahead
2. **Complete ALL checkboxes** in each phase before proceeding
3. **Run verification commands** after each section
4. **If a verification fails**, STOP and fix before continuing
5. **Create complete files**, not code snippets
6. **Use exact file paths** as specified
7. **Run tests** after each module
8. **Commit changes** after each completed phase

**Verification Protocol:**
- ✅ Green checkmark = Task completed and verified
- ⚠️ Yellow warning = Task needs review
- ❌ Red X = Task failed verification

---

# PHASE 0: Environment Setup & Foundation

**Duration:** 1-2 Days  
**Goal:** Clean Laravel installation with proper tooling  

## 0.1 Prerequisites Check

**File:** `PRE_FLIGHT_CHECK.sh`

```bash
#!/bin/bash
# Location: project_root/PRE_FLIGHT_CHECK.sh

echo "=== BTEVTA WASL - Pre-Flight System Check ==="
echo ""

# Check PHP version
echo "Checking PHP..."
PHP_VERSION=$(php -v | head -n 1 | awk '{print $2}' | cut -d '.' -f 1,2)
if (( $(echo "$PHP_VERSION >= 8.2" | bc -l) )); then
    echo "✅ PHP $PHP_VERSION - OK"
else
    echo "❌ PHP $PHP_VERSION - FAILED (Need 8.2+)"
    exit 1
fi

# Check required extensions
echo ""
echo "Checking PHP Extensions..."
REQUIRED_EXTS=("pdo" "pdo_mysql" "mbstring" "xml" "gd" "bcmath" "fileinfo" "openssl" "tokenizer" "ctype")
for ext in "${REQUIRED_EXTS[@]}"; do
    if php -m | grep -q "^$ext$"; then
        echo "✅ $ext"
    else
        echo "❌ $ext - MISSING"
        exit 1
    fi
done

# Check Composer
echo ""
echo "Checking Composer..."
if command -v composer &> /dev/null; then
    COMPOSER_VERSION=$(composer --version | awk '{print $3}')
    echo "✅ Composer $COMPOSER_VERSION - OK"
else
    echo "❌ Composer - NOT FOUND"
    exit 1
fi

# Check Node.js
echo ""
echo "Checking Node.js..."
if command -v node &> /dev/null; then
    NODE_VERSION=$(node -v)
    echo "✅ Node.js $NODE_VERSION - OK"
else
    echo "❌ Node.js - NOT FOUND"
    exit 1
fi

# Check MySQL
echo ""
echo "Checking MySQL..."
if command -v mysql &> /dev/null; then
    MYSQL_VERSION=$(mysql --version | awk '{print $3}' | cut -d '.' -f 1,2)
    echo "✅ MySQL $MYSQL_VERSION - OK"
else
    echo "❌ MySQL - NOT FOUND"
    exit 1
fi

echo ""
echo "=== ✅ ALL CHECKS PASSED ==="
echo "You are ready to begin installation."
```

**Tasks:**
- [ ] Create `PRE_FLIGHT_CHECK.sh` in project root
- [ ] Make executable: `chmod +x PRE_FLIGHT_CHECK.sh`
- [ ] Run: `./PRE_FLIGHT_CHECK.sh`
- [ ] Verify all checks pass

---

## 0.2 Fresh Laravel Installation

**Location:** Choose your project directory

```bash
#!/bin/bash
# Installation script

# Navigate to your development directory
cd ~/Projects  # Or wherever you keep projects

# Create new Laravel 11 project
composer create-project laravel/laravel btevta-wasl-v2

# Navigate into project
cd btevta-wasl-v2

# Install Breeze for authentication
composer require laravel/breeze --dev

# Install Blade stack (no JS framework complexity)
php artisan breeze:install blade

# Install additional required packages
composer require spatie/laravel-activitylog
composer require spatie/laravel-permission
composer require maatwebsite/excel
composer require barryvdh/laravel-dompdf
composer require intervention/image
composer require propaganistas/laravel-phone

# Install development tools
composer require barryvdh/laravel-debugbar --dev
composer require laravel/telescope --dev

# Install and build frontend assets
npm install
npm run build

# Set proper permissions
chmod -R 775 storage bootstrap/cache
```

**Tasks:**
- [ ] Run installation script above
- [ ] Verify Laravel installation: `php artisan --version` shows `Laravel Framework 11.x`
- [ ] Verify Breeze installed: `ls resources/views/auth` shows login.blade.php
- [ ] Verify packages installed: `composer show` lists all packages

---

## 0.3 Environment Configuration

**File:** `.env`

```env
# Application
APP_NAME="BTEVTA WASL"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=Asia/Karachi
APP_URL=http://localhost

# Logging
LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=btevta_wasl_v2
DB_USERNAME=root
DB_PASSWORD=

# Session & Cache
BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

# Mail Configuration
MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@btevta.gov.pk"
MAIL_FROM_NAME="${APP_NAME}"

# BTEVTA Specific Settings
BTEVTA_TIMEZONE=Asia/Karachi
BTEVTA_PHONE_CODE=92
BTEVTA_CURRENCY=PKR
BTEVTA_UPLOAD_MAX_SIZE=20971520
BTEVTA_ALLOWED_PHOTO_TYPES=jpg,jpeg,png
BTEVTA_ALLOWED_DOCUMENT_TYPES=pdf,doc,docx,jpg,jpeg,png

# Password Policy
PASSWORD_MIN_LENGTH=12
PASSWORD_REQUIRE_UPPERCASE=true
PASSWORD_REQUIRE_LOWERCASE=true
PASSWORD_REQUIRE_NUMBER=true
PASSWORD_REQUIRE_SPECIAL=true
PASSWORD_HISTORY_COUNT=5
PASSWORD_EXPIRY_DAYS=90

# File Upload Limits
UPLOAD_MAX_FILESIZE=20M
POST_MAX_SIZE=25M

# Pagination
PAGINATION_PER_PAGE=25
```

**Tasks:**
- [ ] Copy `.env.example` to `.env`
- [ ] Update `.env` with content above
- [ ] Generate app key: `php artisan key:generate`
- [ ] Verify `.env` file has `APP_KEY` populated

---

## 0.4 Create Database

**File:** `CREATE_DATABASE.sh`

```bash
#!/bin/bash
# Location: project_root/CREATE_DATABASE.sh

echo "Creating BTEVTA WASL Database..."

# Read database credentials from .env
DB_NAME=$(grep ^DB_DATABASE= .env | cut -d '=' -f2)
DB_USER=$(grep ^DB_USERNAME= .env | cut -d '=' -f2)
DB_PASS=$(grep ^DB_PASSWORD= .env | cut -d '=' -f2)

# Create database
mysql -u $DB_USER -p$DB_PASS -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

if [ $? -eq 0 ]; then
    echo "✅ Database '$DB_NAME' created successfully"
else
    echo "❌ Failed to create database"
    exit 1
fi

# Verify database exists
mysql -u $DB_USER -p$DB_PASS -e "SHOW DATABASES LIKE '$DB_NAME';" | grep -q $DB_NAME

if [ $? -eq 0 ]; then
    echo "✅ Database verified"
else
    echo "❌ Database verification failed"
    exit 1
fi
```

**Tasks:**
- [ ] Create `CREATE_DATABASE.sh`
- [ ] Make executable: `chmod +x CREATE_DATABASE.sh`
- [ ] Run: `./CREATE_DATABASE.sh`
- [ ] Verify database created: `mysql -u root -p -e "SHOW DATABASES;"`

---

## 0.5 Initialize Git Repository

```bash
#!/bin/bash
# Git initialization

# Initialize git if not already done
if [ ! -d .git ]; then
    git init
    echo "✅ Git initialized"
fi

# Create comprehensive .gitignore
cat > .gitignore << 'EOF'
# Laravel
/node_modules
/public/build
/public/hot
/public/storage
/storage/*.key
/vendor
.env
.env.backup
.env.production
.phpunit.result.cache
Homestead.json
Homestead.yaml
auth.json
npm-debug.log
yarn-error.log
/.fleet
/.idea
/.vscode

# BTEVTA Specific
/storage/logs/*.log
/storage/app/private/*
!/storage/app/private/.gitkeep
/storage/framework/cache/*
!/storage/framework/cache/.gitkeep
/storage/framework/sessions/*
!/storage/framework/sessions/.gitkeep
/storage/framework/views/*
!/storage/framework/views/.gitkeep

# OS Files
.DS_Store
Thumbs.db

# IDE
*.swp
*.swo
*~

# Testing
coverage/
.phpunit.cache/

# Deployment
deployment_credentials.txt
seeder-credentials.log
EOF

# Initial commit
git add .
git commit -m "feat: initial Laravel 11 installation with Breeze"

echo "✅ Git repository initialized"
```

**Tasks:**
- [ ] Run git initialization script
- [ ] Verify: `git log` shows initial commit
- [ ] Verify: `git status` is clean

---

## 0.6 Directory Structure Setup

**File:** `SETUP_DIRECTORIES.sh`

```bash
#!/bin/bash
# Location: project_root/SETUP_DIRECTORIES.sh

echo "Setting up BTEVTA WASL directory structure..."

# Create application directories
mkdir -p app/Enums
mkdir -p app/Services
mkdir -p app/Traits
mkdir -p app/Rules
mkdir -p app/Observers
mkdir -p app/Events
mkdir -p app/Listeners
mkdir -p app/Jobs
mkdir -p app/Http/Resources
mkdir -p app/Http/Requests

# Create storage directories
mkdir -p storage/app/private/documents
mkdir -p storage/app/private/photos
mkdir -p storage/app/private/certificates
mkdir -p storage/app/private/reports
mkdir -p storage/app/public/uploads

# Create test directories
mkdir -p tests/Unit/Models
mkdir -p tests/Unit/Services
mkdir -p tests/Feature/Auth
mkdir -p tests/Feature/Candidates
mkdir -p tests/Feature/Screening
mkdir -p tests/Feature/Registration
mkdir -p tests/Feature/Training
mkdir -p tests/Feature/Visa
mkdir -p tests/Feature/Departure
mkdir -p tests/Feature/Correspondence
mkdir -p tests/Feature/Complaints
mkdir -p tests/Feature/Documents
mkdir -p tests/Feature/Remittances

# Create documentation directory
mkdir -p docs/api
mkdir -p docs/guides
mkdir -p docs/architecture

# Create .gitkeep files in empty directories
find storage/app/private -type d -exec touch {}/.gitkeep \;
find storage/framework -type d -exec touch {}/.gitkeep \;

echo "✅ Directory structure created"
```

**Tasks:**
- [ ] Create `SETUP_DIRECTORIES.sh`
- [ ] Make executable: `chmod +x SETUP_DIRECTORIES.sh`
- [ ] Run: `./SETUP_DIRECTORIES.sh`
- [ ] Verify: `tree -L 3 app/` shows all new directories
- [ ] Verify: `tree -L 3 storage/app/private` shows all document directories

---

## 0.7 Configuration Files Setup

### File 1: `config/btevta.php`

**Location:** `config/btevta.php`

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | BTEVTA Application Settings
    |--------------------------------------------------------------------------
    */

    'timezone' => env('BTEVTA_TIMEZONE', 'Asia/Karachi'),
    'phone_code' => env('BTEVTA_PHONE_CODE', '92'),
    'currency' => env('BTEVTA_CURRENCY', 'PKR'),

    /*
    |--------------------------------------------------------------------------
    | File Upload Settings
    |--------------------------------------------------------------------------
    */

    'upload' => [
        'max_size' => env('BTEVTA_UPLOAD_MAX_SIZE', 20971520), // 20MB
        'allowed_photo_types' => explode(',', env('BTEVTA_ALLOWED_PHOTO_TYPES', 'jpg,jpeg,png')),
        'allowed_document_types' => explode(',', env('BTEVTA_ALLOWED_DOCUMENT_TYPES', 'pdf,doc,docx,jpg,jpeg,png')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Policy
    |--------------------------------------------------------------------------
    */

    'password' => [
        'min_length' => env('PASSWORD_MIN_LENGTH', 12),
        'require_uppercase' => env('PASSWORD_REQUIRE_UPPERCASE', true),
        'require_lowercase' => env('PASSWORD_REQUIRE_LOWERCASE', true),
        'require_number' => env('PASSWORD_REQUIRE_NUMBER', true),
        'require_special' => env('PASSWORD_REQUIRE_SPECIAL', true),
        'history_count' => env('PASSWORD_HISTORY_COUNT', 5),
        'expiry_days' => env('PASSWORD_EXPIRY_DAYS', 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | Candidate Status Workflow
    |--------------------------------------------------------------------------
    */

    'candidate_statuses' => [
        'listed' => 'Listed',
        'screening' => 'Screening',
        'eligible' => 'Eligible',
        'registered' => 'Registered',
        'training' => 'In Training',
        'trained' => 'Training Complete',
        'visa_process' => 'Visa Processing',
        'visa_issued' => 'Visa Issued',
        'ready_to_depart' => 'Ready to Depart',
        'departed' => 'Departed',
        'employed' => 'Employed Abroad',
        'returned' => 'Returned',
        'rejected' => 'Rejected',
        'withdrawn' => 'Withdrawn',
        'blacklisted' => 'Blacklisted',
    ],

    /*
    |--------------------------------------------------------------------------
    | Visa Processing Stages
    |--------------------------------------------------------------------------
    */

    'visa_stages' => [
        'interview' => 'Interview',
        'trade_test' => 'Trade Test',
        'takamol' => 'Takamol Registration',
        'medical' => 'Medical (GAMCA)',
        'e_number' => 'E-Number',
        'biometric' => 'Biometrics (Etimad)',
        'visa_submission' => 'Visa Submission',
        'visa_issued' => 'Visa Issued',
        'ptn' => 'PTN (Protector)',
        'attestation' => 'Attestation',
        'ticket' => 'Ticket Issued',
        'ready' => 'Ready to Depart',
    ],

    /*
    |--------------------------------------------------------------------------
    | Complaint Categories & SLAs
    |--------------------------------------------------------------------------
    */

    'complaint_categories' => [
        'training' => 'Training Related',
        'visa' => 'Visa Processing',
        'salary' => 'Salary Issues',
        'accommodation' => 'Accommodation',
        'conduct' => 'Conduct/Behavior',
        'documentation' => 'Documentation',
        'other' => 'Other',
    ],

    'complaint_priorities' => [
        'low' => ['label' => 'Low', 'sla_hours' => 120], // 5 days
        'medium' => ['label' => 'Medium', 'sla_hours' => 72], // 3 days
        'high' => ['label' => 'High', 'sla_hours' => 48], // 2 days
        'critical' => ['label' => 'Critical', 'sla_hours' => 24], // 1 day
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    'pagination' => [
        'per_page' => env('PAGINATION_PER_PAGE', 25),
        'options' => [10, 25, 50, 100],
    ],

    /*
    |--------------------------------------------------------------------------
    | Districts (Punjab)
    |--------------------------------------------------------------------------
    */

    'districts' => [
        'Lahore', 'Faisalabad', 'Rawalpindi', 'Multan', 'Gujranwala',
        'Sialkot', 'Bahawalpur', 'Sargodha', 'Sheikhupura', 'Jhang',
        'Rahim Yar Khan', 'Gujrat', 'Kasur', 'Sahiwal', 'Okara',
        'Dera Ghazi Khan', 'Mandi Bahauddin', 'Vehari', 'Muzaffargarh', 'Chiniot',
        'Attock', 'Jhelum', 'Chakwal', 'Khushab', 'Mianwali',
        'Bhakkar', 'Layyah', 'Khanewal', 'Pakpattan', 'Narowal',
        'Hafizabad', 'Nankana Sahib', 'Lodhran', 'Rajanpur', 'Toba Tek Singh',
        'Bahawalnagar',
    ],

    /*
    |--------------------------------------------------------------------------
    | Training Modules
    |--------------------------------------------------------------------------
    */

    'training_modules' => [
        'theory' => 'Theory',
        'practical' => 'Practical',
        'soft_skills' => 'Soft Skills',
        'language' => 'Language Training',
        'safety' => 'Safety & Compliance',
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Types
    |--------------------------------------------------------------------------
    */

    'document_types' => [
        'cnic' => 'CNIC',
        'passport' => 'Passport',
        'educational' => 'Educational Certificate',
        'medical' => 'Medical Certificate',
        'police_clearance' => 'Police Clearance',
        'photo' => 'Photograph',
        'visa' => 'Visa',
        'contract' => 'Employment Contract',
        'ticket' => 'Travel Ticket',
        'other' => 'Other',
    ],
];
```

**Tasks:**
- [ ] Create `config/btevta.php` with content above
- [ ] Verify: `php artisan config:cache` runs without errors
- [ ] Verify: `php artisan config:clear` runs without errors
- [ ] Verify: Can access config in tinker: `php artisan tinker` then `config('btevta.districts')`

---

### File 2: `config/activitylog.php` Configuration

```bash
# Publish Spatie Activity Log config
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-config"
```

**File:** `config/activitylog.php` (modify after publishing)

```php
<?php

return [
    'enabled' => env('ACTIVITY_LOGGER_ENABLED', true),

    'delete_records_older_than_days' => 90,

    'default_log_name' => 'default',

    'default_auth_driver' => null,

    'subject_returns_soft_deleted_models' => false,

    'activity_model' => \Spatie\Activitylog\Models\Activity::class,

    'table_name' => 'activity_log',

    'database_connection' => env('ACTIVITY_LOGGER_DB_CONNECTION'),
];
```

**Tasks:**
- [ ] Publish activitylog config: `php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider"`
- [ ] Modify `config/activitylog.php` as shown above
- [ ] Verify: Config file exists at `config/activitylog.php`

---

### File 3: `config/permission.php` Configuration

```bash
# Publish Spatie Permission config
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

**Tasks:**
- [ ] Publish permission config: `php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"`
- [ ] Verify: Config file exists at `config/permission.php`
- [ ] Verify: Migrations created in `database/migrations/`

---

## 0.8 Base Artisan Commands

**File:** `app/Console/Commands/SystemCheckCommand.php`

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SystemCheckCommand extends Command
{
    protected $signature = 'system:check';
    protected $description = 'Run comprehensive system health check';

    public function handle()
    {
        $this->info('=== BTEVTA WASL System Health Check ===');
        $this->newLine();

        // Check database connection
        $this->checkDatabase();

        // Check storage directories
        $this->checkStorage();

        // Check configuration
        $this->checkConfiguration();

        // Check permissions
        $this->checkPermissions();

        $this->newLine();
        $this->info('=== Health Check Complete ===');
    }

    private function checkDatabase()
    {
        $this->info('Checking database connection...');

        try {
            DB::connection()->getPdo();
            $this->line('✅ Database connection successful');

            $tables = DB::select('SHOW TABLES');
            $this->line("✅ Database has " . count($tables) . " tables");
        } catch (\Exception $e) {
            $this->error('❌ Database connection failed: ' . $e->getMessage());
        }
    }

    private function checkStorage()
    {
        $this->info('Checking storage directories...');

        $directories = [
            'private/documents',
            'private/photos',
            'private/certificates',
            'private/reports',
            'public/uploads',
        ];

        foreach ($directories as $dir) {
            if (Storage::exists($dir)) {
                $this->line("✅ $dir exists");
            } else {
                $this->error("❌ $dir missing");
            }
        }
    }

    private function checkConfiguration()
    {
        $this->info('Checking configuration...');

        $configs = [
            'app.name',
            'database.default',
            'btevta.timezone',
            'btevta.districts',
        ];

        foreach ($configs as $config) {
            $value = config($config);
            if ($value) {
                $this->line("✅ $config configured");
            } else {
                $this->error("❌ $config not configured");
            }
        }
    }

    private function checkPermissions()
    {
        $this->info('Checking file permissions...');

        $paths = [
            storage_path(),
            storage_path('logs'),
            storage_path('framework'),
            storage_path('app'),
        ];

        foreach ($paths as $path) {
            if (is_writable($path)) {
                $this->line("✅ $path is writable");
            } else {
                $this->error("❌ $path is not writable");
            }
        }
    }
}
```

**Tasks:**
- [ ] Create `app/Console/Commands/SystemCheckCommand.php` with content above
- [ ] Run: `php artisan system:check`
- [ ] Verify: All checks pass (✅)
- [ ] If any checks fail (❌), fix before proceeding

---

## 0.9 Helper Functions

**File:** `app/Helpers/helpers.php`

```php
<?php

if (! function_exists('format_cnic')) {
    function format_cnic($cnic)
    {
        // Remove all non-numeric characters
        $cnic = preg_replace('/[^0-9]/', '', $cnic);

        // Format as XXXXX-XXXXXXX-X
        if (strlen($cnic) === 13) {
            return substr($cnic, 0, 5) . '-' . substr($cnic, 5, 7) . '-' . substr($cnic, 12, 1);
        }

        return $cnic;
    }
}

if (! function_exists('format_phone')) {
    function format_phone($phone, $countryCode = '92')
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Remove leading zeros
        $phone = ltrim($phone, '0');

        // Add country code if not present
        if (!str_starts_with($phone, $countryCode)) {
            $phone = $countryCode . $phone;
        }

        return '+' . $phone;
    }
}

if (! function_exists('get_user_role_name')) {
    function get_user_role_name($user)
    {
        return $user->roles->first()?->name ?? 'No Role';
    }
}

if (! function_exists('can_user_access_candidate')) {
    function can_user_access_candidate($user, $candidate)
    {
        // Super Admin and Admin can access all
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        // Campus Admin can access candidates in their campus
        if ($user->hasRole('campus_admin')) {
            return $user->campus_id === $candidate->campus_id;
        }

        // OEP can access assigned candidates
        if ($user->hasRole('oep')) {
            return $candidate->oep_id === $user->oep_id;
        }

        return false;
    }
}

if (! function_exists('generate_batch_number')) {
    function generate_batch_number($trade, $district, $year = null)
    {
        $year = $year ?? date('Y');
        $tradeCode = strtoupper(substr($trade, 0, 3));
        $districtCode = strtoupper(substr($district, 0, 3));
        $randomPart = strtoupper(substr(md5(microtime()), 0, 4));

        return "{$tradeCode}-{$districtCode}-{$year}-{$randomPart}";
    }
}

if (! function_exists('mask_cnic')) {
    function mask_cnic($cnic)
    {
        // Format: XXXXX-XXXXXXX-X becomes XXXXX-XXX****-X
        $formatted = format_cnic($cnic);
        return substr($formatted, 0, 9) . '****' . substr($formatted, -2);
    }
}

if (! function_exists('get_days_until')) {
    function get_days_until($date)
    {
        $target = \Carbon\Carbon::parse($date);
        $now = \Carbon\Carbon::now();

        return $now->diffInDays($target, false);
    }
}

if (! function_exists('is_password_expired')) {
    function is_password_expired($user)
    {
        if (!$user->password_changed_at) {
            return true;
        }

        $expiryDays = config('btevta.password.expiry_days', 90);
        $passwordAge = now()->diffInDays($user->password_changed_at);

        return $passwordAge >= $expiryDays;
    }
}
```

**File:** `composer.json` (add autoload section)

```json
{
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Database\\Factories\\": "database/factories/",
            "Database\\Seeders\\": "database/seeders/"
        },
        "files": [
            "app/Helpers/helpers.php"
        ]
    }
}
```

**Tasks:**
- [ ] Create `app/Helpers/helpers.php` with content above
- [ ] Update `composer.json` to autoload helpers
- [ ] Run: `composer dump-autoload`
- [ ] Test in tinker: `php artisan tinker` then `format_cnic('1234512345671')`
- [ ] Verify: Returns formatted CNIC "12345-1234567-1"

---

## 0.10 Base Traits

**File:** `app/Traits/HasActivityLog.php`

```php
<?php

namespace App\Traits;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

trait HasActivityLog
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        $modelName = class_basename($this);
        return ucfirst($eventName) . " {$modelName}";
    }
}
```

**File:** `app/Traits/HasUuid.php`

```php
<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasUuid
{
    protected static function bootHasUuid()
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
```

**File:** `app/Traits/Searchable.php`

```php
<?php

namespace App\Traits;

trait Searchable
{
    public function scopeSearch($query, $term)
    {
        if (empty($term)) {
            return $query;
        }

        $searchableFields = $this->searchable ?? [];

        return $query->where(function ($q) use ($term, $searchableFields) {
            foreach ($searchableFields as $field) {
                $q->orWhere($field, 'LIKE', "%{$term}%");
            }
        });
    }
}
```

**Tasks:**
- [ ] Create `app/Traits/HasActivityLog.php`
- [ ] Create `app/Traits/HasUuid.php`
- [ ] Create `app/Traits/Searchable.php`
- [ ] Verify: Files exist and have no syntax errors
- [ ] Run: `php artisan optimize:clear`

---

## 0.11 Verification & Commit

**Verification Checklist:**

```bash
#!/bin/bash
# PHASE 0 VERIFICATION

echo "=== Phase 0 Verification ==="

# 1. Check Laravel installation
php artisan --version || exit 1

# 2. Check database connection
php artisan db:show || exit 1

# 3. Check all directories exist
[ -d "app/Enums" ] || exit 1
[ -d "app/Services" ] || exit 1
[ -d "storage/app/private/documents" ] || exit 1

# 4. Check config files
[ -f "config/btevta.php" ] || exit 1

# 5. Check helpers loaded
php -r "require 'vendor/autoload.php'; format_cnic('1234512345671');" || exit 1

# 6. Run system check
php artisan system:check || exit 1

echo "✅ Phase 0 Verification Complete"
```

**Tasks:**
- [ ] Run verification script above
- [ ] All checks pass
- [ ] Commit changes: `git add . && git commit -m "feat: Phase 0 - Environment setup complete"`
- [ ] Create tag: `git tag v0.1-foundation`

---

## ✅ Phase 0 Complete

**What We Have:**
- ✅ Clean Laravel 11 installation
- ✅ Database created and connected
- ✅ All required packages installed
- ✅ Directory structure set up
- ✅ Configuration files created
- ✅ Helper functions available
- ✅ Base traits created
- ✅ System health check command working

**Next Phase:** Phase 1 - Database Architecture (Complete)

---

**IMPORTANT:** Do NOT proceed to Phase 1 until ALL checkboxes in Phase 0 are ✅ and verification script passes.

