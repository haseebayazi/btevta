# WASL v3 User Manual

**BTEVTA Workforce Abroad Skills & Linkages System**
**Version:** 3.0.0
**Last Updated:** January 19, 2026
**Audience:** System Users (Data Entry Staff, Supervisors, Program Managers)

---

## Table of Contents

1. [Introduction](#introduction)
2. [Getting Started](#getting-started)
3. [Candidate Journey Overview](#candidate-journey-overview)
4. [Pre-Departure Documents](#pre-departure-documents)
5. [Initial Screening](#initial-screening)
6. [Registration & Allocation](#registration--allocation)
7. [Training Management](#training-management)
8. [Visa Processing](#visa-processing)
9. [Departure Management](#departure-management)
10. [Post-Departure Tracking](#post-departure-tracking)
11. [Employer Management](#employer-management)
12. [Success Stories](#success-stories)
13. [Complaints Management](#complaints-management)
14. [Reports & Analytics](#reports--analytics)
15. [Troubleshooting](#troubleshooting)

---

## Introduction

### What is WASL v3?

WASL (Workforce Abroad Skills & Linkages) v3 is the enhanced candidate management system for BTEVTA that tracks candidates from initial listing through their overseas employment journey.

### What's New in Version 3?

**Key Enhancements:**
- ✅ Structured Pre-Departure Documents checklist
- ✅ Simplified Initial Screening workflow
- ✅ Automatic Batch Generation during Registration
- ✅ Unified Allocation system (Campus + Program + Partner)
- ✅ Dual Training Status tracking (Technical + Soft Skills)
- ✅ Enhanced Post-Departure tracking
- ✅ New Employer Information module
- ✅ Success Stories collection
- ✅ Enhanced Complaints workflow

### System Requirements

**Browser Support:**
- Google Chrome 90+ (Recommended)
- Mozilla Firefox 88+
- Microsoft Edge 90+
- Safari 14+

**Screen Resolution:** Minimum 1366x768

---

## Getting Started

### Logging In

1. Navigate to the WASL system URL
2. Enter your **username** and **password**
3. Click **Login**
4. You will be directed to your dashboard based on your role

### Dashboard Overview

Your dashboard displays:
- **Quick Stats:** Total candidates, active batches, pending tasks
- **Recent Activity:** Latest system activities
- **Pending Actions:** Tasks requiring your attention
- **Shortcuts:** Quick access to common functions

### Navigation Menu

**Main Menu Items:**
- **Dashboard** - System overview
- **Candidates** - Candidate management
- **Screenings** - Initial screening module
- **Registration** - Candidate registration
- **Training** - Training & assessment management
- **Visa** - Visa processing pipeline
- **Departure** - Departure management
- **Post-Departure** - Post-arrival tracking
- **Employers** - Employer information (NEW in v3)
- **Success Stories** - Success story collection (NEW in v3)
- **Complaints** - Complaint management
- **Reports** - Analytics and reports
- **Admin** - System administration

---

## Candidate Journey Overview

### The 9-Phase Journey

WASL v3 implements a strict sequential workflow:

```
1. Candidate Listing
   ↓
2. Pre-Departure Documents ← NEW in v3
   ↓
3. Initial Screening ← Enhanced in v3
   ↓
4. Registration & Allocation ← Enhanced in v3
   ↓
5. Training & Assessment ← Enhanced in v3
   ↓
6. Visa Processing
   ↓
7. Departure ← Enhanced in v3
   ↓
8. Post-Departure ← Enhanced in v3
   ↓
9. Success Stories & Feedback ← NEW in v3
```

### Workflow Gates

**Important:** Candidates cannot skip phases. Each phase must be completed before proceeding.

**Gate 1: Initial Screening → Registration**
- Only candidates with status "SCREENED" can be registered
- System will block registration attempts for unscreened candidates

**Gate 2: Training Completion**
- Both Technical and Soft Skills training must be completed
- Both Interim and Final assessments must be passed

---

## Pre-Departure Documents

### Overview

The Pre-Departure Documents section is the **first step after candidate listing**. All required documents must be collected before proceeding to screening.

### Document Categories

**Mandatory Documents:**
1. **CNIC** - Computerized National Identity Card
2. **Passport** - Valid passport (minimum 6 months validity)
3. **Domicile Certificate** - Proof of permanent residence
4. **FRC** - Family Registration Certificate
5. **PCC** - Police Character Certificate

**Optional Documents:**
6. Pre-medical test results
7. Educational certifications
8. Professional licenses (Driving, RN Nurse, etc.)
9. Resume/CV

### How to Upload Documents

**Step 1: Access Document Section**
1. Navigate to **Candidates** menu
2. Click on candidate name to view details
3. Select **Pre-Departure Documents** tab

**Step 2: Upload Document**
1. Click **Upload Document** button
2. Select **Document Type** from dropdown
3. Click **Choose File** and select document (PDF, JPG, PNG)
   - Maximum file size: 5MB
4. Add **Notes** (optional)
5. Click **Upload**

**Step 3: Verification**
- Uploaded documents appear in the list with status "Pending Verification"
- Authorized staff will verify and mark as "Verified"
- Verified documents show ✓ green checkmark

### Document Verification (Supervisors Only)

1. Review uploaded document
2. Check document validity and clarity
3. Click **Verify** button
4. Add verification notes
5. Document status changes to "Verified"

### Bulk Document Report

**Generate Report:**
1. Go to **Reports** → **Pre-Departure Documents**
2. Select filters (Campus, Batch, Status)
3. Click **Generate Report**

**Report Shows:**
- Candidate name and CNIC
- Mandatory documents uploaded vs. required
- Verification status
- Completion percentage

---

## Initial Screening

### Overview

Initial Screening replaces the old 3-call system with a **single comprehensive review**.

### Screening Status Options

- **SCREENED** - Candidate passed screening (can proceed to Registration)
- **PENDING** - Screening in progress
- **DEFERRED** - Screening postponed with reason

**Note:** "SCREENED" is the only status that allows progression to Registration.

### How to Conduct Screening

**Step 1: Access Screening Module**
1. Navigate to **Screenings** menu
2. Click **New Screening**
3. Search and select candidate

**Step 2: Review Pre-Departure Documents**
- System displays document completion status
- All mandatory documents must be uploaded
- Verify document checklist before proceeding

**Step 3: Screening Interview**

**Consent Verification:**
- Ask: "Do you consent to work abroad?"
- Select: ☐ Yes ☐ No
- If "No", screening cannot proceed

**Placement Interest:**
- Ask: "Are you interested in local or international placement?"
- Select: ⦿ Local ⦿ International
- If "International", specify target country

**Skills Assessment:**
- Evaluate candidate's skills and readiness
- Record assessment notes

**Step 4: Screening Decision**
1. Select **Screening Status**:
   - **SCREENED** - If candidate passes all criteria
   - **DEFERRED** - If candidate needs revaluation (provide reason)
2. Upload **Evidence** (optional) - screening notes, assessment forms
3. Click **Save Screening**

### Screening Dashboard

**Access:** Screenings → Dashboard

**Dashboard Shows:**
- Total screenings conducted
- Status breakdown (Screened, Pending, Deferred)
- Recent screenings
- Pending screenings requiring action

---

## Registration & Allocation

### Overview

Registration is the most critical phase where candidates are:
1. Officially registered in the system
2. Allocated to Campus + Program + OEP + Implementing Partner
3. Automatically assigned to a Batch
4. Assigned a unique Allocated Number

### Prerequisites

**Before Registration:**
- ✅ Candidate status must be "SCREENED"
- ✅ All mandatory pre-departure documents uploaded
- ✅ Screening approved

**System Validation:**
- System will block registration if candidate is not "SCREENED"
- Error message: "Only screened candidates can be registered"

### Registration Form Sections

#### Section 1: Personal Information
- Full Name (auto-filled from candidate listing)
- CNIC (auto-filled)
- Date of Birth
- Contact Information

#### Section 2: Next of Kin Details (Enhanced in v3)

**Required Fields:**
- Next of Kin Name
- Relationship (Father, Mother, Spouse, Sibling, Other)
- CNIC Number
- Contact Phone
- Address

**Financial Details (NEW):**
- Bank Name
- Account Title
- Account Number
- Branch Code

**Document:**
- Upload Next of Kin CNIC copy (required)

#### Section 3: Allocation (NEW in v3)

This section assigns the candidate to organizational units:

**Campus:**
- Select campus where candidate will train
- Example: Islamabad, Lahore, Karachi

**Program:**
- Select training program
- Example: Technical Education & Vocational Training (TEC)

**Trade/OEP:**
- Select specific trade
- Example: Welding, Electrician, Plumbing

**Implementing Partner:**
- Select implementing partner organization
- Example: National Skills Development Corporation

#### Section 4: Course Assignment (NEW in v3)

**Select Course:**
- Choose from available courses
- Course type: Technical, Soft Skills, or Both

**Course Schedule:**
- Start Date
- End Date (calculated based on course duration)

**Training Type:**
- Technical Training
- Soft Skills Training
- Both

### Automatic Batch Generation

**How It Works:**

When you click "Register", the system automatically:

1. **Generates Batch Number** using format:
   ```
   CAMPUS-PROGRAM-TRADE-YEAR-SEQUENCE

   Example: ISB-TEC-WLD-2026-0001
   ```

2. **Assigns Candidate to Batch:**
   - If existing batch has space (configurable: 20/25/30 candidates), candidate joins existing batch
   - If batch is full, system creates new batch with incremented sequence

3. **Generates Allocated Number:**
   ```
   BATCH_NUMBER-POSITION

   Example: ISB-TEC-WLD-2026-0001-0012
   ```

**Batch Size Configuration:**
- Default: 25 candidates per batch
- Configurable by admin: 20, 25, or 30

### Registration Workflow

**Step 1: Navigate to Registration**
1. Go to **Registration** menu
2. Click **New Registration**
3. Search candidate by CNIC or name
4. System validates screening status

**Step 2: Fill Personal Information**
- Review auto-filled fields
- Update if necessary

**Step 3: Complete Next of Kin Details**
- Enter all required fields
- Upload CNIC copy
- Enter financial account details

**Step 4: Complete Allocation**
- Select Campus
- Select Program
- Select Trade/OEP
- Select Implementing Partner

**Step 5: Assign Course**
- Select course from dropdown
- Set start and end dates
- Confirm training type

**Step 6: Save Registration**
1. Click **Save Registration**
2. System validates all fields
3. System generates batch number
4. System assigns allocated number
5. Success message displays with batch and allocated number

**Step 7: Confirmation**
- Registration summary displayed
- Batch Number: ISB-TEC-WLD-2026-0001
- Allocated Number: ISB-TEC-WLD-2026-0001-0012
- Candidate status changes to "REGISTERED"

---

## Training Management

### Overview

Training management tracks both **Technical** and **Soft Skills** training with separate status tracking.

### Training Status Types (NEW in v3)

Each candidate has **two separate statuses**:

**Technical Training Status:**
- NOT_STARTED - Training not begun
- IN_PROGRESS - Currently undergoing technical training
- COMPLETED - Technical training finished

**Soft Skills Training Status:**
- NOT_STARTED - Training not begun
- IN_PROGRESS - Currently undergoing soft skills training
- COMPLETED - Soft skills training finished

### Assessment Types (NEW in v3)

**Interim Assessment:**
- Conducted mid-training
- Evaluates progress
- Identifies improvement areas

**Final Assessment:**
- Conducted at training completion
- Determines pass/fail
- Required for training completion

**Training Completion Criteria:**
- ✅ Both Technical and Soft Skills status = "COMPLETED"
- ✅ Interim Assessment completed and passed
- ✅ Final Assessment completed and passed

### How to Manage Training

#### Starting Training

**Step 1: Access Training Module**
1. Navigate to **Training** menu
2. Select **Batches**
3. Click on batch to view candidates

**Step 2: Update Training Status**
1. Find candidate in batch
2. Click **Update Training**
3. Select **Technical Training Status** → IN_PROGRESS
4. Select **Soft Skills Training Status** → IN_PROGRESS
5. Set **Training Start Date**
6. Click **Save**

#### Recording Assessments

**Step 1: Navigate to Assessments**
1. Go to **Training** → **Assessments**
2. Click **New Assessment**
3. Select candidate from batch

**Step 2: Assessment Form**

**Basic Information:**
- Candidate Name (auto-filled)
- Batch Number (auto-filled)
- Assessment Type: ⦿ Interim ⦿ Final
- Assessment Date

**Scoring:**
- Score Obtained: [___] (e.g., 85.5)
- Maximum Score: [___] (e.g., 100)
- Passing Score: [___] (e.g., 70)
- Result: ⦿ Pass ⦿ Fail (auto-calculated)

**Assessor Information:**
- Assessor Name (auto-filled from logged-in user)
- Remarks: [Text area for detailed feedback]

**Evidence Upload:**
- Upload assessment form (PDF)
- Upload practical test photos/videos (optional)

**Step 3: Save Assessment**
1. Click **Save Assessment**
2. Assessment recorded in candidate profile
3. If Final Assessment and passed, training automatically marked complete

#### Viewing Batch Statistics

**Access:** Training → Batch Statistics

**Statistics Display:**
- Total candidates in batch
- Interim assessments completed/pending
- Final assessments completed/pending
- Average scores (interim and final)
- Pass rates
- Highest and lowest scores
- Training completion status

---

## Visa Processing

### Overview

Visa Processing manages the 12-stage visa pipeline with enhanced appointment and result tracking.

### Enhanced Features in v3

**Appointment Tracking:**
- Appointment Date
- Appointment Time
- Allowed Center

**Result Tracking:**
- Pass
- Fail
- Pending
- Refused

**Evidence Management:**
- Mandatory evidence upload at each stage
- Document verification

### Visa Stages

1. Medical Test
2. Biometric Enrollment
3. Trade Test (country-specific, e.g., Takamol for KSA)
4. Interview
5. Document Verification
6. Visa Application Submission
7. Visa Processing
8. Visa Approval
9. Visa Stamping
10. Visa Collection
11. Final Clearance
12. Visa Issued

### How to Process Visa Stage

**Step 1: Access Visa Module**
1. Navigate to **Visa** menu
2. Select candidate
3. Click on current visa stage

**Step 2: Appointment Details**

**Schedule Appointment:**
- Appointment Date: [Select date]
- Appointment Time: [Select time]
- Allowed Center: [Enter center name]
- Status: ⦿ Scheduled ⦿ Done ⦿ Pending

**Step 3: Result Entry**

After appointment is done:
- Result Status: ⦿ Pass ⦿ Fail ⦿ Pending ⦿ Refused
- Result Date: [Auto-filled]
- Result Notes: [Text area]

**Step 4: Evidence Upload**
- Upload appointment confirmation (required)
- Upload result document (required after completion)
- Upload supporting documents (optional)

**Step 5: Save Stage**
1. Click **Save Stage**
2. If status = "Pass", stage marked complete
3. Next stage becomes available
4. If status = "Fail" or "Refused", workflow may be blocked (depends on stage)

### Visa Dashboard

**Access:** Visa → Dashboard

**Hierarchical View:**
```
Total Candidates in Visa Processing
├── Scheduled (appointments booked)
├── Done (appointments completed)
│   ├── Pass (successful)
│   ├── Fail (unsuccessful)
│   └── Pending (awaiting result)
└── Refused (rejected)
```

**Filter Options:**
- By Stage
- By Date Range
- By Campus
- By Result Status

---

## Departure Management

### Overview

Departure Management tracks the final steps before candidate departure including PTN, Protector, Ticket, Pre-Departure Briefing, and departure confirmation.

### Enhanced Fields in v3

#### PTN (Permission to Navigate) Status

**Status Options:**
- Issued - PTN granted (record issued date)
- Done - PTN process completed
- Pending - PTN application in progress
- Deferred - PTN postponed (record reason)
- Not Issued - PTN not granted (record reason)
- Refused - PTN application rejected (record reason)

**Required Fields:**
- PTN Status: [Select from dropdown]
- If "Issued" or "Done":
  - PTN Issued Date: [Date picker]
- If "Deferred", "Not Issued", or "Refused":
  - Deferred Reason: [Text area - required]

#### Protector Status (NEW in v3)

**Status Options:**
- Applied - Protector application submitted
- Done - Protector process completed
- Pending - Application in progress
- Deferred - Process postponed (record reason)

**Required Fields:**
- Protector Status: [Select from dropdown]
- Protector Applied Date: [Date picker]
- If "Done":
  - Protector Done Date: [Date picker]
- If "Deferred":
  - Deferred Reason: [Text area]

#### Ticket Details (Enhanced in v3)

**Required Fields:**
- Ticket Date: [Date picker]
- Ticket Time: [Time picker]
- Departure Platform: [Text] (e.g., "Islamabad International Airport")
- Landing Platform: [Text] (e.g., "King Khalid International Airport, Riyadh")
- Flight Type: ⦿ Direct ⦿ Connected

#### Pre-Departure Briefing (Enhanced in v3)

**Document Upload:**
- Upload scanned original documents handed to candidate
- File format: PDF
- Maximum size: 10MB

**Video Upload (NEW):**
- Upload video of candidate receiving documents
- Video should show:
  - Candidate receiving documents
  - Terms & Conditions explanation
  - Success story recording (optional)
- File format: MP4, MOV, AVI
- Maximum size: 50MB

#### Final Departure Status (NEW in v3)

**Status Options:**
- Processing - Departure arrangements in progress
- Ready to Depart - All requirements completed
- Departed - Candidate has departed

### How to Manage Departure

**Step 1: Access Departure Module**
1. Navigate to **Departure** menu
2. Search for candidate
3. Click **Manage Departure**

**Step 2: PTN Status**
1. Select PTN Status from dropdown
2. If "Issued" or "Done", enter issued date
3. If "Deferred" or "Not Issued", provide reason
4. Click **Save PTN Status**

**Step 3: Protector Status**
1. Select Protector Status
2. Enter applied date
3. If "Done", enter completion date
4. If "Deferred", provide reason
5. Click **Save Protector Status**

**Step 4: Ticket Details**
1. Enter ticket date and time
2. Enter departure platform (airport/port)
3. Enter landing platform (destination)
4. Select flight type (Direct or Connected)
5. Click **Save Ticket Details**

**Step 5: Pre-Departure Briefing**
1. Click **Upload Briefing Documents**
2. Select PDF file with scanned documents
3. Click **Upload Briefing Video**
4. Select video file (may take time to upload)
5. Wait for upload confirmation
6. Click **Save Briefing**

**Step 6: Final Departure Status**
1. Once all fields completed, status shows "Processing"
2. When candidate is ready, change to "Ready to Depart"
3. After departure, change to "Departed"
4. System records departure timestamp

### Departure Dashboard

**Access:** Departure → Dashboard

**Dashboard Cards:**
- Total Candidates Ready for Departure
- PTN Status Breakdown (Issued, Pending, Deferred, Refused)
- Protector Status Breakdown (Applied, Done, Pending, Deferred)
- Ticket Status (Booked, Pending)
- Departed Candidates

**Filter Options:**
- Date range
- Campus
- Destination country
- Departure status

---

## Post-Departure Tracking

### Overview

Post-Departure Tracking monitors candidates after arrival in destination country, capturing residency, employment, and job mobility information.

### Section 1: Residency & Identity (NEW in v3)

**Residency Proof:**
- Residency Number: [Text] (e.g., Iqama number for KSA)
- Residency Expiry: [Date picker]
- Upload Residency Proof: [PDF/Image]

**Foreign License:**
- Foreign License Number: [Text] (for drivers, professionals)
- License Type: [Text] (e.g., "Driving License", "Professional RN License")

**Contact Information:**
- Foreign Mobile Number: [Text with country code]
- Example: +966-50-1234567

**Banking Details:**
- Foreign Bank Account: [Text] (IBAN or account number)
- Bank Name: [Text]
- Branch: [Text]

**Tracking App Registration:**
- Tracking App Registered: ☐ Yes ☐ No
- Tracking App ID: [Text] (e.g., Absher ID for KSA)

**Documents to Upload:**
- Residency proof (Iqama, residence permit)
- Foreign license copy
- Bank account statement/card

### Section 2: Final Employment Details (NEW in v3)

**Employer Information:**
- Company Name: [Text]
- Employer Contact Person: [Text]
- Employer Contact Mobile: [Text with country code]
- Employer Location: [Text] (City, Region)

**Employment Terms:**
- Final Salary: [Numeric] (actual salary in destination)
- Currency: [Text] (e.g., SAR, AED, USD)
- Employment Terms: [Text area] (Full-time, Part-time, Contract, Permanent)
- Employment Commencement Date: [Date picker]

**Contract Upload:**
- Upload Job Contract: [PDF] (e.g., Qiwa Agreement for KSA)
- Contract Type: [Text]

### Section 3: Company SWITCH Tracking (NEW in v3)

**First Company SWITCH:**
- Previous Company: [Text] (original employer)
- New Company: [Text] (new employer after switch)
- Switch Date: [Date picker]
- Switch Reason: [Text area]
- New Salary: [Numeric]
- New Currency: [Text]
- New Location: [Text]
- New Employer Contact: [Text]
- Upload New Contract: [PDF]

**Second Company SWITCH:**
- Same fields as First Company SWITCH
- Only available after first switch is recorded

### How to Update Post-Departure Details

**Step 1: Access Post-Departure Module**
1. Navigate to **Post-Departure** menu
2. Search for candidate (by name, CNIC, or allocated number)
3. Click on candidate name

**Step 2: Residency & Identity**
1. Click **Update Residency Details**
2. Fill all available fields
3. Upload residency proof document
4. Click **Save Residency**

**Step 3: Employment Details**
1. Click **Update Employment Details**
2. Enter company information
3. Enter contact person details
4. Enter salary and terms
5. Upload job contract
6. Click **Save Employment**

**Step 4: Record Company Switch (if applicable)**
1. Click **Record Company Switch**
2. Select Switch Number: ⦿ First Switch ⦿ Second Switch
3. Enter previous company name
4. Enter new company details
5. Provide switch reason
6. Enter new salary and terms
7. Upload new contract
8. Click **Save Switch**

**Note:** System allows maximum 2 company switches to be tracked.

### Post-Departure Dashboard

**Access:** Post-Departure → Dashboard

**Dashboard Metrics:**
- Total Departed Candidates
- Residency Details Completed
- Employment Details Completed
- Company Switches Recorded (First, Second)
- Pending Updates

**Reports Available:**
- Residency Status Report
- Employment Status Report
- Job Mobility Report (switches)
- Contact Information Report

---

## Employer Management

### Overview

The Employer Management module (NEW in v3) maintains a database of overseas employers who hire BTEVTA-trained candidates.

### Employer Information Fields

**Basic Information:**
- Permission Number: [Text] (after approved demand letter)
- Visa Issuing Company: [Text] (e.g., ARAMCO, SABIC)
- Company Name: [Text] (optional if different from visa issuing company)
- Country: [Select from dropdown]
- Sector: [Text] (e.g., Industrial, Manufacturing, Construction)
- Trade: [Text] (e.g., Welding, Electrician, Plumbing)

**Employment Package:**
- Basic Salary: [Numeric]
- Currency: [Select] (SAR, AED, USD, etc.)
- Food Provided by Company: ☐ Yes ☐ No
- Transport Provided by Company: ☐ Yes ☐ No
- Accommodation Provided by Company: ☐ Yes ☐ No
- Other Conditions: [Text area] (e.g., "Medical insurance provided", "Annual leave 30 days")

**Evidence:**
- Upload Evidence Document: [PDF/Image] (demand letter, MOU, agreement)

**Status:**
- Is Active: ☐ Yes ☐ No

### How to Add Employer

**Step 1: Navigate to Employers**
1. Go to **Employers** menu
2. Click **Add New Employer**

**Step 2: Fill Employer Information**
1. Enter Permission Number (required, unique)
2. Enter Visa Issuing Company (required)
3. Enter Company Name (optional)
4. Select Country (required)
5. Enter Sector and Trade (optional)

**Step 3: Employment Package**
1. Enter Basic Salary (required)
2. Select Currency (required)
3. Check benefits provided:
   - ☐ Food
   - ☐ Transport
   - ☐ Accommodation
4. Enter Other Conditions (optional)

**Step 4: Upload Evidence**
1. Click **Choose File**
2. Select evidence document (PDF, JPG, PNG)
3. Maximum size: 5MB

**Step 5: Save Employer**
1. Set Active status (checked by default)
2. Click **Save Employer**
3. Success message displays
4. Employer added to database

### How to Assign Candidates to Employer

**Step 1: Access Employer Record**
1. Go to **Employers** menu
2. Search for employer
3. Click on employer name

**Step 2: Assign Candidates**
1. Click **Assign Candidates** button
2. Search for candidates to assign
3. Select candidates from search results (use checkboxes)
4. Click **Assign Selected**

**Step 3: Confirmation**
- Assignment records created
- Candidates linked to employer
- Assignment date and assigning user recorded
- Candidates appear in "Current Candidates" list

### Viewing Employer Information

**Employer Detail Page Shows:**
- Full employer information
- Employment package details with visual indicators:
  - ✓ Food (if provided)
  - ✓ Transport (if provided)
  - ✓ Accommodation (if provided)
- Evidence document (with download link)
- Current Candidates list:
  - Candidate name
  - CNIC
  - Trade
  - Assigned date
  - Assigned by (user)
- Historical candidates (previously assigned, no longer current)

### Employer Reports

**Access:** Employers → Reports

**Available Reports:**
1. **Active Employers Report**
   - List of all active employers
   - Country-wise breakdown
   - Sector-wise breakdown
   - Current candidates count

2. **Employment Packages Report**
   - Salary comparison by country
   - Benefits analysis (food, transport, accommodation)
   - Average package value by trade

3. **Candidate Assignment Report**
   - Candidates by employer
   - Assignment timeline
   - Filtering by date range, country, sector

---

## Success Stories

### Overview

The Success Stories module (NEW in v3) collects positive testimonials from candidates about their WASL journey and overseas employment experience.

### Success Story Components

**Written Narrative:**
- Story Text: [Rich text area]
- Candidate describes their journey, training experience, and current success

**Evidence Types:**
- **Audio** - Voice recording
- **Video** - Video testimonial
- **Written** - Written document (PDF, Word)
- **Photo** - Images showing success
- **None** - Story text only

**Publication Settings:**
- Featured: Highlight story on homepage
- Published: Make story publicly visible

### How to Record Success Story

**Step 1: Access Success Stories**
1. Navigate to **Success Stories** menu
2. Click **New Success Story**
3. Search and select candidate

**Step 2: Written Story**
1. In Story Text field, write or paste candidate's story
2. Include:
   - Training experience
   - Journey to overseas employment
   - Current position and achievements
   - Impact on family/community
   - Gratitude/message to BTEVTA

**Example Story:**
```
"After completing my welding training at BTEVTA Islamabad, I secured
a position with a leading company in Saudi Arabia. The technical skills
and soft skills training prepared me well for the challenges of working
abroad. I am now earning a good salary, supporting my family, and
building a better future for my children. I am grateful to BTEVTA for
this life-changing opportunity."
```

**Step 3: Evidence Upload**
1. Select Evidence Type:
   - ⦿ Audio - for voice recording
   - ⦿ Video - for video testimonial
   - ⦿ Written - for written document
   - ⦿ Photo - for images
   - ⦿ None - if no evidence

2. Click **Upload Evidence File**
3. Select file based on type:
   - Audio: MP3, M4A, WAV (max 50MB)
   - Video: MP4, MOV, AVI (max 50MB)
   - Written: PDF, DOC, DOCX (max 5MB)
   - Photo: JPG, PNG (max 5MB)

**Video Processing Note:**
- Large video files may take several minutes to upload
- System processes video in background
- You will receive notification when processing completes

**Step 4: Publication Settings**
1. Check ☐ Featured (if story should be highlighted)
2. Check ☐ Published (if ready for public viewing)
3. Enter Recording Date (optional)

**Step 5: Save Success Story**
1. Click **Save Success Story**
2. Story saved to database
3. If Published, story appears in public gallery
4. If Featured, story appears on homepage/dashboard

### Managing Success Stories

**View All Stories:**
1. Go to **Success Stories** menu
2. View list of all stories
3. Filter by:
   - Country
   - Published status
   - Featured status
   - Date range

**Edit Story:**
1. Click on story to view
2. Click **Edit**
3. Update text, evidence, or settings
4. Click **Save Changes**

**Publish/Unpublish:**
1. View story details
2. Click **Publish** or **Unpublish** button
3. Status updated immediately

**Feature/Unfeature:**
1. View story details
2. Click **Feature** or **Unfeature** button
3. Featured stories appear prominently

### Success Stories Gallery

**Access:** Success Stories → Gallery

**Gallery View:**
- Grid layout with candidate photos
- Story excerpt (first 100 words)
- Evidence type icon (🎤 audio, 🎥 video, 📄 written, 📷 photo)
- Country flag
- View Details button

**Filters:**
- By Country
- By Trade
- By Campus
- By Date Range

---

## Complaints Management

### Overview

Complaints Management has been enhanced in v3 with a structured 4-step workflow for better tracking and resolution.

### Enhanced Workflow (v3)

**4-Step Complaint Process:**

1. **Current Issue Analysis**
   - What is the problem?
   - When did it start?
   - Who is involved?

2. **Support Steps Taken**
   - What actions have been taken so far?
   - Who has been contacted?
   - What resources have been provided?

3. **Suggestions & Recommendations**
   - What further actions are recommended?
   - What resources are needed?
   - Who should be involved?

4. **Conclusion & Resolution**
   - Final outcome
   - Resolution status
   - Lessons learned

### How to Register Complaint

**Step 1: Access Complaints Module**
1. Navigate to **Complaints** menu
2. Click **New Complaint**
3. Search and select candidate

**Step 2: Current Issue Analysis**
1. Issue Title: [Text - brief summary]
2. Issue Description: [Text area - detailed description]
3. Issue Category: [Select]
   - Employment Issue
   - Salary/Payment Issue
   - Accommodation Issue
   - Employer Relations
   - Document Issue
   - Other

4. Priority: [Select]
   - ⦿ Low
   - ⦿ Medium
   - ⦿ High
   - ⦿ Critical

5. Date Reported: [Auto-filled]

**Step 3: Support Steps Taken**
1. Describe Actions Taken: [Text area]
   - Example: "Contacted employer on 2026-01-10. Spoke with HR manager Ahmed. They agreed to review salary discrepancy."

2. Resources Provided: [Text area]
   - Example: "Provided candidate with labor rights documentation. Connected with Pakistani Embassy."

3. People Contacted: [Text area]
   - Example: "Employer HR Manager, OEP Liaison Officer, Embassy Labor Attaché"

**Step 4: Suggestions & Recommendations**
1. Recommended Actions: [Text area]
   - Example: "Follow up with employer within 7 days. If not resolved, escalate to embassy."

2. Required Resources: [Text area]
   - Example: "Legal advice, Embassy intervention, Alternative employer contacts"

3. Stakeholders to Involve: [Text area]
   - Example: "Embassy Labor Section, Implementing Partner, BTEVTA Legal Team"

**Step 5: Evidence Upload**
1. Evidence Type: [Select]
   - Audio (phone call recording)
   - Video (incident recording)
   - Screenshot (messages, emails)
   - Document (letters, notices)
   - Other

2. Upload Evidence File: [File upload]
   - Multiple files can be uploaded
   - Maximum 10MB per file

**Step 6: Conclusion & Resolution**
1. Status: [Select]
   - ⦿ Open
   - ⦿ In Progress
   - ⦿ Resolved
   - ⦿ Closed

2. Resolution Details: [Text area]
   - Example: "Issue resolved on 2026-01-17. Employer paid outstanding salary. Candidate satisfied."

3. Lessons Learned: [Text area]
   - Example: "Ensure salary terms clearly documented in contract. Maintain regular follow-up schedule."

**Step 7: Save Complaint**
1. Click **Save Complaint**
2. Complaint recorded with ticket number
3. SLA timer starts (based on priority)
4. Notifications sent to relevant staff

### Complaint Follow-up

**Update Complaint:**
1. Go to **Complaints** menu
2. Search complaint by ticket number or candidate
3. Click on complaint to view
4. Click **Update Complaint**
5. Update steps, suggestions, or conclusion
6. Change status if resolved
7. Click **Save Updates**

**Add Notes:**
1. View complaint details
2. Click **Add Note**
3. Enter note text (timestamped)
4. Click **Save Note**

**Upload Additional Evidence:**
1. View complaint details
2. Click **Upload Evidence**
3. Select evidence type and file
4. Click **Upload**

### Complaints Dashboard

**Access:** Complaints → Dashboard

**Dashboard Cards:**
- Total Complaints
- Open Complaints
- In Progress
- Resolved (last 30 days)
- Overdue (SLA breached)

**Priority Breakdown:**
- Critical: [Count]
- High: [Count]
- Medium: [Count]
- Low: [Count]

**SLA Tracking:**
- Green: Within SLA
- Yellow: Approaching SLA deadline
- Red: SLA breached

**Filter Options:**
- By Priority
- By Status
- By Category
- By Date Range
- By Campus

---

## Reports & Analytics

### Overview

WASL v3 includes comprehensive reporting for monitoring system performance and generating insights.

### Available Reports

#### 1. Candidate Pipeline Report

**Access:** Reports → Candidate Pipeline

**Shows:**
- Total candidates by status
- Pipeline visualization (funnel chart)
- Status transition timeline
- Bottleneck identification

**Filters:**
- Date range
- Campus
- Program
- Trade

#### 2. Training Completion Report

**Access:** Reports → Training Completion

**Shows:**
- Batch-wise training status
- Technical training completion rates
- Soft skills training completion rates
- Assessment pass rates
- Average assessment scores
- Training duration analysis

**Export:** PDF, Excel, CSV

#### 3. Visa Processing Report

**Access:** Reports → Visa Processing

**Shows:**
- Stage-wise breakdown
- Pass/Fail rates by stage
- Average processing time per stage
- Refusal analysis
- Country-wise visa success rates

**Filters:**
- Date range
- Country
- Campus
- Stage

#### 4. Departure Report

**Access:** Reports → Departures

**Shows:**
- Total departures by month
- PTN status breakdown
- Protector status breakdown
- Destination countries
- Average time from registration to departure

**Export:** PDF, Excel

#### 5. Post-Departure Report

**Access:** Reports → Post-Departure

**Shows:**
- Residency status completion
- Employment details completion
- Company switches count
- Current employer analysis
- Salary analysis by country

**Filters:**
- Destination country
- Date range
- Campus

#### 6. Employer Report

**Access:** Reports → Employers

**Shows:**
- Total employers by country
- Active vs. inactive employers
- Employment packages analysis
- Benefits comparison
- Candidates per employer

**Export:** PDF, Excel

#### 7. Document Completion Report

**Access:** Reports → Document Completion

**Shows:**
- Candidate-wise document status
- Mandatory documents completion %
- Optional documents upload %
- Verification status
- Pending documents list

**Filters:**
- Campus
- Batch
- Date range

#### 8. Success Stories Report

**Access:** Reports → Success Stories

**Shows:**
- Total stories collected
- Stories by country
- Evidence types breakdown
- Published vs. unpublished
- Featured stories

**Export:** PDF with story excerpts

#### 9. Complaints Report

**Access:** Reports → Complaints

**Shows:**
- Complaint trends over time
- Category breakdown
- Priority distribution
- Resolution rates
- Average resolution time
- SLA compliance

**Filters:**
- Date range
- Priority
- Category
- Status

#### 10. Custom Report Builder

**Access:** Reports → Custom Report

**Build Custom Reports:**
1. Select data source (Candidates, Training, Visa, etc.)
2. Choose columns to include
3. Apply filters
4. Select grouping
5. Choose chart type (bar, line, pie)
6. Generate report
7. Save report template for reuse

### How to Generate Report

**Step 1: Navigate to Reports**
1. Go to **Reports** menu
2. Select report type from submenu

**Step 2: Apply Filters**
1. Set date range (if applicable)
2. Select campus, program, trade (if applicable)
3. Choose additional filters

**Step 3: Generate**
1. Click **Generate Report** button
2. Wait for processing (may take a few seconds)
3. Report displays on screen

**Step 4: Export (Optional)**
1. Click **Export** button
2. Select format:
   - PDF - for printing and sharing
   - Excel - for data analysis
   - CSV - for data import
3. File downloads automatically

**Step 5: Schedule Report (Optional)**
1. Click **Schedule** button
2. Set frequency:
   - Daily
   - Weekly
   - Monthly
3. Select recipients (email addresses)
4. Click **Save Schedule**
5. Report automatically generated and emailed

---

## Troubleshooting

### Common Issues and Solutions

#### Issue 1: Cannot Register Candidate

**Error:** "Only screened candidates can be registered"

**Solution:**
1. Verify candidate has been through Initial Screening
2. Check candidate status is "SCREENED"
3. If status is "PENDING" or "DEFERRED", complete screening first
4. Contact supervisor if candidate should be screened but isn't showing correct status

---

#### Issue 2: Batch Not Auto-Generated

**Error:** Registration saved but no batch assigned

**Solution:**
1. Check that all allocation fields are filled:
   - Campus
   - Program
   - Trade/OEP
2. Ensure trade and program are active in system
3. Contact admin to verify auto-batch service is running
4. Check system logs for errors

---

#### Issue 3: Document Upload Fails

**Error:** "File upload failed" or "File too large"

**Solution:**
1. Check file size:
   - Documents: Maximum 5MB
   - Videos: Maximum 50MB
2. Check file format:
   - Documents: PDF, JPG, PNG only
   - Videos: MP4, MOV, AVI only
3. Try different browser (Chrome recommended)
4. Check internet connection
5. Contact IT support if persists

---

#### Issue 4: Cannot Mark Training Complete

**Error:** "Training completion requirements not met"

**Solution:**
1. Verify both assessments completed:
   - ✓ Interim Assessment
   - ✓ Final Assessment
2. Verify both assessments passed (score ≥ passing score)
3. Verify both training statuses set to "COMPLETED":
   - ✓ Technical Training
   - ✓ Soft Skills Training
4. If all requirements met but still blocked, contact supervisor

---

#### Issue 5: Candidate Not Found in Search

**Error:** Search returns no results

**Solution:**
1. Verify search criteria:
   - Correct CNIC format (12345-1234567-1)
   - Correct name spelling
2. Try different search method:
   - Search by CNIC if searching by name fails
   - Search by allocated number if available
3. Clear search filters (may be filtering out candidate)
4. Verify candidate exists in system (may not be imported yet)
5. Contact data entry team if candidate should exist

---

#### Issue 6: Video Upload Stuck at "Processing"

**Error:** Video upload shows "Processing" for long time

**Solution:**
1. Wait at least 10 minutes for large video files
2. Check file size - videos over 50MB will fail
3. Check internet connection stability
4. Refresh page and check if video uploaded successfully
5. If stuck for over 30 minutes, contact IT support
6. Do NOT re-upload without confirming first upload failed

---

#### Issue 7: Report Generation Times Out

**Error:** "Request timeout" when generating report

**Solution:**
1. Reduce date range (try 1 month instead of 1 year)
2. Add more specific filters to reduce data volume
3. Try exporting to Excel instead of viewing on screen
4. Try different browser
5. Contact IT if issue persists with reasonable filters

---

#### Issue 8: Permission Denied

**Error:** "You are not authorized to perform this action"

**Solution:**
1. Verify you are logged in
2. Verify your role has permission for this action
3. Check if you're trying to edit record created by another user
4. Contact supervisor to request permission if needed
5. Contact admin to verify your role permissions

---

### Getting Help

**Technical Support:**
- Email: support@btevta.gov.pk
- Phone: +92-51-9204567
- Hours: Monday-Friday, 9:00 AM - 5:00 PM

**User Training:**
- Contact your campus training coordinator
- Request one-on-one training session
- Access video tutorials: [URL]

**Bug Reports:**
- Report bugs via: support@btevta.gov.pk
- Include:
  - What you were trying to do
  - Error message (screenshot if possible)
  - Your username
  - Date and time of issue

---

## Appendix

### Glossary

- **WASL** - Workforce Abroad Skills & Linkages
- **BTEVTA** - Board of Technical Education and Vocational Training Authority
- **OEP** - Overseas Employment Promoter
- **PTN** - Permission to Navigate
- **CNIC** - Computerized National Identity Card
- **FRC** - Family Registration Certificate
- **PCC** - Police Character Certificate
- **Iqama** - Saudi Arabia residence permit
- **Qiwa** - Saudi Arabia labor platform
- **Absher** - Saudi Arabia government services platform
- **SLA** - Service Level Agreement

### Keyboard Shortcuts

- **Ctrl + S** - Save current form
- **Ctrl + F** - Focus search box
- **Ctrl + N** - New record (if on listing page)
- **Esc** - Close modal/dialog
- **Alt + D** - Go to Dashboard
- **Alt + C** - Go to Candidates

### System Limits

- **File Uploads:**
  - Documents (PDF, Images): 5MB
  - Videos: 50MB
  - Audio: 50MB

- **Text Fields:**
  - Short text: 255 characters
  - Text area: 65,535 characters
  - Rich text: Unlimited

- **Batch Size:**
  - Configurable: 20, 25, or 30 candidates

- **API Rate Limit:**
  - 60 requests per minute

---

**Document End**

*For the latest updates to this manual, visit the WASL Documentation Portal or contact your system administrator.*
