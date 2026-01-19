# WASL v3 Administrator Guide

**BTEVTA Workforce Abroad Skills & Linkages System**
**Version:** 3.0.0
**Last Updated:** January 19, 2026
**Audience:** System Administrators, IT Staff, Configuration Managers

---

## Table of Contents

1. [Introduction](#introduction)
2. [System Architecture](#system-architecture)
3. [Installation & Configuration](#installation--configuration)
4. [WASL v3 Configuration](#wasl-v3-configuration)
5. [User Management](#user-management)
6. [Master Data Management](#master-data-management)
7. [Batch Management](#batch-management)
8. [Document Management](#document-management)
9. [Queue & Job Management](#queue--job-management)
10. [Security & Permissions](#security--permissions)
11. [Backup & Recovery](#backup--recovery)
12. [Performance Optimization](#performance-optimization)
13. [Monitoring & Logging](#monitoring--logging)
14. [Troubleshooting](#troubleshooting)

---

## Introduction

### Purpose

This guide provides comprehensive instructions for administrators to configure, manage, and maintain the WASL v3 system.

### Administrator Roles

**System Administrator:**
- Full system access
- Configuration management
- User management
- System maintenance

**IT Support:**
- Technical troubleshooting
- Performance monitoring
- Backup management
- Server maintenance

**Configuration Manager:**
- Master data management
- Workflow configuration
- Business rules setup

### What's New in v3 Administration

- ✅ WASL-specific configuration file (`config/wasl.php`)
- ✅ Auto-batch service configuration
- ✅ Document checklist administration
- ✅ Employer management
- ✅ Enhanced queue jobs for video processing
- ✅ New enum values for workflow states

---

## System Architecture

### Technology Stack

**Backend:**
- PHP 8.2+
- Laravel 11.x
- MySQL 8.0+

**Frontend:**
- Blade Templating
- Tailwind CSS 3.x
- Alpine.js (for dynamic interactions)

**Storage:**
- Private file storage for documents
- Public storage for published media
- Queue-based job processing

**Key Packages:**
- Spatie Laravel Permission (role-based authorization)
- Spatie Laravel Activitylog (audit trail)
- Laravel Queue (asynchronous jobs)
- Intervention Image (image processing)
- FFMpeg (video processing)

### Directory Structure

```
btevta/
├── app/
│   ├── Enums/                    # PHP 8.2 backed enums (NEW in v3)
│   │   ├── PlacementInterest.php
│   │   ├── TrainingType.php
│   │   ├── TrainingProgress.php
│   │   ├── PTNStatus.php
│   │   ├── ProtectorStatus.php
│   │   ├── DepartureStatus.php
│   │   └── ... (14 enums total)
│   ├── Models/                   # Eloquent models
│   │   ├── Program.php          # NEW in v3
│   │   ├── ImplementingPartner.php # NEW in v3
│   │   ├── Employer.php         # NEW in v3
│   │   ├── Course.php           # NEW in v3
│   │   └── ... (13 new models)
│   ├── Services/                 # Business logic services
│   │   ├── AutoBatchService.php # NEW in v3
│   │   ├── AllocationService.php # NEW in v3
│   │   ├── TrainingAssessmentService.php # NEW in v3
│   │   └── ...
│   ├── Jobs/                     # Queue jobs
│   │   ├── ProcessVideoUpload.php # NEW in v3
│   │   └── ...
│   └── ...
├── config/
│   ├── wasl.php                  # WASL v3 configuration (NEW)
│   ├── database.php
│   ├── filesystems.php
│   └── ...
├── database/
│   ├── migrations/               # Database migrations
│   │   └── ... (20 new v3 migrations)
│   └── seeders/
│       ├── WASLv3Seeder.php      # NEW in v3
│       └── ...
├── resources/
│   └── views/                    # Blade templates
│       ├── admin/employers/      # NEW in v3
│       ├── success-stories/      # NEW in v3
│       └── ...
├── storage/
│   ├── app/
│   │   ├── private/              # Private file storage
│   │   │   ├── documents/
│   │   │   ├── assessments/
│   │   │   └── employers/
│   │   └── public/               # Public file storage
│   └── logs/
└── tests/                        # PHPUnit tests (86+ v3 tests)
```

### Database Schema Changes (v3)

**New Tables (14):**
- countries
- payment_methods
- programs
- implementing_partners
- employers
- candidate_employer (pivot)
- document_checklists
- pre_departure_documents
- courses
- candidate_courses (pivot)
- training_assessments
- post_departure_details
- employment_histories
- success_stories

**Modified Tables (6):**
- candidates (added: program_id, implementing_partner_id, allocated_number)
- candidate_screenings (added: consent, placement_interest, target_country_id)
- training_schedules (added: technical_training_status, soft_skills_status)
- visa_processes (added: stage_details JSON, application_status, issued_status)
- departures (added: PTN/Protector/Ticket details, briefing uploads)
- complaints (added: workflow fields - issue, steps, suggestions, conclusion)

---

## Installation & Configuration

### Prerequisites

**Server Requirements:**
- PHP >= 8.2
- MySQL >= 8.0
- Composer >= 2.5
- Node.js >= 18.x (for asset compilation)
- FFMpeg (for video processing)

**PHP Extensions:**
- BCMath
- Ctype
- Fileinfo
- JSON
- Mbstring
- OpenSSL
- PDO
- Tokenizer
- XML
- GD or Imagick

### Installation Steps

#### Step 1: Clone Repository

```bash
cd /var/www
git clone https://github.com/btevta/wasl.git btevta
cd btevta
```

#### Step 2: Install Dependencies

```bash
# Install PHP dependencies
composer install --optimize-autoloader --no-dev

# Install Node dependencies
npm install
```

#### Step 3: Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

**Edit `.env` file:**

```env
APP_NAME="BTEVTA WASL"
APP_ENV=production
APP_KEY=base64:... (generated)
APP_DEBUG=false
APP_URL=https://wasl.btevta.gov.pk

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=btevta_wasl
DB_USERNAME=wasl_user
DB_PASSWORD=secure_password_here

FILESYSTEM_DISK=private
QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=noreply@btevta.gov.pk
MAIL_PASSWORD=mail_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@btevta.gov.pk
MAIL_FROM_NAME="${APP_NAME}"
```

#### Step 4: Database Setup

```bash
# Create database
mysql -u root -p
CREATE DATABASE btevta_wasl CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON btevta_wasl.* TO 'wasl_user'@'localhost' IDENTIFIED BY 'secure_password_here';
FLUSH PRIVILEGES;
EXIT;

# Run migrations
php artisan migrate --force

# Seed database with master data
php artisan db:seed --class=WASLv3Seeder
```

#### Step 5: Storage Setup

```bash
# Create storage link
php artisan storage:link

# Set permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

#### Step 6: Asset Compilation

```bash
# Compile assets for production
npm run build
```

#### Step 7: Queue Worker Setup

**Create systemd service** `/etc/systemd/system/wasl-worker.service`:

```ini
[Unit]
Description=WASL Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/btevta
ExecStart=/usr/bin/php /var/www/btevta/artisan queue:work --sleep=3 --tries=3 --max-time=3600
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

**Enable and start:**

```bash
systemctl daemon-reload
systemctl enable wasl-worker
systemctl start wasl-worker
```

#### Step 8: Scheduler Setup

**Add to crontab:**

```bash
crontab -e

# Add this line:
* * * * * cd /var/www/btevta && php artisan schedule:run >> /dev/null 2>&1
```

#### Step 9: Web Server Configuration

**Nginx Configuration:**

```nginx
server {
    listen 80;
    server_name wasl.btevta.gov.pk;
    root /var/www/btevta/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

#### Step 10: SSL Configuration

```bash
# Install Certbot
apt install certbot python3-certbot-nginx

# Obtain SSL certificate
certbot --nginx -d wasl.btevta.gov.pk
```

---

## WASL v3 Configuration

### Configuration File

WASL v3 settings are centralized in `config/wasl.php`.

**File Location:** `/var/www/btevta/config/wasl.php`

### Configuration Options

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Auto Batch Configuration
    |--------------------------------------------------------------------------
    */
    'batch' => [
        // Default batch size (candidates per batch)
        'default_size' => env('WASL_BATCH_SIZE', 25),

        // Available batch sizes (admin can select)
        'available_sizes' => [20, 25, 30],

        // Batch number format
        // {CAMPUS}-{PROGRAM}-{TRADE}-{YEAR}-{SEQUENCE}
        'number_format' => '{campus}-{program}-{trade}-{year}-{sequence}',

        // Sequence padding (0001, 0002, etc.)
        'sequence_padding' => 4,
    ],

    /*
    |--------------------------------------------------------------------------
    | Assessment Configuration
    |--------------------------------------------------------------------------
    */
    'assessment' => [
        // Default passing percentage
        'passing_percentage' => env('WASL_PASSING_PERCENTAGE', 70),

        // Assessment types
        'types' => [
            'interim' => 'Interim Assessment',
            'final' => 'Final Assessment',
        ],

        // Require both assessments for training completion
        'require_both' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Screening Configuration
    |--------------------------------------------------------------------------
    */
    'screening' => [
        // Require consent for work
        'require_consent' => true,

        // Allow international placement
        'allow_international' => true,

        // Gate enforcement - only screened can register
        'enforce_gate' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Upload Configuration
    |--------------------------------------------------------------------------
    */
    'documents' => [
        // Maximum file size in KB
        'max_size' => [
            'document' => 5120,  // 5MB
            'video' => 51200,    // 50MB
            'audio' => 51200,    // 50MB
        ],

        // Allowed file types
        'allowed_types' => [
            'document' => ['pdf', 'jpg', 'jpeg', 'png'],
            'video' => ['mp4', 'mov', 'avi', 'mkv'],
            'audio' => ['mp3', 'm4a', 'wav'],
        ],

        // Mandatory document types
        'mandatory_documents' => [
            'CNIC',
            'Passport',
            'Domicile',
            'FRC',
            'PCC',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Employer Configuration
    |--------------------------------------------------------------------------
    */
    'employer' => [
        // Require evidence document
        'require_evidence' => true,

        // Default currency
        'default_currency' => 'SAR',

        // Available currencies
        'currencies' => ['SAR', 'AED', 'USD', 'EUR', 'GBP', 'PKR'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Success Stories Configuration
    |--------------------------------------------------------------------------
    */
    'success_stories' => [
        // Auto-publish stories
        'auto_publish' => false,

        // Evidence types
        'evidence_types' => [
            'audio' => 'Audio Recording',
            'video' => 'Video Recording',
            'written' => 'Written Document',
            'photo' => 'Photograph',
            'none' => 'No Evidence',
        ],

        // Video processing
        'video_processing' => [
            'enabled' => true,
            'queue' => 'default',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Complaint Configuration
    |--------------------------------------------------------------------------
    */
    'complaints' => [
        // SLA in hours by priority
        'sla_hours' => [
            'critical' => 4,
            'high' => 24,
            'medium' => 72,
            'low' => 168,
        ],

        // Send SLA breach notifications
        'sla_notifications' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Post-Departure Configuration
    |--------------------------------------------------------------------------
    */
    'post_departure' => [
        // Maximum company switches to track
        'max_company_switches' => 2,

        // Required fields
        'required_fields' => [
            'residency_number',
            'employer_company_name',
            'final_salary',
        ],
    ],
];
```

### Modifying Configuration

**Via Environment Variables:**

```env
# In .env file
WASL_BATCH_SIZE=30
WASL_PASSING_PERCENTAGE=75
```

**Via Config File:**

```bash
# Edit config/wasl.php
nano /var/www/btevta/config/wasl.php

# Clear config cache after changes
php artisan config:cache
```

### Batch Size Configuration

**Allowed Values:** 20, 25, 30

**To Change Default:**

1. Edit `.env`:
   ```env
   WASL_BATCH_SIZE=30
   ```

2. Or edit `config/wasl.php`:
   ```php
   'default_size' => 30,
   ```

3. Clear cache:
   ```bash
   php artisan config:cache
   ```

**Effect:**
- New batches will be created with selected size
- Existing batches are not affected
- System prevents overflowing batch capacity

---

## User Management

### User Roles (v3)

**Predefined Roles:**
- **Super Admin** - Full system access
- **Admin** - Administrative access
- **Campus Director** - Campus-level management
- **Data Entry** - Candidate data entry
- **Screening Officer** - Initial screening
- **Training Officer** - Training & assessment management
- **Visa Officer** - Visa processing
- **Departure Officer** - Departure management
- **Post-Departure Officer** - Post-departure tracking
- **Report Viewer** - Read-only access to reports

### Creating Users

**Via Admin Panel:**

1. Navigate to **Admin** → **Users**
2. Click **Create User**
3. Fill user details:
   - Name
   - Email
   - Username
   - Password
   - Campus (optional)
4. Assign Role(s)
5. Click **Save**

**Via Command Line:**

```bash
# Create user
php artisan make:user

# Follow prompts:
# Name: John Doe
# Email: john@btevta.gov.pk
# Username: johndoe
# Password: ********
# Role: admin
```

### Managing Roles & Permissions

**View Roles:**

```bash
php artisan permission:show
```

**Create Custom Role:**

```bash
php artisan permission:create-role "Custom Role"
```

**Assign Permissions to Role:**

```php
// In tinker
php artisan tinker

$role = Role::findByName('Custom Role');
$role->givePermissionTo([
    'view candidates',
    'create candidates',
    'edit candidates',
    'view reports',
]);
```

### WASL v3 Specific Permissions

**New Permissions in v3:**
- `view employers`
- `create employers`
- `edit employers`
- `delete employers`
- `assign candidates to employers`
- `view success stories`
- `create success stories`
- `edit success stories`
- `delete success stories`
- `publish success stories`
- `view pre-departure documents`
- `upload pre-departure documents`
- `verify pre-departure documents`
- `view training assessments`
- `create training assessments`
- `edit training assessments`
- `view post-departure details`
- `edit post-departure details`
- `record company switches`

**Assign v3 Permissions:**

```bash
php artisan wasl:assign-v3-permissions
```

This command automatically assigns new v3 permissions to appropriate roles.

---

## Master Data Management

### Programs

Programs are training programs offered by BTEVTA (e.g., Technical Education & Vocational Training).

**Manage Programs:**

1. Navigate to **Admin** → **Programs**
2. Create, edit, or delete programs
3. Each program requires:
   - Name
   - Code (2-4 characters, e.g., "TEC")
   - Duration in weeks
   - Active status

**Seeding Default Programs:**

```bash
php artisan db:seed --class=ProgramSeeder
```

**Default Programs:**
- Technical Education & Vocational Training (TEC)
- Skills Development Program (SDP)
- Advanced Technical Training (ATT)

### Implementing Partners

Organizations partnering with BTEVTA for training delivery.

**Manage Implementing Partners:**

1. Navigate to **Admin** → **Implementing Partners**
2. Add partner details:
   - Name
   - Contact person
   - Email, phone
   - Address
   - Active status

**Import Partners from CSV:**

```bash
php artisan wasl:import-partners partners.csv
```

**CSV Format:**
```csv
name,contact_person,contact_email,contact_phone,address,is_active
"National Skills Dev Corp","Ahmed Khan","ahmed@nsdc.gov.pk","+92-51-9204567","Islamabad",1
```

### Courses

Training courses assigned to candidates during registration.

**Manage Courses:**

1. Navigate to **Admin** → **Courses**
2. Add course details:
   - Name
   - Code
   - Duration (days)
   - Training Type (Technical, Soft Skills, Both)
   - Active status

**Seeding Default Courses:**

```bash
php artisan db:seed --class=CourseSeeder
```

**Training Types:**
- **Technical** - Trade-specific technical skills
- **Soft Skills** - Communication, teamwork, etc.
- **Both** - Combined technical and soft skills

### Document Checklists

Configurable document requirements for pre-departure.

**Manage Document Checklists:**

1. Navigate to **Admin** → **Document Checklists**
2. Add document types:
   - Name (e.g., "CNIC")
   - Description
   - Is Mandatory (yes/no)
   - Category (identification, travel, legal, etc.)
   - Display Order
   - Active status

**Seeding Default Checklist:**

```bash
php artisan db:seed --class=DocumentChecklistSeeder
```

**Default Mandatory Documents:**
1. CNIC - Computerized National Identity Card
2. Passport - Valid passport
3. Domicile Certificate
4. FRC - Family Registration Certificate
5. PCC - Police Character Certificate

**Default Optional Documents:**
6. Pre-medical test results
7. Educational certifications
8. Professional licenses

### Countries

Destination countries for overseas employment.

**Manage Countries:**

1. Navigate to **Admin** → **Countries**
2. Add country details:
   - Name
   - Code (ISO 3166-1 alpha-3, e.g., "SAU")
   - Region
   - Active status

**Import Countries from CSV:**

```bash
php artisan wasl:import-countries countries.csv
```

### Payment Methods

Payment methods for remittances and financial transactions.

**Manage Payment Methods:**

1. Navigate to **Admin** → **Payment Methods**
2. Add payment methods:
   - Name (e.g., "Bank Transfer", "Western Union")
   - Description
   - Active status

---

## Batch Management

### Understanding Auto-Batch System

**Auto-Batch Generation:**
- Triggered automatically during candidate registration
- Batch created based on: Campus + Program + Trade + Year
- Batch size configurable (20/25/30 candidates)
- When batch reaches capacity, new batch auto-created with incremented sequence

**Batch Number Format:**
```
{CAMPUS_CODE}-{PROGRAM_CODE}-{TRADE_CODE}-{YEAR}-{SEQUENCE}

Example: ISB-TEC-WLD-2026-0001
```

**Allocated Number Format:**
```
{BATCH_NUMBER}-{POSITION}

Example: ISB-TEC-WLD-2026-0001-0012
```

### Viewing Batches

**Admin Interface:**

1. Navigate to **Training** → **Batches**
2. View all batches with:
   - Batch code
   - Campus, Program, Trade
   - Current size / Maximum size
   - Status (active, full, completed)
   - Start date, End date

**Filter Options:**
- By Campus
- By Program
- By Trade
- By Status
- By Date Range

### Managing Batch Size

**Change Batch Size Mid-Year:**

⚠️ **Warning:** Changing batch size affects only NEW batches. Existing batches retain their original size.

1. Edit `config/wasl.php`:
   ```php
   'default_size' => 30, // Changed from 25
   ```

2. Clear config cache:
   ```bash
   php artisan config:cache
   ```

3. New registrations will create 30-candidate batches

**Per-Campus Batch Size (Advanced):**

If different campuses need different batch sizes, modify `AutoBatchService`:

```php
// In app/Services/AutoBatchService.php
protected function getBatchSize(Campus $campus): int
{
    $campusSpecificSizes = [
        'ISB' => 30,
        'LHR' => 25,
        'KHI' => 20,
    ];

    return $campusSpecificSizes[$campus->code]
        ?? config('wasl.batch.default_size');
}
```

### Batch Reports

**Generate Batch Report:**

```bash
php artisan wasl:batch-report

# Options:
php artisan wasl:batch-report --campus=ISB --year=2026
```

**Report Includes:**
- Total batches created
- Batches by campus
- Average batch size
- Completion rates
- Training status breakdown

---

## Document Management

### Storage Configuration

**Storage Disks:**

```php
// config/filesystems.php

'disks' => [
    'private' => [
        'driver' => 'local',
        'root' => storage_path('app/private'),
        'visibility' => 'private',
    ],

    'public' => [
        'driver' => 'local',
        'root' => storage_path('app/public'),
        'url' => env('APP_URL').'/storage',
        'visibility' => 'public',
    ],
],
```

**Document Storage Paths:**
- Pre-Departure Documents: `storage/app/private/documents/candidate-{id}/`
- Employer Evidence: `storage/app/private/employers/evidence/`
- Assessment Evidence: `storage/app/private/assessments/batch-{id}/`
- Success Story Videos: `storage/app/public/success-stories/`
- Departure Briefing Videos: `storage/app/private/departures/briefings/`

### File Upload Limits

**PHP Configuration:**

Edit `/etc/php/8.2/fpm/php.ini`:

```ini
upload_max_filesize = 50M
post_max_size = 55M
max_execution_time = 300
memory_limit = 256M
```

**Nginx Configuration:**

Edit `/etc/nginx/nginx.conf`:

```nginx
client_max_body_size 55M;
```

**Restart Services:**

```bash
systemctl restart php8.2-fpm
systemctl restart nginx
```

### Document Cleanup

**Remove Old Unverified Documents:**

```bash
# Remove unverified documents older than 90 days
php artisan wasl:cleanup-documents --days=90 --dry-run

# Actually delete (remove --dry-run)
php artisan wasl:cleanup-documents --days=90
```

**Archive Completed Candidate Documents:**

```bash
# Archive documents for departed candidates
php artisan wasl:archive-documents --status=departed
```

---

## Queue & Job Management

### Queue Configuration

WASL v3 uses database queue driver by default.

**Database Queue Tables:**
- `jobs` - Pending jobs
- `failed_jobs` - Failed jobs
- `job_batches` - Batched jobs (for video processing)

### Queue Worker Management

**Check Worker Status:**

```bash
systemctl status wasl-worker
```

**Restart Worker:**

```bash
systemctl restart wasl-worker
```

**View Worker Logs:**

```bash
journalctl -u wasl-worker -f
```

### Video Processing Job

**ProcessVideoUpload Job:**
- Triggered when success story or briefing video uploaded
- Processes video in background
- Validates video format
- Generates thumbnail
- Optimizes video size

**Monitor Video Processing:**

```bash
# View queue jobs
php artisan queue:work --once

# View failed jobs
php artisan queue:failed

# Retry failed job
php artisan queue:retry {job-id}

# Retry all failed jobs
php artisan queue:retry all
```

**Video Processing Settings:**

```php
// config/wasl.php
'success_stories' => [
    'video_processing' => [
        'enabled' => true,
        'queue' => 'default',
        'timeout' => 600, // 10 minutes
        'max_attempts' => 3,
    ],
],
```

### Queue Monitoring

**Install Horizon (Optional):**

```bash
composer require laravel/horizon

php artisan horizon:install
php artisan horizon
```

Access dashboard: `https://wasl.btevta.gov.pk/horizon`

---

## Security & Permissions

### Role-Based Access Control

WASL v3 uses Spatie Laravel Permission package for RBAC.

**Permission Structure:**
```
{action} {resource}

Examples:
- view candidates
- create employers
- edit training assessments
- publish success stories
```

**Middleware Protection:**

```php
Route::middleware(['auth', 'permission:view employers'])
    ->group(function () {
        Route::get('/employers', [EmployerController::class, 'index']);
    });
```

### Data Access Control

**Campus-Level Restrictions:**

Users assigned to specific campuses see only their campus data.

**Implement Campus Scoping:**

```php
// In AppServiceProvider boot()
Candidate::addGlobalScope('campus', function (Builder $builder) {
    if (auth()->check() && auth()->user()->campus_id) {
        $builder->where('campus_id', auth()->user()->campus_id);
    }
});
```

### Audit Logging

All critical actions logged via Spatie Laravel Activitylog.

**View Activity Logs:**

```bash
php artisan tinker

# View recent activity
Activity::latest()->take(10)->get();

# View activity for specific model
Activity::forSubject(Employer::find(1))->get();

# View activity by user
Activity::causedBy(User::find(1))->get();
```

**Logged Actions:**
- Candidate registration
- Screening decisions
- Batch assignments
- Assessment recordings
- Employer assignments
- Success story publications
- Complaint status changes

### Security Best Practices

**1. Keep Dependencies Updated:**

```bash
composer update --with-all-dependencies
npm update
```

**2. Regular Security Audits:**

```bash
composer audit
npm audit
```

**3. Enable HTTPS:**

Always use HTTPS in production. Configure SSL certificate via Let's Encrypt.

**4. Environment File Security:**

```bash
chmod 600 .env
chown www-data:www-data .env
```

**5. Database Security:**

- Use strong passwords
- Restrict database user privileges
- Enable MySQL SSL connections
- Regular backups

**6. File Upload Security:**

- Validate file types
- Scan uploads for malware
- Store private documents outside public directory
- Use signed URLs for download access

---

## Backup & Recovery

### Database Backup

**Automated Daily Backup:**

Create backup script `/usr/local/bin/wasl-backup.sh`:

```bash
#!/bin/bash

BACKUP_DIR="/backups/wasl"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="btevta_wasl"
DB_USER="wasl_user"
DB_PASS="secure_password_here"

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u$DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_backup_$DATE.sql.gz

# Backup files
tar -czf $BACKUP_DIR/files_backup_$DATE.tar.gz /var/www/btevta/storage/app/private

# Delete backups older than 30 days
find $BACKUP_DIR -name "*.gz" -mtime +30 -delete

echo "Backup completed: $DATE"
```

**Make executable:**

```bash
chmod +x /usr/local/bin/wasl-backup.sh
```

**Schedule in cron:**

```bash
crontab -e

# Add:
0 2 * * * /usr/local/bin/wasl-backup.sh >> /var/log/wasl-backup.log 2>&1
```

### Database Restore

```bash
# Decompress backup
gunzip /backups/wasl/db_backup_20260119_020000.sql.gz

# Restore database
mysql -u wasl_user -p btevta_wasl < /backups/wasl/db_backup_20260119_020000.sql
```

### File Restore

```bash
# Extract files
tar -xzf /backups/wasl/files_backup_20260119_020000.tar.gz -C /var/www/btevta/

# Set permissions
chown -R www-data:www-data /var/www/btevta/storage
```

### Disaster Recovery

**Full System Recovery:**

1. Restore database from backup
2. Restore files from backup
3. Verify .env configuration
4. Run migrations to ensure schema up-to-date:
   ```bash
   php artisan migrate --force
   ```
5. Clear all caches:
   ```bash
   php artisan optimize:clear
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
6. Restart services:
   ```bash
   systemctl restart php8.2-fpm
   systemctl restart nginx
   systemctl restart wasl-worker
   ```

---

## Performance Optimization

### Database Optimization

**Index Optimization:**

```sql
-- Ensure indexes on foreign keys
SHOW INDEX FROM candidates;
SHOW INDEX FROM batches;

-- Add missing indexes
CREATE INDEX idx_candidates_status ON candidates(status);
CREATE INDEX idx_candidates_campus_program ON candidates(campus_id, program_id);
CREATE INDEX idx_batches_campus_trade ON batches(campus_id, trade_id);
```

**Query Optimization:**

```bash
# Enable query logging
php artisan tinker

DB::enableQueryLog();
// Perform operation
DB::getQueryLog();
```

### Cache Configuration

**Enable Redis (Recommended):**

```bash
apt install redis-server
composer require predis/predis

# Update .env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

**Cache Configuration Caching:**

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**Clear Caches:**

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Asset Optimization

**Compile for Production:**

```bash
npm run build
```

**Enable Gzip Compression (Nginx):**

```nginx
gzip on;
gzip_vary on;
gzip_proxied any;
gzip_comp_level 6;
gzip_types text/plain text/css text/xml text/javascript application/json application/javascript application/xml+rss;
```

### PHP-FPM Optimization

**Edit `/etc/php/8.2/fpm/pool.d/www.conf`:**

```ini
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 15
pm.max_requests = 500
```

**Restart PHP-FPM:**

```bash
systemctl restart php8.2-fpm
```

---

## Monitoring & Logging

### Application Logs

**Log Location:** `/var/www/btevta/storage/logs/laravel.log`

**View Logs:**

```bash
tail -f /var/www/btevta/storage/logs/laravel.log
```

**Log Rotation:**

Create `/etc/logrotate.d/wasl`:

```
/var/www/btevta/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
}
```

### System Monitoring

**Install Monitoring Tools:**

```bash
apt install htop iotop nethogs
```

**Monitor PHP-FPM:**

```bash
systemctl status php8.2-fpm
```

**Monitor Nginx:**

```bash
systemctl status nginx
```

**Monitor MySQL:**

```bash
mysqladmin -u root -p processlist
mysqladmin -u root -p status
```

### Performance Monitoring

**Laravel Telescope (Development):**

```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

Access: `https://wasl.btevta.gov.pk/telescope`

**⚠️ Warning:** Disable Telescope in production or restrict access.

### Health Checks

**Create Health Check Endpoint:**

```php
// routes/web.php
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected',
        'cache' => Cache::has('test_key') ? 'working' : 'not working',
        'queue' => Queue::size() >= 0 ? 'working' : 'not working',
    ]);
});
```

**Automated Health Monitoring:**

Use external service (e.g., UptimeRobot, Pingdom) to monitor `/health` endpoint.

---

## Troubleshooting

### Common Issues

#### Issue 1: Queue Worker Not Processing Jobs

**Symptoms:**
- Videos not processing
- Notifications not sending

**Solution:**

```bash
# Check worker status
systemctl status wasl-worker

# Restart worker
systemctl restart wasl-worker

# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

---

#### Issue 2: File Upload Fails

**Symptoms:**
- "413 Request Entity Too Large"
- "File upload failed"

**Solution:**

```bash
# Increase PHP limits
nano /etc/php/8.2/fpm/php.ini
# Set: upload_max_filesize = 50M

# Increase Nginx limits
nano /etc/nginx/nginx.conf
# Set: client_max_body_size 55M;

# Restart services
systemctl restart php8.2-fpm
systemctl restart nginx
```

---

#### Issue 3: Auto-Batch Not Creating

**Symptoms:**
- Registration succeeds but no batch assigned
- Error: "Batch creation failed"

**Solution:**

```bash
# Check AutoBatchService logs
tail -f storage/logs/laravel.log | grep AutoBatch

# Verify batch configuration
php artisan tinker
config('wasl.batch.default_size');

# Clear config cache
php artisan config:clear
php artisan config:cache
```

---

#### Issue 4: Performance Degradation

**Symptoms:**
- Slow page loads
- High server load

**Solution:**

```bash
# Check database queries
php artisan telescope:list

# Optimize database
mysql -u root -p
OPTIMIZE TABLE candidates;
OPTIMIZE TABLE batches;

# Clear and cache configuration
php artisan optimize

# Restart services
systemctl restart php8.2-fpm
systemctl restart nginx
```

---

#### Issue 5: Permission Denied Errors

**Symptoms:**
- "Permission denied" when accessing files
- "Storage not writable"

**Solution:**

```bash
# Fix storage permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Recreate storage link
rm public/storage
php artisan storage:link
```

---

### Getting Support

**Technical Support:**
- Email: tech-support@btevta.gov.pk
- Phone: +92-51-9204567 (Ext. 123)
- Hours: 24/7 for critical issues

**Bug Reports:**
- GitHub Issues: https://github.com/btevta/wasl/issues
- Include:
  - Laravel version
  - PHP version
  - Error message
  - Steps to reproduce
  - Relevant log entries

**Documentation:**
- User Manual: `docs/v3/USER_MANUAL.md`
- API Documentation: `docs/v3/API_DOCUMENTATION.md`
- Deployment Guide: `docs/v3/DEPLOYMENT_GUIDE.md`

---

## Appendix

### Useful Commands

**Cache Management:**
```bash
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

**Database:**
```bash
php artisan migrate
php artisan migrate:rollback
php artisan migrate:fresh --seed
php artisan db:seed
```

**Queue:**
```bash
php artisan queue:work
php artisan queue:listen
php artisan queue:restart
php artisan queue:failed
php artisan queue:retry all
```

**Maintenance Mode:**
```bash
php artisan down --secret="maintenance-bypass-token"
php artisan up
```

**Generate Application Key:**
```bash
php artisan key:generate
```

### Configuration Files Reference

- `config/wasl.php` - WASL v3 specific settings
- `config/database.php` - Database configuration
- `config/filesystems.php` - File storage configuration
- `config/queue.php` - Queue configuration
- `config/mail.php` - Email configuration
- `config/logging.php` - Logging configuration

### Database Tables Reference (v3)

**New Tables:**
- countries, payment_methods, programs, implementing_partners
- employers, candidate_employer
- document_checklists, pre_departure_documents
- courses, candidate_courses
- training_assessments
- post_departure_details, employment_histories
- success_stories

**Modified Tables:**
- candidates, candidate_screenings, training_schedules
- visa_processes, departures, complaints

---

**Document End**

*For the latest updates to this guide, visit the WASL Documentation Portal or contact the system administrator.*
