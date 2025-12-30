# WASL - Workforce Abroad Skills & Linkages

## BTEVTA Overseas Employment Management System

A comprehensive digital platform for managing the complete candidate lifecycle from BTEVTA listing through overseas deployment, post-departure tracking, and remittance management.

**Version:** 1.3.0 | **Status:** Production Ready | **Last Updated:** December 2025

---

## Table of Contents

- [Overview](#overview)
- [Key Features](#key-features)
- [System Requirements](#system-requirements)
- [Installation](#installation)
- [Quick Start Guide](#quick-start-guide)
- [User Roles](#user-roles)
- [Module Documentation](#module-documentation)
- [API Reference](#api-reference)
- [Tutorials](#tutorials)
- [Security Features](#security-features)
- [Troubleshooting](#troubleshooting)
- [Changelog](#changelog)

---

## Overview

WASL (Workforce Abroad Skills & Linkages) is a Laravel-based enterprise application designed for BTEVTA (Board of Technical Education & Vocational Training Authority, Punjab) to streamline overseas employment management. The system tracks candidates from initial listing through training, visa processing, departure, and post-deployment monitoring.

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

## Key Features

### Core Modules (10 Tabs)

| # | Module | Description |
|---|--------|-------------|
| 1 | **Candidates Listing** | Import BTEVTA candidates, auto-assign batches, bulk operations |
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

### 1. Clone Repository

```bash
git clone https://github.com/haseebayazi/btevta.git
cd btevta
```

### 2. Install Dependencies

```bash
composer install
npm install && npm run build
```

### 3. Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configure Database

Edit `.env` file:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=btevta
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 5. Run Migrations & Seeders

```bash
php artisan migrate
php artisan db:seed
```

### 6. Start Development Server

```bash
php artisan serve
```

Access at: `http://localhost:8000`

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
?per_page=25         # Items per page
?status=training     # Filter by status
?campus_id=1         # Filter by campus
?trade_id=2          # Filter by trade
?search=john         # Search term
```

---

## Tutorials

### Tutorial 1: Import Candidates from BTEVTA

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
- Policy-based authorization (23 policies)
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

## Support

**Technical Support:** support@btevta.gov.pk

**Issue Tracker:** GitHub Issues

---

**Developed for BTEVTA - Board of Technical Education & Vocational Training Authority, Punjab**

Product Conceived by: BTEVTA | Developed by: Development Team

---

*Copyright 2025 BTEVTA. All rights reserved.*
