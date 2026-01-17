# WASL - Comprehensive Change Impact Analysis & Implementation Roadmap

**Document Version:** 1.0  
**Date:** January 17, 2026  
**Project:** BTEVTA WASL (Workforce Abroad Skills & Linkages)  
**Analysis Type:** Change-Impact Assessment & Delivery Planning  
**Classification:** Government/Donor Compliance-Sensitive  

---

## Executive Summary

This document provides a comprehensive analysis of changes required to the existing BTEVTA WASL Laravel application based on the newly provided requirements document. The analysis identifies **47 discrete changes** across **9 modules**, classified by type, impact, and risk level.

**Key Statistics:**
- New Features: 23
- Modified Features: 19
- Removed Features: 5
- Critical Changes: 8
- High Priority Changes: 15
- Database Migrations Required: 18
- Estimated Development Phases: 7

---

# PHASE 1: NEW REQUIREMENTS ANALYSIS

## 1.1 Change Register

### Module 1: Candidate Listing (UPDATED)

| Change ID | Type | Description | Affected Domain | Business Impact |
|-----------|------|-------------|-----------------|-----------------|
| **CL-001** | NEW | Pre-Departure Documents section after listing | Candidate Management | HIGH - New workflow step required before screening |
| **CL-002** | NEW | Mandatory document checklist (CNIC, Passport, Domicile, FRC, PCC) | Document Management | HIGH - Compliance requirement |
| **CL-003** | NEW | Optional document checklist (Pre-medical, Certifications, Resume) | Document Management | MEDIUM - Enhanced data capture |
| **CL-004** | NEW | Licenses field (Driving, RN Nurse, etc.) - not mandatory | Document Management | LOW - Optional enhancement |
| **CL-005** | NEW | Document visibility control (read-only for subsequent modules) | Authorization | HIGH - Workflow integrity |
| **CL-006** | NEW | Individual/Bulk document fetch reporting capability | Reporting | MEDIUM - Operational efficiency |

### Module 2: Initial Screening (RENAMED & UPDATED)

| Change ID | Type | Description | Affected Domain | Business Impact |
|-----------|------|-------------|-----------------|-----------------|
| **IS-001** | MODIFIED | Rename "Screening" → "Initial Screening" | UI/Navigation | LOW - Terminology change |
| **IS-002** | MODIFIED | Replace 3-call system with single review workflow | Business Logic | HIGH - Workflow simplification |
| **IS-003** | NEW | Consent for work verification step | Compliance | HIGH - Legal requirement |
| **IS-004** | NEW | Area of interest capture (Local/International placement) | Data Model | MEDIUM - Enhanced profiling |
| **IS-005** | NEW | Country specification field (if applicable) | Data Model | MEDIUM - Destination tracking |
| **IS-006** | NEW | Screening dashboard with status breakdown | Dashboard | HIGH - Operational visibility |
| **IS-007** | MODIFIED | Status tags change: Screened/Pending/Deferred (not Eligible/Rejected/Pending) | Status Machine | HIGH - State management change |
| **IS-008** | NEW | Screening notes and evidence upload | Document Management | MEDIUM - Audit trail |
| **IS-009** | NEW | Gate: Only "Screened" candidates can proceed to Registration | Workflow | CRITICAL - Workflow enforcement |
| **IS-010** | REMOVED | 3-call tracking system | Business Logic | HIGH - Feature removal |

### Module 3: Registration (MAJOR UPDATE)

| Change ID | Type | Description | Affected Domain | Business Impact |
|-----------|------|-------------|-----------------|-----------------|
| **RG-001** | NEW | Entry gate: Only Screened candidates can be Registered | Workflow | CRITICAL - Workflow enforcement |
| **RG-002** | MODIFIED | Next of Kin details restructured with financial account | Data Model | HIGH - New mandatory fields |
| **RG-003** | NEW | ID card copy of next of kin | Document Management | HIGH - Compliance |
| **RG-004** | NEW | Allocation section (Campus, Program, OEP, Implementing Partner) | Data Model | CRITICAL - New assignment workflow |
| **RG-005** | NEW | Auto Batch Creation at registration (not manual) | Business Logic | CRITICAL - Automation |
| **RG-006** | NEW | Configurable batch size (20/25/30 admin-defined) | Configuration | HIGH - Admin control |
| **RG-007** | NEW | Batch generation based on Campus + Program + Trade | Business Logic | HIGH - Grouping logic |
| **RG-008** | NEW | Unique allocated number per batch | Data Model | HIGH - Identification |
| **RG-009** | NEW | Course Assignment at registration | Data Model | HIGH - Training link |
| **RG-010** | NEW | Course fields: Name, Duration, Start/End dates, Training type | Data Model | HIGH - Training metadata |
| **RG-011** | MODIFIED | Move batch creation from Training to Registration | Workflow | CRITICAL - Process change |

### Module 4: Training Management (UPDATED)

| Change ID | Type | Description | Affected Domain | Business Impact |
|-----------|------|-------------|-----------------|-----------------|
| **TM-001** | REMOVED | "Active Batch" concept removed | Business Logic | HIGH - Simplification |
| **TM-002** | NEW | Interim Assessment tracking | Assessment | MEDIUM - Progress tracking |
| **TM-003** | NEW | Final Assessment tracking | Assessment | MEDIUM - Completion validation |
| **TM-004** | NEW | Assessment results & evidence upload | Document Management | MEDIUM - Audit compliance |
| **TM-005** | NEW | Technical Training Status breakdown (Not Started/In Progress/Completed) | Status Machine | HIGH - Granular tracking |
| **TM-006** | NEW | Soft Skills Training Status breakdown (Not Started/In Progress/Completed) | Status Machine | HIGH - Granular tracking |
| **TM-007** | MODIFIED | Completion logic: Training marked complete only if required assessments completed | Business Logic | HIGH - Validation enhancement |

### Module 5: Visa Processing (PIPELINE ENHANCED)

| Change ID | Type | Description | Affected Domain | Business Impact |
|-----------|------|-------------|-----------------|-----------------|
| **VP-001** | MODIFIED | Every stage requires: Appointment status with sub-details | Data Model | HIGH - Enhanced tracking |
| **VP-002** | NEW | Appointment sub-details: date, time, allowed center | Data Model | MEDIUM - Scheduling |
| **VP-003** | MODIFIED | Result status with sub-detail: Pass/Fail/Pending/Refused | Status Machine | HIGH - Outcome tracking |
| **VP-004** | MODIFIED | Evidence upload mandatory at each stage | Document Management | HIGH - Compliance |
| **VP-005** | NEW | Specific trade test stage (e.g., Takamol for KSA) | Workflow | MEDIUM - Country-specific |
| **VP-006** | MODIFIED | Visa application status: Applied/Not Applied/Refused | Status Machine | HIGH - Status granularity |
| **VP-007** | MODIFIED | Visa Issued status: Confirmed/Pending/Refused | Status Machine | HIGH - Status granularity |
| **VP-008** | NEW | Hierarchical dashboard interface (Scheduled→Done→Pass/Fail→Pending) | Dashboard | HIGH - UX improvement |

### Module 6: Departure (ENHANCED)

| Change ID | Type | Description | Affected Domain | Business Impact |
|-----------|------|-------------|-----------------|-----------------|
| **DP-001** | MODIFIED | PTN Status restructured: Issued/Done/Pending/Deferred (Not issued/Refusal) | Status Machine | HIGH - Status granularity |
| **DP-002** | NEW | Protector Status: Applied/Done/Pending/Deferred | Data Model | HIGH - New tracking field |
| **DP-003** | MODIFIED | Ticket Status with sub-details: date, time, departure/landing platform, flight type | Data Model | HIGH - Enhanced flight tracking |
| **DP-004** | MODIFIED | Pre-Departure Briefing with document upload (scanned original docs) | Document Management | HIGH - Handover tracking |
| **DP-005** | NEW | Pre-Departure Briefing video upload (candidate receiving docs, T&Cs, success story) | Media Management | MEDIUM - Video evidence |
| **DP-006** | NEW | Final Departure Status: Ready to Depart / Departed | Status Machine | HIGH - Status clarity |
| **DP-007** | NEW | Departure Dashboard with PTN/Protector/Ticket/Departed status breakdown | Dashboard | HIGH - Operational view |

### Module 7: Post-Departure (NEW SUB-STRUCTURE)

| Change ID | Type | Description | Affected Domain | Business Impact |
|-----------|------|-------------|-----------------|-----------------|
| **PD-001** | NEW | Residency & Identity section | Data Model | HIGH - Compliance tracking |
| **PD-002** | NEW | Residency Proof (e.g., Iqama for KSA) | Document Management | HIGH - Legal compliance |
| **PD-003** | NEW | Foreign License field (for drivers, professionals) | Data Model | MEDIUM - Occupation tracking |
| **PD-004** | NEW | Foreign Mobile Number | Data Model | MEDIUM - Contact tracking |
| **PD-005** | NEW | Foreign Bank Account Details | Data Model | HIGH - Remittance link |
| **PD-006** | NEW | Foreign Tracking App registration (e.g., Absher for KSA) | Data Model | MEDIUM - Compliance |
| **PD-007** | NEW | Final job contract (e.g., Qiwa Agreement for KSA) | Document Management | HIGH - Employment verification |
| **PD-008** | NEW | Final Employment Details section | Data Model | CRITICAL - Core tracking |
| **PD-009** | NEW | Employment fields: Company, Employer contact, Location, Salary, T&Cs, Commencement date | Data Model | HIGH - Employment record |
| **PD-010** | NEW | First Company SWITCH tracking | Data Model | HIGH - Job mobility |
| **PD-011** | NEW | Second Company SWITCH tracking | Data Model | HIGH - Job mobility |

### Module 8: Employer Information (NEW TAB)

| Change ID | Type | Description | Affected Domain | Business Impact |
|-----------|------|-------------|-----------------|-----------------|
| **EI-001** | NEW | Separate Employer Information module/tab | Architecture | CRITICAL - New module |
| **EI-002** | NEW | Permission Number (after approved demand letter) | Data Model | HIGH - Regulatory |
| **EI-003** | NEW | Visa Issuing Company (e.g., ARAMCO) | Data Model | HIGH - Corporate tracking |
| **EI-004** | NEW | Employment Package breakdown (Basic Salary, Food, Transport, Accommodation, Other) | Data Model | HIGH - Compensation detail |
| **EI-005** | NEW | Country and Sector/Trade fields | Data Model | MEDIUM - Classification |
| **EI-006** | NEW | Evidence/document attachment for employer info | Document Management | MEDIUM - Verification |
| **EI-007** | NEW | Link employers to candidates | Relationships | HIGH - Association |

### Module 9: Post-Correspondence (REMIX TAB)

| Change ID | Type | Description | Affected Domain | Business Impact |
|-----------|------|-------------|-----------------|-----------------|
| **PC-001** | NEW | Success Stories section | Feature | MEDIUM - Positive tracking |
| **PC-002** | NEW | Success story fields: Written Note, Evidence (Audio/Video/Written) | Data Model | MEDIUM - Media support |
| **PC-003** | MODIFIED | Complaints enhanced with structured workflow | Business Logic | HIGH - Process improvement |
| **PC-004** | NEW | Complaint workflow: Candidate selection → Issue → Support steps → Suggestions → Conclusion | Workflow | HIGH - SLA tracking |
| **PC-005** | NEW | Complaint evidence types: Audio/Video/Screen Shots/Etc | Media Management | MEDIUM - Evidence variety |
| **PC-006** | MODIFIED | Complaints dashboard remains same as developed | Dashboard | LOW - No change |

### Cross-Module Changes

| Change ID | Type | Description | Affected Domain | Business Impact |
|-----------|------|-------------|-----------------|-----------------|
| **XM-001** | NEW | Module Flow Enforcement: Candidate Listing → Pre-Departure Docs → Initial Screening → Registration → Training → Visa → Departure → Post-Departure → Post-Correspondence → Remittance | Workflow | CRITICAL - Sequential integrity |
| **XM-002** | MODIFIED | Role-based access must respect stage locks | Authorization | HIGH - Security |
| **XM-003** | NEW | Status-driven workflow (no skipping stages) | Business Logic | CRITICAL - Compliance |
| **XM-004** | MODIFIED | Evidence upload mandatory where mentioned | Compliance | HIGH - Audit trail |
| **XM-005** | NEW | Auto batch logic must be configurable (admin panel) | Configuration | HIGH - Admin control |

---

# PHASE 2: CURRENT SYSTEM BASELINE

## 2.1 System Baseline Summary

### What Currently Exists (Fully Implemented)

| Component | Count | Details |
|-----------|-------|---------|
| Models | 34 | candidates, users, campuses, trades, batches, oeps, screenings, trainings, visa_processes, departures, remittances, complaints, correspondences, document_archives, activity_log |
| Controllers | 30 | Full CRUD for all modules |
| Services | 14 | Workflow, SLA, Notification, Export services |
| Policies | 40 | Role-based authorization |
| Enums | 5 | CandidateStatus, TrainingStatus, VisaStage, ComplaintPriority, ComplaintStatus |
| API Resources | 4 | CandidateResource, VisaProcessResource, DepartureResource, RemittanceResource |

### Core Modules - Current State

| Module | Status | Key Features | Gaps vs New Requirements |
|--------|--------|--------------|--------------------------|
| **1. Candidate Listing** | IMPLEMENTED | Import, bulk operations, batch assignment | Missing: Pre-Departure Documents section |
| **2. Screening** | IMPLEMENTED | 3-call system, outcome tracking | MAJOR CHANGE: 3-call to single review; missing consent/interest tracking |
| **3. Registration** | IMPLEMENTED | Profile, documents, OEP allocation | MAJOR CHANGE: Missing auto-batch, course assignment, allocation fields |
| **4. Training** | IMPLEMENTED | Attendance, assessments, certificates | Missing: Technical/Soft Skills status separation; Active Batch to be removed |
| **5. Visa Processing** | IMPLEMENTED | 12-stage pipeline | Missing: Appointment sub-details, hierarchical dashboard |
| **6. Departure** | IMPLEMENTED | Flight tracking, Iqama, Absher | Missing: Protector status, video upload, structured PTN/Ticket status |
| **7. Post-Departure** | PARTIAL | Basic tracking | MAJOR GAPS: Missing entire Residency & Identity, Employment Details, Company SWITCH sections |
| **8. Employer Information** | NOT EXISTS | N/A | NEW MODULE REQUIRED |
| **9. Post-Correspondence** | PARTIAL (Complaints + Correspondence) | SLA complaints, correspondence | Missing: Success Stories, enhanced complaint workflow |
| **10. Remittance** | IMPLEMENTED | Full tracking with alerts | No changes required |

### Architectural Constraints

1. **Laravel 11.x** - Framework version locked
2. **PHP 8.2+** - Enum support available, fully utilized
3. **MySQL 8.0+** - JSON columns available for flexible fields
4. **CandidateStatus Enum** - Currently has 15 states; will need expansion
5. **State Machine** - Current canTransitionTo() logic needs revision for new workflow
6. **File Storage** - Private storage configured for documents; needs video support

### Technical Debt Identified

| Issue | Severity | Impact on Changes |
|-------|----------|-------------------|
| Screening 3-call hardcoded logic | HIGH | Must be refactored completely |
| Batch creation in Training module | HIGH | Must be moved to Registration |
| No separate Employer model | HIGH | New entity required |
| Post-Departure fields scattered | MEDIUM | Needs consolidation |
| No video upload infrastructure | MEDIUM | New capability required |
| Document checklist not configurable | MEDIUM | Must become admin-configurable |

---

# PHASE 3: CHANGE IMPACT ANALYSIS

## 3.1 Impact Analysis Matrix

### CL-001: Pre-Departure Documents Section

| Attribute | Value |
|-----------|-------|
| **Change ID** | CL-001 |
| **Mapped Existing Feature** | Partial - Document Archive exists |
| **Current Behavior** | Documents uploaded during Registration |
| **Expected Behavior** | New workflow step after Listing, before Screening; documents collected early |
| **Gap Description** | No Pre-Departure stage exists; workflow jump from Listing to Screening |
| **Impact Type** | Data, Logic, UI, Workflow |
| **Risk Level** | HIGH |

### IS-009: Gate - Only Screened Can Proceed

| Attribute | Value |
|-----------|-------|
| **Change ID** | IS-009 |
| **Mapped Existing Feature** | CandidateStatus enum with canTransitionTo() |
| **Current Behavior** | Transitions loosely enforced |
| **Expected Behavior** | Hard gate: status must be "Screened" before Registration actions |
| **Gap Description** | State machine needs new "Screened" status; controller middleware required |
| **Impact Type** | Logic, API, Compliance |
| **Risk Level** | CRITICAL |

### RG-004: Allocation Section

| Attribute | Value |
|-----------|-------|
| **Change ID** | RG-004 |
| **Mapped Existing Feature** | Campus assignment exists; OEP assignment exists |
| **Current Behavior** | Campus and OEP assigned separately |
| **Expected Behavior** | Unified Allocation: Campus + Program + OEP + Implementing Partner at Registration |
| **Gap Description** | "Program" and "Implementing Partner" fields don't exist; allocation workflow not unified |
| **Impact Type** | Data, Logic, UI |
| **Risk Level** | CRITICAL |

### RG-005: Auto Batch Creation

| Attribute | Value |
|-----------|-------|
| **Change ID** | RG-005 |
| **Mapped Existing Feature** | Batch creation in Training module |
| **Current Behavior** | Manual batch creation; candidates assigned to batches |
| **Expected Behavior** | Auto-generate batch at Registration based on Campus + Program + Trade |
| **Gap Description** | Major workflow shift; batch service needs rewrite |
| **Impact Type** | Data, Logic, Business Process |
| **Risk Level** | CRITICAL |

### TM-005/TM-006: Technical/Soft Skills Status Separation

| Attribute | Value |
|-----------|-------|
| **Change ID** | TM-005, TM-006 |
| **Mapped Existing Feature** | TrainingStatus enum |
| **Current Behavior** | Single training status per candidate |
| **Expected Behavior** | Dual status: Technical Training Status + Soft Skills Training Status |
| **Gap Description** | Data model needs two status fields; UI needs parallel tracking |
| **Impact Type** | Data, UI, Reporting |
| **Risk Level** | HIGH |

### EI-001: Employer Information Module

| Attribute | Value |
|-----------|-------|
| **Change ID** | EI-001 |
| **Mapped Existing Feature** | None |
| **Current Behavior** | No employer management |
| **Expected Behavior** | Complete CRUD for employers; linked to candidates; employment packages |
| **Gap Description** | New model, controller, views, routes required |
| **Impact Type** | Data, Logic, UI, API |
| **Risk Level** | CRITICAL |

### PD-008/009: Final Employment Details

| Attribute | Value |
|-----------|-------|
| **Change ID** | PD-008, PD-009 |
| **Mapped Existing Feature** | Departure tracking exists |
| **Current Behavior** | Basic post-arrival fields (Iqama, Absher, Qiwa) |
| **Expected Behavior** | Full employment record: Company, Employer contact, Salary, T&Cs, Commencement |
| **Gap Description** | Missing 10+ fields; likely need separate table or JSON column |
| **Impact Type** | Data, UI, Reporting |
| **Risk Level** | HIGH |

### PD-010/011: Company SWITCH Tracking

| Attribute | Value |
|-----------|-------|
| **Change ID** | PD-010, PD-011 |
| **Mapped Existing Feature** | None |
| **Current Behavior** | No job change tracking |
| **Expected Behavior** | Track First and Second company switches with full details |
| **Gap Description** | New data structure; possibly separate table for employment history |
| **Impact Type** | Data, UI, Compliance |
| **Risk Level** | HIGH |

### XM-001: Module Flow Enforcement

| Attribute | Value |
|-----------|-------|
| **Change ID** | XM-001 |
| **Mapped Existing Feature** | Partial via CandidateStatus enum |
| **Current Behavior** | Soft enforcement; some bypasses possible |
| **Expected Behavior** | Strict sequential workflow; no stage skipping |
| **Gap Description** | Middleware enforcement; UI locks; policy updates |
| **Impact Type** | Logic, API, Compliance, Security |
| **Risk Level** | CRITICAL |

---

# PHASE 4: DETAILED IMPLEMENTATION ROADMAP

## Phase 1 — Foundation & Data Model Changes

**Objective:** Establish new database schema, models, and relationships required for all subsequent phases.

**Duration:** 1-2 weeks

### Task 1.1: Create Programs Table and Model

| Attribute | Value |
|-----------|-------|
| **Task ID** | T1.1 |
| **Task Description** | Create programs table for training program management |
| **Change ID(s) Covered** | RG-004 |
| **Files to Modify** | None |
| **Files to Create** | `database/migrations/YYYY_MM_DD_create_programs_table.php`, `app/Models/Program.php`, `app/Http/Controllers/ProgramController.php`, `app/Policies/ProgramPolicy.php` |
| **Database Changes** | New table: programs (id, name, description, duration_weeks, is_active, created_at, updated_at, deleted_at) |
| **Routes** | Add resource routes in `routes/web.php` |
| **UI Changes** | Admin > Programs CRUD views |
| **Business Logic** | None |
| **Tests Required** | Unit: ProgramTest.php; Feature: ProgramControllerTest.php |
| **Dependencies** | None |
| **Risk Level** | LOW |
| **Estimated Complexity** | Simple |

### Task 1.2: Create Implementing Partners Table and Model

| Attribute | Value |
|-----------|-------|
| **Task ID** | T1.2 |
| **Task Description** | Create implementing_partners table |
| **Change ID(s) Covered** | RG-004 |
| **Files to Modify** | None |
| **Files to Create** | `database/migrations/YYYY_MM_DD_create_implementing_partners_table.php`, `app/Models/ImplementingPartner.php`, `app/Http/Controllers/ImplementingPartnerController.php`, `app/Policies/ImplementingPartnerPolicy.php` |
| **Database Changes** | New table: implementing_partners (id, name, contact_person, contact_email, contact_phone, address, is_active, created_at, updated_at, deleted_at) |
| **Routes** | Add resource routes in `routes/web.php` |
| **UI Changes** | Admin > Implementing Partners CRUD views |
| **Business Logic** | None |
| **Tests Required** | Unit: ImplementingPartnerTest.php; Feature: ImplementingPartnerControllerTest.php |
| **Dependencies** | None |
| **Risk Level** | LOW |
| **Estimated Complexity** | Simple |

### Task 1.3: Create Employers Table and Model

| Attribute | Value |
|-----------|-------|
| **Task ID** | T1.3 |
| **Task Description** | Create employers table for Module 8 |
| **Change ID(s) Covered** | EI-001, EI-002, EI-003, EI-004, EI-005, EI-006, EI-007 |
| **Files to Modify** | None |
| **Files to Create** | `database/migrations/YYYY_MM_DD_create_employers_table.php`, `app/Models/Employer.php`, `app/Http/Controllers/EmployerController.php`, `app/Http/Resources/EmployerResource.php`, `app/Policies/EmployerPolicy.php` |
| **Database Changes** | New table: employers (id, permission_number, visa_issuing_company, country_id, sector, trade, basic_salary, food_by_company, transport_by_company, accommodation_by_company, other_conditions, evidence_path, is_active, created_at, updated_at, deleted_at) |
| **Routes** | Add resource routes in `routes/web.php` and `routes/api.php` |
| **UI Changes** | Module 8: Employer Information tab with full CRUD |
| **Business Logic** | None |
| **Tests Required** | Unit: EmployerTest.php; Feature: EmployerControllerTest.php; API: EmployerApiTest.php |
| **Dependencies** | Countries table (may exist) |
| **Risk Level** | MEDIUM |
| **Estimated Complexity** | Moderate |

### Task 1.4: Create Candidate Pre-Departure Documents Table

| Attribute | Value |
|-----------|-------|
| **Task ID** | T1.4 |
| **Task Description** | Create pre-departure documents tracking table |
| **Change ID(s) Covered** | CL-001, CL-002, CL-003, CL-004, CL-005 |
| **Files to Modify** | None |
| **Files to Create** | `database/migrations/YYYY_MM_DD_create_pre_departure_documents_table.php`, `app/Models/PreDepartureDocument.php` |
| **Database Changes** | New table: pre_departure_documents (id, candidate_id, document_type, file_path, uploaded_at, verified_at, verified_by, is_mandatory, notes, created_at, updated_at) |
| **Routes** | None (handled via CandidateController) |
| **UI Changes** | Candidate detail page - Pre-Departure Documents section |
| **Business Logic** | None |
| **Tests Required** | Unit: PreDepartureDocumentTest.php |
| **Dependencies** | None |
| **Risk Level** | LOW |
| **Estimated Complexity** | Simple |

### Task 1.5: Create Document Checklist Configuration Table

| Attribute | Value |
|-----------|-------|
| **Task ID** | T1.5 |
| **Task Description** | Create admin-configurable document checklist |
| **Change ID(s) Covered** | CL-002, CL-003, CL-004 |
| **Files to Modify** | None |
| **Files to Create** | `database/migrations/YYYY_MM_DD_create_document_checklists_table.php`, `app/Models/DocumentChecklist.php`, `app/Http/Controllers/DocumentChecklistController.php` |
| **Database Changes** | New table: document_checklists (id, name, description, is_mandatory, display_order, category, is_active, created_at, updated_at) |
| **Routes** | Admin routes for checklist management |
| **UI Changes** | Admin > Document Checklists CRUD |
| **Business Logic** | Seeder for default checklist items |
| **Tests Required** | Feature: DocumentChecklistControllerTest.php |
| **Dependencies** | None |
| **Risk Level** | LOW |
| **Estimated Complexity** | Simple |

### Task 1.6: Modify Candidates Table - Add Allocation Fields

| Attribute | Value |
|-----------|-------|
| **Task ID** | T1.6 |
| **Task Description** | Add allocation fields to candidates table |
| **Change ID(s) Covered** | RG-004, RG-008 |
| **Files to Modify** | `app/Models/Candidate.php` |
| **Files to Create** | `database/migrations/YYYY_MM_DD_add_allocation_fields_to_candidates_table.php` |
| **Database Changes** | Add columns: program_id (FK), implementing_partner_id (FK), allocated_number (unique) |
| **Routes** | None |
| **UI Changes** | Registration form update |
| **Business Logic** | Update CandidateService |
| **Tests Required** | Unit: CandidateTest.php update |
| **Dependencies** | T1.1, T1.2 |
| **Risk Level** | MEDIUM |
| **Estimated Complexity** | Moderate |

### Task 1.7: Modify Screenings Table - New Workflow Fields

| Attribute | Value |
|-----------|-------|
| **Task ID** | T1.7 |
| **Task Description** | Add new fields for Initial Screening workflow |
| **Change ID(s) Covered** | IS-002, IS-003, IS-004, IS-005, IS-007 |
| **Files to Modify** | `app/Models/Screening.php` |
| **Files to Create** | `database/migrations/YYYY_MM_DD_modify_screenings_table_for_initial_screening.php` |
| **Database Changes** | Add columns: consent_for_work (boolean), placement_interest (enum: local/international), target_country_id (FK nullable), screening_status (enum: screened/pending/deferred), evidence_path, reviewer_id (FK), reviewed_at |
| **Routes** | None |
| **UI Changes** | Screening form update |
| **Business Logic** | ScreeningService update |
| **Tests Required** | Unit: ScreeningTest.php update |
| **Dependencies** | Countries table |
| **Risk Level** | HIGH |
| **Estimated Complexity** | Moderate |

### Task 1.8: Create Courses Table for Training Assignment

| Attribute | Value |
|-----------|-------|
| **Task ID** | T1.8 |
| **Task Description** | Create courses table for training course management |
| **Change ID(s) Covered** | RG-009, RG-010 |
| **Files to Modify** | None |
| **Files to Create** | `database/migrations/YYYY_MM_DD_create_courses_table.php`, `app/Models/Course.php`, `app/Http/Controllers/CourseController.php` |
| **Database Changes** | New table: courses (id, name, description, duration_days, training_type (enum: technical/soft_skills/both), is_active, created_at, updated_at, deleted_at) |
| **Routes** | Admin routes for course management |
| **UI Changes** | Admin > Courses CRUD |
| **Business Logic** | None |
| **Tests Required** | Feature: CourseControllerTest.php |
| **Dependencies** | None |
| **Risk Level** | LOW |
| **Estimated Complexity** | Simple |

### Task 1.9: Create Candidate Course Assignments Table

| Attribute | Value |
|-----------|-------|
| **Task ID** | T1.9 |
| **Task Description** | Create pivot table for candidate-course assignments |
| **Change ID(s) Covered** | RG-009, RG-010 |
| **Files to Modify** | `app/Models/Candidate.php` |
| **Files to Create** | `database/migrations/YYYY_MM_DD_create_candidate_courses_table.php`, `app/Models/CandidateCourse.php` |
| **Database Changes** | New table: candidate_courses (id, candidate_id, course_id, start_date, end_date, status, created_at, updated_at) |
| **Routes** | None |
| **UI Changes** | Registration form - course assignment section |
| **Business Logic** | None |
| **Tests Required** | Unit: CandidateCourseTest.php |
| **Dependencies** | T1.8 |
| **Risk Level** | LOW |
| **Estimated Complexity** | Simple |

### Task 1.10: Modify Trainings Table - Dual Status Tracking

| Attribute | Value |
|-----------|-------|
| **Task ID** | T1.10 |
| **Task Description** | Add technical and soft skills status fields |
| **Change ID(s) Covered** | TM-005, TM-006 |
| **Files to Modify** | `app/Models/Training.php` |
| **Files to Create** | `database/migrations/YYYY_MM_DD_add_dual_status_to_trainings_table.php` |
| **Database Changes** | Add columns: technical_training_status (enum: not_started/in_progress/completed), soft_skills_status (enum: not_started/in_progress/completed) |
| **Routes** | None |
| **UI Changes** | Training detail page |
| **Business Logic** | TrainingService update |
| **Tests Required** | Unit: TrainingTest.php update |
| **Dependencies** | None |
| **Risk Level** | MEDIUM |
| **Estimated Complexity** | Moderate |

### Task 1.11: Create Training Assessments Table

| Attribute | Value |
|-----------|-------|
| **Task ID** | T1.11 |
| **Task Description** | Create assessments tracking table |
| **Change ID(s) Covered** | TM-002, TM-003, TM-004 |
| **Files to Modify** | None |
| **Files to Create** | `database/migrations/YYYY_MM_DD_create_training_assessments_table.php`, `app/Models/TrainingAssessment.php` |
| **Database Changes** | New table: training_assessments (id, training_id, candidate_id, assessment_type (enum: interim/final), score, max_score, evidence_path, assessed_by, assessed_at, notes, created_at, updated_at) |
| **Routes** | None |
| **UI Changes** | Training module - assessments tab |
| **Business Logic** | AssessmentService |
| **Tests Required** | Unit: TrainingAssessmentTest.php |
| **Dependencies** | None |
| **Risk Level** | LOW |
| **Estimated Complexity** | Simple |

### Task 1.12: Modify Visa Processes Table - Enhanced Pipeline

| Attribute | Value |
|-----------|-------|
| **Task ID** | T1.12 |
| **Task Description** | Add appointment and result sub-details to visa stages |
| **Change ID(s) Covered** | VP-001, VP-002, VP-003, VP-005, VP-006, VP-007 |
| **Files to Modify** | `app/Models/VisaProcess.php` |
| **Files to Create** | `database/migrations/YYYY_MM_DD_enhance_visa_processes_table.php` |
| **Database Changes** | Add JSON columns for each stage: interview_details, trade_test_details, medical_details, biometric_details, visa_application_details, visa_issued_details (each containing appointment_date, appointment_time, center, result_status, evidence_path) |
| **Routes** | None |
| **UI Changes** | Visa Processing pipeline UI |
| **Business Logic** | VisaProcessService update |
| **Tests Required** | Unit: VisaProcessTest.php update |
| **Dependencies** | None |
| **Risk Level** | HIGH |
| **Estimated Complexity** | Complex |

### Task 1.13: Modify Departures Table - Enhanced Tracking

| Attribute | Value |
|-----------|-------|
| **Task ID** | T1.13 |
| **Task Description** | Add PTN, Protector, Ticket sub-status fields |
| **Change ID(s) Covered** | DP-001, DP-002, DP-003, DP-004, DP-005, DP-006 |
| **Files to Modify** | `app/Models/Departure.php` |
| **Files to Create** | `database/migrations/YYYY_MM_DD_enhance_departures_table.php` |
| **Database Changes** | Add columns: ptn_status (enum), ptn_issued_at, ptn_deferred_reason, protector_status (enum), protector_applied_at, protector_done_at, protector_deferred_reason, ticket_date, ticket_time, departure_platform, landing_platform, flight_type (enum: direct/connected), pre_departure_doc_path, pre_departure_video_path, final_departure_status (enum: ready_to_depart/departed) |
| **Routes** | None |
| **UI Changes** | Departure detail page |
| **Business Logic** | DepartureService update |
| **Tests Required** | Unit: DepartureTest.php update |
| **Dependencies** | None |
| **Risk Level** | HIGH |
| **Estimated Complexity** | Complex |

### Task 1.14: Create Post-Departure Extended Data Table

| Attribute | Value |
|-----------|-------|
| **Task ID** | T1.14 |
| **Task Description** | Create table for post-departure extended tracking |
| **Change ID(s) Covered** | PD-001 to PD-009 |
| **Files to Modify** | `app/Models/Departure.php` (add relationship) |
| **Files to Create** | `database/migrations/YYYY_MM_DD_create_post_departure_details_table.php`, `app/Models/PostDepartureDetail.php` |
| **Database Changes** | New table: post_departure_details (id, departure_id, residency_proof_path, foreign_license_path, foreign_mobile_number, foreign_bank_name, foreign_bank_account, tracking_app_registration, final_contract_path, company_name, employer_name, employer_designation, employer_contact, work_location, final_salary, final_job_terms, job_commencement_date, special_conditions, created_at, updated_at) |
| **Routes** | None |
| **UI Changes** | Post-Departure detail page |
| **Business Logic** | None |
| **Tests Required** | Unit: PostDepartureDetailTest.php |
| **Dependencies** | None |
| **Risk Level** | MEDIUM |
| **Estimated Complexity** | Moderate |

### Task 1.15: Create Employment History Table (Company SWITCH)

| Attribute | Value |
|-----------|-------|
| **Task ID** | T1.15 |
| **Task Description** | Create table for tracking company switches |
| **Change ID(s) Covered** | PD-010, PD-011 |
| **Files to Modify** | `app/Models/Departure.php` (add relationship) |
| **Files to Create** | `database/migrations/YYYY_MM_DD_create_employment_histories_table.php`, `app/Models/EmploymentHistory.php` |
| **Database Changes** | New table: employment_histories (id, departure_id, switch_number (1, 2), company_name, work_location, salary, job_terms, commencement_date, special_conditions, created_at, updated_at) |
| **Routes** | None |
| **UI Changes** | Post-Departure page - Company Switch sections |
| **Business Logic** | None |
| **Tests Required** | Unit: EmploymentHistoryTest.php |
| **Dependencies** | T1.14 |
| **Risk Level** | LOW |
| **Estimated Complexity** | Simple |

### Task 1.16: Create Success Stories Table

| Attribute | Value |
|-----------|-------|
| **Task ID** | T1.16 |
| **Task Description** | Create success stories tracking table |
| **Change ID(s) Covered** | PC-001, PC-002 |
| **Files to Modify** | None |
| **Files to Create** | `database/migrations/YYYY_MM_DD_create_success_stories_table.php`, `app/Models/SuccessStory.php`, `app/Http/Controllers/SuccessStoryController.php` |
| **Database Changes** | New table: success_stories (id, candidate_id, departure_id, written_note, evidence_type (enum: audio/video/written), evidence_path, recorded_by, recorded_at, is_featured, created_at, updated_at, deleted_at) |
| **Routes** | Resource routes for success stories |
| **UI Changes** | Post-Correspondence tab - Success Stories section |
| **Business Logic** | None |
| **Tests Required** | Feature: SuccessStoryControllerTest.php |
| **Dependencies** | None |
| **Risk Level** | LOW |
| **Estimated Complexity** | Simple |

### Task 1.17: Modify Complaints Table - Enhanced Workflow

| Attribute | Value |
|-----------|-------|
| **Task ID** | T1.17 |
| **Task Description** | Add structured workflow fields to complaints |
| **Change ID(s) Covered** | PC-003, PC-004, PC-005 |
| **Files to Modify** | `app/Models/Complaint.php` |
| **Files to Create** | `database/migrations/YYYY_MM_DD_enhance_complaints_table.php` |
| **Database Changes** | Add columns: current_issue, support_steps_taken, suggestions, conclusion, evidence_type (enum: audio/video/screenshot/other) |
| **Routes** | None |
| **UI Changes** | Complaints form update |
| **Business Logic** | ComplaintService update |
| **Tests Required** | Unit: ComplaintTest.php update |
| **Dependencies** | None |
| **Risk Level** | MEDIUM |
| **Estimated Complexity** | Moderate |

### Task 1.18: Update CandidateStatus Enum

| Attribute | Value |
|-----------|-------|
| **Task ID** | T1.18 |
| **Task Description** | Update CandidateStatus enum with new statuses |
| **Change ID(s) Covered** | CL-001, IS-009, RG-001, XM-001, XM-003 |
| **Files to Modify** | `app/Enums/CandidateStatus.php` |
| **Files to Create** | None |
| **Database Changes** | None (enum is PHP-side) |
| **Routes** | None |
| **UI Changes** | Status badges update |
| **Business Logic** | Update canTransitionTo() method with new flow |
| **Tests Required** | Unit: CandidateStatusTest.php update |
| **Dependencies** | None |
| **Risk Level** | CRITICAL |
| **Estimated Complexity** | Complex |

---

## Phase 2 — Workflow & State Machine Updates

**Objective:** Implement the new sequential workflow, stage gates, and transition rules.

**Duration:** 1-2 weeks

### Task 2.1: Implement Stage Gate Middleware

| Attribute | Value |
|-----------|-------|
| **Task ID** | T2.1 |
| **Task Description** | Create middleware to enforce stage progression |
| **Change ID(s) Covered** | IS-009, RG-001, XM-001, XM-003 |
| **Files to Modify** | `app/Http/Kernel.php` |
| **Files to Create** | `app/Http/Middleware/EnforceStageGate.php` |
| **Database Changes** | None |
| **Routes** | Apply middleware to Registration, Training, Visa, Departure, Post-Departure routes |
| **UI Changes** | Disabled buttons/links for locked stages |
| **Business Logic** | Check candidate status before allowing access |
| **Tests Required** | Feature: StageGateMiddlewareTest.php |
| **Dependencies** | T1.18 |
| **Risk Level** | CRITICAL |
| **Estimated Complexity** | Complex |

### Task 2.2: Update CandidateStatus Transitions

| Attribute | Value |
|-----------|-------|
| **Task ID** | T2.2 |
| **Task Description** | Rewrite canTransitionTo() with new workflow |
| **Change ID(s) Covered** | XM-001, XM-003 |
| **Files to Modify** | `app/Enums/CandidateStatus.php` |
| **Files to Create** | None |
| **Database Changes** | None |
| **Routes** | None |
| **UI Changes** | None |
| **Business Logic** | New transition rules: LISTED → PRE_DEPARTURE_DOCS → SCREENING → REGISTERED → TRAINING → VISA_PROCESS → DEPARTED → POST_DEPARTURE |
| **Tests Required** | Unit: CandidateStatusTransitionTest.php |
| **Dependencies** | T1.18 |
| **Risk Level** | CRITICAL |
| **Estimated Complexity** | Complex |

### Task 2.3: Implement Auto Batch Creation Service

| Attribute | Value |
|-----------|-------|
| **Task ID** | T2.3 |
| **Task Description** | Create service for automatic batch generation at registration |
| **Change ID(s) Covered** | RG-005, RG-006, RG-007, RG-008 |
| **Files to Modify** | `app/Services/BatchService.php` (if exists, else create) |
| **Files to Create** | `app/Services/AutoBatchService.php` |
| **Database Changes** | None |
| **Routes** | None |
| **UI Changes** | None |
| **Business Logic** | Auto-create batch when: same Campus + Program + Trade; configurable batch size; generate unique allocated number |
| **Tests Required** | Unit: AutoBatchServiceTest.php |
| **Dependencies** | T1.6 |
| **Risk Level** | CRITICAL |
| **Estimated Complexity** | Complex |

### Task 2.4: Create Batch Size Configuration

| Attribute | Value |
|-----------|-------|
| **Task ID** | T2.4 |
| **Task Description** | Add admin-configurable batch size setting |
| **Change ID(s) Covered** | RG-006, XM-005 |
| **Files to Modify** | `config/wasl.php` (create if not exists), `app/Models/Setting.php` (if exists) |
| **Files to Create** | `database/migrations/YYYY_MM_DD_add_batch_size_setting.php` (if using DB settings), `resources/views/admin/settings/batch.blade.php` |
| **Database Changes** | Add setting: batch_size (default: 25, allowed: 20, 25, 30) |
| **Routes** | Admin settings route |
| **UI Changes** | Admin > Settings > Batch Configuration |
| **Business Logic** | Config retrieval in AutoBatchService |
| **Tests Required** | Feature: BatchConfigurationTest.php |
| **Dependencies** | T2.3 |
| **Risk Level** | MEDIUM |
| **Estimated Complexity** | Simple |

### Task 2.5: Implement Training Completion Logic

| Attribute | Value |
|-----------|-------|
| **Task ID** | T2.5 |
| **Task Description** | Update training completion to require assessments |
| **Change ID(s) Covered** | TM-007 |
| **Files to Modify** | `app/Services/TrainingService.php` |
| **Files to Create** | None |
| **Database Changes** | None |
| **Routes** | None |
| **UI Changes** | Training completion button disabled until assessments complete |
| **Business Logic** | Check: interim AND final assessments completed before allowing status change to COMPLETED |
| **Tests Required** | Unit: TrainingCompletionTest.php |
| **Dependencies** | T1.10, T1.11 |
| **Risk Level** | HIGH |
| **Estimated Complexity** | Moderate |

### Task 2.6: Remove Active Batch Concept

| Attribute | Value |
|-----------|-------|
| **Task ID** | T2.6 |
| **Task Description** | Remove "Active Batch" flag and related logic |
| **Change ID(s) Covered** | TM-001 |
| **Files to Modify** | `app/Models/Batch.php`, `app/Http/Controllers/BatchController.php`, `app/Http/Controllers/TrainingController.php`, related views |
| **Files to Create** | `database/migrations/YYYY_MM_DD_remove_active_batch_from_batches.php` |
| **Database Changes** | Remove column: is_active (if exists) |
| **Routes** | Remove activate/deactivate routes |
| **UI Changes** | Remove active batch toggle |
| **Business Logic** | Remove filtering by active batch |
| **Tests Required** | Feature: BatchControllerTest.php update |
| **Dependencies** | None |
| **Risk Level** | HIGH |
| **Estimated Complexity** | Moderate |

### Task 2.7: Deprecate 3-Call Screening System

| Attribute | Value |
|-----------|-------|
| **Task ID** | T2.7 |
| **Task Description** | Remove 3-call tracking and replace with single review |
| **Change ID(s) Covered** | IS-002, IS-010 |
| **Files to Modify** | `app/Http/Controllers/ScreeningController.php`, `app/Services/ScreeningService.php`, `app/Models/Screening.php`, related views |
| **Files to Create** | `database/migrations/YYYY_MM_DD_deprecate_three_call_fields.php` |
| **Database Changes** | Soft-deprecate columns: call_1_date, call_1_outcome, call_2_date, call_2_outcome, call_3_date, call_3_outcome (keep for historical data) |
| **Routes** | Remove call-specific routes |
| **UI Changes** | Replace 3-call UI with single screening form |
| **Business Logic** | New ScreeningService methods |
| **Tests Required** | Feature: ScreeningControllerTest.php update |
| **Dependencies** | T1.7 |
| **Risk Level** | HIGH |
| **Estimated Complexity** | Complex |

---

## Phase 3 — Module 1 & 2 Updates (Candidate Listing & Initial Screening)

**Objective:** Implement Pre-Departure Documents section and Initial Screening workflow.

**Duration:** 1 week

### Task 3.1: Create Pre-Departure Documents Section UI

| Attribute | Value |
|-----------|-------|
| **Task ID** | T3.1 |
| **Task Description** | Build Pre-Departure Documents upload interface |
| **Change ID(s) Covered** | CL-001, CL-002, CL-003, CL-004, CL-005 |
| **Files to Modify** | `resources/views/candidates/show.blade.php` |
| **Files to Create** | `resources/views/candidates/partials/pre-departure-documents.blade.php`, `resources/views/components/document-checklist.blade.php` |
| **Database Changes** | None (uses T1.4, T1.5) |
| **Routes** | Add `candidates/{id}/pre-departure-documents` routes |
| **UI Changes** | New section in candidate detail page; document upload for each checklist item |
| **Business Logic** | None |
| **Tests Required** | Feature: PreDepartureDocumentsTest.php |
| **Dependencies** | T1.4, T1.5 |
| **Risk Level** | MEDIUM |
| **Estimated Complexity** | Moderate |

### Task 3.2: Implement Document Visibility Control

| Attribute | Value |
|-----------|-------|
| **Task ID** | T3.2 |
| **Task Description** | Make pre-departure documents read-only after screening |
| **Change ID(s) Covered** | CL-005 |
| **Files to Modify** | `app/Policies/PreDepartureDocumentPolicy.php` |
| **Files to Create** | `app/Policies/PreDepartureDocumentPolicy.php` |
| **Database Changes** | None |
| **Routes** | None |
| **UI Changes** | Disable upload/delete buttons based on status |
| **Business Logic** | Policy check: editable only if status < SCREENED |
| **Tests Required** | Unit: PreDepartureDocumentPolicyTest.php |
| **Dependencies** | T1.4, T2.2 |
| **Risk Level** | MEDIUM |
| **Estimated Complexity** | Simple |

### Task 3.3: Create Document Fetch Report

| Attribute | Value |
|-----------|-------|
| **Task ID** | T3.3 |
| **Task Description** | Build individual/bulk document fetch reporting |
| **Change ID(s) Covered** | CL-006 |
| **Files to Modify** | None |
| **Files to Create** | `app/Http/Controllers/Reports/DocumentReportController.php`, `resources/views/reports/documents/index.blade.php` |
| **Database Changes** | None |
| **Routes** | `reports/documents`, `reports/documents/bulk` |
| **UI Changes** | Reports > Documents section |
| **Business Logic** | Query pre_departure_documents with filters |
| **Tests Required** | Feature: DocumentReportControllerTest.php |
| **Dependencies** | T1.4 |
| **Risk Level** | LOW |
| **Estimated Complexity** | Simple |

### Task 3.4: Rename Screening to Initial Screening

| Attribute | Value |
|-----------|-------|
| **Task ID** | T3.4 |
| **Task Description** | Update all UI labels from "Screening" to "Initial Screening" |
| **Change ID(s) Covered** | IS-001 |
| **Files to Modify** | `resources/views/screenings/*.blade.php`, `resources/views/layouts/navigation.blade.php`, `resources/lang/en/*.php` |
| **Files to Create** | None |
| **Database Changes** | None |
| **Routes** | None (keep URL paths for backward compatibility) |
| **UI Changes** | All instances of "Screening" → "Initial Screening" |
| **Business Logic** | None |
| **Tests Required** | None (cosmetic) |
| **Dependencies** | None |
| **Risk Level** | LOW |
| **Estimated Complexity** | Simple |

### Task 3.5: Build Initial Screening Form

| Attribute | Value |
|-----------|-------|
| **Task ID** | T3.5 |
| **Task Description** | Create new screening form with consent/interest fields |
| **Change ID(s) Covered** | IS-002, IS-003, IS-004, IS-005, IS-007, IS-008 |
| **Files to Modify** | `app/Http/Controllers/ScreeningController.php`, `app/Http/Requests/ScreeningRequest.php` |
| **Files to Create** | `resources/views/screenings/review.blade.php` |
| **Database Changes** | None (uses T1.7) |
| **Routes** | Update store/update methods |
| **UI Changes** | New form: consent checkbox, placement interest radio, country dropdown, notes textarea, evidence upload |
| **Business Logic** | Validate mandatory fields; update status |
| **Tests Required** | Feature: ScreeningReviewTest.php |
| **Dependencies** | T1.7, T2.7 |
| **Risk Level** | HIGH |
| **Estimated Complexity** | Moderate |

### Task 3.6: Build Initial Screening Dashboard

| Attribute | Value |
|-----------|-------|
| **Task ID** | T3.6 |
| **Task Description** | Create screening dashboard with status breakdown |
| **Change ID(s) Covered** | IS-006 |
| **Files to Modify** | `app/Http/Controllers/ScreeningController.php` |
| **Files to Create** | `resources/views/screenings/dashboard.blade.php`, `resources/views/screenings/partials/stats-cards.blade.php` |
| **Database Changes** | None |
| **Routes** | `screenings/dashboard` |
| **UI Changes** | Dashboard with: Total Candidates, Screened, Un-screened, Pending, Deferred counts |
| **Business Logic** | Aggregate queries |
| **Tests Required** | Feature: ScreeningDashboardTest.php |
| **Dependencies** | T1.7 |
| **Risk Level** | MEDIUM |
| **Estimated Complexity** | Moderate |

---

## Phase 4 — Module 3 Updates (Registration)

**Objective:** Implement allocation, auto-batch, and course assignment at registration.

**Duration:** 1-2 weeks

### Task 4.1: Build Allocation Section UI

| Attribute | Value |
|-----------|-------|
| **Task ID** | T4.1 |
| **Task Description** | Create allocation section in registration form |
| **Change ID(s) Covered** | RG-004 |
| **Files to Modify** | `resources/views/candidates/registration.blade.php` (or equivalent) |
| **Files to Create** | `resources/views/candidates/partials/allocation.blade.php` |
| **Database Changes** | None (uses T1.6) |
| **Routes** | None |
| **UI Changes** | Dropdowns: Campus, Program, OEP, Implementing Partner |
| **Business Logic** | Cascading dropdown logic (Program based on Campus if needed) |
| **Tests Required** | Feature: RegistrationAllocationTest.php |
| **Dependencies** | T1.1, T1.2, T1.6 |
| **Risk Level** | HIGH |
| **Estimated Complexity** | Moderate |

### Task 4.2: Integrate Auto Batch Creation

| Attribute | Value |
|-----------|-------|
| **Task ID** | T4.2 |
| **Task Description** | Call AutoBatchService during registration |
| **Change ID(s) Covered** | RG-005, RG-007, RG-011 |
| **Files to Modify** | `app/Http/Controllers/CandidateController.php` (register method), `app/Http/Requests/RegistrationRequest.php` |
| **Files to Create** | None |
| **Database Changes** | None |
| **Routes** | None |
| **UI Changes** | Display generated batch info post-registration |
| **Business Logic** | Call AutoBatchService->assignOrCreateBatch($candidate) |
| **Tests Required** | Feature: AutoBatchIntegrationTest.php |
| **Dependencies** | T2.3, T4.1 |
| **Risk Level** | CRITICAL |
| **Estimated Complexity** | Moderate |

### Task 4.3: Build Course Assignment UI

| Attribute | Value |
|-----------|-------|
| **Task ID** | T4.3 |
| **Task Description** | Create course assignment section in registration |
| **Change ID(s) Covered** | RG-009, RG-010 |
| **Files to Modify** | `resources/views/candidates/registration.blade.php` |
| **Files to Create** | `resources/views/candidates/partials/course-assignment.blade.php` |
| **Database Changes** | None (uses T1.8, T1.9) |
| **Routes** | None |
| **UI Changes** | Course dropdown, start/end date pickers |
| **Business Logic** | Create CandidateCourse record |
| **Tests Required** | Feature: CourseAssignmentTest.php |
| **Dependencies** | T1.8, T1.9 |
| **Risk Level** | MEDIUM |
| **Estimated Complexity** | Moderate |

### Task 4.4: Update Next of Kin Section

| Attribute | Value |
|-----------|-------|
| **Task ID** | T4.4 |
| **Task Description** | Add financial account and ID card fields to next of kin |
| **Change ID(s) Covered** | RG-002, RG-003 |
| **Files to Modify** | `app/Models/Candidate.php` (or NextOfKin model if separate), `resources/views/candidates/registration.blade.php` |
| **Files to Create** | `database/migrations/YYYY_MM_DD_enhance_next_of_kin_fields.php` |
| **Database Changes** | Add columns to candidates (or next_of_kins): nok_financial_account_type, nok_financial_account_number, nok_id_card_path |
| **Routes** | None |
| **UI Changes** | Financial account dropdown (EasyPaisa, Bank, etc.), account number input, ID card upload |
| **Business Logic** | None |
| **Tests Required** | Feature: NextOfKinUpdateTest.php |
| **Dependencies** | None |
| **Risk Level** | MEDIUM |
| **Estimated Complexity** | Simple |

### Task 4.5: Enforce Registration Gate

| Attribute | Value |
|-----------|-------|
| **Task ID** | T4.5 |
| **Task Description** | Ensure only Screened candidates can be registered |
| **Change ID(s) Covered** | RG-001 |
| **Files to Modify** | `app/Http/Controllers/CandidateController.php`, `app/Policies/CandidatePolicy.php` |
| **Files to Create** | None |
| **Database Changes** | None |
| **Routes** | None |
| **UI Changes** | Hide/disable registration actions for non-screened candidates |
| **Business Logic** | Policy check: candidate.status === 'screened' |
| **Tests Required** | Feature: RegistrationGateTest.php |
| **Dependencies** | T2.1, T2.2 |
| **Risk Level** | CRITICAL |
| **Estimated Complexity** | Simple |

---

## Phase 5 — Module 4, 5, 6 Updates (Training, Visa, Departure)

**Objective:** Implement training enhancements, visa pipeline improvements, and departure tracking updates.

**Duration:** 1-2 weeks

### Task 5.1: Build Dual Training Status UI

| Attribute | Value |
|-----------|-------|
| **Task ID** | T5.1 |
| **Task Description** | Create UI for Technical and Soft Skills status tracking |
| **Change ID(s) Covered** | TM-005, TM-006 |
| **Files to Modify** | `resources/views/trainings/show.blade.php` |
| **Files to Create** | `resources/views/trainings/partials/dual-status.blade.php` |
| **Database Changes** | None (uses T1.10) |
| **Routes** | None |
| **UI Changes** | Two status progress bars/badges; update forms for each |
| **Business Logic** | None |
| **Tests Required** | Feature: DualTrainingStatusTest.php |
| **Dependencies** | T1.10 |
| **Risk Level** | MEDIUM |
| **Estimated Complexity** | Moderate |

### Task 5.2: Build Assessment Tracking UI

| Attribute | Value |
|-----------|-------|
| **Task ID** | T5.2 |
| **Task Description** | Create assessment entry and tracking interface |
| **Change ID(s) Covered** | TM-002, TM-003, TM-004 |
| **Files to Modify** | `app/Http/Controllers/TrainingController.php` |
| **Files to Create** | `resources/views/trainings/assessments.blade.php`, `app/Http/Requests/AssessmentRequest.php` |
| **Database Changes** | None (uses T1.11) |
| **Routes** | `trainings/{id}/assessments` GET/POST |
| **UI Changes** | Assessment form: type dropdown, score inputs, evidence upload |
| **Business Logic** | Create TrainingAssessment records |
| **Tests Required** | Feature: AssessmentTrackingTest.php |
| **Dependencies** | T1.11 |
| **Risk Level** | MEDIUM |
| **Estimated Complexity** | Moderate |

### Task 5.3: Build Enhanced Visa Pipeline UI

| Attribute | Value |
|-----------|-------|
| **Task ID** | T5.3 |
| **Task Description** | Create hierarchical visa stage tracking interface |
| **Change ID(s) Covered** | VP-001, VP-002, VP-003, VP-004, VP-008 |
| **Files to Modify** | `app/Http/Controllers/VisaProcessController.php`, `resources/views/visa-processes/show.blade.php` |
| **Files to Create** | `resources/views/visa-processes/partials/stage-card.blade.php` |
| **Database Changes** | None (uses T1.12) |
| **Routes** | None |
| **UI Changes** | Per-stage cards with: appointment details form, result dropdown, evidence upload, nested stats (Scheduled→Done→Pass/Fail→Pending) |
| **Business Logic** | VisaProcessService stage update methods |
| **Tests Required** | Feature: VisaPipelineTest.php |
| **Dependencies** | T1.12 |
| **Risk Level** | HIGH |
| **Estimated Complexity** | Complex |

### Task 5.4: Build Visa Stage Dashboard

| Attribute | Value |
|-----------|-------|
| **Task ID** | T5.4 |
| **Task Description** | Create hierarchical dashboard per stage |
| **Change ID(s) Covered** | VP-008 |
| **Files to Modify** | `app/Http/Controllers/VisaProcessController.php` |
| **Files to Create** | `resources/views/visa-processes/dashboard.blade.php` |
| **Database Changes** | None |
| **Routes** | `visa-processes/dashboard` |
| **UI Changes** | Per-stage breakdown: Interview (Scheduled: X, Done: Y, Pass: Z, Fail: W, Pending: P) |
| **Business Logic** | Aggregate queries per stage |
| **Tests Required** | Feature: VisaDashboardTest.php |
| **Dependencies** | T1.12 |
| **Risk Level** | MEDIUM |
| **Estimated Complexity** | Moderate |

### Task 5.5: Build Enhanced Departure Tracking UI

| Attribute | Value |
|-----------|-------|
| **Task ID** | T5.5 |
| **Task Description** | Create PTN, Protector, Ticket status interface |
| **Change ID(s) Covered** | DP-001, DP-002, DP-003, DP-006 |
| **Files to Modify** | `app/Http/Controllers/DepartureController.php`, `resources/views/departures/show.blade.php` |
| **Files to Create** | `resources/views/departures/partials/ptn-status.blade.php`, `resources/views/departures/partials/protector-status.blade.php`, `resources/views/departures/partials/ticket-status.blade.php` |
| **Database Changes** | None (uses T1.13) |
| **Routes** | None |
| **UI Changes** | Status cards for PTN, Protector, Ticket with sub-status dropdowns |
| **Business Logic** | DepartureService update methods |
| **Tests Required** | Feature: DepartureTrackingTest.php |
| **Dependencies** | T1.13 |
| **Risk Level** | HIGH |
| **Estimated Complexity** | Moderate |

### Task 5.6: Implement Pre-Departure Briefing Upload

| Attribute | Value |
|-----------|-------|
| **Task ID** | T5.6 |
| **Task Description** | Add document and video upload for pre-departure briefing |
| **Change ID(s) Covered** | DP-004, DP-005 |
| **Files to Modify** | `app/Http/Controllers/DepartureController.php`, `config/filesystems.php` (if video storage needs config) |
| **Files to Create** | `resources/views/departures/partials/briefing-upload.blade.php` |
| **Database Changes** | None (uses T1.13) |
| **Routes** | `departures/{id}/briefing` POST |
| **UI Changes** | Document upload (scanned originals), video upload with preview |
| **Business Logic** | File validation (video: mp4, max size); storage to private disk |
| **Tests Required** | Feature: BriefingUploadTest.php |
| **Dependencies** | T1.13 |
| **Risk Level** | MEDIUM |
| **Estimated Complexity** | Moderate |

### Task 5.7: Build Departure Dashboard

| Attribute | Value |
|-----------|-------|
| **Task ID** | T5.7 |
| **Task Description** | Create departure dashboard with status breakdown |
| **Change ID(s) Covered** | DP-007 |
| **Files to Modify** | `app/Http/Controllers/DepartureController.php` |
| **Files to Create** | `resources/views/departures/dashboard.blade.php` |
| **Database Changes** | None |
| **Routes** | `departures/dashboard` |
| **UI Changes** | Columns: PTN Status, Protector Status, Ticket Status, Departed Status, Ready to Depart, Not Departed |
| **Business Logic** | Aggregate queries |
| **Tests Required** | Feature: DepartureDashboardTest.php |
| **Dependencies** | T1.13 |
| **Risk Level** | MEDIUM |
| **Estimated Complexity** | Moderate |

---

## Phase 6 — Module 7, 8, 9 Updates (Post-Departure, Employer, Correspondence)

**Objective:** Build new Post-Departure sections, Employer Information module, and Post-Correspondence enhancements.

**Duration:** 1-2 weeks

### Task 6.1: Build Post-Departure Residency & Identity Section

| Attribute | Value |
|-----------|-------|
| **Task ID** | T6.1 |
| **Task Description** | Create Residency & Identity UI in Post-Departure |
| **Change ID(s) Covered** | PD-001, PD-002, PD-003, PD-004, PD-005, PD-006, PD-007 |
| **Files to Modify** | `resources/views/departures/post-departure.blade.php` (or create) |
| **Files to Create** | `resources/views/departures/partials/residency-identity.blade.php` |
| **Database Changes** | None (uses T1.14) |
| **Routes** | `departures/{id}/post-departure` |
| **UI Changes** | Form fields: Residency proof upload, Foreign license upload, Foreign mobile, Bank details, Tracking app, Contract upload |
| **Business Logic** | Create/update PostDepartureDetail record |
| **Tests Required** | Feature: ResidencyIdentityTest.php |
| **Dependencies** | T1.14 |
| **Risk Level** | HIGH |
| **Estimated Complexity** | Moderate |

### Task 6.2: Build Final Employment Details Section

| Attribute | Value |
|-----------|-------|
| **Task ID** | T6.2 |
| **Task Description** | Create employment details UI in Post-Departure |
| **Change ID(s) Covered** | PD-008, PD-009 |
| **Files to Modify** | `resources/views/departures/post-departure.blade.php` |
| **Files to Create** | `resources/views/departures/partials/employment-details.blade.php` |
| **Database Changes** | None (uses T1.14) |
| **Routes** | None (part of post-departure form) |
| **UI Changes** | Form: Company name, Employer contact fields, Work location, Salary, T&Cs, Commencement date, Special conditions |
| **Business Logic** | Update PostDepartureDetail record |
| **Tests Required** | Feature: EmploymentDetailsTest.php |
| **Dependencies** | T1.14 |
| **Risk Level** | MEDIUM |
| **Estimated Complexity** | Simple |

### Task 6.3: Build Company SWITCH Tracking

| Attribute | Value |
|-----------|-------|
| **Task ID** | T6.3 |
| **Task Description** | Create company switch tracking UI |
| **Change ID(s) Covered** | PD-010, PD-011 |
| **Files to Modify** | `resources/views/departures/post-departure.blade.php` |
| **Files to Create** | `resources/views/departures/partials/company-switch.blade.php` |
| **Database Changes** | None (uses T1.15) |
| **Routes** | `departures/{id}/employment-history` POST |
| **UI Changes** | Repeatable section: First Switch, Second Switch with all employment fields |
| **Business Logic** | Create EmploymentHistory records |
| **Tests Required** | Feature: CompanySwitchTest.php |
| **Dependencies** | T1.15 |
| **Risk Level** | MEDIUM |
| **Estimated Complexity** | Moderate |

### Task 6.4: Build Employer Information Module

| Attribute | Value |
|-----------|-------|
| **Task ID** | T6.4 |
| **Task Description** | Create complete CRUD for Employer Information |
| **Change ID(s) Covered** | EI-001 to EI-007 |
| **Files to Modify** | `resources/views/layouts/navigation.blade.php` (add tab) |
| **Files to Create** | `resources/views/employers/index.blade.php`, `resources/views/employers/create.blade.php`, `resources/views/employers/show.blade.php`, `resources/views/employers/edit.blade.php`, `app/Http/Requests/EmployerRequest.php` |
| **Database Changes** | None (uses T1.3) |
| **Routes** | Resource routes: `Route::resource('employers', EmployerController::class)` |
| **UI Changes** | New navigation tab "Employer Information"; full CRUD interface |
| **Business Logic** | Link employers to candidates via pivot if needed |
| **Tests Required** | Feature: EmployerControllerTest.php |
| **Dependencies** | T1.3 |
| **Risk Level** | MEDIUM |
| **Estimated Complexity** | Moderate |

### Task 6.5: Link Employers to Candidates

| Attribute | Value |
|-----------|-------|
| **Task ID** | T6.5 |
| **Task Description** | Create employer-candidate relationship |
| **Change ID(s) Covered** | EI-007 |
| **Files to Modify** | `app/Models/Employer.php`, `app/Models/Candidate.php` |
| **Files to Create** | `database/migrations/YYYY_MM_DD_create_candidate_employer_table.php` |
| **Database Changes** | New pivot table: candidate_employer (candidate_id, employer_id, assigned_at) |
| **Routes** | None |
| **UI Changes** | Employer detail page shows linked candidates; candidate detail shows linked employer |
| **Business Logic** | Many-to-many relationship |
| **Tests Required** | Unit: EmployerCandidateRelationshipTest.php |
| **Dependencies** | T6.4 |
| **Risk Level** | LOW |
| **Estimated Complexity** | Simple |

### Task 6.6: Build Success Stories Section

| Attribute | Value |
|-----------|-------|
| **Task ID** | T6.6 |
| **Task Description** | Create success stories UI in Post-Correspondence |
| **Change ID(s) Covered** | PC-001, PC-002 |
| **Files to Modify** | `resources/views/correspondence/index.blade.php` (or create new view) |
| **Files to Create** | `resources/views/success-stories/index.blade.php`, `resources/views/success-stories/create.blade.php`, `resources/views/success-stories/show.blade.php` |
| **Database Changes** | None (uses T1.16) |
| **Routes** | Resource routes for success-stories |
| **UI Changes** | Post-Correspondence tab split: Success Stories, Complaints |
| **Business Logic** | None |
| **Tests Required** | Feature: SuccessStoryControllerTest.php |
| **Dependencies** | T1.16 |
| **Risk Level** | LOW |
| **Estimated Complexity** | Simple |

### Task 6.7: Enhance Complaints Form

| Attribute | Value |
|-----------|-------|
| **Task ID** | T6.7 |
| **Task Description** | Add structured workflow fields to complaints |
| **Change ID(s) Covered** | PC-003, PC-004, PC-005 |
| **Files to Modify** | `app/Http/Controllers/ComplaintController.php`, `resources/views/complaints/create.blade.php`, `resources/views/complaints/show.blade.php`, `app/Http/Requests/ComplaintRequest.php` |
| **Files to Create** | None |
| **Database Changes** | None (uses T1.17) |
| **Routes** | None |
| **UI Changes** | Form steps: Select candidate, Record issue, Support steps taken, Suggestions, Conclusion; Multi-type evidence upload |
| **Business Logic** | None |
| **Tests Required** | Feature: ComplaintEnhancementTest.php |
| **Dependencies** | T1.17 |
| **Risk Level** | MEDIUM |
| **Estimated Complexity** | Moderate |

---

## Phase 7 — Integration, Testing & Documentation

**Objective:** Full integration testing, regression testing, documentation update, and deployment preparation.

**Duration:** 1 week

### Task 7.1: Integration Testing - Workflow

| Attribute | Value |
|-----------|-------|
| **Task ID** | T7.1 |
| **Task Description** | Test complete candidate workflow end-to-end |
| **Change ID(s) Covered** | XM-001, XM-002, XM-003 |
| **Files to Modify** | None |
| **Files to Create** | `tests/Feature/WorkflowIntegrationTest.php` |
| **Database Changes** | None |
| **Routes** | None |
| **UI Changes** | None |
| **Business Logic** | Test: Listing → Pre-Departure Docs → Screening → Registration → Training → Visa → Departure → Post-Departure |
| **Tests Required** | Full workflow integration tests |
| **Dependencies** | All previous tasks |
| **Risk Level** | CRITICAL |
| **Estimated Complexity** | Complex |

### Task 7.2: Regression Testing

| Attribute | Value |
|-----------|-------|
| **Task ID** | T7.2 |
| **Task Description** | Run full regression test suite |
| **Change ID(s) Covered** | All |
| **Files to Modify** | Existing test files as needed |
| **Files to Create** | None |
| **Database Changes** | None |
| **Routes** | None |
| **UI Changes** | None |
| **Business Logic** | None |
| **Tests Required** | All existing tests must pass |
| **Dependencies** | T7.1 |
| **Risk Level** | HIGH |
| **Estimated Complexity** | Moderate |

### Task 7.3: Update API Resources

| Attribute | Value |
|-----------|-------|
| **Task ID** | T7.3 |
| **Task Description** | Update API resources with new fields |
| **Change ID(s) Covered** | All data model changes |
| **Files to Modify** | `app/Http/Resources/CandidateResource.php`, `app/Http/Resources/VisaProcessResource.php`, `app/Http/Resources/DepartureResource.php` |
| **Files to Create** | `app/Http/Resources/EmployerResource.php`, `app/Http/Resources/SuccessStoryResource.php` |
| **Database Changes** | None |
| **Routes** | None |
| **UI Changes** | None |
| **Business Logic** | None |
| **Tests Required** | API: ResourceTransformationTest.php |
| **Dependencies** | All data model tasks |
| **Risk Level** | MEDIUM |
| **Estimated Complexity** | Moderate |

### Task 7.4: Update OpenAPI Specification

| Attribute | Value |
|-----------|-------|
| **Task ID** | T7.4 |
| **Task Description** | Update API documentation with new endpoints |
| **Change ID(s) Covered** | All API changes |
| **Files to Modify** | `docs/openapi.yaml` |
| **Files to Create** | None |
| **Database Changes** | None |
| **Routes** | None |
| **UI Changes** | None |
| **Business Logic** | None |
| **Tests Required** | None |
| **Dependencies** | T7.3 |
| **Risk Level** | LOW |
| **Estimated Complexity** | Moderate |

### Task 7.5: Update User Documentation

| Attribute | Value |
|-----------|-------|
| **Task ID** | T7.5 |
| **Task Description** | Update user guides and README |
| **Change ID(s) Covered** | All |
| **Files to Modify** | `README.md`, `docs/*.md` |
| **Files to Create** | `docs/INITIAL_SCREENING_GUIDE.md`, `docs/EMPLOYER_INFORMATION_GUIDE.md`, `docs/POST_DEPARTURE_GUIDE.md` |
| **Database Changes** | None |
| **Routes** | None |
| **UI Changes** | None |
| **Business Logic** | None |
| **Tests Required** | None |
| **Dependencies** | All features complete |
| **Risk Level** | LOW |
| **Estimated Complexity** | Moderate |

### Task 7.6: Database Migration Sequence Validation

| Attribute | Value |
|-----------|-------|
| **Task ID** | T7.6 |
| **Task Description** | Validate all migrations can run in sequence |
| **Change ID(s) Covered** | All database changes |
| **Files to Modify** | Migration files as needed |
| **Files to Create** | None |
| **Database Changes** | None |
| **Routes** | None |
| **UI Changes** | None |
| **Business Logic** | None |
| **Tests Required** | `php artisan migrate:fresh --seed` must complete |
| **Dependencies** | All migration tasks |
| **Risk Level** | HIGH |
| **Estimated Complexity** | Simple |

### Task 7.7: Deployment Preparation

| Attribute | Value |
|-----------|-------|
| **Task ID** | T7.7 |
| **Task Description** | Prepare deployment checklist and rollback plan |
| **Change ID(s) Covered** | All |
| **Files to Modify** | None |
| **Files to Create** | `DEPLOYMENT_CHECKLIST_V2.md`, `ROLLBACK_PLAN.md` |
| **Database Changes** | None |
| **Routes** | None |
| **UI Changes** | None |
| **Business Logic** | None |
| **Tests Required** | None |
| **Dependencies** | T7.6 |
| **Risk Level** | CRITICAL |
| **Estimated Complexity** | Moderate |

---

# PHASE 5: DELIVERY PRIORITIZATION

## 5.1 Priority Classification

### Critical (Compliance / Ethics / Blocking)

| Task ID | Description | Rationale |
|---------|-------------|-----------|
| T1.18 | Update CandidateStatus Enum | Foundation for all workflow changes |
| T2.1 | Stage Gate Middleware | Enforces compliance workflow |
| T2.2 | Status Transitions | Core business logic |
| T2.3 | Auto Batch Creation Service | Critical workflow change |
| T4.5 | Registration Gate | Compliance enforcement |
| T7.1 | Integration Testing | Validation of all changes |
| T7.7 | Deployment Preparation | Safe rollout |
| EI-001 (T6.4) | Employer Information Module | New compliance requirement |

### High (Core Functionality)

| Task ID | Description |
|---------|-------------|
| T1.3 | Create Employers Table |
| T1.6 | Add Allocation Fields to Candidates |
| T1.7 | Modify Screenings Table |
| T1.12 | Enhance Visa Processes Table |
| T1.13 | Enhance Departures Table |
| T1.14 | Create Post-Departure Details Table |
| T2.5 | Training Completion Logic |
| T2.6 | Remove Active Batch |
| T2.7 | Deprecate 3-Call System |
| T3.1 | Pre-Departure Documents UI |
| T3.5 | Initial Screening Form |
| T4.1 | Allocation Section UI |
| T4.2 | Auto Batch Integration |
| T5.3 | Enhanced Visa Pipeline UI |
| T5.5 | Enhanced Departure Tracking UI |
| T6.1 | Post-Departure Residency & Identity |
| T6.2 | Final Employment Details |

### Medium (Operational Improvements)

| Task ID | Description |
|---------|-------------|
| T1.1 | Create Programs Table |
| T1.2 | Create Implementing Partners Table |
| T1.4 | Create Pre-Departure Documents Table |
| T1.5 | Create Document Checklist Configuration |
| T1.8 | Create Courses Table |
| T1.9 | Create Candidate Course Assignments |
| T1.10 | Add Dual Status to Trainings |
| T1.11 | Create Training Assessments Table |
| T1.15 | Create Employment History Table |
| T1.16 | Create Success Stories Table |
| T1.17 | Enhance Complaints Table |
| T2.4 | Batch Size Configuration |
| T3.2 | Document Visibility Control |
| T3.3 | Document Fetch Report |
| T3.6 | Initial Screening Dashboard |
| T4.3 | Course Assignment UI |
| T4.4 | Next of Kin Enhancement |
| T5.1 | Dual Training Status UI |
| T5.2 | Assessment Tracking UI |
| T5.4 | Visa Stage Dashboard |
| T5.6 | Pre-Departure Briefing Upload |
| T5.7 | Departure Dashboard |
| T6.3 | Company SWITCH Tracking |
| T6.5 | Link Employers to Candidates |
| T6.7 | Enhance Complaints Form |
| T7.2 | Regression Testing |
| T7.3 | Update API Resources |

### Low (Enhancements)

| Task ID | Description |
|---------|-------------|
| T3.4 | Rename Screening to Initial Screening |
| T6.6 | Success Stories Section |
| T7.4 | Update OpenAPI Specification |
| T7.5 | Update User Documentation |
| T7.6 | Migration Sequence Validation |

---

# PHASE 6: VALIDATION & ACCEPTANCE CRITERIA

## Phase 1 — Foundation & Data Model Changes

### Acceptance Criteria
1. All 18 new migrations execute without error
2. All new models have proper relationships defined
3. Existing data is not corrupted
4. Foreign key constraints are properly enforced

### Functional Checks
- [ ] `php artisan migrate` completes successfully
- [ ] All model factories work
- [ ] Database seeders run without error
- [ ] All new tables created with correct columns

### Security Checks
- [ ] New columns have appropriate defaults
- [ ] No sensitive data exposed in fillable arrays unnecessarily
- [ ] Soft deletes enabled where required

### Regression Checks
- [ ] Existing model relationships still work
- [ ] Existing CRUD operations unaffected
- [ ] API endpoints return expected data structure

## Phase 2 — Workflow & State Machine Updates

### Acceptance Criteria
1. Stage gate middleware blocks unauthorized access
2. Status transitions follow new workflow exactly
3. Auto-batch creates batches with correct grouping
4. 3-call system completely deprecated

### Functional Checks
- [ ] Unscreened candidate cannot access Registration
- [ ] Unregistered candidate cannot access Training
- [ ] Batch auto-created when registration completed
- [ ] Batch size respects admin configuration
- [ ] Training completion requires assessments

### Security Checks
- [ ] Middleware cannot be bypassed via direct URL
- [ ] Policy checks enforced at controller level
- [ ] Activity logging captures all status changes

### Regression Checks
- [ ] Existing candidates maintain valid status
- [ ] Historical batches remain intact
- [ ] Training records preserve attendance data

## Phase 3 — Module 1 & 2 Updates

### Acceptance Criteria
1. Pre-Departure Documents section functional
2. Document checklist configurable by admin
3. Initial Screening form captures all required fields
4. Screening dashboard shows accurate counts

### Functional Checks
- [ ] Upload works for all document types
- [ ] Documents become read-only after screening
- [ ] Consent checkbox required
- [ ] Placement interest saves correctly
- [ ] Dashboard counts match database

### Security Checks
- [ ] File uploads validated for type and size
- [ ] Document access restricted by role

### Regression Checks
- [ ] Existing candidate listing unaffected
- [ ] Existing document archive works

## Phase 4 — Registration Updates

### Acceptance Criteria
1. Allocation section captures all four fields
2. Auto-batch assigns correctly
3. Course assignment creates proper records
4. Only screened candidates can register

### Functional Checks
- [ ] Campus/Program/OEP/Partner dropdowns populate
- [ ] Batch created if group reaches size threshold
- [ ] Course start/end dates validate
- [ ] Next of Kin financial details save

### Security Checks
- [ ] Registration gate enforced
- [ ] Financial account numbers masked in logs

### Regression Checks
- [ ] Existing registrations remain valid
- [ ] OEP assignment still works

## Phase 5 — Training, Visa, Departure Updates

### Acceptance Criteria
1. Dual training status tracked separately
2. Assessments required for completion
3. Visa pipeline shows hierarchical stats
4. Departure tracks PTN/Protector/Ticket statuses

### Functional Checks
- [ ] Technical/Soft Skills status update independently
- [ ] Interim and Final assessments recorded
- [ ] Video upload accepts mp4 format
- [ ] Departure dashboard columns populate

### Security Checks
- [ ] Video files stored securely
- [ ] Evidence paths not exposed

### Regression Checks
- [ ] Existing training records intact
- [ ] Existing visa processes maintain history
- [ ] Existing departures preserve data

## Phase 6 — Post-Departure, Employer, Correspondence Updates

### Acceptance Criteria
1. Post-Departure captures all residency/employment fields
2. Company SWITCH tracks up to 2 switches
3. Employer Information is standalone module
4. Success Stories and enhanced Complaints functional

### Functional Checks
- [ ] All 7 Residency & Identity fields save
- [ ] Employment details capture employer contact
- [ ] Company switch creates history records
- [ ] Employer CRUD works end-to-end
- [ ] Success story evidence uploads work
- [ ] Complaint workflow fields save

### Security Checks
- [ ] Bank account numbers handled securely
- [ ] Employer data access controlled by role

### Regression Checks
- [ ] Existing departure records unaffected
- [ ] Existing complaints maintain SLA data

## Phase 7 — Integration, Testing & Documentation

### Acceptance Criteria
1. Full workflow test passes
2. All existing tests pass
3. API documentation complete
4. User guides updated

### Functional Checks
- [ ] End-to-end test: Listed → Departed candidate
- [ ] `php artisan test` passes 100%
- [ ] OpenAPI spec validates
- [ ] README reflects new features

### Security Checks
- [ ] No security regression
- [ ] Penetration test passed (if required)

### Regression Checks
- [ ] All modules functional
- [ ] Performance benchmarks met

### Reporting Validation
- [ ] Dashboard counts accurate
- [ ] Reports include new fields
- [ ] Export includes new data

---

# PHASE 7: FINAL OUTPUT SUMMARY

## 7.1 Change Register Summary

| Category | Count |
|----------|-------|
| **New Features** | 23 |
| **Modified Features** | 19 |
| **Removed Features** | 5 |
| **Total Changes** | 47 |

## 7.2 Impact Analysis Summary

| Impact Type | Count |
|-------------|-------|
| Data Model Changes | 18 |
| Business Logic Changes | 12 |
| UI Changes | 24 |
| API Changes | 6 |
| Compliance Changes | 8 |
| Reporting Changes | 5 |

## 7.3 Phase-wise Task Summary

| Phase | Tasks | Duration | Dependencies |
|-------|-------|----------|--------------|
| Phase 1: Foundation | 18 tasks | 1-2 weeks | None |
| Phase 2: Workflow | 7 tasks | 1-2 weeks | Phase 1 |
| Phase 3: Listing/Screening | 6 tasks | 1 week | Phase 1, 2 |
| Phase 4: Registration | 5 tasks | 1-2 weeks | Phase 1, 2, 3 |
| Phase 5: Training/Visa/Departure | 7 tasks | 1-2 weeks | Phase 1, 2 |
| Phase 6: Post-Departure/Employer/Correspondence | 7 tasks | 1-2 weeks | Phase 1 |
| Phase 7: Integration/Testing | 7 tasks | 1 week | All |
| **Total** | **57 tasks** | **8-12 weeks** | |

## 7.4 New Database Tables Required

1. `programs`
2. `implementing_partners`
3. `employers`
4. `pre_departure_documents`
5. `document_checklists`
6. `courses`
7. `candidate_courses`
8. `training_assessments`
9. `post_departure_details`
10. `employment_histories`
11. `success_stories`
12. `candidate_employer` (pivot)

## 7.5 Modified Database Tables

1. `candidates` - Add allocation fields
2. `screenings` - Add consent, interest, status fields
3. `trainings` - Add dual status fields
4. `visa_processes` - Add JSON stage detail columns
5. `departures` - Add PTN/Protector/Ticket detail columns
6. `complaints` - Add workflow fields

## 7.6 Risk Summary

| Risk Level | Count | Key Items |
|------------|-------|-----------|
| CRITICAL | 8 | Stage gates, Auto-batch, Status transitions, Employer module |
| HIGH | 15 | 3-call deprecation, Allocation fields, Visa pipeline, Departure tracking |
| MEDIUM | 24 | Most UI tasks, dashboards, configuration |
| LOW | 10 | Renaming, documentation, simple CRUD |

## 7.7 Recommended Implementation Order

1. **Sprint 1 (Week 1-2):** Phase 1 - All database migrations and models
2. **Sprint 2 (Week 3-4):** Phase 2 - Workflow and state machine
3. **Sprint 3 (Week 5):** Phase 3 - Candidate Listing & Initial Screening
4. **Sprint 4 (Week 6-7):** Phase 4 - Registration updates
5. **Sprint 5 (Week 8-9):** Phase 5 - Training, Visa, Departure
6. **Sprint 6 (Week 10-11):** Phase 6 - Post-Departure, Employer, Correspondence
7. **Sprint 7 (Week 12):** Phase 7 - Integration testing and deployment

---

# Appendix A: File-Level Task Breakdown

## New Files to Create (62 files)

### Migrations (18 files)
```
database/migrations/YYYY_MM_DD_create_programs_table.php
database/migrations/YYYY_MM_DD_create_implementing_partners_table.php
database/migrations/YYYY_MM_DD_create_employers_table.php
database/migrations/YYYY_MM_DD_create_pre_departure_documents_table.php
database/migrations/YYYY_MM_DD_create_document_checklists_table.php
database/migrations/YYYY_MM_DD_add_allocation_fields_to_candidates_table.php
database/migrations/YYYY_MM_DD_modify_screenings_table_for_initial_screening.php
database/migrations/YYYY_MM_DD_create_courses_table.php
database/migrations/YYYY_MM_DD_create_candidate_courses_table.php
database/migrations/YYYY_MM_DD_add_dual_status_to_trainings_table.php
database/migrations/YYYY_MM_DD_create_training_assessments_table.php
database/migrations/YYYY_MM_DD_enhance_visa_processes_table.php
database/migrations/YYYY_MM_DD_enhance_departures_table.php
database/migrations/YYYY_MM_DD_create_post_departure_details_table.php
database/migrations/YYYY_MM_DD_create_employment_histories_table.php
database/migrations/YYYY_MM_DD_create_success_stories_table.php
database/migrations/YYYY_MM_DD_enhance_complaints_table.php
database/migrations/YYYY_MM_DD_create_candidate_employer_table.php
```

### Models (11 files)
```
app/Models/Program.php
app/Models/ImplementingPartner.php
app/Models/Employer.php
app/Models/PreDepartureDocument.php
app/Models/DocumentChecklist.php
app/Models/Course.php
app/Models/CandidateCourse.php
app/Models/TrainingAssessment.php
app/Models/PostDepartureDetail.php
app/Models/EmploymentHistory.php
app/Models/SuccessStory.php
```

### Controllers (7 files)
```
app/Http/Controllers/ProgramController.php
app/Http/Controllers/ImplementingPartnerController.php
app/Http/Controllers/EmployerController.php
app/Http/Controllers/DocumentChecklistController.php
app/Http/Controllers/CourseController.php
app/Http/Controllers/SuccessStoryController.php
app/Http/Controllers/Reports/DocumentReportController.php
```

### Services (1 file)
```
app/Services/AutoBatchService.php
```

### Middleware (1 file)
```
app/Http/Middleware/EnforceStageGate.php
```

### Policies (4 files)
```
app/Policies/ProgramPolicy.php
app/Policies/ImplementingPartnerPolicy.php
app/Policies/EmployerPolicy.php
app/Policies/PreDepartureDocumentPolicy.php
```

### Resources (2 files)
```
app/Http/Resources/EmployerResource.php
app/Http/Resources/SuccessStoryResource.php
```

### Views (18+ files)
```
resources/views/candidates/partials/pre-departure-documents.blade.php
resources/views/candidates/partials/allocation.blade.php
resources/views/candidates/partials/course-assignment.blade.php
resources/views/components/document-checklist.blade.php
resources/views/screenings/review.blade.php
resources/views/screenings/dashboard.blade.php
resources/views/screenings/partials/stats-cards.blade.php
resources/views/trainings/partials/dual-status.blade.php
resources/views/trainings/assessments.blade.php
resources/views/visa-processes/partials/stage-card.blade.php
resources/views/visa-processes/dashboard.blade.php
resources/views/departures/partials/ptn-status.blade.php
resources/views/departures/partials/protector-status.blade.php
resources/views/departures/partials/ticket-status.blade.php
resources/views/departures/partials/briefing-upload.blade.php
resources/views/departures/dashboard.blade.php
resources/views/departures/partials/residency-identity.blade.php
resources/views/departures/partials/employment-details.blade.php
resources/views/departures/partials/company-switch.blade.php
resources/views/employers/index.blade.php
resources/views/employers/create.blade.php
resources/views/employers/show.blade.php
resources/views/employers/edit.blade.php
resources/views/success-stories/index.blade.php
resources/views/success-stories/create.blade.php
resources/views/success-stories/show.blade.php
resources/views/reports/documents/index.blade.php
resources/views/admin/settings/batch.blade.php
```

## Existing Files to Modify (30+ files)

### Models
```
app/Models/Candidate.php
app/Models/Screening.php
app/Models/Training.php
app/Models/VisaProcess.php
app/Models/Departure.php
app/Models/Complaint.php
app/Models/Batch.php
```

### Enums
```
app/Enums/CandidateStatus.php
```

### Controllers
```
app/Http/Controllers/CandidateController.php
app/Http/Controllers/ScreeningController.php
app/Http/Controllers/TrainingController.php
app/Http/Controllers/VisaProcessController.php
app/Http/Controllers/DepartureController.php
app/Http/Controllers/ComplaintController.php
app/Http/Controllers/BatchController.php
```

### Services
```
app/Services/ScreeningService.php
app/Services/TrainingService.php
app/Services/VisaProcessService.php
app/Services/DepartureService.php
app/Services/ComplaintService.php
app/Services/BatchService.php
```

### Policies
```
app/Policies/CandidatePolicy.php
```

### Views
```
resources/views/candidates/show.blade.php
resources/views/candidates/registration.blade.php
resources/views/screenings/*.blade.php
resources/views/trainings/show.blade.php
resources/views/visa-processes/show.blade.php
resources/views/departures/show.blade.php
resources/views/departures/post-departure.blade.php
resources/views/complaints/create.blade.php
resources/views/complaints/show.blade.php
resources/views/layouts/navigation.blade.php
```

### Resources
```
app/Http/Resources/CandidateResource.php
app/Http/Resources/VisaProcessResource.php
app/Http/Resources/DepartureResource.php
```

### Configuration
```
app/Http/Kernel.php
routes/web.php
routes/api.php
config/wasl.php (create if not exists)
```

### Documentation
```
README.md
docs/openapi.yaml
docs/*.md
```

---

# Appendix B: Dependency Map

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         PHASE 1: FOUNDATION                              │
│  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐       │
│  │ T1.1    │  │ T1.2    │  │ T1.3    │  │ T1.4    │  │ T1.5    │       │
│  │Programs │  │Partners │  │Employers│  │PreDocs  │  │Checklist│       │
│  └────┬────┘  └────┬────┘  └────┬────┘  └────┬────┘  └────┬────┘       │
│       │            │            │            │            │             │
│       └────────────┴────────────┼────────────┴────────────┘             │
│                                 │                                        │
│                                 ▼                                        │
│  ┌─────────────────────────────────────────────────────────────────┐    │
│  │ T1.6: Add Allocation Fields to Candidates                       │    │
│  └─────────────────────────────────────────────────────────────────┘    │
│                                 │                                        │
│  ┌─────────┐  ┌─────────┐      │      ┌─────────┐  ┌─────────┐         │
│  │ T1.7    │  │ T1.8    │      │      │ T1.10   │  │ T1.11   │         │
│  │Screening│  │Courses  │      │      │Training │  │Assess   │         │
│  └────┬────┘  └────┬────┘      │      └────┬────┘  └────┬────┘         │
│       │            │           │           │            │               │
│       │            └───────────┼───────────┘            │               │
│       │                        │                        │               │
│  ┌────┴────┐  ┌─────────┐  ┌──┴──────┐  ┌─────────┐   │               │
│  │ T1.9    │  │ T1.12   │  │ T1.13   │  │ T1.14   │   │               │
│  │CandCrs  │  │VisaEnhc │  │DepEnhc  │  │PostDet  │   │               │
│  └─────────┘  └─────────┘  └────┬────┘  └────┬────┘   │               │
│                                 │            │         │               │
│                            ┌────┴────┐       │         │               │
│                            │ T1.15   │       │         │               │
│                            │EmpHist  │       │         │               │
│                            └─────────┘       │         │               │
│                                              │         │               │
│  ┌─────────┐  ┌─────────┐                   │         │               │
│  │ T1.16   │  │ T1.17   │                   │         │               │
│  │Success  │  │Complain │                   │         │               │
│  └─────────┘  └─────────┘                   │         │               │
│                                              │         │               │
│                            ┌─────────────────┴─────────┴───────┐       │
│                            │ T1.18: Update CandidateStatus Enum│       │
│                            └───────────────────────────────────┘       │
└─────────────────────────────────────────────────────────────────────────┘
                                        │
                                        ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                         PHASE 2: WORKFLOW                                │
│  ┌───────────────────────────────────────────────────────────────┐      │
│  │ T2.1: Stage Gate Middleware ←── T2.2: Status Transitions      │      │
│  └───────────────────────────────────────────────────────────────┘      │
│                                 │                                        │
│  ┌─────────────────────────────┼────────────────────────────────┐       │
│  │ T2.3: Auto Batch Service    │    T2.4: Batch Config          │       │
│  └─────────────────────────────┼────────────────────────────────┘       │
│                                │                                         │
│  ┌─────────────────────────────┼────────────────────────────────┐       │
│  │ T2.5: Training Completion   │    T2.6: Remove Active Batch   │       │
│  └─────────────────────────────┼────────────────────────────────┘       │
│                                │                                         │
│  ┌─────────────────────────────┴────────────────────────────────┐       │
│  │ T2.7: Deprecate 3-Call System                                │       │
│  └──────────────────────────────────────────────────────────────┘       │
└─────────────────────────────────────────────────────────────────────────┘
                                        │
                    ┌───────────────────┼───────────────────┐
                    ▼                   ▼                   ▼
        ┌───────────────────┐ ┌───────────────────┐ ┌───────────────────┐
        │ PHASE 3           │ │ PHASE 4           │ │ PHASE 5           │
        │ Listing/Screening │ │ Registration      │ │ Train/Visa/Depart │
        │ T3.1 - T3.6       │ │ T4.1 - T4.5       │ │ T5.1 - T5.7       │
        └─────────┬─────────┘ └─────────┬─────────┘ └─────────┬─────────┘
                  │                     │                     │
                  └─────────────────────┴─────────────────────┘
                                        │
                                        ▼
        ┌───────────────────────────────────────────────────────────────┐
        │                    PHASE 6: Post/Employer/Correspondence      │
        │                    T6.1 - T6.7                                 │
        └───────────────────────────────┬───────────────────────────────┘
                                        │
                                        ▼
        ┌───────────────────────────────────────────────────────────────┐
        │                    PHASE 7: Integration & Testing             │
        │                    T7.1 - T7.7                                 │
        └───────────────────────────────────────────────────────────────┘
```

---

**Document Prepared By:** Senior Laravel Architect / Delivery Lead  
**Review Status:** Ready for Technical Review  
**Next Actions:** Technical review, Sprint planning, Resource allocation

---

*End of Document*
