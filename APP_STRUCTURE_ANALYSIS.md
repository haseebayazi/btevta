# BTEVTA Overseas Employment Management System - Complete Application Structure

## 1. APPLICATION OVERVIEW

**Name:** BTEVTA System
**Type:** Overseas Employment Management Platform
**Framework:** Laravel 11.x
**PHP Version:** 8.2+
**Database:** MySQL 8.0+
**License:** Proprietary (Copyright © 2025 BTEVTA, Punjab)
**Version:** 1.0.1 (November 2025)

**Key Purpose:** Complete candidate lifecycle management from listing to overseas deployment and post-departure tracking.

---

## 2. ALL IMPLEMENTED MODULES/FEATURES (10 Core + Admin)

### Core Modules (10 Dashboard Tabs)
1. **Candidates Listing** - Import and manage BTEVTA candidates with role-based filtering
2. **Candidate Screening** - Multi-call tracking system (desk, call, physical)
3. **Registration at Campus** - Document management and undertakings collection
4. **Training Management** - Attendance, assessments, certificates, and batch management
5. **Visa Processing** - Complete pre-departure workflow (interview, medical, visa, ticket)
6. **Departure Tracking** - Post-departure compliance monitoring (90-day reports, salary tracking)
7. **Correspondence** - Official communication tracking with reply status
8. **Complaints Redressal** - SLA-based complaint management with escalation
9. **Document Archive** - Centralized document repository with versioning and expiry tracking
10. **Reporting Module** - Dynamic report generation with custom filters and exports

### Admin Features
- Campus management (create, edit, toggle status)
- OEP (Overseas Employment Promoter) management
- Trade management (skill classification)
- Batch management (planning and activation)
- User management with role-based access
- System settings and audit logs
- Activity logging and audit trail

---

## 3. COMPLETE DATABASE MODELS (23 Models)

### Core Entity Models
1. **User** - System users with roles (admin, campus_admin, oep, trainer, candidate)
2. **Candidate** - Core candidate information with lifecycle tracking
3. **Campus** - Training campus/institute details
4. **Oep** - Overseas Employment Promoter information
5. **Trade** - Available training trades/skills
6. **Batch** - Training batches with capacity management

### Screening & Registration Models
7. **CandidateScreening** - Multi-stage screening records (desk, call, physical)
8. **RegistrationDocument** - Candidate document uploads during registration
9. **Undertaking** - Legal undertakings from candidates
10. **NextOfKin** - Emergency contact information

### Training Models
11. **TrainingClass** - Training class sessions
12. **TrainingAttendance** - Daily attendance records
13. **TrainingAssessment** - Test scores and evaluations
14. **TrainingCertificate** - Certificate issuance records
15. **Instructor** - Trainer/Instructor information

### Visa & Departure Models
16. **VisaProcess** - Visa workflow tracking (interview, medical, biometric, visa, ticket)
17. **Departure** - Post-departure monitoring (iqama, absher, salary, 90-day compliance)

### Communication & Complaint Models
18. **Correspondence** - Official communications tracking
19. **Complaint** - Complaint management with SLA tracking
20. **ComplaintUpdate** - Complaint status updates and notes
21. **ComplaintEvidence** - Supporting evidence for complaints

### System Models
22. **DocumentArchive** - Global document repository
23. **SystemSetting** - Application configuration values

---

## 4. ALL CONTROLLERS (21 Controllers + Base)

### Authentication & Core
- **AuthController** - Login, logout, password reset
- **DashboardController** - Main dashboard with 10 tabs

### Main Module Controllers
- **CandidateController** - CRUD + profile, timeline, status, export, photo upload
- **ScreeningController** - Screening workflow with call logging
- **RegistrationController** - Registration with document upload and undertaking
- **TrainingController** - Training with attendance, assessment, and certificates
- **VisaProcessingController** - Visa workflow with multiple stages
- **DepartureController** - Departure tracking with compliance monitoring
- **CorrespondenceController** - Communication management
- **ComplaintController** - Complaint management with workflow
- **DocumentArchiveController** - Document repository with versioning
- **ReportController** - Report generation and analytics

### Admin Controllers
- **CampusController** - Campus management
- **OepController** - OEP management
- **TradeController** - Trade management
- **BatchController** - Batch management
- **UserController** - User management and settings
- **InstructorController** - Instructor/Trainer management
- **TrainingClassController** - Training class management

### Utility Controllers
- **ImportController** - Excel import for candidates
- **Controller** - Base controller class

---

## 5. ROUTE STRUCTURE (192 Total Routes)

### Route Organization by Module

**Web Routes (185 routes)**
```
Authentication Routes (7):
- /login, /logout, /forgot-password, /reset-password

Dashboard Routes (11):
- /dashboard, + 10 dashboard tabs

Candidates (10):
- REST resource + profile, timeline, status, export, photo upload

Import/Export (3):
- Form, process, template download

Screening (7):
- REST resource + pending, call log, outcome, export

Registration (8):
- REST resource + documents, next-of-kin, undertaking, complete

Training (16):
- REST resource + attendance (bulk), assessment, certificates, reports

Visa Processing (20):
- REST resource + interview, medical, visa, ticket + reports

Departure (19):
- REST resource + record, iqama, absher, WPS/QIWA, salary, compliance

Correspondence (6):
- REST resource + pending reply tracking

Complaints (16):
- REST resource + assignment, escalation, evidence, analytics, export

Document Archive (21):
- REST resource + versions, search, expiring, bulk upload, reports

Reports (12):
- 7 report types + custom builder + export

Admin Routes (17):
- Campuses, OEPs, trades, batches, users, settings, audit logs

Instructors (5):
- REST resource routes

Training Classes (7):
- REST resource + assign/remove candidates
```

**API Routes (7 routes)**
- `/api/v1/candidates/search`
- `/api/v1/campuses/list`
- `/api/v1/oeps/list`
- `/api/v1/trades/list`
- `/api/v1/batches/by-campus/{campus}`
- `/api/v1/notifications`
- `/api/v1/notifications/{notification}/mark-read`

---

## 6. MIDDLEWARE & POLICIES

### Custom Middleware
- **RoleMiddleware** - Role-based access control (admin, campus_admin, oep, trainer)
- Standard Laravel middleware (Auth, CSRF, TrimStrings, etc.)

### Authorization Policies (15 Policies)
- CandidatePolicy
- ScreeningPolicy
- RegistrationPolicy
- TrainingPolicy
- TrainingClassPolicy
- VisaProcessingPolicy
- DeparturePolicy
- CorrespondencePolicy
- ComplaintPolicy
- DocumentArchivePolicy
- ReportPolicy
- CampusPolicy
- TradePolicy
- OepPolicy
- BatchPolicy
- UserPolicy
- InstructorPolicy
- ImportPolicy

---

## 7. SERVICE LAYER (8 Services)

Business logic abstraction services:
1. **DocumentArchiveService** - Document management operations
2. **TrainingService** - Training workflow operations
3. **VisaProcessingService** - Visa processing workflow
4. **DepartureService** - Departure and compliance tracking
5. **ScreeningService** - Screening workflow operations
6. **RegistrationService** - Registration and document handling
7. **NotificationService** - Email/SMS notifications
8. **ComplaintService** - Complaint management operations

---

## 8. DATABASE SCHEMA STRUCTURE

### Core Tables
- `users` (id, name, email, password, role, campus_id, oep_id, is_active, ...)
- `candidates` (id, btevta_id, cnic, name, trade_id, campus_id, batch_id, status, ...)
- `campuses` (id, name, code, address, city, phone, email, is_active, ...)
- `oeps` (id, name, code, company_name, country, city, is_active, ...)
- `trades` (id, code, name, duration_months, is_active, ...)
- `batches` (id, uuid, batch_code, trade_id, campus_id, capacity, status, ...)

### Screening & Registration
- `candidate_screenings` (id, candidate_id, screening_type, status, date, remarks, ...)
- `registration_documents` (id, candidate_id, document_type, file_path, upload_date, ...)
- `undertakings` (id, candidate_id, undertaken_date, document_path, ...)
- `next_of_kins` (id, candidate_id, name, relation, contact, address, ...)

### Training
- `training_classes` (id, batch_id, instructor_id, start_time, end_time, ...)
- `training_attendances` (id, candidate_id, batch_id, date, status, ...)
- `training_assessments` (id, candidate_id, batch_id, score, grade, ...)
- `training_certificates` (id, candidate_id, grade, issue_date, ...)
- `instructors` (id, name, cnic, campus_id, trade_id, status, ...)

### Visa & Departure
- `visa_processes` (id, candidate_id, interview_date, medical_date, visa_number, ...)
- `departures` (id, candidate_id, departure_date, flight_number, iqama_number, ...)

### Communication & Complaints
- `correspondences` (id, campus_id, oep_id, subject, content, reply_date, ...)
- `complaints` (id, candidate_id, complaint_number, category, priority, status, ...)
- `complaint_updates` (id, complaint_id, update_text, created_by, ...)
- `complaint_evidence` (id, complaint_id, file_path, upload_date, ...)

### Archive & System
- `document_archives` (id, document_name, document_type, file_path, expiry_date, ...)
- `system_settings` (id, setting_key, setting_value, ...)
- `activity_log` - Spatie activity logging

---

## 9. CONFIGURATION FILES

### Key Configuration
- `config/app.php` - Application name: "BTEVTA", timezone: "Asia/Karachi"
- `config/auth.php` - Authentication configuration
- `config/database.php` - Database connection
- `config/filesystems.php` - File storage configuration
- `config/hashing.php` - Password hashing (bcrypt)
- `config/activitylog.php` - Activity logging configuration

### Environment Configuration
```
APP_NAME=BTEVTA System
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost
APP_TIMEZONE=Asia/Karachi
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=btevta_system
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
```

---

## 10. VIEWS & FRONTEND STRUCTURE

### View Organization (18 Directory Groups)
```
resources/views/
├── layouts/              - Master layout templates
├── auth/                 - Login, forgot password, reset password
├── dashboard/            - Dashboard + 10 module tabs
├── candidates/           - Candidate management views
├── screening/            - Screening workflow views
├── registration/         - Registration & documents
├── training/             - Training & attendance views
├── visa-processing/      - Visa workflow views
├── departure/            - Departure tracking views
├── correspondence/       - Communication views
├── complaints/           - Complaint management views
├── document-archive/     - Document repository views
├── reports/              - Report generation views
├── admin/                - Admin panel views
├── instructors/          - Instructor management
├── classes/              - Training class views
├── import/               - Import/export views
└── COMPLETE_VIEWS_MANIFEST.md - View documentation
```

### Frontend Technologies
- Tailwind CSS - Utility-first CSS framework
- Alpine.js - Lightweight JavaScript framework
- Blade templating - Laravel's template engine

---

## 11. KEY FEATURES BY MODULE

### Candidates Management
- Import from Excel (BTEVTA template)
- Profile management with photo upload
- Status lifecycle tracking
- Campus and OEP assignment
- Timeline view
- Export functionality
- Role-based filtering

### Screening System
- Multi-type screening (desk, call, physical)
- Call logging with remarks
- Screening outcome recording
- Pending screening view
- Export reports

### Registration Process
- Document upload and management
- Multiple document types support
- Next of kin information collection
- Undertaking acceptance
- Registration completion workflow

### Training Module
- Batch management with capacity tracking
- Daily attendance marking (single & bulk)
- Assessment scoring and grading
- Certificate generation and download
- Batch performance reports
- Attendance analysis

### Visa Processing
- Multi-stage workflow tracking
- Interview, medical, trade test records
- Biometric and visa tracking
- Ticket upload and management
- Timeline reporting
- Overdue tracking

### Departure & Compliance
- Departure date and flight tracking
- Pre-departure briefing
- Iqama registration
- Absher registration
- WPS/QIWA employment tracking
- First salary recording
- 90-day compliance reporting
- Issue tracking and resolution

### Correspondence
- Official communication tracking
- Pending reply monitoring
- Communication register
- Date tracking

### Complaints Management
- SLA-based complaint tracking
- Priority and category classification
- Assignment workflow
- Evidence attachment
- Escalation tracking
- Resolution notes
- Complaint analytics and reporting

### Document Archive
- Centralized document repository
- Version control and history
- Expiry date tracking
- Expiry alerts and reminders
- Document search and filtering
- Access logging
- Bulk upload capability
- Archive and restore functionality

### Reports
- Candidate profile reports
- Batch summary reports
- Campus performance analysis
- OEP performance metrics
- Visa timeline reports
- Training statistics
- Complaint analysis
- Custom report builder
- Multiple export formats (Excel, CSV, PDF)

---

## 12. SECURITY FEATURES

### Authentication & Authorization
- Role-based access control (RBAC)
- Session security with timeout
- Password hashing with bcrypt
- Email verification support
- Password reset functionality

### Data Protection
- CSRF protection on all forms
- SQL injection prevention
- XSS protection
- Secure file uploads with validation
- Hidden sensitive fields (CNIC, passport, salary amounts)
- Soft deletes for data preservation

### Audit & Compliance
- Complete activity logging (Spatie)
- User action tracking
- Change history with timestamps
- Created by/Updated by tracking
- Audit logs with IP tracking
- SLA monitoring for complaints

### Rate Limiting & Throttling
- Login attempts: 5/minute
- Password reset: 3/minute
- File uploads: 30/minute
- Bulk operations: 30/minute
- Report generation: 5/minute
- Custom report builder: 3/minute
- API requests: 60/minute

---

## 13. TECHNOLOGY STACK

### Backend
- **Framework:** Laravel 11.x
- **Language:** PHP 8.2+
- **Database:** MySQL 8.0+
- **Package Manager:** Composer

### Database & Caching
- MySQL 8.0+ for persistent storage
- Laravel Cache (configurable Redis support)
- Query optimization with eager loading

### File Processing
- PhpSpreadsheet - Excel import/export
- Intervention/Image - Image processing
- DomPDF - PDF generation
- Spatie Activity Log - Audit trail

### Frontend
- Blade Templating Engine
- Tailwind CSS - Responsive design
- Alpine.js - Interactive components
- Vite - Asset bundling

### Additional Services
- Email notifications (SMTP)
- Optional SMS/WhatsApp integration
- Storage linked for file uploads
- Scheduled tasks via Cron

---

## 14. DEPLOYMENT & INSTALLATION

### Requirements
- PHP 8.2+
- MySQL 8.0+
- Composer
- Node.js & NPM
- Apache/Nginx
- SSL Certificate (recommended)

### Installation Steps
1. Create PHP application on hosting
2. Upload files via SSH/FTP
3. Install dependencies: `composer install --optimize-autoloader --no-dev`
4. Configure `.env` with database credentials
5. Generate app key: `php artisan key:generate`
6. Run migrations: `php artisan migrate --force`
7. Set file permissions on storage/bootstrap
8. Create storage link: `php artisan storage:link`
9. Optimize for production with config/route/view caching

### Default Credentials
- **Admin:** admin@btevta.gov.pk / Admin@123
- **Campus Admin:** ttc.rawalpindi.admin@btevta.gov.pk / Campus@123
- **OEP:** info@alkhabeer.com / Oep@123

---

## 15. PROJECT STATISTICS

### Code Metrics
- **Total Models:** 23
- **Total Controllers:** 21 + Base
- **Total Routes:** 192 (185 Web + 7 API)
- **Total Migrations:** 24+
- **Authorization Policies:** 18
- **Service Classes:** 8
- **Middleware Classes:** 11
- **View Directories:** 18

### Database Schema
- **Core Tables:** 23+
- **Relationship Types:** One-to-Many, Many-to-Many (through), Has-Many-Through
- **Soft Deletes:** Enabled on all sensitive tables
- **Audit Trail:** Complete activity logging

### Performance Optimizations
- Route model binding
- Eager loading with relationships
- Query caching for dropdown data (24 hours)
- Indexed database columns for search
- Ready for route caching (90% improvement)
- Pagination with 20 items per page

---

## 16. RECENT UPDATES

### Version 1.0.1 (November 2025)
- Code cleanup: Removed duplicate view files
- All views properly organized in `/resources/views/`
- Phase 4 authorization policies added
- Missing foreign key constraints added
- Performance indexes added
- Unique constraints implemented
- Phase 2 complete with full implementation

### Version 1.0.0 (October 2025)
- Initial release
- All 10 core modules implemented
- Complete candidate lifecycle management

---

## 17. BRANDING & CONFIGURATION

### Application Branding
- **Name:** BTEVTA System
- **Full Name:** BTEVTA Overseas Employment Management System
- **Organization:** Board of Technical Education & Vocational Training Authority, Punjab
- **Support Email:** support@btevta.gov.pk
- **Website:** www.btevta.gov.pk
- **Contact:** +92-51-9201596

### Timezone
- **Default Timezone:** Asia/Karachi (Pakistan Standard Time)

### Application Settings
- Max file upload: 20MB
- Allowed file types: pdf, jpg, jpeg, png, doc, docx, xlsx
- Default batch capacity: 30 candidates
- Default passing score: 60
- Session timeout: Configurable

---

## SUMMARY

The BTEVTA Overseas Employment Management System is a comprehensive Laravel-based platform with:

✅ **10 Core Modules** for complete candidate lifecycle management
✅ **23 Database Models** with rich relationships
✅ **21+ Controllers** with specialized business logic
✅ **192 Routes** (185 Web + 7 API)
✅ **18 Policies** for role-based authorization
✅ **8 Service Classes** for business logic separation
✅ **Complete Audit Trail** with activity logging
✅ **SLA Management** for complaints
✅ **Document Versioning** with expiry tracking
✅ **Dynamic Reporting** with custom filters
✅ **Role-Based Access** (Admin, Campus Admin, OEP, Trainer, Candidate)
✅ **Security First** with CSRF, XSS, SQL injection prevention
✅ **Performance Optimized** with caching and eager loading

The application is production-ready with comprehensive documentation, security features, and all necessary modules for managing overseas employment deployments.

