# WASL - Workforce Abroad Skills & Linkages

## TheLeap Overseas Employment Management System

A comprehensive digital platform for managing the complete candidate lifecycle from TheLeap listing through overseas deployment, post-departure tracking, and remittance management.

**Version:** 1.5.0 | **Status:** Production Ready | **Last Updated:** February 2026

---

## Table of Contents

- [Overview](#overview)
- [System Architecture](#system-architecture)
- [Key Features](#key-features)
- [System Requirements](#system-requirements)
- [Installation](#installation)
- [Environment Configuration](#environment-configuration)
- [Quick Start Guide](#quick-start-guide)
- [User Roles](#user-roles)
- [Module Documentation](#module-documentation)
- [API Reference](#api-reference)
- [Tutorials](#tutorials)
- [Security Features](#security-features)
- [Production Deployment](#production-deployment)
- [Maintenance & Monitoring](#maintenance--monitoring)
- [Troubleshooting](#troubleshooting)
- [Developer Resources](#developer-resources)
- [Changelog](#changelog)

---

## Overview

WASL (Workforce Abroad Skills & Linkages) is a Laravel-based enterprise application designed for TheLeap (Board of Technical Education & Vocational Training Authority, Punjab) to streamline overseas employment management. The system tracks candidates from initial listing through training, visa processing, departure, and post-deployment monitoring.

### Technology Stack

| Component | Technology |
|-----------|------------|
| Backend | Laravel 11.x (PHP 8.2+) |
| Database | MySQL 8.0+ |
| Frontend | Tailwind CSS 3.x, Alpine.js 3.x |
| Charts | Chart.js |
| Documents | PhpSpreadsheet, DomPDF |
| Authentication | Laravel Sanctum |
| Activity Logging | Spatie Activity Log |

---

## System Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────────────┐
│                              CLIENTS                                     │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐                  │
│  │   Browser    │  │  Mobile App  │  │  API Client  │                  │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘                  │
└─────────┼─────────────────┼─────────────────┼───────────────────────────┘
          │                 │                 │
          └────────────────┬┴─────────────────┘
                           │ HTTPS
                           ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                         LOAD BALANCER / NGINX                            │
│                    (SSL Termination, Static Assets)                      │
└─────────────────────────────────────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                         LARAVEL APPLICATION                              │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐    │
│  │   Routes    │  │ Controllers │  │  Services   │  │   Models    │    │
│  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘    │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐    │
│  │  Policies   │  │ Middleware  │  │   Events    │  │    Jobs     │    │
│  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘    │
└─────────────────────────────────────────────────────────────────────────┘
          │                 │                 │
          ▼                 ▼                 ▼
┌─────────────────┐ ┌─────────────────┐ ┌─────────────────┐
│     MySQL       │ │  Redis/Cache    │ │  File Storage   │
│   (Primary DB)  │ │ (Sessions/Queue)│ │  (Documents)    │
└─────────────────┘ └─────────────────┘ └─────────────────┘
```

### Request Flow

```
1. Request → Nginx → Laravel
2. Middleware: CORS → Session → CSRF → Auth → Role Check
3. Controller → Service → Repository → Model
4. Response (JSON/HTML) → Client
```

### Directory Structure

```
btevta/
├── app/
│   ├── Http/
│   │   ├── Controllers/     # Request handlers (30 controllers)
│   │   ├── Middleware/      # Auth, CSRF, Role, Security
│   │   └── Requests/        # Form validation
│   ├── Models/              # Eloquent models (34 models)
│   ├── Policies/            # Authorization (40 policies)
│   ├── Services/            # Business logic (14 services)
│   ├── Observers/           # Model event handlers
│   └── Rules/               # Custom validation rules
├── config/                  # Application configuration
├── database/
│   ├── migrations/          # Database schema
│   └── seeders/             # Initial data
├── resources/views/         # Blade templates
├── routes/
│   ├── web.php              # Web routes
│   └── api.php              # API routes
└── storage/
    ├── app/private/         # Secure document storage
    └── logs/                # Application logs
```

### Database Schema Overview

```
Core Entities:
├── users (9 roles, soft-delete)
├── candidates (main entity, 15 statuses)
├── campuses (training locations)
├── trades (skill categories)
├── batches (training groups)
└── oeps (overseas employment promoters)

Workflow Entities:
├── screenings (3-call system)
├── trainings (attendance, assessments)
├── visa_processes (12-stage pipeline)
├── departures (flight tracking)
└── remittances (money transfers)

Support Entities:
├── complaints (SLA management)
├── correspondences (communications)
├── document_archives (versioned files)
└── activity_log (audit trail)
```

---

## Key Features

### Core Modules (10 Tabs)

| # | Module | Description |
|---|--------|-------------|
| 1 | **Candidates Listing** | Import TheLeap candidates, auto-assign batches, bulk operations |
| 2 | **Screening** | 3-call screening workflow, outcome tracking, evidence upload |
| 3 | **Registration** | Profile creation, document archive, OEP allocation |
| 4 | **Training** | Attendance, assessments, certificates, batch management |
| 5 | **Visa Processing** | Interview, trade test, Takamol, GAMCA, E-number, PTN |
| 6 | **Departure** | Flight tracking, Iqama, Absher, 90-day compliance |
| 7 | **Correspondence** | Official communications, reply tracking |
| 8 | **Complaints** | SLA-based complaint management, escalation |
| 9 | **Document Archive** | Version control, expiry alerts, access logging |
| 10 | **Reports** | Dynamic reports, Excel/PDF/CSV export |

### Additional Features

- **Remittance Management** - Track money transfers with alerts and analytics
- **Real-time Notifications** - WebSocket/polling-based live updates
- **Interactive Analytics** - Dashboard widgets with Chart.js
- **Bulk Operations** - Multi-select actions for candidates
- **Mobile-Responsive** - Bottom navigation, touch-friendly UI

---

## System Requirements

### Server Requirements

```
PHP >= 8.2
MySQL >= 8.0 or PostgreSQL >= 13
Composer >= 2.0
Node.js >= 18.0 (for asset compilation)
```

### PHP Extensions

```
BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML, GD/Imagick
```

---

## Installation

### Prerequisites Check

Before installation, verify all requirements are met:

```bash
# Check PHP version (must be 8.2+)
php -v

# Check required PHP extensions
php -m | grep -E 'pdo_mysql|gd|mbstring|openssl|bcmath|fileinfo|xml|ctype|tokenizer'

# Check Composer version (must be 2.0+)
composer -V

# Check Node.js version (must be 18+)
node -v

# Check npm
npm -v
```

### Step 1: Clone Repository

```bash
git clone https://github.com/haseebayazi/btevta.git
cd btevta
```

### Step 2: Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node dependencies and build assets
npm install && npm run build
```

### Step 3: Environment Setup

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### Step 4: Create Database

```bash
# Connect to MySQL and create database
mysql -u root -p -e "CREATE DATABASE btevta CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### Step 5: Configure Environment

Edit `.env` file with your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=btevta
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### Step 6: Run Migrations & Seeders

```bash
# Run database migrations
php artisan migrate

# Seed initial data (creates admin accounts)
php artisan db:seed
```

> **Note:** Credentials are saved to `storage/logs/seeder-credentials.log`. Delete this file after noting the passwords.

### Step 7: Create Storage Symlink

```bash
# Link storage to public directory (required for file uploads)
php artisan storage:link
```

### Step 8: Set Permissions

```bash
# Set proper permissions for storage and cache
chmod -R 775 storage bootstrap/cache
```

### Step 9: Start Development Server

```bash
php artisan serve
```

Access at: `http://localhost:8000`

---

## Environment Configuration

### Development vs Production

| Setting | Development | Production |
|---------|-------------|------------|
| `APP_ENV` | local | production |
| `APP_DEBUG` | true | **false** |
| `LOG_LEVEL` | debug | error |
| `CACHE_DRIVER` | file | redis |
| `SESSION_DRIVER` | file | redis |
| `QUEUE_CONNECTION` | sync | redis |

### Security Configuration

```env
# Password Policy (Government Standard)
PASSWORD_MIN_LENGTH=12
PASSWORD_REQUIRE_UPPERCASE=true
PASSWORD_REQUIRE_LOWERCASE=true
PASSWORD_REQUIRE_NUMBER=true
PASSWORD_REQUIRE_SPECIAL=true
PASSWORD_HISTORY_COUNT=5
PASSWORD_EXPIRY_DAYS=90

# Session Security
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=strict

# API Token Expiry (24 hours)
SANCTUM_TOKEN_EXPIRATION=1440
```

### Mail Configuration

```env
# Gmail Example
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@btevta.gov.pk"
MAIL_FROM_NAME="${APP_NAME}"
```

> **Gmail Note:** Use an App Password, not your regular password. Enable 2FA and generate an App Password in Google Account settings.

---

## Quick Start Guide

### Initial Access

After running `php artisan db:seed`, administrative accounts are created automatically.

> **Security Notice:** For security compliance, login credentials are displayed ONLY in the terminal during seeding and written to `storage/logs/seeder-credentials.log`. This file should be securely deleted after initial setup.

> **Important:** All seeded accounts require **mandatory password change on first login**. The system will redirect you to the password change screen automatically.

**Seeded Roles:**
- Super Admin (`superadmin@btevta.gov.pk`)
- Admin (`admin@btevta.gov.pk`)
- Project Director (`director@btevta.gov.pk`)
- Campus Admins (one per campus)
- Trainers, OEP Users, Visa Partners, Viewer, Staff

Contact your deployment administrator for initial credentials.

### First Steps After Login

1. **Configure Campuses** - Go to Admin > Campuses
2. **Add Trades** - Go to Admin > Trades
3. **Register OEPs** - Go to Admin > OEPs
4. **Create Users** - Go to Admin > Users
5. **Import Candidates** - Dashboard > Import from Excel

---

## User Roles

### Role Hierarchy

```
Super Admin
    |
    +-- Admin (full access)
    |
    +-- Campus Admin (campus-scoped)
    |
    +-- OEP (assigned candidates)
    |
    +-- Instructor (training module)
    |
    +-- Viewer (read-only)
```

### Permission Matrix

| Feature | Super Admin | Admin | Campus Admin | OEP | Instructor | Viewer |
|---------|:-----------:|:-----:|:------------:|:---:|:----------:|:------:|
| All Candidates | Y | Y | Campus Only | Assigned | N | N |
| Import Candidates | Y | Y | Y | N | N | N |
| Manage Training | Y | Y | Y | N | Y | N |
| Visa Processing | Y | Y | Y | Y | N | N |
| System Settings | Y | Y | N | N | N | N |
| User Management | Y | Y | N | N | N | N |
| View Reports | Y | Y | Y | Y | Y | Y |
| Bulk Delete | Y | Y | N | N | N | N |

---

## Module Documentation

### 1. Candidates Listing

**Import Candidates from Excel:**
1. Download template: Dashboard > Import > Download Template
2. Fill in candidate data following the template format
3. Upload: Dashboard > Import from Excel
4. System validates and creates candidates with status "Listed"

**Bulk Operations:**
- Select multiple candidates using checkboxes
- Available actions: Change Status, Assign Batch, Assign Campus, Export, Delete

**Search & Filter:**
- Search by Name, CNIC, or TheLeap ID
- Filter by Status, Trade, or Batch
- Results automatically paginate

### 1a. Pre-Departure Documents

Pre-departure document collection is required before candidates can proceed to screening.

**Document Types:**

| Document | Required | Description |
|----------|----------|-------------|
| CNIC Front & Back | Yes | National ID card copy |
| Passport (1st & 2nd Page) | Yes | Valid passport pages |
| Domicile | Yes | Proof of residence |
| Family Registration Certificate (FRC) | Yes | NADRA family certificate |
| Police Character Certificate (PCC) | Yes | Police clearance |
| Driving License | No | If applicable |
| Professional License | No | Trade-specific licenses |
| Pre-Medical Reports | No | Optional health records |

**Accessing Pre-Departure Documents:**
1. Navigate to Candidates > Select a Candidate
2. On the candidate profile, click the "Pre-Departure Documents" card or "Upload Documents" button
3. Upload required documents for each checklist item

**Document Management Features:**
- **Upload:** PDF, JPG, PNG files up to 5MB
- **Download:** Retrieve uploaded documents
- **Verify:** Admin/Project Director can verify documents
- **Reject:** Return documents with reason for re-upload
- **Delete:** Remove documents (only in editable statuses)

**Progress Tracking:**
- Dashboard shows document completion status (e.g., "3/5 Mandatory Documents")
- Candidates cannot proceed to screening until all mandatory documents are uploaded
- Visual indicators show verified vs pending documents

**Reports:**
- Generate individual candidate document reports (PDF/Excel)
- Bulk document status reports by campus/status

**Access Control:**
| Role | View | Upload | Verify | Delete |
|------|------|--------|--------|--------|
| Super Admin | All | All | Yes | Yes |
| Project Director | All | All | Yes | No |
| Campus Admin | Own Campus | Own Campus | Yes | Own Campus |
| OEP | Own Candidates | Own Candidates | No | Own Candidates |

### 2. Screening Workflow

The screening process follows a 3-call system:

```
Call 1: Document collection reminder
    |
Call 2: Registration & campus selection
    |
Call 3: Training confirmation
    |
Outcome: Eligible / Rejected / Pending
```

**Recording a Call:**
1. Go to Screening > Select Candidate
2. Click "Log Call"
3. Select outcome: Answered, No Answer, Busy, Wrong Number
4. Add notes and evidence if needed

### 3. Registration Process

**Complete Registration:**
1. Navigate to Registration tab
2. Upload required documents:
   - CNIC (front & back)
   - Passport
   - Educational certificates
   - Police clearance
   - Medical report
3. Enter Next of Kin information
4. Complete undertaking form
5. Assign to OEP

### 4. Training Management

**Mark Attendance:**
```
Daily attendance options: Present | Absent | Late | Leave
```

**Record Assessment:**
1. Go to Training > Select Batch
2. Click "Assessments"
3. Enter scores for: Midterm, Final, Practical
4. System calculates grades automatically

**Generate Certificate:**
- Requires: 80% attendance + passing grades
- Click "Generate Certificate" on candidate profile

### 5. Visa Processing Stages

```
Interview -> Trade Test -> Takamol -> Medical (GAMCA) ->
E-Number -> Biometrics (Etimad) -> Visa Submission ->
Visa Issued -> PTN -> Attestation -> Ticket -> Ready
```

**Update Stage:**
1. Go to Visa Processing > Select Candidate
2. Click current stage to update
3. Upload required documents
4. System validates prerequisites before advancing

### 6. Departure Tracking

**Record Departure:**
1. Pre-departure briefing completion
2. Enter flight details
3. Track post-arrival:
   - Iqama number
   - Absher registration
   - Qiwa ID
   - First salary confirmation

**90-Day Compliance:**
- System automatically tracks 90-day post-arrival compliance
- Alerts generated for missing information

### 7. Remittance Management

**Record Remittance:**
1. Go to Remittances > Add New
2. Select candidate and departure record
3. Enter transfer details (amount, date, method)
4. Upload proof of transfer
5. System tracks monthly remittance patterns

**Alerts:**
- Missing proof alerts
- Irregular pattern alerts
- Auto-generated based on configurable rules

---

## API Reference

### Authentication

```bash
# Login
POST /api/v1/login
Content-Type: application/json
{
    "email": "user@example.com",
    "password": "password"
}

# Response
{
    "token": "1|abc123...",
    "user": { ... }
}

# Use token in requests
Authorization: Bearer {token}
```

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/candidates` | List candidates (paginated) |
| POST | `/api/v1/candidates` | Create candidate |
| GET | `/api/v1/candidates/{id}` | Get candidate details |
| PUT | `/api/v1/candidates/{id}` | Update candidate |
| DELETE | `/api/v1/candidates/{id}` | Delete candidate |
| GET | `/api/v1/departures` | List departures |
| GET | `/api/v1/visa-processes` | List visa processes |
| GET | `/api/v1/remittances` | List remittances |
| GET | `/api/v1/reports/*` | Various reports |

### Query Parameters

```
?page=1              # Pagination
?per_page=25         # Items per page (max 100)
?status=training     # Filter by status
?campus_id=1         # Filter by campus
?trade_id=2          # Filter by trade
?search=john         # Search term
?sort=created_at     # Sort field
?order=desc          # Sort order (asc/desc)
```

### Rate Limiting

API requests are rate-limited to prevent abuse:

| Endpoint Type | Limit | Window |
|--------------|-------|--------|
| Authentication | 5 requests | 1 minute |
| General API | 60 requests | 1 minute |
| Bulk Operations | 10 requests | 1 minute |
| Report Generation | 5 requests | 1 minute |
| File Upload | 20 requests | 1 minute |

**Rate Limit Headers:**
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1703900400
```

**Rate Limit Exceeded Response:**
```json
{
    "message": "Too Many Attempts.",
    "retry_after": 60
}
```
HTTP Status: `429 Too Many Requests`

### API Token Management

Tokens expire after 24 hours (configurable via `SANCTUM_TOKEN_EXPIRATION`).

```bash
# Create new token
POST /api/v1/tokens/create
{
    "token_name": "mobile-app",
    "abilities": ["read", "write"]
}

# Revoke token
DELETE /api/v1/tokens/{token_id}

# List active tokens
GET /api/v1/tokens
```

### Error Responses

| Status Code | Description |
|-------------|-------------|
| 400 | Bad Request - Invalid input |
| 401 | Unauthorized - Invalid/expired token |
| 403 | Forbidden - Insufficient permissions |
| 404 | Not Found - Resource doesn't exist |
| 422 | Validation Error - Invalid data |
| 429 | Too Many Requests - Rate limited |
| 500 | Server Error - Contact support |

**Validation Error Example:**
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": ["The email field is required."],
        "cnic": ["The cnic has already been taken."]
    }
}
```

---

## Tutorials

### Tutorial 1: Import Candidates from TheLeap

```
Step 1: Download Template
        Dashboard > Import Candidates > Download Template

Step 2: Prepare Data
        - Fill columns: Name, CNIC, Trade, District, Phone
        - Save as .xlsx format

Step 3: Upload File
        Dashboard > Import Candidates > Choose File > Upload

Step 4: Review Import
        - Check validation errors
        - Confirm successful imports
        - View imported candidates in listing
```

### Tutorial 2: Complete Candidate Registration

```
Step 1: Find Candidate
        Registration > Search by CNIC or Name

Step 2: Upload Documents
        - Click "Upload Documents"
        - Select document type
        - Upload file (PDF, JPG, PNG)
        - Repeat for all required documents

Step 3: Enter Next of Kin
        - Full name, relationship
        - Contact information
        - Address

Step 4: Complete Undertaking
        - Review terms
        - Digital signature
        - Submit

Step 5: Assign OEP
        - Select available OEP
        - Confirm assignment
        - Status changes to "Registered"
```

### Tutorial 3: Process Visa Application

```
Step 1: Schedule Interview
        Visa Processing > Select Candidate > Schedule Interview
        Enter: Date, Location, Notes

Step 2: Record Results
        After interview: Pass/Fail with remarks

Step 3: Proceed Through Stages
        Each stage requires:
        - Date of completion
        - Result (if applicable)
        - Supporting documents

Step 4: Generate E-Number
        After Takamol: System auto-generates E-Number

Step 5: Issue PTN
        After visa approval: Enter PTN details

Step 6: Upload Ticket
        Final step: Upload travel itinerary
        Status: "Ready to Depart"
```

### Tutorial 4: Generate Reports

```
Step 1: Select Report Type
        Reports > Choose from:
        - Candidate Summary
        - Training Progress
        - Visa Pipeline
        - Departure Statistics
        - Remittance Analysis

Step 2: Apply Filters
        - Date range
        - Campus
        - Trade
        - Status
        - OEP

Step 3: Preview
        Click "Generate" to preview data

Step 4: Export
        Choose format: Excel, PDF, or CSV
        Click "Download"
```

### Tutorial 5: Handle Complaints

```
Step 1: Register Complaint
        Complaints > New Complaint
        - Select candidate
        - Category (Training, Visa, Salary, etc.)
        - Priority (Low, Medium, High, Critical)
        - Description

Step 2: Assign to Staff
        - Select staff member
        - Set deadline based on SLA

Step 3: Investigation
        - Add notes
        - Upload evidence
        - Update status

Step 4: Resolution
        - Document resolution
        - Close complaint
        - Notify candidate
```

### Tutorial 6: Use Bulk Operations

```
Step 1: Select Candidates
        Candidates Listing > Check boxes next to candidates
        Or use "Select All" checkbox

Step 2: Choose Action
        Bulk Action Bar appears with options:
        - Change Status
        - Assign Batch
        - Export
        - Delete (Admin only)

Step 3: Confirm Action
        Review selection count
        Click action button
        Confirm in dialog

Step 4: View Results
        Toast notification shows success/failure count
        Page refreshes with updated data
```

---

## Security Features

### Authentication & Authorization
- Password hashing (bcrypt, cost 10)
- Account lockout (5 failed attempts, 15-min cooldown)
- Session regeneration on login
- Role-based access control (RBAC)
- Policy-based authorization (40 policies)
- API authentication via Sanctum

### Input Protection
- CSRF protection on all forms
- SQL injection prevention (Eloquent ORM)
- XSS protection (Blade escaping)
- Rate limiting on auth/API endpoints

### File Security
- Magic bytes validation (content-based type verification)
- Dangerous extension blocking (PHP, EXE, BAT, etc.)
- Double extension attack prevention
- PHP code injection detection
- Private storage for sensitive files
- Directory traversal prevention

### Audit & Compliance
- Comprehensive activity logging
- Login/logout tracking with IP
- File access logging
- Status change audit trail
- Soft deletes for recovery

### Password Policy (Government Standard)
- Minimum 12 characters
- Requires uppercase, lowercase, number, and special character
- Password history (last 5 passwords blocked)
- Password expiry (90 days default, 60 days for admin roles)
- Common password detection
- Forced password change on first login

---

## Production Deployment

### Deployment Checklist

```markdown
Pre-Deployment:
- [ ] Set APP_ENV=production
- [ ] Set APP_DEBUG=false
- [ ] Generate new APP_KEY for production
- [ ] Configure production database (separate from dev)
- [ ] Set up Redis for cache/session/queue
- [ ] Configure mail service (SMTP)
- [ ] Set up SSL certificate
- [ ] Configure backup automation

Security:
- [ ] Change all default passwords
- [ ] Enable 2FA for admin accounts
- [ ] Configure firewall rules (ports 80, 443)
- [ ] Set proper file permissions
- [ ] Remove seeder-credentials.log
- [ ] Review IP allowlisting if applicable

Infrastructure:
- [ ] Configure Nginx/Apache
- [ ] Set up Supervisor for queue workers
- [ ] Configure cron for scheduler
- [ ] Set up monitoring (optional)
- [ ] Configure log rotation

Post-Deployment:
- [ ] Run all migrations
- [ ] Clear and rebuild caches
- [ ] Test all critical workflows
- [ ] Verify email notifications work
- [ ] Test file upload/download
```

### Nginx Configuration

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name btevta.gov.pk www.btevta.gov.pk;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name btevta.gov.pk www.btevta.gov.pk;

    root /var/www/btevta/public;
    index index.php;

    # SSL Configuration
    ssl_certificate /etc/ssl/certs/btevta.crt;
    ssl_certificate_key /etc/ssl/private/btevta.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256;
    ssl_prefer_server_ciphers off;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Gzip Compression
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml;

    # Laravel Routing
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP Processing
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Deny access to sensitive files
    location ~ /\.(?!well-known) {
        deny all;
    }

    # Cache static assets
    location ~* \.(css|js|jpg|jpeg|png|gif|ico|svg|woff|woff2)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
```

### Queue Worker (Supervisor)

Create `/etc/supervisor/conf.d/btevta-worker.conf`:

```ini
[program:btevta-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/btevta/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/btevta/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
# Start supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start btevta-worker:*
```

### Task Scheduler (Cron)

Add to crontab (`crontab -e`):

```cron
* * * * * cd /var/www/btevta && php artisan schedule:run >> /dev/null 2>&1
```

### Production Optimization

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev

# Clear old caches first if updating
php artisan optimize:clear && php artisan optimize
```

---

## Maintenance & Monitoring

### Health Check Endpoint

```
GET /up
```

Returns HTTP 200 if application is healthy.

### Scheduled Tasks

| Task | Schedule | Description |
|------|----------|-------------|
| Log Cleanup | Daily 1:00 AM | Prune logs older than 30 days |
| Backup | Daily 2:00 AM | Database and file backup |
| Password Expiry Check | Daily 6:00 AM | Notify users of expiring passwords |
| SLA Breach Check | Every 15 min | Check complaint SLA compliance |
| Cache Cleanup | Weekly | Clear expired cache entries |

### Log Files

| Log | Location | Purpose |
|-----|----------|---------|
| Application | `storage/logs/laravel.log` | General application logs |
| Worker | `storage/logs/worker.log` | Queue worker logs |
| Slow Queries | `storage/logs/slow-queries.log` | Database performance |
| Security | `storage/logs/security.log` | Auth failures, suspicious activity |

### Backup Strategy

```bash
# Database backup (daily)
mysqldump -u root -p btevta > /backup/btevta_$(date +%Y%m%d).sql

# Files backup (daily)
tar -czf /backup/btevta_files_$(date +%Y%m%d).tar.gz /var/www/btevta/storage/app

# Retention: Keep 30 days of backups
find /backup -name "btevta_*.sql" -mtime +30 -delete
find /backup -name "btevta_files_*.tar.gz" -mtime +30 -delete
```

### Monitoring Commands

```bash
# Check queue status
php artisan queue:monitor redis:default

# View failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush

# Check scheduler status
php artisan schedule:list
```

### Maintenance Mode

```bash
# Enable maintenance mode
php artisan down --render="errors::503" --secret="maintenance-bypass-token"

# Access during maintenance (add to URL)
https://btevta.gov.pk/maintenance-bypass-token

# Disable maintenance mode
php artisan up
```

---

## Troubleshooting

### Common Issues

**1. Database Connection Error**
```bash
# Check MySQL is running
sudo systemctl status mysql

# Verify credentials in .env
DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD
```

**2. Storage Permission Error**
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

**3. 500 Internal Server Error**
```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

**4. Import Failing**
- Verify Excel format matches template
- Check for duplicate CNICs
- Ensure required columns are filled
- Maximum 1000 rows per import

**5. File Upload Issues**
- Max file size: 10MB (configurable)
- Allowed: PDF, JPG, PNG, XLSX
- Check `upload_max_filesize` in php.ini

### Performance Optimization

```bash
# Production optimization
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer install --optimize-autoloader --no-dev
```

---

## Running Tests

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage
```

### Test Coverage

The project includes comprehensive tests for:
- Candidate state machine transitions
- Authorization policies (all roles)
- API endpoints (CRUD operations)
- Service classes (workflows, SLA)
- Security features (file validation)

---

## Changelog

### Version 1.5.0 (February 2026) - Pre-Departure Documents & Initial Screening

**Initial Screening (Module 2):**
- Single-review screening workflow replacing legacy 3-call system
- Consent for work verification with legal disclaimer
- Placement interest capture (Local/International)
- Target country specification for international placements
- Screening outcomes: Screened, Pending, or Deferred
- Evidence file upload support (PDF, JPG, PNG, max 10MB)
- Screening notes and reviewer tracking
- Initial Screening Dashboard with statistics
- Gate enforcement: Only screened candidates proceed to registration
- 25 comprehensive tests (13 unit + 12 feature)
- Full backward compatibility with legacy screening data

**Pre-Departure Documents (Module 1):**
- Complete document collection workflow for candidates
- 5 mandatory documents (CNIC, Passport, Domicile, FRC, PCC)
- Optional documents (Driving License, Professional License, Pre-Medical)
- Document upload with file validation (PDF, JPG, PNG, max 5MB)
- Document verification workflow (verify/reject with notes)
- Progress tracking on candidate profile
- Individual and bulk PDF/Excel reports
- Role-based access control with campus scoping

**Candidates Listing Enhancements:**
- Fixed truncated index view (restored full functionality)
- Search by Name, CNIC, TheLeap ID
- Filter by Status, Trade, Batch
- Bulk operations (status change, batch assign, export, delete)
- Consistent UI between /candidates/ and /dashboard/candidates-listing

**UI/UX Improvements:**
- Redesigned Pre-Departure Documents page with Tailwind CSS
- Modern card-based document upload interface
- Gradient section headers (red for mandatory, blue for optional)
- Responsive design for mobile and desktop
- Verify/Reject modals with proper styling
- Auto-dismiss success notifications

**Technical Improvements:**
- Added `PreDepartureDocumentService` for business logic
- Added `PreDepartureDocumentPolicy` with defensive null handling
- Added `DocumentChecklistsSeeder` for initial document types
- Fixed route model binding for nested resources
- Role alias handling (admin ↔ super_admin equivalence)

### Version 1.4.0 (December 2025) - Architecture & Code Quality

**PHP 8.1+ Enums:**
- `CandidateStatus` - Type-safe candidate workflow states
- `TrainingStatus` - Training lifecycle management
- `VisaStage` - Visa processing stages with metadata
- `ComplaintPriority` - SLA-based priority levels
- `ComplaintStatus` - Complaint workflow states

**API Resources:**
- `CandidateResource` - Consistent candidate JSON responses
- `VisaProcessResource` - Structured visa data
- `DepartureResource` - Departure tracking responses
- `RemittanceResource` - Remittance data with formatting

**Developer Documentation:**
- Event/Listener architecture guide
- OpenAPI 3.1 specification
- README Developer Resources section
- Code usage examples

**Code Cleanup:**
- Removed deprecated route comments
- Cleaned up broken route references
- Improved code organization

### Version 1.3.0 (December 2025) - Feature Enhancements

**Real-time Notifications:**
- WebSocket broadcasting for status changes
- Toast notification system
- Polling fallback for compatibility

**Dashboard Analytics:**
- Interactive Chart.js widgets
- Live KPI cards
- Performance metrics table

**Bulk Operations:**
- Multi-select with select all
- Bulk status update
- Bulk batch/campus assignment
- Bulk export (CSV, Excel, PDF)
- Bulk delete (admin only)

**Mobile Improvements:**
- Responsive sidebar overlay
- Bottom navigation bar
- Touch-friendly sizing
- Safe area support

### Version 1.2.0 (December 2025) - Security Audit

- Security hardening (9 critical fixes)
- State machine validation
- Magic bytes file validation
- Performance optimization
- Comprehensive test coverage

### Version 1.0.0 (October 2025)

- Initial release
- 10 core modules
- Complete candidate lifecycle

---

## Developer Resources

### Code Architecture

The application follows modern PHP 8.1+ patterns:

| Component | Location | Description |
|-----------|----------|-------------|
| **Enums** | `app/Enums/` | Type-safe status constants (CandidateStatus, VisaStage, etc.) |
| **API Resources** | `app/Http/Resources/` | Consistent JSON API responses |
| **Events** | `app/Events/` | Real-time broadcasting events |
| **Services** | `app/Services/` | Business logic layer |

### Documentation Files

| Document | Description |
|----------|-------------|
| [`docs/EVENTS_AND_LISTENERS.md`](docs/EVENTS_AND_LISTENERS.md) | Event/Listener architecture and WebSocket setup |
| [`docs/openapi.yaml`](docs/openapi.yaml) | OpenAPI 3.1 specification for API |
| [`docs/API_REMITTANCE.md`](docs/API_REMITTANCE.md) | Remittance API documentation |
| [`docs/REMITTANCE_USER_GUIDE.md`](docs/REMITTANCE_USER_GUIDE.md) | Remittance module user guide |
| [`docs/REMITTANCE_DEVELOPER_GUIDE.md`](docs/REMITTANCE_DEVELOPER_GUIDE.md) | Remittance module developer guide |
| [`docs/REMITTANCE_SETUP_GUIDE.md`](docs/REMITTANCE_SETUP_GUIDE.md) | Remittance setup & configuration |
| [`docs/REMITTANCE_ADMIN_MANUAL.md`](docs/REMITTANCE_ADMIN_MANUAL.md) | Administrator operations manual |
| [`docs/TESTING_IMPROVEMENT_REPORT.md`](docs/TESTING_IMPROVEMENT_REPORT.md) | Test coverage and validation report |

### Using Enums

```php
use App\Enums\CandidateStatus;

// Get status with metadata
$status = CandidateStatus::TRAINING;
echo $status->label();  // "Training"
echo $status->color();  // "warning"

// Check transitions
if ($status->canTransitionTo(CandidateStatus::VISA_PROCESS)) {
    $candidate->update(['status' => CandidateStatus::VISA_PROCESS->value]);
}

// Get all active statuses for dropdown
$options = CandidateStatus::toArray();
```

### Using API Resources

```php
use App\Http\Resources\CandidateResource;
use App\Http\Resources\CandidateCollection;

// Single resource
return new CandidateResource($candidate);

// Collection with pagination
return new CandidateCollection(Candidate::paginate(20));
```

---

## Support

**Technical Support:** support@btevta.gov.pk

**Issue Tracker:** GitHub Issues

---

**Developed for TheLeap - Board of Technical Education & Vocational Training Authority, Punjab**

Product Conceived by: TheLeap | Developed by: Development Team

---

*Copyright 2025 TheLeap. All rights reserved.*
