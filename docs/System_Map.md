# WASL Digital ERP — Authoritative System Blueprint  
Version: 1.0  
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
- API integrations (stubs; see expansion hooks)
- Document management and audit compliance
- AI-powered analytics (prediction and bottleneck flagging; basic ML hooks)
- Multi-device support (responsive web, mobile-ready UIs)
- Secure, exportable, real-time reporting

### Explicitly Out-of-Scope
- Payroll/disbursement to staff (non-candidate)
- Site hosting/infrastructure specifics
- Deep financial/banking integrations (only stubs intended)
- Non-ERP features (marketing, CRM, etc.)
- Physical biometric hardware integrations
- Internationalization (i18n) unless mandated in future hooks

### Assumptions & Constraints
- Single source of truth: platform is authoritative for candidate data.
- All third-party data exchanges are via RESTful APIs (no direct DB access).
- Document uploads are limited to PDF/JPEG/PNG (configurable).
- 100% digital process; no physical forms tracked.
- Only approved, pre-registered OEPs participate.

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

- **Objective:** Aggregate and deduplicate candidate records from multiple sources (BTEVTA import, manual entry).
- **Features:**
  - Bulk import (BTEVTA template, CSV)
  - Auto batch assignment (by trade/district/intake)
  - Duplicate checking (CNIC, name)
  - Smart campus allocation (proximity, demand)
  - Import audit trail
  
- **User Roles:** Govt Admin, Campus Admin, Super Admin  
- **Input:** Candidate lists/files, manual entries
- **Output:** Validated candidate batches, import logs
- **Dependencies:** None (entry point for candidate data)

### 4.2 Candidate Screening

- **Objective:** Record candidate contact and eligibility status via integrated call center workflow.
- **Features:**
  - In-app call log (reminders, follow-up flags)
  - Eligibility tagging, desk-based assessment
  - Upload call notes/recordings/verification docs
  
- **User Roles:** Govt Admin, Campus Admin, Screening Staff
- **Input:** Candidate records, screening outcomes
- **Output:** Screening status, performance analytics
- **Dependencies:** Candidate Listing

### 4.3 Registration at Campus

- **Objective:** Digitize registration/verification for campus intake.
- **Features:**
  - Picture capture, autofill from screening data
  - Document upload (CNIC, passport, education, medical)
  - Next-of-kin and consent forms collection
  - Allocation to OEP
  
- **User Roles:** Campus Admin, Registrars, Candidates
- **Input:** Screened candidates, registration details
- **Output:** Registered candidates, uploaded docs
- **Dependencies:** Candidate Screening

### 4.4 Training Management

- **Objective:** Manage, track, and certify training programs for candidates.
- **Features:**
  - Training schedule/design management
  - Attendance tracking
  - Assessment uploads, auto certificate generation
  - Trainer evaluation
  
- **User Roles:** Trainer, Campus Admin, Candidate
- **Input:** Registered candidates, trainer assignments
- **Output:** Training status, certifications, performance data
- **Dependencies:** Registration at Campus

### 4.5 Visa Processing

- **Objective:** Orchestrate all pre-departure legal, medical, and administrative steps.
- **Features:**
  - Manage pre-departure workflow (medical, biometric, interview, visa)
  - Real-time update with OEP, embassy integration
  - E-number, PTN, attestation, ticket tracking
  
- **User Roles:** OEP Manager, Govt Admin
- **Input:** Trained candidates, required documents
- **Output:** Visa status per candidate, stage reports
- **Dependencies:** Training Management

### 4.6 Departure & Post-Deployment

- **Objective:** Monitor candidate departure, arrival, and post-deployment welfare.
- **Features:**
  - Pre-departure briefing tracking
  - Iqama, Absher, Qiwa ID management
  - Salary verification, welfare recording
  - Issue/incident tracker
  
- **User Roles:** OEP Manager, Candidate, Family, Govt Admin
- **Input:** Visa approved candidates, post-arrival docs
- **Output:** Deployment logs, welfare status, tracking reports
- **Dependencies:** Visa Processing

### 4.7 Correspondence

- **Objective:** Centralize and log all official platform communications.
- **Features:**
  - Register incoming/outgoing letters, memos, emails
  - Sender, recipient, file, subject, reference management
  - Upload PDF attachments
  
- **User Roles:** Correspondence Staff, Admins
- **Input:** Communication records, uploaded docs
- **Output:** Communication logs, pendency tracker
- **Dependencies:** All modules as needed

### 4.8 Complaints & Grievance Redressal

- **Objective:** Enable lifecycle complaint management for all user types.
- **Features:**
  - In-app complaint submission
  - Category/tag assignment (training, visa, conduct, etc.)
  - Escalation matrix, SLA-based workflow
  
- **User Roles:** Candidate, Trainer, OEP, Grievance Officer, Admin
- **Input:** Complaint tickets, attachments
- **Output:** Resolution status, SLA analytics
- **Dependencies:** All modules; module tags for complaint linkage

### 4.9 Document Archive

- **Objective:** Provide secure, versioned, indexed storage of all candidate/process documents.
- **Features:**
  - Centralized repository linked to modules
  - Versioning and access logs
  - Filter/search by candidate, campus, trade, etc.
  
- **User Roles:** All roles with respective access
- **Input:** Documents from modules, version history
- **Output:** Retrieval logs, expiry alerts, verification status
- **Dependencies:** All modules; document links

### 4.10 Remittance Management

- **Objective:** Capture, categorize, and analyze candidate remittances post-deployment.
- **Features:**
  - Timestamped remittance tracking
  - Tagging by use-case (education, rent, etc.)
  - Upload digital proof (receipts, photos)
  - Alert triggers
  
- **User Roles:** Candidate, Family, Admin, Auditor
- **Input:** Remittance records (manual/API), uploaded proofs
- **Output:** Inflow/usage analytics, welfare reports
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
- Candidate ⟶ Batch [M:1]
- Candidate ⟶ OEP [M:1]
- Batch ⟶ Trade [M:1]
- Candidate ⟶ Screening Log [1:M]
- Candidate ⟶ Training Attendance [1:M]
- Candidate ⟶ Visa Record [1:1]
- Candidate ⟶ Departure Record [1:1]
- Candidate ⟶ Documents [1:M]
- Candidate ⟶ Remittance [1:M]
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

1. **Candidate Import & Listing:** Data entry (bulk/manual) → validation → batch assignment.
2. **Screening:** Call logs → status tagging → eligibility or rejection recorded.
3. **Registration:** Document capture → next-of-kin/consent → OEP assignment.
4. **Training:** Schedule assignment → attendance/assessment → completion/certification.
5. **Visa:** Initiate workflow → track stages (medical, biometric, embassy) → visa issued.
6. **Departure:** Schedule briefing → post-arrival verification → ID activation.
7. **Post-Deployment:** Salary verification, welfare updates, issue/incident tracking.
8. **Remittance:** Receipts logged → purpose tagged → alerts for missing proof/no activity.
9. **Reporting/AI Analytics:** Throughout — dashboards, bottleneck detection, predictions.

### Cross-Module Data Handoff
- Candidate ID acts as the primary key across all modules.
- Modules fetch/link data using defined relationships and foreign keys.
- Status fields/update timestamps dictate the module transitions (example: `screened_status`, `visa_status`, `training_complete`).

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

- **Controllers:** Handle request validation, authorization, and high-level orchestration; thin as possible; avoid business logic.
- **Services:** Encapsulate business processes (e.g., batch import, AI analytics).
- **Models:** ORM mapping, relationships, scopes.

### Validation Strategy

- Laravel Form Request validation for all web/API inputs.
- Custom rules for batch imports, file types, CNIC format, document expiries.
- Centralized validation error response structure.

### Authorization Approach

- Use Laravel policies for entity-based CRUD control.
- Gates for role-based or action-based checks outside of entity CRUD.
- All sensitive actions double-checked against role matrix.

### API vs Web Routes

- RESTful API under `/api` prefix for integrations and mobile/frontends.
- Web routes for Blade-based interface, protected by session-based middleware.
- API auth via Laravel Sanctum or Passport (configurable).

---

## 8. Non-Functional Requirements

### Security

- Enforce HTTPS across platform.
- Strong password hashing with Laravel defaults.
- Role-based access control; least privilege by default.
- File uploads virus-scanned (optional hook).
- All sensitive activities logged.

### Performance

- Async/batch processing for bulk imports.
- Eager-loading on ORM for complex reports.
- Paginated listings throughout.

### Scalability

- Modular codebase for future horizontal scaling.
- Config-based entity mapping for new districts/trades/OEPs.
- Queue jobs for heavy tasks (notifications, report generation).

### Logging & Auditing

- Daily audit logs; entity change history retained for one year minimum.
- All system warnings/errors logged in `storage/logs/`.
- User action history for compliance.

### Error Handling

- Centralized error handler.
- Consistent API error responses (with codes, messages).
- Friendly web error screens for end users; detailed logs for admins.

---

## 9. Development Rules

- Use PSR-12 for PHP and Laravel coding standards.
- Controllers, models, services, and requests must follow singular, PascalCase naming.
- Table names in plural snake_case (`candidates`, `batches`).
- Use Laravel migrations for all schema updates; NO direct DB changes.
- All entities seeded via factories; bulk import seeders for initial setup.
- Do NOT hardcode values (district names, trades, OEPs, file paths) — use config, DB driven.
- No business logic in Blade templates or views.
- All external credentials and URLs in `.env`, not in codebase.

---

## 10. Future Expansion Hooks

- **Modules Planned:** 
  - Recruitment partner CRM
  - Marketplace for placement offers
  - Advanced analytics (ML-powered trends)
  - In-app messaging (candidate/OEP/campus)
  
- **Integrations:**
  - BTEVTA, embassy, bank APIs (RESTful endpoints, to be further scoped)
  - SMS gateway, notification APIs
  - External document verification (NADRA, etc.)

- **Extensibility:**
  - Config/DB-driven module toggling.
  - API endpoints versioned/futureproofed.
  - All entities and workflows extensible via `config/wasl.php` (planned settings registry).

---

## 11. Open Questions / Clarifications

- **Candidate Batch Assignment:** Detailed algorithm for “smart campus allocation” needs confirmation (currently assumes proximity and trade demand; further logic may be required).
- **Import Templates:** BTEVTA format variations, frequency, and error management to be clarified.
- **Document Types:** Precise list of required document types and expiry rules (e.g., medical, CNIC expiry notifications).
- **OEP Assignment:** Are candidates manually assigned to OEPs at registration, or is there an allocation rule? Workflow logic needed.
- **Visa Processing:** Exact integration points with embassies, Takamol, etc., undefined; currently designed as manual/API stub.
- **AI Analytics:** Scope of ML (only bottleneck prediction for v1); future specifics to be agreed.
- **Remittance Tracking:** Trusted proof mechanism (only manual uploads, no banking API at present).
- **Complaint Escalation Matrix:** SLA handling (3-5 days) to be strictly defined with roles; possible auto-escalation logic unconfirmed.
- **Role List:** Are there sub-admin/staff hierarchies? Permission matrix based on provided roles — refine as roles evolve.
- **External API Integrators:** Access rights and rate limits to be scoped in detail; current document assumes read/write per explicit permission.

---

## Institutional Credits

- Product Conceived by: AMAN Innovatia
- Developed by: The LEAP @ ZAFNM
- Contact: [info@amaninnovatia.com](mailto:info@amaninnovatia.com)
- Web: [www.amaninnovatia.com](https://www.amaninnovatia.com)

---

> _WASL — Empowering Journeys from Enrollment to Earning through Digital Connectivity._
```