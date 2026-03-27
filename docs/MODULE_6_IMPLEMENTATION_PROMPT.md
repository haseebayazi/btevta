# Module 6: Departure Management

**Version:** 2.0.0
**Status:** Complete
**Implementation Date:** February 2026

---

## Overview

Module 6 manages the complete **departure lifecycle** for BTEVTA WASL — from pre-departure preparation through overseas welfare monitoring and 90-day compliance verification. It covers two major phases:

1. **Pre-Departure Phase** — PTN issuance, Protector clearance, ticketing, and briefing (before the candidate flies)
2. **Post-Departure Phase** — Iqama, Absher, Qiwa/WPS registration, salary confirmation, 90-day compliance, issue tracking, and return recording

The module also provides an **Enhanced Departure Dashboard**, per-departure **checklist view**, and a **Welfare Monitoring Dashboard** with at-risk candidate detection.

---

## Features

### Core Functionality

1. **Pre-Departure Checklist (4 required items)**
   - **PTN (Permission to Proceed/Travel Notice)** — structured issuance with PTN number, issued date, expiry date, and evidence upload
   - **Protector Clearance** — tracks Applied / Done / Pending / Deferred status with certificate upload
   - **Ticket Details** — full flight sub-details: airline, flight number, departure/arrival dates, times, airports, PNR, plus ticket file upload
   - **Pre-Departure Briefing** — two-step (Schedule → Complete) with document upload, video upload (MP4/MOV/AVI, max 100 MB), and acknowledgment file
   - Candidate cannot be marked "Ready to Depart" until all four checklist items are complete

2. **Departure Status Progression**
   - `processing` → `ready_to_depart` → `departed` (or `cancelled`)
   - `markReadyToDepart()` validates full checklist before allowing status change
   - `recordActualDeparture()` records the `departed_at` timestamp and transitions the candidate to `DEPARTED`

3. **Briefing Workflow (2-step)**
   - **Schedule**: set a future `briefing_date`
   - **Complete**: upload briefing document, briefing video, and acknowledgment file; mark `acknowledgment_signed`
   - `BriefingStatus` enum: `not_scheduled` → `scheduled` → `completed`

4. **Post-Departure Tracking**
   - **Iqama**: Saudi residence permit number, issue/expiry dates, post-arrival medical report
   - **Absher**: registration date, Absher ID, verification status
   - **Qiwa/WPS**: ID, activation date, activation status
   - **Salary Confirmation**: amount, currency (SAR/PKR/USD/AED), date, proof document, confirmed-by user
   - **Accommodation**: type, address, verification status
   - **Employer Contact**: name, contact, address, employer ID

5. **90-Day Compliance Tracking**
   - Automated deadline calculation from `departure_date`
   - 5 compliance items: Iqama, Absher, Qiwa/WPS, Salary Confirmed, Accommodation Verified
   - Compliance statuses: `pending` / `partial` (≥80%) / `compliant` (100%) / `non_compliant` (overdue)
   - Scheduled Artisan command `check-90-day-compliance` runs daily

6. **Issue Reporting & Management**
   - Issue types: salary_delay, contract_violation, work_condition, accommodation, medical, other
   - Severity levels: low, medium, high, critical
   - UUID-based issue IDs for secure referencing
   - Issue lifecycle: open → investigating → resolved → closed

7. **Return Recording**
   - Records `return_date`, `return_reason`, `return_remarks`
   - Transitions candidate to `COMPLETED` status

8. **Departure Timeline**
   - Chronological timeline of all milestones: briefing → departure → iqama → absher → qiwa → salary → compliance

9. **Enhanced Departure Dashboard**
   - Status breakdown by departure_status, briefing_status, protector_status
   - Campus filtering (campus admins see own campus only)
   - At-risk candidate detection (60+ days no salary, non-compliant, active issues)

10. **Welfare Monitoring Dashboard**
    - Total deployed, 90-day compliance rates, salary confirmation rates
    - By-country breakdown (top 10)
    - Recent issues list
    - At-risk candidate identification with risk factor labels

11. **Value Object Pattern**
    - `PTNDetails`: `isIssued()`, `isExpired()`
    - `TicketDetails`: `isComplete()`, `getDepartureDateTime()`
    - `BriefingDetails`: `isComplete()`, `hasDocuments()`
    - All stored as JSON columns and accessed via model accessors

12. **Reporting Suite**
    - Departure list report (filterable by date, trade, OEP)
    - Compliance report (PDF and Excel export)
    - 90-day tracking report (CSV export)
    - Pending activations report (Iqama / Absher)
    - Salary disbursement status report
    - Non-compliant candidates list
    - Active issues list

---

## Workflow

```
+-----------------------------+
|      VISA_APPROVED          |
|      (Module 5)             |
+------------+----------------+
             |
             | Visa approved → departure record created
             v
+-----------------------------+
|   DEPARTURE_PROCESSING      |◄── Pre-departure briefing recorded
+------------+----------------+
             |
             v
+-------------------------------------------------------------+
|  Pre-Departure Checklist (all 4 required)                    |
|                                                              |
|  [1] PTN Issued                                             |
|      └── PTN number, issued date, expiry date, evidence     |
|                                                              |
|  [2] Protector Clearance = "done"                           |
|      └── Applied → Done (certificate upload)                |
|                                                              |
|  [3] Ticket Details Complete                                |
|      └── Airline + Flight + Dates + Airports + PNR + File   |
|                                                              |
|  [4] Briefing Complete                                      |
|      └── Schedule → Complete (doc + video + ack upload)     |
+-------------------------------------------------------------+
             |
             | canMarkReadyToDepart() = true
             v
+-----------------------------+
|     READY_TO_DEPART         |◄── departure_status = ready_to_depart
+------------+----------------+
             |
             | recordActualDeparture()
             v
+-----------------------------+
|        DEPARTED             |◄── departure_status = departed
|  (CandidateStatus::DEPARTED)|    departed_at = timestamp
+------------+----------------+
             |
             | recordIqama() → POST_DEPARTURE
             v
+-------------------------------------------------------------+
|  Post-Departure Tracking                                     |
|                                                              |
|  ○ Iqama issued                 ○ Absher registered          |
|  ○ Qiwa/WPS activated           ○ Salary confirmed           |
|  ○ Accommodation verified       ○ Communication logged       |
+-------------------------------------------------------------+
             |
             | All 5 compliance items complete (within 90 days)
             v
+-----------------------------+
|         COMPLETED           |◄── 90-day compliance verified
|   (CandidateStatus)         |    or candidate returned
+-----------------------------+
```

---

## Database Schema

### Table: `departures` (core columns)

| Column | Type | Description |
|--------|------|-------------|
| `candidate_id` | bigint FK | Owning candidate |
| `departure_date` | date | Actual flight date |
| `flight_number` | varchar | Flight identifier |
| `destination` | varchar | Destination country/city |
| `airport` | varchar | Departure airport |
| `country_code` | varchar(2) | ISO country code |
| `current_stage` | varchar | pre_briefing / departed / iqama_issued / ... |

### Table: `departures` (Module 6 enhancement columns)

Migration: `2026_02_18_000001_add_enhanced_departure_columns.php`

| Column | Type | Description |
|--------|------|-------------|
| `ptn_number` | varchar | PTN reference number |
| `ptn_details` | json | PTN structured data (status, issued_date, expiry_date, evidence_path, notes) |
| `protector_status` | enum | not_started / applied / done / pending / deferred |
| `protector_details` | json | Applied date, completion date, certificate path, notes |
| `ticket_path` | varchar | Uploaded ticket file path |
| `ticket_details` | json | Full flight data: airline, flight_number, departure/arrival dates, times, airports, PNR |
| `briefing_status` | enum | not_scheduled / scheduled / completed |
| `briefing_details` | json | scheduled_date, completed_date, document_path, video_path, acknowledgment_signed, acknowledgment_path |
| `departure_status` | enum | processing / ready_to_depart / departed / cancelled |
| `departed_at` | timestamp | Actual departure timestamp |

### Table: `departures` (post-departure tracking columns)

| Column | Type | Description |
|--------|------|-------------|
| `iqama_number` | varchar | Saudi residence permit number |
| `iqama_issue_date` | date | Iqama issue date |
| `iqama_expiry_date` | date | Iqama expiry date |
| `post_arrival_medical_path` | varchar | Medical report file path |
| `absher_registered` | boolean | Absher registration confirmed |
| `absher_registration_date` | date | Date of Absher registration |
| `absher_id` | varchar | Absher identifier |
| `absher_verification_status` | varchar | verified / pending |
| `qiwa_id` | varchar | Qiwa/WPS identifier |
| `qiwa_activated` | boolean | Qiwa activation status |
| `qiwa_activation_date` | date | Date of activation |
| `salary_amount` | decimal | First salary amount |
| `salary_currency` | varchar | SAR / PKR / USD / AED |
| `first_salary_date` | date | Date of first salary receipt |
| `salary_confirmed` | boolean | Salary confirmation flag |
| `salary_confirmed_by` | bigint FK | User who confirmed |
| `salary_confirmed_at` | datetime | Confirmation timestamp |
| `salary_proof_path` | varchar | Proof document file path |
| `accommodation_type` | varchar | employer_provided / self-rented |
| `accommodation_status` | varchar | verified / pending / issues |
| `employer_name` | varchar | Employer name |
| `employer_contact` | varchar | Employer contact details |
| `ninety_day_report_submitted` | boolean | 90-day compliance submitted |
| `ninety_day_compliance_status` | varchar | compliant / partial / non_compliant / pending |
| `compliance_verified_date` | date | When compliance was verified |
| `issues` | json | Array of reported issues (UUID IDs) |
| `communication_logs` | json | Communication history log entries |
| `return_date` | date | Return date if candidate came back |
| `return_reason` | varchar | Reason for return |

### JSON Structures

**PTN Details:**
```json
{
    "status": "issued",
    "issued_date": "2026-03-01",
    "expiry_date": "2026-09-01",
    "evidence_path": "departures/42/ptn/evidence.pdf",
    "notes": "PTN issued by OEP"
}
```

**Ticket Details:**
```json
{
    "airline": "PIA",
    "flight_number": "PK-786",
    "departure_date": "2026-04-15",
    "departure_time": "14:30",
    "arrival_date": "2026-04-15",
    "arrival_time": "18:00",
    "departure_airport": "LHE",
    "arrival_airport": "RUH",
    "pnr": "ABCDEF",
    "ticket_path": "departures/42/ticket/ticket.pdf"
}
```

**Briefing Details:**
```json
{
    "scheduled_date": "2026-04-10",
    "completed_date": "2026-04-10",
    "document_path": "departures/42/briefing/documents/guide.pdf",
    "video_path": "departures/42/briefing/videos/briefing.mp4",
    "acknowledgment_signed": true,
    "acknowledgment_path": "departures/42/briefing/acknowledgments/signed.pdf"
}
```

---

## Enums

### `DepartureStatus`

| Value | Label | Color |
|-------|-------|-------|
| `processing` | Processing | secondary |
| `ready_to_depart` | Ready to Depart | info |
| `departed` | Departed | success |
| `cancelled` | Cancelled | danger |

### `ProtectorStatus`

| Value | Label | Color | Icon |
|-------|-------|-------|------|
| `not_started` | Not Started | secondary | fa-circle |
| `applied` | Applied | info | fa-paper-plane |
| `done` | Completed | success | fa-check-circle |
| `pending` | Pending | warning | fa-clock |
| `deferred` | Deferred | danger | fa-pause-circle |

### `BriefingStatus`

| Value | Label | Color |
|-------|-------|-------|
| `not_scheduled` | Not Scheduled | secondary |
| `scheduled` | Scheduled | info |
| `completed` | Completed | success |

---

## Value Objects

### `PTNDetails` (`app/ValueObjects/PTNDetails.php`)

```php
PTNDetails::fromArray($departure->ptn_details);

$ptn->isIssued();   // bool — status === 'issued'
$ptn->isExpired();  // bool — expiry_date < today
```

### `TicketDetails` (`app/ValueObjects/TicketDetails.php`)

```php
TicketDetails::fromArray($departure->ticket_details);

$ticket->isComplete();          // bool — has airline + flight_number + departure_date
$ticket->getDepartureDateTime(); // Carbon|null
```

### `BriefingDetails` (`app/ValueObjects/BriefingDetails.php`)

```php
BriefingDetails::fromArray($departure->briefing_details);

$briefing->isComplete();    // bool — completed_date + acknowledgment_signed
$briefing->hasDocuments();  // bool — document_path set
```

### Model Accessors

```php
$departure->ptn_details_object      // PTNDetails instance
$departure->ticket_details_object   // TicketDetails instance
$departure->briefing_details_object // BriefingDetails instance
```

---

## User Interface

### 1. Candidate Departure Index

**Route:** `GET /departure`

- Lists all candidates with `status = departed`
- Filterable by compliance stage and search (name, CNIC, passport)
- Campus-scoped for campus admins
- Paginated (20 per page)

### 2. Departure Show / Detail

**Route:** `GET /departure/{candidate}`

- Loads candidate with departure, trade, OEP, campus relations
- Displays compliance checklist via `getComplianceChecklist()`
- Pre-departure briefing recording form
- Full departure detail view

### 3. Enhanced Dashboard

**Route:** `GET /departure/enhanced-dashboard`

- Status summary cards: processing, ready_to_depart, departed, cancelled counts
- Briefing status breakdown
- Protector status breakdown
- Campus filter (admin only)
- At-risk candidate panel

### 4. Departure Checklist

**Route:** `GET /departure/{departure}/checklist`

- Per-departure checklist view showing all 4 pre-departure items
- Progress percentage `completed/total (%)`
- Inline action forms for each item
- "Mark Ready to Depart" button (enabled only when `can_mark_ready = true`)
- "Record Departure" button (available when `departure_status = ready_to_depart`)

### 5. Welfare Monitoring Dashboard

**Route:** `GET /departure/welfare-monitoring`

- Total deployed count
- 90-day compliance: compliant / partial / non-compliant / pending counts
- Compliance rate % and salary confirmation rate %
- Top 10 destination countries
- Recent issues list
- At-risk candidates (top 20, sorted by risk factor count)

### 6. Departure Timeline

**Route:** `GET /departure/{candidate}/timeline`

- Chronological list of all departure milestones

### 7. Reports

| Route | Description |
|-------|-------------|
| `GET /departure/reports/list` | Departure list (filter by date, trade, OEP) |
| `GET /departure/compliance-report` | Compliance report |
| `GET /departure/90-day-tracking` | 90-day tracking data |
| `GET /departure/90-day-tracking/export` | CSV export of 90-day tracking |
| `GET /departure/non-compliant` | Non-compliant candidates |
| `GET /departure/active-issues` | Active open issues |
| `GET /departure/pending-compliance` | Pending compliance departures |
| `GET /departure/reports/pending-activations` | Pending Iqama / Absher activations |
| `GET /departure/reports/salary-status` | Salary disbursement status report |
| `GET /departure/compliance-report/pdf` | Compliance report PDF |
| `GET /departure/compliance-report/excel` | Compliance report Excel |

---

## Routes

All routes require authentication and appropriate role authorization.

### Core CRUD

| Method | Route | Action |
|--------|-------|--------|
| GET | `/departure` | Index — list departed candidates |
| GET | `/departure/{candidate}` | Show departure details |
| POST | `/departure/{candidate}/record-departure` | Record departure |
| POST | `/departure/{candidate}/record-briefing` | Record pre-departure briefing |

### Enhanced Checklist Routes (Module 6 Enhancement)

| Method | Route | Name | Description |
|--------|-------|------|-------------|
| GET | `/departure/enhanced-dashboard` | `departure.enhanced-dashboard` | Enhanced dashboard |
| GET | `/departure/{departure}/checklist` | `departure.checklist` | Per-departure checklist |
| POST | `/departure/{departure}/ptn` | `departure.update-ptn` | Update PTN details |
| POST | `/departure/{departure}/protector` | `departure.update-protector` | Update protector status |
| POST | `/departure/{departure}/ticket` | `departure.update-ticket` | Update ticket details |
| POST | `/departure/{departure}/briefing/schedule` | `departure.schedule-briefing` | Schedule briefing |
| POST | `/departure/{departure}/briefing/complete` | `departure.complete-briefing` | Complete briefing with uploads |
| POST | `/departure/{departure}/ready` | `departure.mark-ready` | Mark ready to depart |
| POST | `/departure/{departure}/depart` | `departure.record-departure-actual` | Record actual departure |

### Post-Departure Routes

| Method | Route | Description |
|--------|-------|-------------|
| POST | `/departure/{candidate}/record-iqama` | Record Iqama details |
| POST | `/departure/{candidate}/record-absher` | Record Absher registration |
| POST | `/departure/{candidate}/record-wps` | Record Qiwa/WPS activation |
| POST | `/departure/{candidate}/record-first-salary` | Record first salary |
| POST | `/departure/{departure}/confirm-salary` | Confirm salary with documentation |
| POST | `/departure/{candidate}/90-day-compliance` | Record 90-day compliance |
| POST | `/departure/{candidate}/report-issue` | Report post-departure issue |
| POST | `/departure/{issueId}/update-issue` | Update issue status |
| POST | `/departure/{candidate}/mark-returned` | Mark candidate as returned |
| POST | `/departure/{departure}/mark-compliant` | Mark departure as compliant |

---

## Business Logic

### Checklist Completion Validation

```
canMarkReadyToDepart():
  ✓ PTN issued (ptn_details_object->isIssued())
  ✓ Protector done (protector_status === DONE)
  ✓ Ticket complete (ticket_details_object->isComplete())
  ✓ Briefing complete (briefing_details_object->isComplete())
```

### 90-Day Compliance Scoring

```
Items (all boolean):
  1. iqama_number is not null
  2. absher_registered === true
  3. qiwa_id is not null
  4. salary_confirmed === true
  5. accommodation_status === 'verified'

Percentage = completedItems / 5 * 100

Result:
  100%   → compliant
  ≥80%   → partial
  <100%, >90 days elapsed → non_compliant
  else   → pending
```

### At-Risk Candidate Detection

A candidate is at-risk if ANY of these conditions hold:
1. Departed 60+ days ago without salary confirmation
2. 90-day compliance status is `non_compliant`
3. Has any logged issues in the `issues` JSON column

Risk level: **high** if 2+ risk factors, **medium** if 1 factor.

### Post-Departure Status Transitions

```
recordIqama()     → Candidate: DEPARTED → POST_DEPARTURE
recordAbsher()    → Candidate: DEPARTED → POST_DEPARTURE (if not already)
recordQiwa()      → Candidate: DEPARTED → POST_DEPARTURE (if not already)
markAsReturned()  → Candidate: → COMPLETED
```

---

## Service Methods (`DepartureService`)

### Pre-Departure

| Method | Description |
|--------|-------------|
| `recordPreDepartureBriefing($candidateId, $data)` | Record briefing (sets DEPARTURE_PROCESSING status) |
| `issuePTN($departure, $ptnNumber, $issuedDate, $expiryDate, $file)` | Issue PTN with evidence |
| `updateProtector($departure, $status, $notes, $certFile)` | Update protector clearance |
| `updateTicket($departure, $ticketData, $ticketFile)` | Update full ticket details |
| `scheduleBriefing($departure, $date)` | Schedule briefing (future date) |
| `completeBriefing($departure, $ackSigned, $notes, $docFile, $videoFile, $ackFile)` | Complete briefing |
| `markReadyToDepart($departure)` | Validates checklist; sets departure_status = READY_TO_DEPART |
| `recordActualDeparture($departure, $timestamp)` | Records departure; sets candidate to DEPARTED |

### Post-Departure

| Method | Description |
|--------|-------------|
| `recordDeparture($candidateId, $data)` | Core departure recording |
| `recordIqamaDetails($candidateId, $iqamaNumber, $issueDate, $expiryDate, $medicalPath)` | Iqama details |
| `recordAbsherRegistration($candidateId, $data)` | Absher registration |
| `recordQiwaActivation($departureId, $data)` | Qiwa/WPS activation |
| `recordSalaryConfirmation($departureId, $data)` | Salary confirmation |
| `recordAccommodation($departureId, $data)` | Accommodation details |
| `recordEmployerContact($departureId, $data)` | Employer contact details |
| `addCommunicationLog($departureId, $data)` | Add communication log entry |
| `record90DayCompliance($candidateId, $date, $isCompliant, $remarks)` | 90-day compliance |
| `reportIssue($candidateId, $type, $date, $desc, $severity, $evidencePath)` | Report issue (UUID ID) |
| `updateIssueStatus($issueId, $status, $resolutionNotes)` | Update issue lifecycle |
| `markAsReturned($candidateId, $returnDate, $reason, $remarks)` | Mark returned |

### Analytics & Reporting

| Method | Description |
|--------|-------------|
| `check90DayCompliance($departureId)` | Calculate compliance status for one departure |
| `get90DayComplianceReport($filters)` | Aggregated compliance report |
| `getPendingComplianceItems($filters)` | List departures with pending compliance items |
| `getStatistics($filters)` | Count-based statistics dashboard data |
| `getDepartureList($filters)` | Filtered departure list for reports |
| `getDepartureTimeline($candidateId)` | Chronological milestone timeline |
| `getEnhancedDashboard($campusId)` | Enhanced dashboard data |
| `getDepartureChecklist($departure)` | Checklist with completion percentage |
| `sendComplianceReminder($departureId)` | Log compliance reminder activity |

---

## Form Requests

| Class | Route | Key Rules |
|-------|-------|-----------|
| `UpdateTicketDetailsRequest` | POST `/departure/{departure}/ticket` | airline, flight_number, departure_date, departure_time (all required); airports required; ticket_file optional PDF/JPG/PNG ≤5MB |
| `CompleteBriefingRequest` | POST `/departure/{departure}/briefing/complete` | acknowledgment_signed required; briefing_document optional PDF ≤10MB; briefing_video optional MP4/MOV/AVI ≤102400KB; acknowledgment_file optional PDF ≤5MB |

---

## File Storage

All files stored in `storage/app/private/departures/{candidate_id}/` (accessed via `SecureFileController`):

| Subfolder | Contents |
|-----------|----------|
| `ptn/` | PTN evidence files (PDF, JPG, PNG) |
| `protector/` | Protector certificate files |
| `ticket/` | Ticket PDF/image files |
| `briefing/documents/` | Briefing document PDFs |
| `briefing/videos/` | Briefing video files (MP4/MOV/AVI, max 100 MB) |
| `briefing/acknowledgments/` | Signed acknowledgment files |
| `departure/medical/` | Post-arrival medical reports |
| `departure/salary-proof/` | Salary proof documents |
| `departure/issues/` | Issue evidence files |

---

## Access Control (RBAC)

| Action | Super Admin | Project Director | Campus Admin | OEP | Viewer |
|--------|:-----------:|:----------------:|:------------:|:---:|:------:|
| View departure list | Y | Y | Campus only | Own candidates | Y |
| View departure details | Y | Y | Campus only | Own candidates | Y |
| Create departure | Y | Y | Y | N | N |
| Update departure / checklist | Y | Y | Campus only | N | N |
| Record briefing | Y | Y | Y | N | N |
| Record actual departure | Y | Y | Y | N | N |
| Record Iqama / Absher / Qiwa | Y | Y | Y | N | N |
| Confirm salary | Y | Y | Y | N | N |
| Record 90-day compliance | Y | Y | Y | N | N |
| Report / update issues | Y | Y | Y | Y | N |
| View reports | Y | Y | Y | N | Y |
| Mark returned | Y | Y | Y | N | N |
| Delete departure | Y | N | N | N | N |

All routes protected by `role:admin,project_director,campus_admin,oep,visa_partner,viewer` middleware.

---

## Key Files

| File | Description |
|------|-------------|
| `app/Http/Controllers/DepartureController.php` | 30+ controller methods |
| `app/Services/DepartureService.php` | 27+ service methods |
| `app/Models/Departure.php` | Eloquent model with casts and value object accessors |
| `app/Policies/DeparturePolicy.php` | RBAC authorization policy |
| `app/Enums/DepartureStatus.php` | processing / ready_to_depart / departed / cancelled |
| `app/Enums/ProtectorStatus.php` | not_started / applied / done / pending / deferred |
| `app/Enums/BriefingStatus.php` | not_scheduled / scheduled / completed |
| `app/ValueObjects/PTNDetails.php` | PTN immutable value object |
| `app/ValueObjects/TicketDetails.php` | Ticket immutable value object |
| `app/ValueObjects/BriefingDetails.php` | Briefing immutable value object |
| `app/Http/Requests/UpdateTicketDetailsRequest.php` | Ticket update validation |
| `app/Http/Requests/CompleteBriefingRequest.php` | Briefing completion validation |
| `resources/views/departure/` | All departure Blade templates |
| `resources/views/departure/enhanced-dashboard.blade.php` | Enhanced dashboard view |
| `resources/views/departure/checklist.blade.php` | Per-departure checklist with inline forms |
| `tests/Feature/DepartureControllerTest.php` | Feature tests |
| `tests/Unit/DepartureServiceTest.php` | Unit tests |
| `tests/Unit/DepartureEnhancedTest.php` | Value object and enum unit tests |
| `tests/Feature/DepartureEnhancedTest.php` | HTTP tests for enhancement routes |

---

## Scheduled Commands

| Command | Schedule | Purpose |
|---------|----------|---------|
| `check-90-day-compliance` | Daily | Detect overdue compliance candidates |
| `check-document-expiry` | Daily | Alert on expiring Iqama documents |
| `check-90-day-compliance` | Daily | Post-arrival verification reminders |

---

## Testing

```bash
# Run all departure tests
php artisan test --filter=Departure

# Run specific test files
php artisan test tests/Feature/DepartureControllerTest.php
php artisan test tests/Unit/DepartureServiceTest.php
php artisan test tests/Unit/DepartureEnhancedTest.php
php artisan test tests/Feature/DepartureEnhancedTest.php
```

---

*Module 6 implemented and enhanced February 2026*
