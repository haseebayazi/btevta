# Departure Module Documentation

## Overview

The **Departure** module tracks candidates who have completed training and traveled to Saudi Arabia for employment. It manages the critical **90-day compliance period**, monitors post-departure activities, records issues, and ensures successful employment integration.

---

## Purpose

After candidates complete training and obtain visas, they depart for Saudi Arabia. The departure module:
1. **Tracks the departure process** (pre-departure briefing → actual departure)
2. **Monitors post-arrival compliance** (Iqama, Absher, WPS, salary)
3. **Records and resolves issues** that arise post-departure
4. **Ensures 90-day compliance** - a critical success metric for OEPs

---

## Who Can Access

- **Admin**: Full access to all departure records and reports
- **Campus Admin**: Access to candidates from their assigned campus only
- **OEP User**: View candidates sent through their OEP

---

## Departure Workflow Stages

### Stage 1: Pre-Departure Briefing

**Purpose**: Orient candidates before travel

**Required Information**:
- Briefing date (when orientation was conducted)
- Departure date (scheduled travel date)
- Flight number
- Destination (city in Saudi Arabia)
- Briefing remarks (optional notes)

**Action**: Record briefing → System sends notification to candidate

**Location**: `/departure/{candidate}/show` → Pre-Departure Briefing section

---

### Stage 2: Actual Departure

**Purpose**: Confirm the candidate has departed Pakistan

**Required Information**:
- Actual departure date (may differ from scheduled)
- Departure remarks (optional)

**What Happens**:
1. Departure is recorded in system
2. Candidate status changes to `departed`
3. Departure confirmation notification sent
4. 90-day compliance tracking begins

**Important**: The 90-day clock starts from actual departure date

---

### Stage 3: Post-Arrival Compliance (The "90-Day Period")

This is the **most critical phase**. The candidate must complete all compliance steps within 90 days of departure.

#### 3a. Iqama Registration

**What is Iqama?**: Saudi Arabian residence permit (like a visa/work permit)

**Required Information**:
- Iqama number (unique ID)
- Issue date
- Expiry date (optional)
- Post-arrival medical certificate (optional file upload)

**Compliance Importance**: Iqama is mandatory for legal residence and employment

---

#### 3b. Absher Registration

**What is Absher?**: Saudi government's online portal for expat services

**Required Information**:
- Absher registration date
- Absher ID (optional)
- Remarks (optional)

**Purpose**: Enables candidates to access government services, track Iqama status, report complaints

---

#### 3c. WPS Registration

**What is WPS?**: Wage Protection System - Saudi Arabia's salary payment tracking system

**Required Information**:
- WPS registration date
- WPS ID (optional)
- Remarks (optional)

**Compliance Importance**: Ensures salary is paid through official banking channels

---

#### 3d. First Salary Receipt

**Required Information**:
- First salary date (when received)
- Salary amount (in SAR or PKR)
- Salary proof (optional file upload - bank statement, pay slip)

**Compliance Importance**: Confirms employment has begun and candidate is being paid as agreed

---

### Stage 4: 90-Day Compliance Verification

**Purpose**: Final confirmation that the candidate successfully integrated into employment

**Required Information**:
- Compliance date (when verification was conducted)
- Is compliant? (Yes/No checkbox)
- Compliance remarks (explanation if non-compliant)

**What Happens if Compliant**:
1. Compliance status marked as "achieved"
2. Success notification sent
3. OEP's compliance rate improves
4. Candidate considered successfully placed

**What Happens if Non-Compliant**:
1. Reason for non-compliance recorded
2. Case may be flagged for review
3. OEP may be contacted
4. Remedial actions may be required

---

## Issue Management

### Reporting Issues

**Issue Types**:
1. **Salary Delay**: Employer not paying on time
2. **Contract Violation**: Terms different from agreed contract
3. **Work Condition**: Unsafe or unsuitable work environment
4. **Accommodation**: Housing problems
5. **Medical**: Health-related issues
6. **Other**: Any other problem

**Required Information**:
- Issue type
- Date issue occurred
- Detailed description
- Severity (Low / Medium / High / Critical)
- Evidence (optional file upload - photos, documents)

**What Happens**:
1. Issue is logged in system
2. Status starts as "Open"
3. Notification sent to administrators
4. Issue tracking begins

---

### Issue Resolution

**Issue Statuses**:
- **Open**: Issue reported, awaiting action
- **Investigating**: Under review
- **Resolved**: Solution implemented
- **Closed**: Issue fully resolved

**Resolution Process**:
1. Admin/OEP investigates issue
2. Resolution actions taken (contact employer, involve embassy, etc.)
3. Status updated with resolution notes
4. Candidate informed of resolution
5. Issue marked as closed when fully resolved

---

## Compliance Tracking & Reports

### 90-Day Tracking Report

**Purpose**: Monitor all candidates approaching or past the 90-day mark

**Shows**:
- Candidates who have departed
- Days since departure
- Compliance checklist status for each candidate
- Alert for candidates past 90 days without compliance

**Access**: `/departure/90-day-tracking`

---

### Non-Compliant Candidates Report

**Purpose**: Identify candidates who failed 90-day compliance

**Shows**:
- Candidates marked as non-compliant
- Reasons for non-compliance
- Days since departure
- Associated OEP
- Action items

**Access**: `/departure/non-compliant`

---

### Active Issues Report

**Purpose**: Track ongoing post-departure problems

**Shows**:
- All issues with status "Open" or "Investigating"
- Severity levels
- Days since reported
- Assignment status

**Access**: `/departure/active-issues`

---

### Compliance Report (Date Range)

**Purpose**: Generate compliance statistics for a specific period

**Parameters**:
- Start date
- End date
- OEP filter (optional)

**Output**:
- Total departures in period
- Compliance rate percentage
- Breakdown by compliance stage
- Issue summary

**Access**: `/departure/compliance-report`

---

## Additional Features

### Return to Pakistan

**Purpose**: Record when a candidate returns to Pakistan

**Scenarios**:
- Contract completion (normal return)
- Early termination (by employer or candidate)
- Medical reasons
- Family emergency
- Deportation

**Required Information**:
- Return date
- Return reason
- Detailed remarks

**What Happens**:
- Candidate status changes to `returned`
- Compliance tracking stops
- Return is logged for records

---

### Departure Timeline

**Purpose**: Visual chronological view of all departure activities

**Access**: `/departure/{candidate}/timeline`

**Shows**:
- Pre-departure briefing date
- Actual departure date
- Iqama registration date
- Absher registration date
- WPS registration date
- First salary receipt date
- Compliance verification date
- All recorded issues with timestamps

---

## Navigation & Routes

### Main Routes

```php
// List all departed candidates
GET /departure - departure.index

// View individual departure details
GET /departure/{candidate} - departure.show

// Pre-departure briefing
POST /departure/{candidate}/briefing - departure.record-briefing

// Record actual departure
POST /departure/{candidate}/record-departure - departure.record-departure

// Record Iqama
POST /departure/{candidate}/iqama - departure.record-iqama

// Record Absher
POST /departure/{candidate}/absher - departure.record-absher

// Record WPS
POST /departure/{candidate}/wps - departure.record-wps

// Record first salary
POST /departure/{candidate}/first-salary - departure.record-first-salary

// Record 90-day compliance
POST /departure/{candidate}/90-day-compliance - departure.record-90-day-compliance

// Report issue
POST /departure/{candidate}/report-issue - departure.report-issue

// Update issue status
PUT /departure/issues/{issue} - departure.update-issue

// Mark as returned
POST /departure/{candidate}/returned - departure.mark-returned

// Reports
GET /departure/90-day-tracking - departure.tracking-90-days
GET /departure/non-compliant - departure.non-compliant
GET /departure/active-issues - departure.active-issues
GET /departure/compliance-report - departure.compliance-report
GET /departure/{candidate}/timeline - departure.timeline
```

---

## File Storage

### Medical Certificates
- **Path**: `storage/app/public/departure/medical/`
- **Accessible URL**: `/storage/departure/medical/{filename}`

### Salary Proof
- **Path**: `storage/app/public/departure/salary-proof/`
- **Accessible URL**: `/storage/departure/salary-proof/{filename}`

### Issue Evidence
- **Path**: `storage/app/public/departure/issues/`
- **Accessible URL**: `/storage/departure/issues/{filename}`

---

## Compliance Checklist

The system automatically tracks completion of these milestones:

✅ Pre-departure briefing conducted
✅ Actual departure recorded
✅ Iqama obtained
✅ Absher registered
✅ WPS registered
✅ First salary received
✅ 90-day compliance verified

**Compliance Stage Progression**:
- `scheduled` → `departed` → `in_progress` → `completed` (if compliant)
- `scheduled` → `departed` → `in_progress` → `non_compliant` (if failed)

---

## Notifications

The system automatically sends notifications for:

1. **Pre-departure briefing completed** → Candidate + Admin
2. **Departure confirmed** → Candidate + OEP + Campus Admin
3. **Iqama recorded** → Candidate + Admin
4. **First salary confirmed** → Candidate + Admin + OEP
5. **Compliance achieved** → Candidate + Admin + OEP
6. **Issue reported** → Admin + OEP (if severity is High/Critical)

---

## Best Practices

### For Admins

1. **Record briefings promptly**: Conduct pre-departure briefings 1-2 days before travel
2. **Monitor the 90-day window**: Check the tracking report weekly
3. **Follow up proactively**: Contact candidates around day 30, 60, and 85
4. **Investigate issues quickly**: High/critical issues should be addressed within 48 hours
5. **Maintain communication**: Regular contact with OEPs ensures smooth compliance

### For Data Entry

1. **Accuracy is critical**: Iqama numbers, dates, and amounts must be correct
2. **Document everything**: Upload supporting files when available
3. **Add context**: Use remarks fields to provide helpful details
4. **Timely updates**: Record information as soon as it's received
5. **Verify before marking compliant**: Review all checklist items before final compliance

### For OEPs

1. **Stay connected**: Maintain contact with deployed candidates
2. **Report proactively**: Don't wait for issues to escalate
3. **Support compliance**: Help candidates complete Absher, WPS registration
4. **Provide proof**: Encourage candidates to share salary slips, Iqama photos
5. **Resolve quickly**: Address candidate concerns before they become official issues

---

## Common Questions

### Q: What happens if a candidate doesn't achieve 90-day compliance?

**A**: The candidate is marked as non-compliant with documented reasons. This:
- Affects OEP performance metrics
- May require corrective action
- Could impact future candidate placements through that OEP
- May involve embassy intervention in severe cases

### Q: Can compliance be extended beyond 90 days?

**A**: Extensions are rare and require admin approval. Valid reasons might include:
- Medical emergencies
- Employer administrative delays
- COVID-19 or government restrictions
- Document processing delays

### Q: Who is responsible for collecting compliance information?

**A**: Typically:
- **Iqama/Absher/WPS**: OEP or employer should provide
- **Salary proof**: Candidate should share
- **Overall verification**: Admin confirms with OEP and candidate

### Q: What is the expected compliance rate?

**A**: Healthy programs typically achieve:
- **85-95%** compliance rate
- **<5%** critical issues
- **90%+** issue resolution within 30 days

### Q: Can a returned candidate be re-deployed?

**A**: Yes, if they returned for legitimate reasons (contract completion, family emergency, etc.). Those who were deported or terminated for cause may face restrictions.

---

## Integration with Other Modules

**OEP Module**: Links to view OEP performance and candidate placement history

**Visa Processing**: Provides context on visa issuance date

**Training Module**: Shows which batch/trade the candidate completed

**Candidate Profile**: Full candidate history accessible from departure page

**Dashboard**: Departure statistics displayed on main dashboard

---

## Troubleshooting

### Issue: Cannot record departure
**Check**:
- Is pre-departure briefing recorded?
- Does candidate have valid visa?
- Is flight information correct?

### Issue: Compliance checklist not updating
**Possible causes**:
- Information recorded on wrong candidate
- Database sync issue (refresh page)
- Missing required fields

### Issue: Notification not sent
**Check**:
- Email configured correctly in settings
- Candidate has valid email/phone
- Notification service is active

---

## Database Tables

### `departures`
Main departure records with all compliance data

### `post_departure_issues`
Tracks issues reported after departure

### `departure_timeline`
Chronological events for audit trail

---

## Success Metrics

Track these KPIs:
1. **On-time departure rate**: % departing as scheduled
2. **90-day compliance rate**: % achieving compliance
3. **Average days to compliance**: How quickly candidates complete requirements
4. **Issue resolution time**: Average days to resolve issues
5. **First salary receipt rate**: % receiving salary within 30 days
6. **Return rate**: % returning to Pakistan prematurely

---

## Support

For questions about the departure module:
- **Technical issues**: Contact system administrator
- **Compliance questions**: Refer to OEP coordination team
- **Embassy matters**: Escalate through proper diplomatic channels

---

**Last Updated**: 2025-12-10
**Module Status**: Fully Functional ✅
**Issue Reference**: Resolves Issue #9 from user testing feedback

---

## Appendix: Saudi Arabia Systems Overview

### Iqama (Residence Permit)
- **Purpose**: Legal residence and work authorization
- **Validity**: Typically 1-2 years, renewable
- **Required for**: Banking, SIM cards, travel, employment

### Absher Portal
- **Website**: https://www.absher.sa
- **Services**: Visa status, Iqama renewal, labor complaints, exit re-entry permits
- **Access**: Requires Iqama number and registration

### WPS (Wage Protection System)
- **Purpose**: Ensures timely salary payment through banks
- **Benefit**: Legal protection against wage delays
- **Monitoring**: Government tracks all WPS payments

