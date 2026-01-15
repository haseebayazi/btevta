# WASL Digital ERP — Authoritative System Blueprint  
Version: 3.0  
Last Updated: 2026-01-15  
Status: Stable (Ready for Development)

---

## 1. Project Overview

### Purpose
WASL is an integrated Laravel-based ERP platform designed to manage the complete overseas employment and remittance lifecycle. It connects candidates, government, educational campuses, overseas promoters (OEPs), and families for seamless data flow, transparency, and traceability.

### Core Business Problem
- Manual and fragmented management of overseas employment candidates.
- Inefficient coordination among government, academic, and overseas promoters.
- Lack of traceable remittance tracking and welfare monitoring.
- Delays and risks due to duplication, missing documents, and process bottlenecks.

### Target Users & Roles
- Government administrators
- Campus staff and registrars
- Overseas Employment Promoters (OEPs)
- Trainers/instructors
- Overseas candidates (and their families)
- Support teams (correspondence, grievance)
- Auditors (internal/external)
- IT admins/superusers

---

## 2. System Scope

### In-Scope Features
- End-to-end candidate journey management (import → deployment → remittance tracking)
- Ten integrated modules as listed in proposal (see Section 4)
- Role-based dashboards and dynamic reporting
- API integrations (stubs for future use)
- Document management and audit compliance
- AI-powered analytics (prediction and bottleneck flagging; basic ML hooks)
- Multi-device support (responsive web, mobile-ready UIs)
- Secure, exportable, real-time reporting

### Explicitly Out-of-Scope
- Payroll/disbursement to staff (non-candidate)
- Site hosting/infrastructure specifics
- Deep financial/banking integrations (stubs only)
- Non-ERP features (marketing, CRM, etc.)
- Physical biometric hardware integrations
- Internationalization (i18n) unless mandated in future hooks

### Assumptions & Constraints
- Single source of truth: platform is authoritative for candidate data.
- All third-party data exchanges are via RESTful APIs (no direct DB access).
- Document uploads limited to PDF/JPEG/PNG (configurable).
- 100% digital process; no physical forms tracked.
- Only approved, pre-registered OEPs participate.
- All batch, OEP and visa assignments and process steps are strictly manual as per confirmed requirements.

---

## 3. User Roles & Permissions

### Role List

| Role                  | Description                                          |
|-----------------------|------------------------------------------------------|
| Super Admin           | Full access, manages platform settings               |
| Government Admin      | Can view, manage, and report on all candidates      |
| Campus Admin/Registrar| Manages campus registrations, candidate intake       |
| OEP Manager           | Manages overseas employment logistics, visa etc.     |
| Trainer/Instructor    | Manages training, assessments, feedback              |
| Candidate             | Views personal progress, uploads docs                |
| Family Member         | Views remittance status, welfare updates             |
| Correspondence Staff  | Sends/receives official communications               |
| Grievance Officer     | Manages complaints/escalations                       |
| Auditor/Compliance    | Views audit history, reports                         |
| External Integrator   | API access only (read/write as scoped)              |

### High-Level Permission Matrix

| Module                        | Super Admin | Govt Admin | Campus Admin | OEP | Trainer | Candidate | Family | Corr. Staff | Grievance | Auditor | Ext. API |
|-------------------------------|:-----------:|:----------:|:------------:|:---:|:-------:|:---------:|:------:|:------------:|:---------:|:-------:|:--------:|
| Candidate Listing             | RWX         | RWX        | RWX          | R   |         |           |        |             |           | R       | R        |
| Candidate Screening           | RWX         | RWX        | RX           | R   |         |           |        |             |           | R       | R        |
| Campus Registration           | RWX         | RX         | RWX          | RX  |         | RX        |        |             |           | R       | R        |
| Training Mgmt                 | RWX         | RX         | RX           | R   | RWX     | RX        |        |             |           | R       | R        |
| Visa Processing               | RWX         | RX         | RX           | RWX |         | RX        |        |             |           | R       | R        |
| Departure/Post-Deployment     | RWX         | RX         | RX           | RWX |         | RX        | RX     |             |           | R       | R        |
| Correspondence                | RWX         | RX         | RX           | RX  |         |           |        | RWX         |           | R       | R        |
| Complaints/Grievance          | RWX         | RX         | RX           | RX  | RX      | RWX       |        |             | RWX       | R       | R        |
| Document Archive              | RWX         | RX         | RWX          | RX  | RX      | RX        | RX     |             |           | R       | R        |
| Remittance Mgmt               | RWX         | RX         | RX           | RX  |         |           | R      |             |           | R       | RW       |

Legend: R=Read, W=Write, X=Execute/Manage

---

## 4. Functional Modules

### 4.1 Candidate Listing

- **Objective:** Aggregate and deduplicate candidate records from BTEVTA template import or manual entry.
- **Features:**
  - Bulk import using BTEVTA-provided templates, which are updated by users and uploaded
  - Manual batch assignment (no smart automation)
  - Duplicate checking (by CNIC, name)
  - Audit trail of imports

- **User Roles:** Govt Admin, Campus Admin, Super Admin  
- **Input:** Candidate lists (via WASL template)
- **Output:** Validated candidate batches, import logs
- **Dependencies:** None (entry point for candidate data)

### 4.2 Candidate Screening

- **Objective:** Record candidate contact and eligibility status via integrated call center workflow.
- **Features:**
  - In-app call log (reminders, follow-up flags)
  - Eligibility tagging, desk-based assessment
  - Upload call notes/recordings/verification docs

- **User Roles:** Govt Admin, Campus Admin, Screening Staff
- **Input:** Candidates (from Listing), screening outcomes
- **Output:** Screening status, performance analytics
- **Dependencies:** Candidate Listing

### 4.3 Registration at Campus

- **Objective:** Digitize all candidate and document registration for campus intake.
- **Features:**
  - Capture candidate profile and digital photo
  - Required document upload: CNIC, Passport, Medical, Academic Qualifications  
    - Fields for each: Document Name, Expiry Date, other relevant meta
  - Next-of-kin and consent form data
  - Manual OEP assignment to candidate

- **User Roles:** Campus Admin, Registrars, Candidates
- **Input:** Screened candidates, registration docs
- **Output:** Registered candidates, uploaded docs
- **Dependencies:** Candidate Screening

### 4.4 Training Management

- **Objective:** Deliver and track training completion for enrolled candidates.
- **Features:**
  - Training schedule/design
  - Attendance tracking
  - Mid/final assessment upload, auto certificate generation
  - Trainer evaluation and feedback

- **User Roles:** Trainer, Campus Admin, Candidate
- **Input:** Registered candidates, trainers, schedule, attendance/assessment
- **Output:** Training status, certifications
- **Dependencies:** Registration at Campus

### 4.5 Visa Processing

- **Objective:** Track and manage visa application and processing using required manual data input (no integrations).
- **Features:**
  - All visa process data (medical, biometric, interview, Takamol, visa, ticketing, etc.) input/uploaded manually by authorized users. No external or embassy API integrations.
  - Required fields for each stage/step, managed via the UI

- **User Roles:** OEP Manager, Govt Admin
- **Input:** Trained candidates, manually entered visa process data
- **Output:** Candidate visa status, stage reports
- **Dependencies:** Training Management

### 4.6 Departure & Post-Deployment

- **Objective:** Manage candidate departure events, post-arrival compliance and welfare using data uploaded or entered by admins/OEPs.
- **Features:**
  - Track pre-departure briefings and post-arrival doc uploads
  - Manual entry for Iqama, Absher, Qiwa IDs
  - Salary/welfare monitoring, post-departure issue tracking

- **User Roles:** OEP Manager, Candidate, Family, Govt Admin
- **Input:** Visa-approved candidates, post-arrival docs
- **Output:** Deployment/welfare tracking reports
- **Dependencies:** Visa Processing

### 4.7 Correspondence

- **Objective:** Centralized tracking and archive of all official communication.
- **Features:**
  - Register and upload of all incoming/outgoing letters, memos, and emails
  - Log sender, recipient, file, subject, official reference
  - PDF letter/memo upload

- **User Roles:** Correspondence Staff, Admins
- **Input:** Uploaded communication records
- **Output:** Logs, pendency tracker
- **Dependencies:** Linked as needed throughout all modules

### 4.8 Complaints & Grievance Redressal

- **Objective:** In-app complaints lifecycle for all user types, enforcing SLAs.
- **Features:**
  - Complaint submission by all key actors, tagged by category (training, visa, salary, conduct, etc.)
  - Escalation/SLA workflow (SLA: 3–5 days per stage; flag violations for review)
  - Status transitions: Open → In Progress → Escalated → Resolved → Closed

- **User Roles:** Candidate, Trainer, OEP, Grievance Officer, Admin
- **Input:** Complaint entries and attachments
- **Output:** Resolution analytics and SLA compliance metrics
- **Dependencies:** All modules

### 4.9 Document Archive

- **Objective:** Secure, versioned repository for all required candidate and process documentation.
- **Features:**
  - Central archive, version control, and access logging
  - Filter/search by candidate, campus, trade, OEP, document type
  - Mandatory doc expiry/validity where relevant (e.g. CNIC, Medical)

- **User Roles:** All roles with given access
- **Input:** Documents from modules, version history
- **Output:** Retrieval logs, expiry alerts
- **Dependencies:** All modules for document linking

### 4.10 Remittance Management

- **Objective:** Post-deployment remittance tracking (strictly manual entry and evidence upload).
- **Features:**
  - Manually entered remittance details (amount, timestamp, purpose tag)
  - Upload of digital proof (receipt, photo) is mandatory for each entry
  - Real-time sender/beneficiary linkage and impact reporting

- **User Roles:** Candidate, Family, Admin, Auditor
- **Input:** Remittance entries and proofs (manual data entry)
- **Output:** Inflow, usage analytics, welfare reports
- **Dependencies:** Departure & Post-Deployment

---

## 5. Data Architecture

### Core Entities (Tables)
- Candidates
- Campuses
- Batches
- Districts
- Trades
- Screening Logs (Call logs)
- Registrations
- OEPs (Promoters)
- Training Schedules
- Training Attendance
- Assessments/Certificates
- Visa Records
- Departure Records
- Post-Deployment Records
- Correspondence Records
- Complaints
- Documents (Archive)
- Remittances
- Users
- Roles & Permissions
- Audit Logs

### Relationships
- Candidate ⟶ Campus [M:1]
- Candidate ⟶ Batch [M:1] (assigned manually)
- Candidate ⟶ OEP [M:1] (manual assignment at registration)
- Batch ⟶ Trade [M:1]
- Candidate ⟶ Screening Log [1:M]
- Candidate ⟶ Training Attendance [1:M]
- Candidate ⟶ Visa Record [1:1] (all visa stages manually tracked)
- Candidate ⟶ Departure Record [1:1]
- Candidate ⟶ Documents [1:M] (minimum: CNIC, Passport, Medical, Academic Qualifications)
- Candidate ⟶ Remittance [1:M] (manual entries)
- Candidate ⟶ Complaints [1:M]
- User ⟶ Roles [M:M]
- All entities ⟶ Audit Log [N:M]

### Ownership & Audit Rules
- All process data is owned by the originating organization/user but is platform-controlled.
- Soft deletes for all entities (except critical audit logs).
- Audit: All create, update, delete actions logged with before/after snapshots.

---

## 6. Application Flow

### End-to-End Lifecycle

1. **Candidate Import & Listing:**  
   - User downloads WASL-provided template  
   - Adds/updates candidate data  
   - Uploads through the web UI  
   - System validates entries, checks duplicates  
   - Candidates are assigned to batches manually

2. **Screening:**  
   - Desk/admin staff contact candidates  
   - Logs calls, uploads screening docs  
   - Tags as eligible/rejected

3. **Registration:**  
   - Candidate attends campus  
   - Admin uploads all required personal documents  
   - Next-of-kin/consent forms created  
   - OEP is assigned manually during registration

4. **Training:**  
   - Schedules set  
   - Attendance and assessments tracked  
   - Completion (certification) recorded

5. **Visa Processing:**  
   - All fields per visa stage entered/uploaded by authorized users  
   - Data covers medical, biometric, interview, Takamol, visa, ticketing, etc.

6. **Departure/Post-Deployment:**  
   - Pre-departure and post-arrival events logged  
   - Iqama/Absher/Qiwa data entered  
   - Welfare and salary monitoring  
   - Issues tracked in the system

7. **Remittance:**  
   - After deployment, authorized users or family manually enter each remittance  
   - Mandatory upload of receipt/proof for each entry  
   - All usage categorized/tagged

8. **Reporting/Analytics:**  
   - Data flows are reflected in dashboards and reports  
   - Process bottleneck prediction supports efficiency goals

### Cross-Module Data Handoff

- Candidate record (by unique ID) is the unifying reference across all modules.
- All document and process data keyed off the candidate.
- No automated assignments or integrations; all transitions and linkages are by manual system action.
- Identification or status transitions noted by explicit status or timestamp fields.

### Status Transitions (Key Examples)

- Candidate: Imported → Screened → Registered → Trained → VisaReady → Deployed → Remitting
- Complaint: Open → In Progress → Escalated → Resolved → Closed
- Document: Uploaded → Pending Verification → Verified → Expired

---

## 7. Technical Architecture

### Laravel Folder Structure

```
app/
  └── Models/
  └── Services/
  └── Http/
      ├── Controllers/
      ├── Requests/
      └── Resources/
  └── Policies/
  └── Observers/
database/
  ├── migrations/
  ├── seeders/
public/
resources/
  ├── views/  (Blade templates)
routes/
  ├── web.php
  ├── api.php
config/
storage/
```

### Controllers / Services / Models Responsibilities

- **Controllers:**  
  Responsible for request validation, enforcing role and entity permissions, orchestration of high-level workflows.  
  Must not contain business logic.

- **Services:**  
  Contain business operations logic (e.g., handling bulk import, generating reports, applying batch/manual OEP assignments).  
  Decouple logic from controllers.

- **Models:**  
  Eloquent ORM mapping; define relationships, casts, scopes.

### Validation Strategy

- Laravel Form Request validation for all web/API inputs.
- Batch import: enforce WASL template structure, required document types, duplication checks by CNIC/name.
- All document uploads validated for type, size, and required document fields (name, expiry date, etc).
- Centralized and consistent error responses.

### Authorization Approach

- Policies for entity-based CRUD and workflow transitions.
- Gates for special role and action checks.
- Every sensitive action checked against the permission matrix.
- Audit logs for all data mutations.

### API vs Web Routes

- All APIs versioned under `/api` prefix for partner integrations and mobile use.
- Blade routes protected by standard session middleware.
- API auth via Laravel Sanctum or Passport.
- No hardcoded endpoints—use config and route files.

---

## 8. Non-Functional Requirements

### Security

- HTTPS enforced
- Passwords managed via Laravel best practices (bcrypt/argon2)
- All file uploads scanned; strict extensions and virus scanning hook ready
- Least privilege role controls everywhere; no access without explicit grant
- All actions logged for audit

### Performance

- Bulk imports/exports processed asynchronously
- All listings paginated
- Reports built with eager loads, chunking for large datasets

### Scalability

- Modular structure—new modules/fields extendable via config/DB without code changes
- Queued background tasks for intensive processes

### Logging & Auditing

- Daily audit logs
- All create/update/delete actions for key entities logged
- Error logs written to `storage/logs`
- Audit log retention: 12 months minimum

### Error Handling

- Unified error handling for web and API (API responses must be structured with codes/messages)
- All business logic exceptions caught and meaningful messages/errors returned

---

## 9. Development Rules

- Adhere to PSR-12 for PHP and Laravel
- Use singular PascalCase for class/entity naming, plural snake_case for table/DB
- All data changes via Laravel migrations; never directly in DB
- Use factories and seeders for all entity test/demo data
- No hardcoded settings, values, or credentials; use `config` or `.env` exclusively
- Business logic never in Blade views
- All process logic, assignment, and data entry must be strictly manual as reflected in requirements above

---

## 10. Future Expansion Hooks

- **Planned Modules:** Recruitment partner CRM, Placement marketplace, Advanced ML/analytics, In-app messaging
- **Integration Points:** BTEVTA, embassy, bank APIs (RESTful, stubs for future), SMS gateways, notification services, document verification with NADRA/third-parties
- **Config Extensibility:**  
  - All assignment and workflow rules managed via DB or `/config/wasl.php`  
  - API and entity versioning support  
  - Extendable reporting and document types

---

## 11. Open Questions / Clarifications

_All previously open questions have been answered and are now reflected in module and architecture details above. All assignments (batch, OEP, visa, remittance entry) are confirmed to be manual. Required document types and their structure are strictly defined. Visa and remittance flows are manual-entry and evidence-driven only. SLA for complaint resolution set at 3–5 days per stage. No integrations or automations are to be implemented unless specified under future hooks._

---

## Institutional Credits

- Product Conceived by: AMAN Innovatia
- Developed by: The LEAP @ ZAFNM
- Contact: [info@amaninnovatia.com](mailto:info@amaninnovatia.com)
- Web: [www.amaninnovatia.com](https://www.amaninnovatia.com)

---

> _WASL — Empowering Journeys from Enrollment to Earning through Digital Connectivity._
```
