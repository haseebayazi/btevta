# BTEVTA Overseas Employment Management System
## Complete System Overview & Feature Documentation

---

## 🎯 System Purpose

A comprehensive digital platform that manages the entire candidate lifecycle from BTEVTA listing through overseas deployment and post-departure tracking, with separate records for each campus to enable performance comparison, reporting, and transparency.

---

## 📱 System Architecture

### Technology Stack
- **Backend Framework:** Laravel 11.x (PHP 8.2+)
- **Database:** MySQL 8.0+
- **Frontend:** Tailwind CSS 3.x, Alpine.js 3.x
- **Document Processing:** PhpSpreadsheet, DomPDF
- **Activity Logging:** Spatie Activity Log
- **File Storage:** Laravel Storage (Local/Cloud)
- **Caching:** Redis (Optional)
- **Session Management:** Database/Redis

### Design Pattern
- **MVC Architecture** (Model-View-Controller)
- **Repository Pattern** for data access
- **Service Layer** for business logic
- **Observer Pattern** for event handling
- **Middleware** for authentication and authorization

---

## 🔐 User Roles & Permissions

### 1. System Administrator
**Access Level:** Full System Access

**Capabilities:**
- Manage all campuses, OEPs, trades, and users
- View cross-campus analytics
- Configure system settings
- Access audit logs
- Generate system-wide reports
- Manage complaints and correspondence
- Override any action in the system

### 2. Campus Administrator
**Access Level:** Campus-Specific Access

**Capabilities:**
- Manage candidates assigned to their campus
- Record screening calls and outcomes
- Register candidates and manage documents
- Manage training (attendance, assessments, certificates)
- Track visa processing for campus candidates
- Handle campus-specific complaints
- Generate campus reports
- View campus performance metrics

### 3. OEP (Overseas Employment Promoter)
**Access Level:** Assigned Candidates Only

**Capabilities:**
- View candidates assigned to their organization
- Update visa processing information
- Upload visa documents
- Record departure details
- Track post-departure compliance
- Generate OEP-specific reports
- Communicate with campuses

### 4. Trainer
**Access Level:** Training Module Only

**Capabilities:**
- Mark attendance for assigned batches
- Record assessment scores
- Upload training materials
- Generate attendance reports
- View trainee performance

### 5. Candidate
**Access Level:** Self-Service Portal

**Capabilities:**
- View personal profile and documents
- Track application status
- Upload required documents
- View training schedule and results
- Register complaints
- Receive notifications

---

## 📊 Core Modules (10 Tabs)

### Tab 1: Candidates Listing

**Purpose:** Import and manage candidate listings from BTEVTA

**Features:**
- ✅ Excel import from BTEVTA template
- ✅ Auto-assign batch numbers by trade, district, intake
- ✅ Capture BTEVTA Assigned ID
- ✅ Validation & deduplication (CNIC, Application ID, Name+DOB)
- ✅ Campus allocation based on district/proximity
- ✅ Bulk operations (assign campus, change status)
- ✅ Advanced search and filtering
- ✅ Export to Excel/PDF

**Reports:**
- Intake summary by district, trade, gender, campus
- Batch-wise candidate count
- Data completeness analysis
- Import audit logs

**Workflow:**
```
BTEVTA Excel → Import → Validate → Auto-Assign Campus → Create Batch → Listed Status
```

---

### Tab 2: Candidate Screening

**Purpose:** Three-call screening process management

**Features:**
- ✅ Call log system with three stages:
  - Call 1: Document collection reminder
  - Call 2: Registration & campus selection
  - Call 3: Training confirmation
- ✅ Track call outcomes (answered, no answer, busy, wrong number)
- ✅ Record screening outcomes (eligible, rejected, pending)
- ✅ Upload evidence (call notes, screenshots, forms)
- ✅ Map desk screening results with BTEVTA intake data
- ✅ Follow-up reminder system
- ✅ Bulk call scheduling

**Reports:**
- Call status summary (pending/completed/follow-up)
- Screening outcomes per district & trade
- Document readiness tracker
- Communication performance metrics

**Workflow:**
```
Listed → Call 1 → Call 2 → Call 3 → Screening Outcome → Eligible/Rejected
```

---

### Tab 3: Registration at Campus

**Purpose:** Complete candidate registration and documentation

**Features:**
- ✅ Candidate profile creation with photo
- ✅ Document archive system:
  - CNIC
  - Passport
  - Education certificates
  - Police clearance
  - Medical reports
- ✅ Next of kin information capture
- ✅ Digital signing of undertakings
- ✅ OEP allocation based on demand/trade
- ✅ Document verification workflow
- ✅ Biometric capture (optional)

**Reports:**
- Registered candidates per campus
- Pending documentation tracker
- Undertakings completion rate
- OEP-wise allocation overview

**Workflow:**
```
Eligible → Register → Upload Docs → Next of Kin → Undertakings → Assign OEP → Registered Status
```

---

### Tab 4: Training

**Purpose:** Campus-wise training management

**Features:**
- ✅ Batch management system
- ✅ Daily attendance marking:
  - Present/Absent/Late/Leave
  - Check-in/check-out times
  - Attendance percentage tracking
- ✅ Assessment management:
  - Midterm exams
  - Final exams
  - Practical assessments
  - Theory tests
- ✅ Grade calculation and result generation
- ✅ Certificate generation with unique numbers
- ✅ Trainer evaluation system
- ✅ Course schedule management
- ✅ Module completion tracking

**Reports:**
- Attendance rate per batch
- Completion & certification status
- Trainer performance metrics
- Campus-wise training comparison
- Pass/fail analysis
- Dropout tracking

**Workflow:**
```
Registered → Batch Assignment → Daily Attendance → Assessments → Pass/Fail → Certificate → Training Complete
```

---

### Tab 5: Visa Processing

**Purpose:** Comprehensive pre-departure process management

**Features:**
- ✅ Interview scheduling & results
- ✅ Trade test booking & results
- ✅ Takamol test tracking
- ✅ Medical test (GAMCA) management
- ✅ E-number generation and tracking
- ✅ Biometrics (Etimad) appointment system
- ✅ Visa document submission tracking
- ✅ Visa & PTN number recording
- ✅ Attestation date tracking
- ✅ Ticket issuance & travel plan upload
- ✅ Stage-wise progress tracking
- ✅ Document expiry alerts

**Reports:**
- Visa processing timeline (average days per stage)
- Pending medical/biometric tracker
- PTN issuance summary
- OEP-wise visa status
- Bottleneck analysis
- Success rate by OEP

**Workflow:**
```
Training Complete → Interview → Trade Test → Takamol → Medical → E-Number →
Biometric → Visa Submission → Visa Issued → PTN → Attestation → Ticket → Ready to Depart
```

---

### Tab 6: Departure

**Purpose:** Post-departure tracking and compliance

**Features:**
- ✅ Pre-departure briefing tracking
- ✅ Flight details recording
- ✅ Post-arrival documentation:
  - Iqama number
  - Post-arrival medical report
  - Absher registration
  - Qiwa ID activation
  - Salary confirmation
- ✅ 90-day post-arrival compliance report
- ✅ Issue tracking and resolution
- ✅ Regular follow-up reminders
- ✅ Communication log with workers

**Reports:**
- Departure list by date, trade, OEP
- Pending Iqama/Absher activation
- Salary disbursement status
- 90-day compliance report
- Issue resolution tracking
- Worker satisfaction surveys

**Workflow:**
```
Visa Approved → Pre-Departure Briefing → Departure → Arrival → Iqama →
Absher → Qiwa → First Salary → 90-Day Report → Departed Status Complete
```

---

### Tab 7: Correspondence

**Purpose:** Official communications management

**Features:**
- ✅ Centralized correspondence repository
- ✅ Reference number system
- ✅ Track communications with:
  - BTEVTA
  - OEPs
  - Embassies
  - Campuses
  - Government agencies
- ✅ File upload (PDF copies)
- ✅ Reply tracking system
- ✅ Pending reply alerts
- ✅ Search by organization/date/subject
- ✅ Document linking to candidates

**Reports:**
- Correspondence register
- Pending reply tracker
- Communication summary
- Response time analysis
- Organization-wise correspondence volume

**Workflow:**
```
Create Correspondence → Upload Document → Set Reply Deadline → Track Status → Mark Replied
```

---

### Tab 8: Complaints Redressal Mechanism

**Purpose:** SLA-based complaint management

**Features:**
- ✅ In-app complaint registration
- ✅ Multi-channel submission (candidates, trainers, OEPs, admin)
- ✅ Category-based tagging:
  - Screening issues
  - Training quality
  - Visa processing
  - Salary disputes
  - Conduct/behavior
  - Facility issues
- ✅ Priority levels (low, medium, high, critical)
- ✅ Status tracking:
  - Registered
  - Under Review
  - Assigned
  - In Progress
  - Resolved
  - Closed
  - Escalated
- ✅ SLA monitoring (default 5 working days)
- ✅ Assignment to staff members
- ✅ Resolution documentation
- ✅ Escalation matrix
- ✅ Evidence attachment

**Reports:**
- Total complaints (received/resolved/pending)
- Average resolution time
- Campus-wise complaint trends
- Category-wise analysis
- SLA compliance rate
- Recurring issue identification

**Workflow:**
```
Register Complaint → Assign Category/Priority → Assign to Staff → Investigate →
Resolve → Document Resolution → Close (or Escalate if needed)
```

---

### Tab 9: Document Archive

**Purpose:** Global document repository with version control

**Features:**
- ✅ Centralized document storage
- ✅ Document categorization:
  - Candidate documents
  - Campus documents
  - OEP documents
  - Correspondence
  - Reports
  - Templates
- ✅ Version control system:
  - New upload replaces old
  - Previous versions archived
  - Version history tracking
- ✅ Smart filtering:
  - By candidate
  - By campus
  - By trade
  - By OEP
  - By document type
  - By date range
- ✅ Secure access control
- ✅ Audit log (who accessed/downloaded)
- ✅ Document expiry tracking
- ✅ Bulk download option

**Reports:**
- Missing document summary
- Expiry alerts (medical, visa, CNIC)
- Document verification status
- Storage utilization analysis
- Access log report

**Workflow:**
```
Upload Document → Categorize → Tag (Candidate/Campus/OEP) → Store → Version Control →
Access Control → Expiry Tracking
```

---

### Tab 10: Reporting Module

**Purpose:** Dynamic report generation engine

**Features:**
- ✅ Role-based dashboard views:
  - Project Director dashboard
  - BTEVTA dashboard
  - OEP dashboard
  - Campus Admin dashboard
  - Visa Facilitation Partner dashboard
- ✅ Pre-built reports:
  1. **Candidate Reports**
     - Individual profile (auto-generated PDF)
     - Consolidated candidate record
     - Status-wise lists
     - Progress tracking
  2. **Operational Reports**
     - Batch-wise process completion
     - Visa status tracking
     - Salary & post-departure updates
     - OEP performance summary
  3. **Training Reports**
     - Campus attendance analysis
     - Pass/fail rates
     - Trainer performance
     - Course completion statistics
  4. **Grievance & Compliance Reports**
     - Complaint closure rate
     - Document verification audit
     - SLA compliance
  5. **Custom Reports**
     - Build-your-own using filters
     - Multiple export formats

**Export Formats:**
- Excel (.xlsx)
- CSV
- PDF
- Dashboard charts/graphs

**Filters Available:**
- Campus
- Trade
- Status
- Gender
- Date range
- District
- OEP
- Batch

**Workflow:**
```
Select Report Type → Apply Filters → Preview → Export (Excel/PDF/CSV) → Schedule (Optional)
```

---

## 🔧 Administrative Features

### Campus Management
- Add/edit/delete campuses
- Set capacity and facilities
- Assign administrators
- Track performance metrics
- Compare campus effectiveness

### OEP Management
- Register OEP companies
- License number tracking
- Performance monitoring
- Candidate allocation
- Commission tracking

### Trade Management
- Define available trades
- Set training