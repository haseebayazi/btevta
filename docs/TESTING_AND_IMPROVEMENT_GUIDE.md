# BTEVTA Overseas Employment System - Testing & Improvement Guide

**Application:** BTEVTA Overseas Employment Management System
**Purpose:** End-to-end testing of candidate lifecycle & system improvements
**Date:** December 2025

---

## OVERVIEW

This document provides a systematic approach to test each phase of the candidate lifecycle and identify improvements. The system manages **9 phases** from initial registration through post-deployment monitoring.

### Candidate Status Flow
```
NEW → SCREENING → REGISTERED → TRAINING → VISA_PROCESS → READY → DEPARTED
  ↓        ↓           ↓           ↓            ↓
REJECTED  REJECTED   DROPPED    DROPPED     REJECTED
```

---

## PHASE 1: CANDIDATE REGISTRATION

### Testing Checklist

#### 1.1 Create Candidate
- [ ] Create new candidate with all required fields
- [ ] Verify BTEVTA ID auto-generates (format: BTV-YYYY-XXXXX)
- [ ] Verify Application ID auto-generates (format: APPYYYY000001)
- [ ] Test CNIC validation (13 digits, unique)
- [ ] Test date of birth validation (must be in past)
- [ ] Upload candidate photo successfully
- [ ] Verify photo displays in profile

#### 1.2 Assignments
- [ ] Assign candidate to campus
- [ ] Assign candidate to trade
- [ ] Assign candidate to OEP
- [ ] Assign candidate to batch
- [ ] Verify assignments reflect in candidate profile

#### 1.3 Next of Kin
- [ ] Add next of kin information
- [ ] Verify relationship options work
- [ ] Test CNIC format validation
- [ ] Test phone number validation

#### 1.4 Data Validation
- [ ] Test duplicate CNIC rejection
- [ ] Test invalid email format rejection
- [ ] Test required field validation
- [ ] Test province/district/tehsil hierarchy

#### 1.5 Status Transitions
- [ ] Verify new candidate status is "NEW"
- [ ] Test moving candidate to SCREENING status
- [ ] Test rejection flow with remarks

### Improvement Opportunities

| Area | Current State | Suggested Improvement |
|------|---------------|----------------------|
| ID Generation | Sequential | Add checksum for validation |
| Photo Upload | Basic upload | Add face detection/crop tool |
| Duplicate Check | CNIC only | Add phone/email duplicate warning |
| Address | Text fields | Integrate with Pakistan postal API |
| Bulk Import | CSV import | Add Excel template with validation |

### Meta Prompt for Phase 1 Testing
```
Test the candidate registration module:
1. Create 3 test candidates with different profiles (male, female, different districts)
2. Test all validation rules - try invalid CNIC, future DOB, duplicate email
3. Upload photos in different formats (JPG, PNG, oversized file)
4. Assign candidates to different campuses, trades, and OEPs
5. Add complete next of kin information for each
6. Verify timeline shows creation activity
7. Test search/filter functionality on candidate list
8. Test export functionality
9. Report any validation gaps or UX issues
```

---

## PHASE 2: SCREENING

### Testing Checklist

#### 2.1 Desk Screening
- [ ] Create desk screening record
- [ ] Add evidence file upload
- [ ] Test pass/fail outcomes
- [ ] Verify remarks are saved
- [ ] Test screener assignment

#### 2.2 Call Screening
- [ ] Create call screening record
- [ ] Test call attempt counter (max 3)
- [ ] Verify next_call_date scheduling
- [ ] Test call duration logging
- [ ] Test all status transitions (pending → in_progress → passed/failed)

#### 2.3 Physical Screening
- [ ] Create physical screening record
- [ ] Add medical/physical evidence
- [ ] Test outcome recording
- [ ] Verify all screening types complete

#### 2.4 Auto-Progression
- [ ] Verify candidate moves to REGISTERED when all 3 screenings pass
- [ ] Verify candidate moves to REJECTED when any screening fails
- [ ] Test partial completion state

#### 2.5 Reporting
- [ ] Generate screening report by screener
- [ ] Generate screening report by type
- [ ] Export screening data
- [ ] View pending screenings list

### Improvement Opportunities

| Area | Current State | Suggested Improvement |
|------|---------------|----------------------|
| Call Scheduling | Manual dates | Calendar integration |
| Call Logging | Basic duration | Click-to-call integration |
| Evidence | File upload | Document classification AI |
| Workflow | Manual progression | Automated status updates |
| Notifications | None | SMS/Email alerts for outcomes |

### Meta Prompt for Phase 2 Testing
```
Test the screening module end-to-end:
1. Take 3 candidates from Phase 1 through complete screening
2. For Candidate A: Pass all 3 screenings - verify auto-progression to REGISTERED
3. For Candidate B: Fail desk screening - verify auto-rejection
4. For Candidate C: Test call screening with 3 attempts, then pass
5. Upload evidence files for each screening type
6. Add detailed remarks for each screening
7. Test pending screening list functionality
8. Generate and review screening reports
9. Check for any workflow gaps or missing validations
```

---

## PHASE 3: REGISTRATION (DOCUMENT COLLECTION)

### Testing Checklist

#### 3.1 Required Documents
- [ ] Upload CNIC copy (verify format restrictions)
- [ ] Upload Educational Certificate
- [ ] Upload Domicile Certificate
- [ ] Upload Passport Size Photo
- [ ] Verify document completion indicator

#### 3.2 Optional Documents
- [ ] Upload Passport copy
- [ ] Upload Police Clearance
- [ ] Upload Medical Certificate
- [ ] Test expiry date validation

#### 3.3 Document Verification
- [ ] Mark document as verified
- [ ] Mark document as rejected
- [ ] Add verification remarks
- [ ] Test document replacement

#### 3.4 Undertaking
- [ ] Generate undertaking document
- [ ] Record signature/acknowledgment
- [ ] Verify witness information
- [ ] Test undertaking completion

#### 3.5 Registration Completion
- [ ] Complete registration with all docs
- [ ] Test completion with missing required docs (should fail)
- [ ] Verify progression to TRAINING status

### Improvement Opportunities

| Area | Current State | Suggested Improvement |
|------|---------------|----------------------|
| Document Upload | Individual files | Multi-file drag-drop |
| Verification | Manual review | OCR validation for CNIC/Passport |
| Undertaking | Text generation | Digital signature capture |
| Expiry Tracking | Manual check | Automated expiry alerts |
| Templates | None | Downloadable document templates |

### Meta Prompt for Phase 3 Testing
```
Test the document registration module:
1. Take candidates who passed screening to document upload
2. Upload all 4 required documents with valid formats
3. Test file size limits (try uploading >5MB file)
4. Test invalid file formats (try uploading .exe file)
5. Add expiry dates to passport and verify future date validation
6. Mark documents as verified and add verification remarks
7. Generate undertaking document and complete signature process
8. Complete registration and verify status changes to TRAINING
9. Test incomplete registration (missing required doc) - should block
10. Report any UX issues or missing validations
```

---

## PHASE 4: TRAINING

### Testing Checklist

#### 4.1 Batch Assignment
- [ ] Assign candidate to training batch
- [ ] Verify batch capacity limits
- [ ] Check start/end date validation
- [ ] Verify trainer assignment

#### 4.2 Attendance Tracking
- [ ] Mark individual attendance (present)
- [ ] Mark attendance as absent
- [ ] Mark attendance as late (0.5 weight)
- [ ] Mark attendance as leave (with type)
- [ ] Test bulk attendance entry
- [ ] Verify attendance percentage calculation

#### 4.3 Assessments
- [ ] Record initial assessment score
- [ ] Record midterm assessment score
- [ ] Record practical assessment score
- [ ] Record final assessment score
- [ ] Verify score validation (0-100)
- [ ] Verify average calculation

#### 4.4 Certificate Generation
- [ ] Generate certificate (≥60% score, ≥80% attendance)
- [ ] Test certificate rejection (low score)
- [ ] Test certificate rejection (low attendance)
- [ ] Download certificate PDF

#### 4.5 Training Completion
- [ ] Complete training for eligible candidate
- [ ] Verify progression to VISA_PROCESS
- [ ] Test completion with failing criteria (should block)
- [ ] Test dropout flow

### Improvement Opportunities

| Area | Current State | Suggested Improvement |
|------|---------------|----------------------|
| Attendance | Manual entry | QR code check-in |
| Late Tracking | Fixed 0.5 | Configurable late thresholds |
| Assessments | Basic scoring | Detailed rubric support |
| Certificate | Static PDF | QR-verified digital certificate |
| Performance | Basic metrics | Learning analytics dashboard |
| Dropout | Manual | Early warning system |

### Meta Prompt for Phase 4 Testing
```
Test the training module comprehensively:
1. Create a batch and assign 5 candidates
2. Mark attendance for 20 training days with mix of present/absent/late
3. Verify attendance percentage calculates correctly
4. Record all 4 assessment types with varying scores
5. For Candidate A: 90% attendance, 75% score - should get certificate
6. For Candidate B: 75% attendance (below 80%), 80% score - should NOT get certificate
7. For Candidate C: 85% attendance, 55% score (below 60%) - should NOT get certificate
8. Generate and download certificates for eligible candidates
9. Complete training and verify progression to VISA_PROCESS
10. Test batch performance report generation
11. Report any calculation errors or UX issues
```

---

## PHASE 5: VISA PROCESSING

### Testing Checklist

#### 5.1 Stage 1-3: Interview & Tests
- [ ] Schedule and record interview result
- [ ] Record trade test result
- [ ] Schedule Takamol test
- [ ] Upload Takamol result with score

#### 5.2 Stage 4-5: Medical & E-Number
- [ ] Schedule GAMCA medical
- [ ] Upload medical result
- [ ] Verify E-Number auto-generation
- [ ] Check E-Number format (OEP-YYYY-XXXX)

#### 5.3 Stage 6-8: Biometrics & Visa
- [ ] Record biometric registration (Etimad)
- [ ] Submit visa application
- [ ] Record visa approval
- [ ] Verify visa number saved

#### 5.4 Stage 9-11: PTN & Ticket
- [ ] Verify PTN auto-generation
- [ ] Check PTN format (PTN-YYYY-TRADE-XXXXX)
- [ ] Upload airline ticket
- [ ] Complete all 11 stages

#### 5.5 Timeline & Tracking
- [ ] View visa processing timeline
- [ ] Check overdue cases report
- [ ] Test stage-wise filtering
- [ ] Generate visa processing report

### Improvement Opportunities

| Area | Current State | Suggested Improvement |
|------|---------------|----------------------|
| Scheduling | Manual dates | Calendar sync (Google/Outlook) |
| Takamol | Manual entry | API integration |
| GAMCA | Manual entry | Barcode scanner integration |
| Timeline | Linear view | Gantt chart visualization |
| Overdue | List view | Alert notifications |
| Documents | Individual upload | Document checklist with status |

### Meta Prompt for Phase 5 Testing
```
Test the 11-stage visa processing pipeline:
1. Take a training-completed candidate through all 11 stages
2. Stage 1-2: Schedule and pass interview and trade test
3. Stage 3: Book Takamol, upload result with score ≥50
4. Stage 4: Book GAMCA medical, upload result, verify expiry date
5. Stage 5: Trigger E-Number generation, verify format
6. Stage 6: Record Etimad biometric registration
7. Stage 7-8: Submit visa documents and record approval
8. Stage 9: Verify PTN auto-generation and format
9. Stage 10: Upload airline ticket with flight details
10. Stage 11: Complete visa process
11. View timeline visualization
12. Check overdue report with delayed test candidates
13. Verify progression to READY status
14. Report any stage transition issues or data gaps
```

---

## PHASE 6: PRE-DEPARTURE & DEPARTURE

### Testing Checklist

#### 6.1 Pre-Departure
- [ ] Conduct pre-departure briefing
- [ ] Record briefing topics covered
- [ ] Mark candidate ready for departure
- [ ] Verify all pre-departure checks

#### 6.2 Departure Recording
- [ ] Record actual departure date
- [ ] Record flight number and airport
- [ ] Record destination
- [ ] Verify status changes to DEPARTED

#### 6.3 Post-Arrival Registration
- [ ] Record Iqama number
- [ ] Record Iqama expiry date
- [ ] Record Absher registration
- [ ] Record Qiwa/WPS activation

#### 6.4 Employment Confirmation
- [ ] Record employer details
- [ ] Record first salary
- [ ] Upload salary proof
- [ ] Record accommodation details

#### 6.5 90-Day Compliance
- [ ] Track 90-day compliance progress
- [ ] Record milestones (7-day, 14-day, 30-day, 90-day)
- [ ] Submit medical report if required
- [ ] Complete compliance verification
- [ ] View non-compliant workers list

### Improvement Opportunities

| Area | Current State | Suggested Improvement |
|------|---------------|----------------------|
| Briefing | Manual checklist | Video-based with acknowledgment |
| Flight | Manual entry | Flight tracking integration |
| Iqama | Manual entry | OCR scanning |
| Compliance | Manual tracking | Automated reminders (SMS/WhatsApp) |
| Salary | Upload proof | Bank statement parsing |
| Dashboard | Basic list | Map visualization |

### Meta Prompt for Phase 6 Testing
```
Test the departure and 90-day compliance flow:
1. Take a visa-complete candidate through pre-departure
2. Conduct and record pre-departure briefing with all topics
3. Record departure details (date, flight, destination)
4. Verify status changes to DEPARTED
5. Record Iqama number with valid expiry date
6. Register in Absher and Qiwa systems
7. Record employer and accommodation details
8. Record first salary with proof upload
9. Track 90-day compliance milestones:
   - Day 7: First contact
   - Day 14: Accommodation verified
   - Day 30: First salary confirmed
   - Day 90: Final compliance report
10. Test non-compliant worker identification
11. Generate compliance report
12. Report any tracking gaps or notification needs
```

---

## PHASE 7: POST-DEPLOYMENT (REMITTANCES & COMPLAINTS)

### Testing Checklist

#### 7.1 Beneficiary Management
- [ ] Add remittance beneficiary
- [ ] Add multiple beneficiaries
- [ ] Set primary beneficiary
- [ ] Verify CNIC and account number validation

#### 7.2 Remittance Recording
- [ ] Create remittance record
- [ ] Test all transfer methods (bank, Western Union, etc.)
- [ ] Test currency conversion
- [ ] Upload receipt proof
- [ ] Verify remittance

#### 7.3 Remittance Analytics
- [ ] View monthly remittance report
- [ ] View purpose analysis
- [ ] Generate candidate remittance summary
- [ ] Check first remittance milestone

#### 7.4 Complaints
- [ ] File new complaint
- [ ] Test all complaint categories
- [ ] Assign complaint to staff
- [ ] Add complaint updates
- [ ] Upload evidence
- [ ] Escalate complaint
- [ ] Resolve and close complaint
- [ ] Check SLA compliance

#### 7.5 Correspondence
- [ ] Create official correspondence
- [ ] Track pending replies
- [ ] Mark correspondence as replied
- [ ] View correspondence register

### Improvement Opportunities

| Area | Current State | Suggested Improvement |
|------|---------------|----------------------|
| Remittance | Manual entry | Bank API integration |
| Beneficiary | Basic info | Biometric verification |
| Analytics | Basic charts | Predictive analytics |
| Complaints | Email updates | WhatsApp bot integration |
| SLA | Manual tracking | Automated escalation |
| Impact | Amount tracking | Family welfare indicators |

### Meta Prompt for Phase 7 Testing
```
Test post-deployment support modules:
1. Add 2-3 beneficiaries for a deployed candidate
2. Set one as primary with percentage allocation
3. Record 3 remittances with different purposes
4. Upload receipt proofs and verify
5. Check if first remittance triggers milestone notification
6. View monthly and purpose analysis reports
7. File 3 complaints with different categories and priorities
8. Assign complaints to different staff members
9. Add updates and evidence to complaints
10. Escalate one complaint to management
11. Resolve and close complaints, verify SLA calculations
12. Create official correspondence and track replies
13. Report any workflow gaps or feature requests
```

---

## PHASE 8: DASHBOARD & ANALYTICS

### Testing Checklist

#### 8.1 Dashboard Overview
- [ ] Verify candidate count by status
- [ ] Verify distribution charts
- [ ] Check recent activities feed
- [ ] View alerts and warnings

#### 8.2 Tab Navigation
- [ ] Test Candidates tab
- [ ] Test Screening tab
- [ ] Test Registration tab
- [ ] Test Training tab
- [ ] Test Visa Processing tab
- [ ] Test Departure tab
- [ ] Test Complaints tab
- [ ] Test Correspondence tab

#### 8.3 Reports
- [ ] Generate candidate profile report
- [ ] Generate batch summary report
- [ ] Generate campus performance report
- [ ] Generate OEP performance report
- [ ] Test export functionality (Excel, PDF)

#### 8.4 Role-Based Access
- [ ] Test Admin access (full)
- [ ] Test Campus Admin access (filtered)
- [ ] Test OEP access (assigned candidates only)
- [ ] Test Trainer access (training only)
- [ ] Test Viewer access (read-only)

### Meta Prompt for Phase 8 Testing
```
Test dashboard and analytics comprehensively:
1. Login as Admin and verify full dashboard access
2. Check all tabs load correctly with data
3. Verify status distribution chart matches actual counts
4. Generate all available reports
5. Export reports in different formats (Excel, PDF)
6. Login as Campus Admin and verify:
   - Only sees own campus candidates
   - Cannot access other campus data
   - Can manage training and screening
7. Login as OEP and verify:
   - Only sees assigned candidates
   - Can view visa and remittance data
8. Login as Trainer and verify:
   - Can mark attendance and assessments
   - Cannot modify registration data
9. Login as Viewer and verify:
   - Read-only access to all modules
   - Cannot create or modify any data
10. Report any access control gaps
```

---

## CROSS-PHASE INTEGRATION TESTING

### Test Scenarios

#### Scenario 1: Happy Path (Complete Lifecycle)
```
1. Create candidate with all information
2. Pass all 3 screenings
3. Upload all required documents, complete registration
4. Assign to batch, complete training with 85% attendance, 70% score
5. Complete all 11 visa stages
6. Conduct briefing and record departure
7. Track 90-day compliance
8. Record 3 remittances
Expected: All status transitions work correctly
```

#### Scenario 2: Rejection Path
```
1. Create candidate
2. Fail desk screening
Expected: Candidate moves to REJECTED status
```

#### Scenario 3: Dropout Path
```
1. Create candidate
2. Pass screenings, complete registration
3. Assign to training
4. Drop candidate mid-training
Expected: Candidate moves to DROPPED status
```

#### Scenario 4: Visa Failure
```
1. Complete candidate through training
2. Fail visa interview
Expected: Candidate moves to REJECTED status (visa rejection)
```

#### Scenario 5: Compliance Failure
```
1. Complete candidate through departure
2. Miss 90-day compliance deadlines
Expected: Candidate flagged as non-compliant
```

### Meta Prompt for Integration Testing
```
Test complete candidate lifecycle integration:
1. Create 5 candidates with diverse profiles
2. Candidate A: Complete happy path through all 9 phases
3. Candidate B: Fail at screening stage
4. Candidate C: Drop during training
5. Candidate D: Fail visa interview
6. Candidate E: Complete deployment but miss compliance
7. Verify all status transitions are correct
8. Check data integrity across all modules
9. Verify timeline shows complete history
10. Generate comprehensive reports for each candidate
11. Document any data flow issues or gaps
```

---

## PERFORMANCE & SECURITY TESTING

### Performance Checklist
- [ ] Test with 1000+ candidates in database
- [ ] Test bulk attendance entry for 50 candidates
- [ ] Test report generation with large datasets
- [ ] Test search/filter performance
- [ ] Test file upload performance (multiple files)
- [ ] Measure page load times

### Security Checklist
- [ ] Test CSRF protection on all forms
- [ ] Test XSS prevention in text fields
- [ ] Test SQL injection in search fields
- [ ] Test file upload restrictions (dangerous extensions)
- [ ] Test authentication on all protected routes
- [ ] Test authorization (role-based access)
- [ ] Verify sensitive data hidden ($hidden arrays)
- [ ] Test password policies

### Meta Prompt for Performance/Security Testing
```
Test performance and security:
1. Create 1000 test candidates via seeder
2. Measure dashboard load time (<3 seconds)
3. Test bulk attendance entry for 50 candidates (<5 seconds)
4. Generate reports with all candidates (<10 seconds)
5. Test file uploads with:
   - Valid files (should succeed)
   - .php files (should block)
   - Files with double extensions (should block)
   - Files >5MB (should block)
6. Test XSS with script injection in text fields
7. Test SQL injection in search/filter fields
8. Verify all routes require authentication
9. Test role-based access control enforcement
10. Document any performance bottlenecks or security gaps
```

---

## IMPROVEMENT RECOMMENDATIONS SUMMARY

### High Priority
1. **Notification System** - Add SMS/Email/WhatsApp notifications for key events
2. **Mobile Responsiveness** - Optimize for mobile devices
3. **API Integrations** - Takamol, GAMCA, flight tracking
4. **Automated Alerts** - Document expiry, compliance deadlines, SLA breaches

### Medium Priority
1. **Digital Signatures** - For undertakings and certificates
2. **QR Code Verification** - For certificates and documents
3. **Advanced Analytics** - Predictive dropout, success rates
4. **Calendar Integration** - Scheduling for interviews, tests

### Low Priority
1. **AI Document Classification** - Auto-categorize uploads
2. **Chatbot** - FAQ and status queries
3. **Multi-language Support** - Urdu, Arabic
4. **Offline Mode** - For field data collection

---

## APPENDIX: QUICK REFERENCE

### Status Codes
| Status | Description | Next Status |
|--------|-------------|-------------|
| NEW | Initial registration | SCREENING |
| SCREENING | Undergoing screenings | REGISTERED or REJECTED |
| REGISTERED | Documents collected | TRAINING |
| TRAINING | In training program | VISA_PROCESS or DROPPED |
| VISA_PROCESS | Visa pipeline | READY or REJECTED |
| READY | Awaiting departure | DEPARTED |
| DEPARTED | In destination country | N/A |
| REJECTED | Rejected at any stage | N/A |
| DROPPED | Dropped out | N/A |

### Key Validation Rules
| Field | Rule |
|-------|------|
| CNIC | 13 digits, unique |
| Email | Valid format, unique |
| Phone | Max 20 chars |
| DOB | Must be in past |
| Attendance | Min 80% required |
| Assessment | Min 60% to pass |
| File Size | Max 5MB |
| File Types | PDF, JPG, PNG |

### SLA Deadlines
| Priority | Resolution Time |
|----------|-----------------|
| Urgent | 2 days |
| High | 5 days |
| Normal | 7 days |
| Low | 14 days |

---

*This guide should be used in conjunction with the AUDIT_REPORT.md for comprehensive testing and improvement of the BTEVTA Overseas Employment Management System.*
