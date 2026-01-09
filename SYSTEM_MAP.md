# SYSTEM_MAP.md

> **WASL - Integrated Digital Platform for Overseas Employment & Remittance Lifecycle Management**
>
> This is the single authoritative source of truth for the entire system architecture.
> All changes MUST update this document first before implementation.

---

## 1. Project Overview

| Field | Value |
|-------|-------|
| **Project Name** | WASL (وصل) |
| **Full Name** | WASL - Integrated Digital Platform for Overseas Employment & Remittance Lifecycle Management |
| **Tagline** | Connecting Talent, Opportunity, and Remittance |
| **Purpose** | BTEVTA Overseas Employment Management System - Tracks candidates from initial registration through training, visa processing, departure, and post-deployment remittance tracking |
| **Domain** | Overseas Employment Management, Vocational Training, Workforce Deployment |
| **Conceived By** | AMAN Innovatia |
| **Developed By** | The LEAP |

### Key Business Modules

1. **Candidate Management** - Registration, profile management, status tracking
2. **Screening** - Desk, call, and physical screening workflows
3. **Registration** - Document upload, next-of-kin, undertakings
4. **Training** - Classes, attendance, assessments, certificates
5. **Visa Processing** - Interview, trade test, TAKAMOL, medical, biometric, visa issuance
6. **Departure** - Pre-departure briefing, travel coordination, 90-day compliance
7. **Remittance Tracking** - Post-deployment financial tracking, beneficiary management
8. **Complaints & Correspondence** - Issue resolution, official communications
9. **Reporting & Analytics** - Comprehensive reporting across all modules

---

## 2. Laravel & Environment Details

| Field | Value |
|-------|-------|
| **Laravel Version** | 11.x |
| **PHP Version** | ^8.2 |
| **Database** | MySQL (default) |
| **Cache Driver** | Database (default) |
| **Session Driver** | Database |
| **Queue Driver** | Sync (no background jobs configured) |
| **Authentication** | Laravel Sanctum (API) + Session (Web) |

### Core Dependencies

| Package | Version | Purpose |
|---------|---------|---------|
| `laravel/framework` | ^11.0 | Core framework |
| `laravel/sanctum` | ^4.2 | API token authentication |
| `barryvdh/laravel-dompdf` | ^2.2 | PDF generation |
| `maatwebsite/excel` | ^3.1 | Excel import/export |
| `spatie/laravel-activitylog` | ^4.8 | Activity logging |
| `spatie/laravel-permission` | ^6.4 | Role-based permissions |
| `intervention/image` | ^3.5 | Image processing |
| `guzzlehttp/guzzle` | ^7.8 | HTTP client |

### Dev Dependencies

| Package | Version | Purpose |
|---------|---------|---------|
| `phpunit/phpunit` | ^11.0 | Testing |
| `laravel/dusk` | ^8.3 | Browser testing |
| `laravel/pint` | ^1.13 | Code styling |
| `spatie/laravel-ignition` | ^2.4 | Error page |

---

## 3. Directory & File Structure

```
btevta/
├── app/
│   ├── Http/
│   │   ├── Controllers/          # 37 controllers (29 web + 8 API)
│   │   │   ├── Api/              # API-specific controllers (8)
│   │   │   └── *.php             # Web controllers (29)
│   │   └── Middleware/           # 14 custom middleware classes
│   ├── Models/                   # 34 Eloquent models
│   ├── Observers/                # Model observers (UserPasswordObserver)
│   ├── Providers/                # Service providers
│   ├── Services/                 # 14 business logic services
│   └── Helpers/                  # Helper functions (helpers.php)
├── bootstrap/
│   └── app.php                   # Application bootstrap with route bindings
├── config/                       # Configuration files
├── database/
│   └── migrations/               # 60 migration files
├── resources/
│   └── views/                    # 187 Blade templates
├── routes/
│   ├── web.php                   # ~185 web routes
│   ├── api.php                   # ~70 API routes
│   └── console.php               # Console commands
├── storage/                      # Uploaded files, logs, cache
├── tests/                        # PHPUnit tests
└── composer.json                 # Dependencies
```

---

## 4. Database Schema

### Tables (34 tables total)

#### Core Entity Tables

| Table | Description | Key Columns |
|-------|-------------|-------------|
| `users` | System users (all roles) | id, name, email, role, campus_id, oep_id, is_active |
| `candidates` | Training candidates | id, btevta_id, cnic, name, status, training_status, trade_id, campus_id, batch_id |
| `campuses` | Training campuses | id, name, code, city, is_active |
| `oeps` | Overseas Employment Promoters | id, name, company_name, registration_number, is_active |
| `trades` | Training trades/skills | id, code, name, category, duration_months, is_active |
| `batches` | Training batches | id, name, trade_id, campus_id, oep_id, start_date, status |

#### Workflow Tables

| Table | Description | Key Columns |
|-------|-------------|-------------|
| `candidate_screenings` | Screening records | id, candidate_id, screening_type, status, screening_date |
| `registration_documents` | Uploaded documents | id, candidate_id, document_type, file_path, is_verified |
| `undertakings` | Signed undertakings | id, candidate_id, undertaking_type, is_completed |
| `next_of_kins` | Emergency contacts | id, candidate_id, name, relationship, phone |
| `training_classes` | Training class schedules | id, name, instructor_id, batch_id, status |
| `training_attendances` | Attendance records | id, candidate_id, date, status |
| `training_assessments` | Assessment scores | id, candidate_id, assessment_type, score, result |
| `training_certificates` | Issued certificates | id, candidate_id, certificate_number, issue_date |
| `visa_processes` | Visa processing workflow | id, candidate_id, interview_date, trade_test_passed, visa_issued |
| `departures` | Departure records | id, candidate_id, departure_date, flight_number, 90_day_compliance |

#### Remittance Tables

| Table | Description | Key Columns |
|-------|-------------|-------------|
| `remittances` | Remittance transactions | id, candidate_id, beneficiary_id, amount, currency, transfer_date, status |
| `remittance_beneficiaries` | Beneficiary records | id, candidate_id, name, relationship, account_number, is_primary |
| `remittance_receipts` | Transfer receipts | id, remittance_id, file_path, is_verified |
| `remittance_alerts` | Compliance alerts | id, candidate_id, alert_type, message, status |
| `remittance_usage_breakdowns` | Usage categories | id, remittance_id, category, amount |

#### Supporting Tables

| Table | Description | Key Columns |
|-------|-------------|-------------|
| `complaints` | Complaint records | id, candidate_id, subject, status, resolution_date |
| `complaint_updates` | Complaint updates | id, complaint_id, update_text, created_by |
| `complaint_evidence` | Complaint attachments | id, complaint_id, file_path |
| `correspondence` | Official communications | id, campus_id, oep_id, subject, correspondence_date |
| `document_archives` | Archived documents | id, document_name, document_type, file_path, candidate_id |
| `instructors` | Training instructors | id, name, trade_id, campus_id, is_active |
| `visa_partners` | Visa processing partners | id, name, contact_info |

#### System Tables

| Table | Description | Key Columns |
|-------|-------------|-------------|
| `activity_log` | Spatie activity logs | id, log_name, description, subject_type, causer_id |
| `system_settings` | System configuration | id, setting_key, setting_value |
| `password_histories` | Password history (security) | id, user_id, password_hash |
| `sessions` | User sessions | id, user_id, ip_address, last_activity |
| `cache` | Cache storage | key, value, expiration |
| `notifications` | Laravel notifications | id, type, notifiable_type, data |
| `personal_access_tokens` | Sanctum API tokens | id, tokenable_type, name, token |

### Key Status Enums

#### Candidate Status (Workflow Stage)
```
new → screening → registered → training → visa_process → ready → departed
                                                              ↓
Terminal States: rejected, dropped, returned
```

#### Candidate Training Status (Sub-detail)
```
not_started → in_progress → completed
                         ↓
            Terminal: failed, dropped
```

#### Batch Status
```
planned → active → completed
               ↓
          cancelled
```

#### Complaint Status
```
open → in_progress → escalated → resolved → closed
                              ↓
                          reopened
```

---

## 5. Eloquent Models

### Models (34 total)

| Model | Table | Soft Deletes | Key Relationships |
|-------|-------|--------------|-------------------|
| `User` | users | Yes | belongsTo: Campus, Oep, VisaPartner |
| `Candidate` | candidates | Yes | belongsTo: Trade, Campus, Batch, Oep; hasMany: Screenings, Documents, Attendances, Assessments, Remittances, Complaints |
| `Campus` | campuses | Yes | hasMany: Users, Candidates, Batches |
| `Oep` | oeps | Yes | hasMany: Users, Candidates, Batches |
| `Trade` | trades | Yes | hasMany: Candidates, Batches, Instructors |
| `Batch` | batches | Yes | belongsTo: Trade, Campus, Oep; hasMany: Candidates |
| `CandidateScreening` | candidate_screenings | No | belongsTo: Candidate |
| `RegistrationDocument` | registration_documents | No | belongsTo: Candidate |
| `Undertaking` | undertakings | No | belongsTo: Candidate |
| `NextOfKin` | next_of_kins | No | belongsTo: Candidate |
| `TrainingClass` | training_classes | Yes | belongsTo: Instructor, Batch; belongsToMany: Candidates |
| `TrainingAttendance` | training_attendances | No | belongsTo: Candidate |
| `TrainingAssessment` | training_assessments | No | belongsTo: Candidate |
| `TrainingCertificate` | training_certificates | No | belongsTo: Candidate |
| `TrainingSchedule` | training_schedules | No | belongsTo: TrainingClass |
| `Instructor` | instructors | Yes | belongsTo: Trade, Campus; hasMany: TrainingClasses |
| `VisaProcess` | visa_processes | No | belongsTo: Candidate |
| `VisaPartner` | visa_partners | Yes | hasMany: Candidates |
| `Departure` | departures | No | belongsTo: Candidate; hasMany: Remittances |
| `Remittance` | remittances | Yes | belongsTo: Candidate, Departure, Beneficiary |
| `RemittanceBeneficiary` | remittance_beneficiaries | Yes | belongsTo: Candidate; hasMany: Remittances |
| `RemittanceReceipt` | remittance_receipts | No | belongsTo: Remittance |
| `RemittanceAlert` | remittance_alerts | No | belongsTo: Candidate |
| `RemittanceUsageBreakdown` | remittance_usage_breakdowns | No | belongsTo: Remittance |
| `Complaint` | complaints | Yes | belongsTo: Candidate, Campus, Oep; hasMany: Updates, Evidence |
| `ComplaintUpdate` | complaint_updates | No | belongsTo: Complaint |
| `ComplaintEvidence` | complaint_evidence | No | belongsTo: Complaint |
| `Correspondence` | correspondence | Yes | belongsTo: Campus, Oep |
| `DocumentArchive` | document_archives | Yes | belongsTo: Candidate |
| `SystemSetting` | system_settings | No | - |
| `PasswordHistory` | password_histories | No | belongsTo: User |
| `CampusEquipment` | campus_equipment | No | belongsTo: Campus |
| `EquipmentUsageLog` | equipment_usage_logs | No | belongsTo: CampusEquipment |
| `CampusKpi` | campus_kpis | No | belongsTo: Campus |

### Key Model Constants

#### User Roles
```php
ROLE_SUPER_ADMIN = 'super_admin'
ROLE_ADMIN = 'admin'
ROLE_PROJECT_DIRECTOR = 'project_director'
ROLE_CAMPUS_ADMIN = 'campus_admin'
ROLE_TRAINER = 'trainer'
ROLE_INSTRUCTOR = 'instructor'
ROLE_OEP = 'oep'
ROLE_VISA_PARTNER = 'visa_partner'
ROLE_CANDIDATE = 'candidate'
ROLE_VIEWER = 'viewer'
ROLE_STAFF = 'staff'
```

#### Candidate Status Constants
```php
STATUS_NEW = 'new'
STATUS_SCREENING = 'screening'
STATUS_REGISTERED = 'registered'
STATUS_TRAINING = 'training'
STATUS_VISA_PROCESS = 'visa_process'
STATUS_READY = 'ready'
STATUS_DEPARTED = 'departed'
STATUS_REJECTED = 'rejected'
STATUS_DROPPED = 'dropped'
STATUS_RETURNED = 'returned'
```

---

## 6. Routes Map

### Web Routes (~185 routes)

#### Authentication Routes (Guest Only)
| Method | URI | Controller@Action | Name |
|--------|-----|-------------------|------|
| GET | `/login` | AuthController@showLogin | login |
| POST | `/login` | AuthController@login | login.attempt |
| POST | `/logout` | AuthController@logout | logout |
| GET | `/forgot-password` | AuthController@showForgotPassword | password.request |
| POST | `/forgot-password` | AuthController@sendResetLink | password.email |
| GET | `/reset-password/{token}` | AuthController@showResetPassword | password.reset |
| POST | `/reset-password` | AuthController@resetPassword | password.update |
| GET | `/password/force-change` | AuthController@showForcePasswordChange | password.force-change |
| POST | `/password/force-change` | AuthController@updateForcePasswordChange | password.force-change.update |

#### Dashboard Routes
| Method | URI | Controller@Action | Name |
|--------|-----|-------------------|------|
| GET | `/dashboard` | DashboardController@index | dashboard |
| GET | `/dashboard/candidates-listing` | DashboardController@candidatesListing | dashboard.candidates-listing |
| GET | `/dashboard/screening` | DashboardController@screening | dashboard.screening |
| GET | `/dashboard/registration` | DashboardController@registration | dashboard.registration |
| GET | `/dashboard/training` | DashboardController@training | dashboard.training |
| GET | `/dashboard/visa-processing` | DashboardController@visaProcessing | dashboard.visa-processing |
| GET | `/dashboard/departure` | DashboardController@departure | dashboard.departure |
| GET | `/dashboard/correspondence` | DashboardController@correspondence | dashboard.correspondence |
| GET | `/dashboard/complaints` | DashboardController@complaints | dashboard.complaints |
| GET | `/dashboard/document-archive` | DashboardController@documentArchive | dashboard.document-archive |
| GET | `/dashboard/reports` | DashboardController@reports | dashboard.reports |
| GET | `/dashboard/compliance-monitoring` | DashboardController@complianceMonitoring | dashboard.compliance-monitoring |

#### Candidate Routes (Resource + Custom)
| Method | URI | Controller@Action | Name | Roles |
|--------|-----|-------------------|------|-------|
| GET | `/candidates` | CandidateController@index | candidates.index | All Auth |
| POST | `/candidates` | CandidateController@store | candidates.store | All Auth |
| GET | `/candidates/create` | CandidateController@create | candidates.create | All Auth |
| GET | `/candidates/{candidate}` | CandidateController@show | candidates.show | All Auth |
| GET | `/candidates/{candidate}/edit` | CandidateController@edit | candidates.edit | All Auth |
| PUT | `/candidates/{candidate}` | CandidateController@update | candidates.update | All Auth |
| DELETE | `/candidates/{candidate}` | CandidateController@destroy | candidates.destroy | All Auth |
| GET | `/candidates/{candidate}/profile` | CandidateController@profile | candidates.profile | All Auth |
| GET | `/candidates/{candidate}/timeline` | CandidateController@timeline | candidates.timeline | All Auth |
| POST | `/candidates/{candidate}/update-status` | CandidateController@updateStatus | candidates.update-status | All Auth |
| GET | `/candidates/export` | CandidateController@export | candidates.export | All Auth |

#### Screening Routes
| Method | URI | Controller@Action | Name | Roles |
|--------|-----|-------------------|------|-------|
| GET | `/screening` | ScreeningController@index | screening.index | All Auth |
| GET | `/screening/pending` | ScreeningController@pending | screening.pending | All Auth |
| POST | `/screening/{candidate}/call-log` | ScreeningController@logCall | screening.log-call | All Auth |
| POST | `/screening/{candidate}/screening-outcome` | ScreeningController@recordOutcome | screening.outcome | All Auth |
| GET | `/screening/{candidate}/progress` | ScreeningController@progress | screening.progress | All Auth |

#### Registration Routes
| Method | URI | Controller@Action | Name | Roles |
|--------|-----|-------------------|------|-------|
| Resource | `/registration` | RegistrationController | registration.* | All Auth |
| GET | `/registration/{candidate}/status` | RegistrationController@status | registration.status | All Auth |
| POST | `/registration/{candidate}/documents` | RegistrationController@uploadDocument | registration.upload-document | All Auth |
| POST | `/registration/{candidate}/next-of-kin` | RegistrationController@saveNextOfKin | registration.next-of-kin | All Auth |
| POST | `/registration/{candidate}/undertaking` | RegistrationController@saveUndertaking | registration.undertaking | All Auth |
| POST | `/registration/{candidate}/complete` | RegistrationController@completeRegistration | registration.complete | All Auth |

#### Training Routes
| Method | URI | Controller@Action | Name | Roles |
|--------|-----|-------------------|------|-------|
| Resource | `/training` | TrainingController | training.* | admin, campus_admin, instructor |
| POST | `/training/attendance` | TrainingController@markAttendance | training.attendance | admin, campus_admin, instructor |
| POST | `/training/{candidate}/certificate` | TrainingController@generateCertificate | training.certificate | admin, campus_admin, instructor |
| GET | `/training/{candidate}/assessment` | TrainingController@assessment | training.assessment-view | admin, campus_admin, instructor |
| POST | `/training/{candidate}/store-assessment` | TrainingController@storeAssessment | training.store-assessment | admin, campus_admin, instructor |
| GET | `/training/batch/{batch}/performance` | TrainingController@batchPerformance | training.batch-performance | admin, campus_admin, instructor |

#### Visa Processing Routes
| Method | URI | Controller@Action | Name | Roles |
|--------|-----|-------------------|------|-------|
| Resource | `/visa-processing` | VisaProcessingController | visa-processing.* | admin, project_director, campus_admin, instructor, oep, visa_partner |
| POST | `/visa-processing/{candidate}/update-interview` | VisaProcessingController@updateInterview | visa-processing.update-interview | ... |
| POST | `/visa-processing/{candidate}/update-trade-test` | VisaProcessingController@updateTradeTest | visa-processing.update-trade-test | ... |
| POST | `/visa-processing/{candidate}/update-medical` | VisaProcessingController@updateMedical | visa-processing.update-medical | ... |
| POST | `/visa-processing/{candidate}/update-visa` | VisaProcessingController@updateVisa | visa-processing.update-visa | ... |
| GET | `/visa-processing/{candidate}/timeline` | VisaProcessingController@timeline | visa-processing.timeline | ... |
| GET | `/visa-processing/reports/overdue` | VisaProcessingController@overdue | visa-processing.overdue | ... |

#### Departure Routes
| Method | URI | Controller@Action | Name | Roles |
|--------|-----|-------------------|------|-------|
| Resource | `/departure` | DepartureController | departure.* | admin, project_director, campus_admin, oep, visa_partner, viewer |
| POST | `/departure/{candidate}/record-departure` | DepartureController@recordDeparture | departure.record-departure | ... |
| POST | `/departure/{candidate}/briefing` | DepartureController@recordBriefing | departure.briefing | ... |
| POST | `/departure/{candidate}/90-day-compliance` | DepartureController@record90DayCompliance | departure.90-day-compliance | ... |
| GET | `/departure/tracking/90-days` | DepartureController@tracking90Days | departure.tracking-90-days | ... |
| GET | `/departure/non-compliant` | DepartureController@nonCompliant | departure.non-compliant | ... |

#### Remittance Routes
| Method | URI | Controller@Action | Name | Roles |
|--------|-----|-------------------|------|-------|
| Resource | `/remittances` | RemittanceController | remittances.* | admin, campus_admin, oep, viewer |
| POST | `/remittances/{id}/verify` | RemittanceController@verify | remittances.verify | ... |
| POST | `/remittances/{id}/upload-receipt` | RemittanceController@uploadReceipt | remittances.upload-receipt | ... |
| GET | `/remittance/reports/dashboard` | RemittanceReportController@dashboard | remittance.reports.dashboard | admin, campus_admin, oep |
| GET | `/remittance/reports/monthly` | RemittanceReportController@monthlyReport | remittance.reports.monthly | ... |
| GET | `/remittance/alerts` | RemittanceAlertController@index | remittance.alerts.index | ... |

#### Admin Routes (Admin Only)
| Method | URI | Controller@Action | Name |
|--------|-----|-------------------|------|
| Resource | `/admin/campuses` | CampusController | admin.campuses.* |
| Resource | `/admin/oeps` | OepController | admin.oeps.* |
| Resource | `/admin/trades` | TradeController | admin.trades.* |
| Resource | `/admin/batches` | BatchController | admin.batches.* |
| Resource | `/admin/users` | UserController | admin.users.* |
| GET | `/admin/settings` | UserController@settings | admin.settings |
| GET | `/admin/activity-logs` | ActivityLogController@index | admin.activity-logs |

### API Routes (~70 routes)

| Method | URI | Controller@Action | Name |
|--------|-----|-------------------|------|
| GET | `/api/health` | HealthController@check | health.check |
| POST | `/api/v1/auth/token` | ApiTokenController@createToken | v1.auth.token.create |
| GET | `/api/v1/global-search` | GlobalSearchController@search | v1.global-search |
| GET | `/api/v1/candidates` | CandidateApiController@index | v1.candidates.index |
| GET | `/api/v1/candidates/stats` | CandidateApiController@statistics | v1.candidates.statistics |
| GET | `/api/v1/departures` | DepartureApiController@index | v1.departures.index |
| GET | `/api/v1/visa-processes` | VisaProcessApiController@index | v1.visa-processes.index |
| GET | `/api/v1/remittances` | RemittanceApiController@index | v1.remittances.index |
| GET | `/api/v1/remittance/reports/dashboard` | RemittanceReportApiController@dashboard | v1.remittance.reports.dashboard |
| GET | `/api/v1/remittance/alerts` | RemittanceAlertApiController@index | v1.remittance.alerts.index |

---

## 7. Controllers & Request Flow

### Web Controllers (29)

| Controller | Actions | Purpose |
|------------|---------|---------|
| `AuthController` | showLogin, login, logout, showForgotPassword, sendResetLink, resetPassword, showForcePasswordChange, updateForcePasswordChange | Authentication |
| `DashboardController` | index, candidatesListing, screening, registration, training, visaProcessing, departure, correspondence, complaints, documentArchive, reports, complianceMonitoring | Dashboard tabs |
| `CandidateController` | index, create, store, show, edit, update, destroy, profile, timeline, updateStatus, assignCampus, assignOep, uploadPhoto, export, checkDuplicates, validateCnic, validatePhone | Candidate management |
| `ScreeningController` | index, create, store, edit, update, pending, logCall, recordOutcome, progress, uploadEvidence, export | Screening workflow |
| `RegistrationController` | index, create, store, show, edit, update, destroy, status, uploadDocument, deleteDocument, verifyDocument, rejectDocument, saveNextOfKin, saveUndertaking, completeRegistration, startTraining | Registration workflow |
| `TrainingController` | index, create, store, show, edit, update, destroy, attendance, markAttendance, bulkAttendance, assessment, storeAssessment, updateAssessment, downloadCertificate, generateCertificate, complete, attendanceReport, assessmentReport, batchPerformance | Training management |
| `VisaProcessingController` | index, create, store, show, edit, update, destroy, updateInterview, updateTradeTest, updateTakamol, updateMedical, updateEnumber, updateBiometric, updateVisaSubmission, updateVisa, updatePTN, uploadTakamolResult, uploadGamcaResult, uploadTravelPlan, uploadTicket, timeline, overdue, complete, report | Visa processing |
| `DepartureController` | index, create, store, show, edit, update, destroy, recordDeparture, recordBriefing, recordIqama, recordAbsher, recordWps, recordFirstSalary, record90DayCompliance, reportIssue, updateIssue, markReturned, timeline, tracking90Days, nonCompliant, activeIssues, pendingCompliance, markCompliant, createIssue, complianceReport | Departure management |
| `RemittanceController` | index, create, store, show, edit, update, destroy, verify, uploadReceipt, deleteReceipt, export | Remittance management |
| `RemittanceBeneficiaryController` | index, create, store, edit, update, destroy, setPrimary, data | Beneficiary management |
| `RemittanceReportController` | dashboard, monthlyReport, purposeAnalysis, beneficiaryReport, proofComplianceReport, impactAnalytics, export | Remittance reports |
| `RemittanceAlertController` | index, show, markAsRead, markAllAsRead, resolve, dismiss, bulkAction, generateAlerts, autoResolve, unreadCount | Alert management |
| `ComplaintController` | index, create, store, show, edit, update, destroy, assign, addUpdate, addEvidence, escalate, resolve, close, reopen, overdue, byCategory, myAssignments, statistics, analytics, slaReport, export | Complaint management |
| `CorrespondenceController` | index, create, store, show, edit, update, destroy, pendingReply, markReplied, register, summary | Correspondence management |
| `DocumentArchiveController` | index, create, store, show, edit, update, destroy, view, download, versions, uploadVersion, restoreVersion, archive, restore, search, expiring, expired, candidateDocuments, accessLogs, sendExpiryReminders, statistics, report, missingDocuments, verificationStatus, bulkUpload | Document management |
| `ReportController` | index, candidateProfile, batchSummary, campusPerformance, oepPerformance, visaTimeline, trainingStatistics, complaintAnalysis, customReport, generateCustomReport, export, exportProfilePdf, exportToCsv, trainerPerformance, departureUpdatesReport, instructorUtilization, fundingMetrics, calculateKpis | Reporting |
| `CampusController` | index, create, store, show, edit, update, destroy, toggleStatus, apiList | Campus admin |
| `OepController` | index, create, store, show, edit, update, destroy, toggleStatus, apiList | OEP admin |
| `TradeController` | index, create, store, show, edit, update, destroy, toggleStatus, apiList | Trade admin |
| `BatchController` | index, create, store, show, edit, update, destroy, changeStatus, byCampus | Batch admin |
| `UserController` | index, create, store, show, edit, update, destroy, toggleStatus, resetPassword, profile, updateProfile, notifications, markNotificationRead, markAllNotificationsRead, settings, updateSettings, auditLogs | User management |
| `InstructorController` | index, create, store, show, edit, update, destroy | Instructor management |
| `TrainingClassController` | index, create, store, show, edit, update, destroy, assignCandidates, removeCandidate | Class management |
| `ActivityLogController` | index, show, statistics, export, clean | Activity logs |
| `ImportController` | showCandidateImport, importCandidates, downloadTemplate | Data import |
| `BulkOperationsController` | updateStatus, assignToBatch, assignToCampus, export, delete, sendNotification | Bulk operations |
| `EquipmentController` | index, create, store, show, edit, update, destroy, logUsage, endUsage, utilizationReport | Equipment tracking |
| `SecureFileController` | download, view | Secure file access |
| `HealthController` | check, detailed | System health |

### API Controllers (8)

| Controller | Actions | Purpose |
|------------|---------|---------|
| `ApiTokenController` | createToken, listTokens, revokeToken, revokeAllTokens, currentUser | Token auth |
| `GlobalSearchController` | search | Global search |
| `CandidateApiController` | index, show, store, update, destroy, statistics | Candidate API |
| `DepartureApiController` | index, show, store, update, byCandidate, statistics | Departure API |
| `VisaProcessApiController` | index, show, store, update, byCandidate, statistics | Visa API |
| `RemittanceApiController` | index, show, store, update, destroy, byCandidate, search, statistics, verify | Remittance API |
| `RemittanceReportApiController` | dashboard, monthlyTrends, purposeAnalysis, transferMethods, countryAnalysis, proofCompliance, beneficiaryReport, impactAnalytics, topCandidates | Reports API |
| `RemittanceAlertApiController` | index, show, statistics, unreadCount, byCandidate, markAsRead, resolve, dismiss | Alerts API |

### Service Classes (14)

| Service | Purpose |
|---------|---------|
| `CandidateDeduplicationService` | Candidate duplicate detection and merging |
| `ComplaintService` | Complaint workflow management |
| `DepartureService` | Departure processing and compliance |
| `DocumentArchiveService` | Document storage and versioning |
| `FileStorageService` | File upload and storage handling |
| `GlobalSearchService` | Cross-module search functionality |
| `NotificationService` | User notification management |
| `RegistrationService` | Registration workflow processing |
| `RemittanceAlertService` | Remittance compliance alerts |
| `RemittanceAnalyticsService` | Remittance analytics and reporting |
| `ReportingService` | Report generation and export |
| `ScreeningService` | Screening workflow processing |
| `TrainingService` | Training management operations |
| `VisaProcessingService` | Visa workflow processing |

---

## 8. Blade Views & UI Pages

### Total Views: 187 templates

### View Directory Structure

```
resources/views/
├── activity-logs/           # 3 views (index, show, statistics)
├── admin/                   # 21 views
│   ├── batches/            # 4 views (CRUD)
│   ├── campuses/           # 4 views (CRUD)
│   ├── oeps/               # 4 views (CRUD)
│   ├── trades/             # 4 views (CRUD)
│   ├── users/              # 4 views (CRUD)
│   └── settings.blade.php
├── auth/                    # 4 views (login, forgot-password, reset-password, force-password-change)
├── candidates/              # 6 views (CRUD + profile, timeline)
├── classes/                 # 4 views (CRUD)
├── complaints/              # 10 views (CRUD + workflow views)
├── components/              # 5 views (card, button, breadcrumbs, analytics-widgets, realtime-notifications)
├── correspondence/          # 6 views (CRUD + pending-reply, register, reports/summary)
├── dashboard/               # 13 views (role dashboards + tabs)
│   ├── tabs/               # 10 views (module tabs)
│   └── *.blade.php         # Role dashboards (admin, campus-admin, oep, instructor, visa-partner)
├── departure/               # 11 views (index, show, timeline, reports, issues)
├── document-archive/        # 15 views (CRUD + versions, search, reports)
├── emails/                  # 4 views (email templates)
├── equipment/               # 5 views (CRUD + utilization-report)
├── import/                  # 1 view (candidates)
├── instructors/             # 4 views (CRUD)
├── layouts/                 # 1 view (app.blade.php - main layout)
├── profile/                 # 1 view (index)
├── registration/            # 6 views (CRUD + status, verify-result)
├── remittances/             # 16 views
│   ├── alerts/             # 2 views (index, show)
│   ├── beneficiaries/      # 3 views (CRUD)
│   ├── reports/            # 6 views (dashboard, monthly, impact, etc.)
│   │   └── pdf/            # 5 views (PDF templates)
│   └── *.blade.php         # 3 views (CRUD)
├── reports/                 # 14 views (various reports)
│   └── pdf/                # 1 view (candidate-profile)
├── screening/               # 6 views (CRUD + pending, progress)
├── training/                # 11 views (CRUD + attendance, assessment, reports)
└── visa-processing/         # 8 views (CRUD + timeline, overdue, reports)
```

### UI Stack

- **CSS Framework**: Tailwind CSS (via CDN)
- **JavaScript Framework**: Alpine.js (via CDN)
- **Charts**: Chart.js (via CDN)
- **Icons**: Font Awesome (via CDN)
- **HTTP Client**: Axios (via CDN)
- **PDF Generation**: DomPDF (server-side)
- **Layout**: Single `layouts/app.blade.php` with component slots

### CDN Dependencies (Production Risk)

| Library | CDN URL | Version |
|---------|---------|---------|
| Tailwind CSS | `cdn.tailwindcss.com` | Latest |
| Alpine.js | `cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js` | 3.x |
| Font Awesome | `cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css` | 6.4.0 |
| Chart.js | `cdn.jsdelivr.net/npm/chart.js` | Latest |
| Axios | `cdn.jsdelivr.net/npm/axios/dist/axios.min.js` | Latest |

> **⚠️ Production Warning**: These external CDN dependencies should be bundled locally for production deployment to ensure reliability and security.

---

## 9. Middleware & Authorization

### Custom Middleware (14)

| Middleware | File | Purpose |
|------------|------|---------|
| `RoleMiddleware` | `app/Http/Middleware/RoleMiddleware.php` | Role-based access control |
| `CheckUserActive` | `app/Http/Middleware/CheckUserActive.php` | Verify user is active |
| `ForcePasswordChange` | `app/Http/Middleware/ForcePasswordChange.php` | Enforce password change |
| `Authenticate` | `app/Http/Middleware/Authenticate.php` | Authentication |
| `AuthenticateSession` | `app/Http/Middleware/AuthenticateSession.php` | Session auth |
| `EncryptCookies` | `app/Http/Middleware/EncryptCookies.php` | Cookie encryption |
| `VerifyCsrfToken` | `app/Http/Middleware/VerifyCsrfToken.php` | CSRF protection |
| `RedirectIfAuthenticated` | `app/Http/Middleware/RedirectIfAuthenticated.php` | Guest redirect |
| `TrimStrings` | `app/Http/Middleware/TrimStrings.php` | Input trimming |
| `TrustProxies` | `app/Http/Middleware/TrustProxies.php` | Proxy trust |
| `ValidateSignature` | `app/Http/Middleware/ValidateSignature.php` | URL signature |
| `ConvertEmptyStringsToNull` | `app/Http/Middleware/ConvertEmptyStringsToNull.php` | Empty to null |
| `PreventRequestsDuringMaintenance` | `app/Http/Middleware/PreventRequestsDuringMaintenance.php` | Maintenance mode |
| `SecurityHeaders` | `app/Http/Middleware/SecurityHeaders.php` | Security headers |

### Middleware Groups (Defined in bootstrap/app.php)

```php
// Web middleware (automatic for web routes)
web: [CheckUserActive::class]

// Custom groups
admin: ['auth', 'role:admin']
staff: ['auth', 'role:admin,staff']

// Middleware aliases
'role' => RoleMiddleware::class
'active' => CheckUserActive::class
```

### Role-Based Access Matrix

| Module | Admin | Project Director | Campus Admin | Trainer | OEP | Visa Partner | Viewer |
|--------|-------|------------------|--------------|---------|-----|--------------|--------|
| Dashboard | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Candidates | Full | Full | Campus | View | OEP | View | View |
| Screening | Full | Full | Campus | View | - | - | View |
| Registration | Full | Full | Campus | View | - | - | View |
| Training | Full | Full | Campus | Full | View | - | View |
| Visa Processing | Full | Full | Campus | View | Full | Full | View |
| Departure | Full | Full | Campus | View | Full | Full | View |
| Remittances | Full | Full | Campus | - | Full | - | View |
| Complaints | Full | Full | Campus | - | - | - | View |
| Correspondence | Full | Full | Campus | - | - | - | View |
| Reports | Full | Full | Campus | View | View | View | View |
| Admin Settings | Full | - | - | - | - | - | - |

---

## 10. Jobs, Queues & Background Tasks

### Current Status: No Background Jobs Configured

The system currently runs all operations synchronously. The queue driver is set to `sync`.

### Recommended Future Jobs

| Job | Purpose | Trigger |
|-----|---------|---------|
| `SendPasswordResetEmail` | Email password reset links | User request |
| `GenerateMonthlyReport` | Create scheduled reports | Scheduler |
| `ProcessRemittanceAlerts` | Check for remittance compliance | Daily |
| `SendExpiryReminders` | Document expiry notifications | Daily |
| `CleanupActivityLogs` | Purge old activity logs | Weekly |

---

## 11. Validation Rules

### Candidate Validation

```php
'name' => 'required|string|max:255',
'cnic' => 'required|string|size:13|unique:candidates,cnic',
'phone' => 'required|string|max:20',
'email' => 'nullable|email|max:255',
'date_of_birth' => 'required|date|before:today',
'gender' => 'required|in:male,female,other',
'father_name' => 'required|string|max:255',
'address' => 'required|string',
'district' => 'required|string|max:100',
'trade_id' => 'required|exists:trades,id',
'campus_id' => 'nullable|exists:campuses,id',
```

### User Validation

```php
'name' => 'required|string|max:255',
'email' => 'required|email|unique:users,email',
'password' => 'required|string|min:8|confirmed',
'role' => 'required|in:' . implode(',', User::ROLES),
'campus_id' => 'nullable|exists:campuses,id',
'oep_id' => 'nullable|exists:oeps,id',
```

### Remittance Validation

```php
'candidate_id' => 'required|exists:candidates,id',
'beneficiary_id' => 'nullable|exists:remittance_beneficiaries,id',
'amount' => 'required|numeric|min:0',
'currency' => 'required|string|max:3',
'transfer_date' => 'required|date',
'transfer_method' => 'required|string|max:50',
'primary_purpose' => 'required|string|max:100',
```

### CNIC Validation (Custom)

```php
// Pakistani CNIC format: XXXXX-XXXXXXX-X (13 digits)
// Model method: Candidate::validateCnicChecksum($cnic)
```

### Phone Validation (Custom)

```php
// Pakistan phone formats accepted:
// 03XX-XXXXXXX (11 digits starting with 03)
// +923XXXXXXXXX (13 chars starting with +92)
// 923XXXXXXXXX (12 digits starting with 92)
// Model method: Candidate::validatePakistanPhone($phone)
```

---

## 12. System Configuration & Constants

### Application Constants (config/app.php)

```php
'name' => 'WASL',
'tagline' => 'Connecting Talent, Opportunity, and Remittance',
'full_name' => 'WASL - وصل',
'subtitle' => 'Integrated Digital Platform for Overseas Employment & Remittance Lifecycle Management',
'timezone' => 'UTC',
'locale' => 'en',
```

### Database Configuration (config/database.php)

```php
'default' => 'mysql',
'connections' => [
    'mysql' => [
        'driver' => 'mysql',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ],
],
```

### Authentication Configuration (config/auth.php)

```php
'defaults' => ['guard' => 'web'],
'guards' => [
    'web' => ['driver' => 'session', 'provider' => 'users'],
    'api' => ['driver' => 'sanctum'],
],
'providers' => [
    'users' => ['driver' => 'eloquent', 'model' => App\Models\User::class],
],
```

### Throttle Limits (Applied via Middleware)

| Action Type | Limit | Purpose |
|-------------|-------|---------|
| Login attempts | 5/min | Prevent brute force |
| API requests | 60/min | API rate limiting |
| File uploads | 30/min | Storage abuse prevention |
| Report generation | 5/min | CPU protection |
| Custom reports | 3/min | Heavy CPU protection |
| Bulk operations | 30/min | Database protection |
| Export operations | 5/min | Resource protection |

---

## 13. Known Risks & Technical Debt

### High Priority Issues

| Issue | Description | Recommendation |
|-------|-------------|----------------|
| **Hardcoded Status Strings** | 57 blade files contain hardcoded status values (`'new'`, `'screening'`, `'registered'`, `'training'`, `'visa_process'`, `'departed'`, `'rejected'`) | Use `Candidate::STATUS_*` and other Model constants consistently |
| **CDN Dependencies** | 5 external CDN dependencies (Tailwind, Alpine.js, Chart.js, Font Awesome, Axios) | Bundle assets locally for production |
| **No Background Jobs** | All operations synchronous | Implement queue for emails, reports |
| **No Rate Limiting on Some Routes** | Some sensitive routes lack throttle | Add throttle middleware |

### Hardcoded Configuration Values (Should Be Environment Variables)

| Value | Location | Current Value | Recommendation |
|-------|----------|---------------|----------------|
| Document Expiry Warning | `DashboardController.php`, `RegistrationController.php` | `30` days | Add `DOCUMENT_EXPIRY_WARNING_DAYS` env var |
| Screening Followup Days | `ScreeningController.php` | `7` days | Add `SCREENING_DEFAULT_FOLLOWUP_DAYS` env var |
| Disk Storage Threshold | `HealthController.php` | `85%` | Add `STORAGE_WARNING_THRESHOLD` env var |
| Max Failed Jobs Alert | `HealthController.php` | `10` jobs | Add `HEALTH_CHECK_MAX_FAILED_JOBS` env var |
| Login Lockout | `AuthController.php` | `5 attempts / 15 min` | Already constants, consider moving to config |

### Medium Priority Issues

| Issue | Description | Recommendation |
|-------|-------------|----------------|
| **Large Controllers** | Some controllers have 20+ methods | Split into smaller services |
| **Missing API Documentation** | No OpenAPI/Swagger docs | Generate API documentation |
| **No Caching Strategy** | Limited caching implementation | Implement query caching |
| **Mixed Authorization** | Role checks in controllers vs middleware | Standardize authorization |

### Low Priority Issues

| Issue | Description | Recommendation |
|-------|-------------|----------------|
| **Duplicate Relationships** | Some model relationships duplicated | Consolidate aliases |
| **View Naming Inconsistency** | Some views use kebab-case, others snake_case | Standardize naming |
| **Missing Indexes** | Some foreign keys lack indexes | Add database indexes |

### Security Considerations

1. **Password Security**: Force password change implemented, password history tracked
2. **Session Security**: Session-based auth with active user verification
3. **API Security**: Sanctum token authentication with throttling
4. **CSRF Protection**: Enabled on all POST/PUT/DELETE routes
5. **File Security**: Secure file controller for document access
6. **Input Validation**: Request validation on all forms
7. **SQL Injection**: Eloquent ORM prevents SQL injection
8. **XSS Prevention**: Blade escaping by default

---

## 14. Change Log

### Version History

| Date | Version | Changes | Author |
|------|---------|---------|--------|
| 2026-01-09 | 1.0.1 | Audit: Fixed controller count (37 not 38), documented 5 CDN dependencies, identified 57 files with hardcoded status strings, added hardcoded config values table | Claude |
| 2026-01-09 | 1.0.0 | Initial SYSTEM_MAP.md creation | System |
| 2025-12-31 | - | Phase 2 model relationship fixes | Claude |
| 2025-12-28 | - | Database constraints added | Claude |
| 2025-12-22 | - | Security columns added to users | Claude |
| 2025-12-19 | - | Performance indexes, audit columns | Claude |
| 2025-11-11 | - | Remittance module added | Claude |
| 2025-11-09 | - | Training classes, instructors added | Claude |
| 2025-10-31 | - | Initial migration structure | Claude |

### Recent Commits

```
7e4fb90 feat: Add missing views for training classes, instructors, and reports
036f96e Merge pull request #109 from haseebayazi/claude/audit-database-consistency-gGU5H
07dcbee fix: Address missing controllers, models, and database issues
e469397 fix: Add missing routes and fix route name typos
13ad97e fix: Workflow consistency fixes for end-to-end candidate flow
```

---

## Quick Reference

### Common Commands

```bash
# Clear all caches
php artisan cache:clear && php artisan config:clear && php artisan route:clear && php artisan view:clear

# Run migrations
php artisan migrate

# List all routes
php artisan route:list

# Run tests
php artisan test

# Generate route cache (production)
php artisan route:cache
```

### Key URLs

| URL | Purpose |
|-----|---------|
| `/` | Redirects to login |
| `/login` | Login page |
| `/dashboard` | Main dashboard |
| `/candidates` | Candidate listing |
| `/admin/users` | User management (admin) |
| `/reports` | Reports index |
| `/api/health` | API health check |

### Key Files

| File | Purpose |
|------|---------|
| `routes/web.php` | All web routes (~185) |
| `routes/api.php` | All API routes (~70) |
| `bootstrap/app.php` | App configuration, middleware |
| `app/Models/Candidate.php` | Core candidate model with all logic |
| `app/Models/User.php` | User model with role system |
| `resources/views/layouts/app.blade.php` | Main layout template |

---

> **Document Maintenance**: This document must be updated whenever:
> - New tables/columns are added
> - New models are created
> - New routes are defined
> - New controllers are added
> - Middleware changes
> - Configuration changes
> - Security updates

---

*Last Updated: 2026-01-09 (v1.0.1)*
*Generated for: WASL - BTEVTA Overseas Employment System*
