# Remittance Management - Administrator Manual

## Table of Contents

1. [Admin Responsibilities](#admin-responsibilities)
2. [Daily Operations](#daily-operations)
3. [Alert Management](#alert-management)
4. [Report Generation & Analysis](#report-generation--analysis)
5. [Data Quality Management](#data-quality-management)
6. [User Management](#user-management)
7. [Configuration Management](#configuration-management)
8. [Performance Monitoring](#performance-monitoring)
9. [Troubleshooting Guide](#troubleshooting-guide)
10. [Best Practices](#best-practices)

---

## Admin Responsibilities

### Primary Duties

**Daily Tasks:**
- Monitor critical alerts
- Review and verify pending remittances
- Respond to data quality issues
- Generate daily status reports

**Weekly Tasks:**
- Generate weekly summary reports
- Review alert patterns and trends
- Update beneficiary information
- Audit data accuracy

**Monthly Tasks:**
- Generate comprehensive monthly reports
- Analyze remittance trends
- Update alert thresholds if needed
- Review system performance
- Archive old data
- Conduct staff training sessions

**Quarterly Tasks:**
- Comprehensive impact analysis
- Policy review and updates
- System optimization
- Stakeholder reporting

### Access Levels

**Super Administrator:**
- Full system access
- Configuration management
- User management
- Data deletion
- System settings

**Remittance Administrator:**
- Verify remittances
- Manage alerts
- Generate reports
- View all data
- Bulk operations

**Remittance Staff:**
- Create/edit remittances
- Upload proof
- View assigned candidates
- Basic reports

**Viewer:**
- Read-only access
- View reports
- Export data

---

## Daily Operations

### Morning Routine (15-30 minutes)

**1. Check Dashboard (5 min)**
```
Navigate to: Remittances → Dashboard

Review:
- New remittances (last 24 hours)
- Pending verifications
- Critical alerts
- System health indicators
```

**2. Review Critical Alerts (10 min)**
```
Navigate to: Remittances → Alerts
Filter: Severity = Critical, Status = Unresolved

Actions:
- Prioritize by severity and age
- Contact relevant candidates/staff
- Resolve or assign for follow-up
- Document actions taken
```

**3. Process Pending Verifications (15 min)**
```
Navigate to: Remittances → Index
Filter: Status = Pending

For each remittance:
- Verify transaction reference
- Check proof documents
- Validate amounts
- Approve or flag for review
```

### Afternoon Tasks

**4. Data Entry Quality Check (10 min)**
```
Review recent entries for:
- Missing required fields
- Duplicate transactions
- Unusual amounts
- Incomplete beneficiary information
```

**5. Generate Daily Summary (5 min)**
```
Navigate to: Remittances → Reports → Dashboard

Export daily summary with:
- Today's remittances count
- Total amount received
- Pending verifications
- Active alerts
```

### End of Day (10 min)

**6. Status Report**
```
Prepare brief status report:
- Remittances processed: X
- Verified: X
- Flagged: X
- Alerts resolved: X
- Outstanding issues: X
```

**7. Next Day Planning**
```
- Review unresolved critical alerts
- Plan follow-ups
- Schedule candidate contacts
- Note any system issues
```

---

## Alert Management

### Understanding Alert Workflow

```
Alert Generation → Review → Investigation → Action → Resolution
```

### Alert Types and Standard Responses

#### 1. Missing Remittance Alerts

**Severity:** Warning → Critical (after 180 days)

**Standard Response:**
```
Day 1-3: Review candidate deployment status
Day 3-7: Contact candidate via phone/email
Day 7-14: Contact OEP for verification
Day 14+: Escalate to management

Template Email:
Subject: Remittance Status Inquiry - [Candidate Name]

Dear [Candidate Name],

We noticed that no remittances have been recorded for the past [X] days.
We would like to verify your status and ensure everything is well.

Please contact us at your earliest convenience or send proof of any
recent remittances you may have made.

Contact: remittance@btevta.gov.pk
Phone: [Contact Number]

Best regards,
BTEVTA Remittance Team
```

**Resolution Criteria:**
- Remittance received and recorded
- Valid explanation received and documented
- Candidate confirmed no remittance sent (document reason)

#### 2. Missing Proof Alerts

**Severity:** Warning → Critical (after 60 days)

**Standard Response:**
```
Day 1-7: Email reminder to upload proof
Day 7-14: Phone follow-up
Day 14-30: Contact via OEP
Day 30+: Flag remittance for verification

Template Email:
Subject: Upload Remittance Proof - Transaction [REF]

Dear [Candidate Name],

We have recorded your remittance (Transaction: [REF], Amount: PKR [AMOUNT],
Date: [DATE]), but we are missing the transfer receipt/proof.

Please upload the proof document through:
1. BTEVTA Portal: [URL]
2. Email: remittance@btevta.gov.pk
3. WhatsApp: [Number]

This helps us verify the transaction and maintain accurate records.

Thank you for your cooperation.

Best regards,
BTEVTA Remittance Team
```

**Resolution Criteria:**
- Proof uploaded and verified
- Transaction verified through alternative means
- Waiver granted with justification

#### 3. First Remittance Delay Alerts

**Severity:** Warning → Critical (after 90 days)

**Standard Response:**
```
Day 1-14: Monitor (normal deployment period)
Day 14-30: Contact candidate
Day 30-60: Verify employment status via OEP
Day 60+: Escalate to management

Actions:
1. Verify candidate still employed
2. Check if salary payments started
3. Identify any issues preventing remittance
4. Provide guidance on transfer methods
5. Connect with family if needed
```

**Resolution Criteria:**
- First remittance received
- Valid delay reason documented
- Candidate confirmed  unemployed (update status)

#### 4. Low Frequency Alerts

**Severity:** Info

**Standard Response:**
```
Review Factors:
- Salary amount and schedule
- Family circumstances
- Economic conditions in host country
- Personal financial commitments

Actions:
1. Contact candidate (informal check-in)
2. Verify no issues with employment
3. Offer remittance assistance if needed
4. Document any special circumstances
```

**Resolution Criteria:**
- Frequency increases
- Valid explanation documented
- Dismiss if no issues identified

#### 5. Unusual Amount Alerts

**Severity:** Info

**Standard Response:**
```
Investigation Steps:
1. Compare with candidate's typical amounts
2. Check for data entry errors
3. Verify proof document matches amount
4. Contact candidate if significant deviation

Possible Reasons:
- Bonus/overtime payment
- Accumulated savings
- Special occasion (Eid, wedding, emergency)
- Change in employment/salary
- One-time large expense
```

**Resolution Criteria:**
- Amount verified correct
- Reason documented
- Data entry error corrected if applicable

### Bulk Alert Management

**For Multiple Similar Alerts:**

1. **Filter and Group**
```
Navigate to: Remittances → Alerts
Apply Filters:
- Alert Type: [specific type]
- Severity: [level]
- Created Date: Last 7 days
```

2. **Analyze Pattern**
```
Look for:
- Common candidates
- Specific time periods
- Related OEPs/countries
- Systemic issues
```

3. **Bulk Actions**
```
Select multiple alerts (checkbox)
Choose action:
- Mark as Read
- Assign to staff member
- Add bulk notes
- Resolve (with standard note)
```

4. **Document Pattern**
```
If systemic issue found:
- Create incident report
- Notify relevant department
- Implement preventive measures
- Update procedures if needed
```

### Alert Priority Matrix

| Severity | Age | Priority | Response Time |
|----------|-----|----------|---------------|
| Critical | >30 days | High | Same day |
| Critical | 15-30 days | High | Within 24 hours |
| Critical | <15 days | Medium | Within 48 hours |
| Warning | >60 days | Medium | Within 3 days |
| Warning | <60 days | Low | Within 7 days |
| Info | Any | Low | As time permits |

---

## Report Generation & Analysis

### Standard Reports

#### Daily Status Report

**Purpose:** Quick daily snapshot

**Schedule:** Every morning by 9 AM

**Steps:**
1. Navigate to: Remittances → Reports → Dashboard
2. Note key metrics:
   - Yesterday's remittances: Count & Amount
   - Pending verifications: Count
   - New alerts: Count by severity
3. Export or screenshot
4. Email to: management@btevta.gov.pk

**Template:**
```
Daily Remittance Status - [DATE]

Yesterday's Activity:
- Remittances Received: [X] (PKR [AMOUNT])
- Verified: [X]
- Pending Verification: [X]

Alerts:
- Critical: [X]
- Warnings: [X]
- Info: [X]

Actions Taken:
- [Brief summary]

Outstanding Issues:
- [Any concerns]

Prepared by: [Your Name]
```

#### Weekly Summary Report

**Purpose:** Week-over-week tracking

**Schedule:** Every Monday morning

**Steps:**
1. Navigate to: Remittances → Reports → Monthly Report
2. Filter: Last 7 days
3. Export to Excel
4. Add analysis:
   - Compare with previous week
   - Note trends
   - Highlight concerns

**Key Metrics:**
- Total remittances count
- Total amount (PKR & foreign)
- Average per remittance
- Proof compliance rate
- Top 10 candidates
- Alerts generated/resolved
- Countries breakdown

#### Monthly Comprehensive Report

**Purpose:** Detailed monthly analysis

**Schedule:** 1st of each month (for previous month)

**Steps:**

1. **Collect Data (Multiple Reports)**
```
a) Monthly Trends Report
   - Navigate to: Reports → Monthly Report
   - Select: Previous month
   - Export: Excel

b) Purpose Analysis
   - Navigate to: Reports → Purpose Analysis
   - Filter: Last month
   - Export: Excel

c) Proof Compliance
   - Navigate to: Reports → Proof Compliance
   - Filter: Last month
   - Export: PDF

d) Top Remitters
   - Navigate to: Reports → Dashboard
   - View: Top Candidates
   - Export: Excel

e) Country Analysis
   - Navigate to: Reports → Impact Analytics
   - Section: Country breakdown
   - Export: Excel
```

2. **Compile Report Document**
```
Create comprehensive document including:
- Executive Summary
- Overall Statistics
- Trends Analysis
- Purpose Breakdown
- Country-wise Analysis
- Proof Compliance Status
- Alert Summary
- Issues and Recommendations
- Month-over-Month Comparison
- Year-to-Date Summary
```

3. **Distribute**
```
Send to:
- BTEVTA Management
- Finance Department
- OEP Coordinators
- Relevant Ministries

Format: PDF + Excel Data
CC: remittance-team@btevta.gov.pk
```

### Custom Report Generation

**For Specific Requests:**

**Example: "OEP-Specific Performance Report"**

1. **Filter Data**
```
Navigate to: Remittances → Index
Filters:
- Departure Destination: [Country]
- Date Range: [Period]
- OEP: [OEP Name]
```

2. **Export Base Data**
```
Click: Export → Excel
Save as: [OEP]_[Month]_Remittances.xlsx
```

3. **Analyze in Excel**
```
Create pivot tables:
- Remittances by Candidate
- Amounts by Month
- Proof Compliance Rate
- Average Per Candidate
```

4. **Add Visualizations**
```
Create charts:
- Monthly trend line
- Purpose pie chart
- Top candidates bar chart
```

5. **Compile Final Report**
```
Include:
- Summary statistics
- Charts
- Top performers
- Issues identified
- Recommendations
```

### Report Distribution Schedule

| Report | Frequency | Recipients | Delivery Method |
|--------|-----------|------------|-----------------|
| Daily Status | Daily | Management | Email |
| Weekly Summary | Weekly | Management, Finance | Email + Portal |
| Monthly Comprehensive | Monthly | All Stakeholders | Email + Meeting |
| Quarterly Impact | Quarterly | Ministry, Board | Formal Presentation |
| Annual Report | Yearly | All Stakeholders | Published Document |

---

## Data Quality Management

### Data Validation Checks

**Daily Validation (15 minutes)**

**1. Duplicate Detection**
```
Query for duplicate transaction references:

SELECT transaction_reference, COUNT(*) as count
FROM remittances
WHERE deleted_at IS NULL
GROUP BY transaction_reference
HAVING count > 1;

Action: Investigate and merge/delete duplicates
```

**2. Missing Required Data**
```
Navigate to: Remittances → Index
Custom Filter:
- Has Proof = No
- Transfer Date < 30 days ago

Action: Contact candidates for proof upload
```

**3. Unusual Amounts**
```
Check for potential data entry errors:
- Amounts > PKR 500,000 (verify if legitimate)
- Amounts < PKR 1,000 (check if missing zero)
- Foreign amounts not matching PKR amounts

Action: Verify with proof documents
```

**4. Date Inconsistencies**
```
Check for:
- Transfer dates in the future
- Transfer dates before deployment
- Large gaps in remittance dates

Action: Correct data entry errors
```

### Data Cleanup Procedures

**Monthly Cleanup (1-2 hours)**

**1. Update Beneficiary Information**
```
Review beneficiaries:
- Inactive beneficiaries
- Missing contact information
- Outdated banking details

Contact candidates for updates
```

**2. Verify Candidate Status**
```
Cross-check with Departure records:
- Candidates marked as deployed but no departure record
- Returned candidates still showing remittances
- Employment status changes

Update records accordingly
```

**3. Archive Old Alerts**
```
Auto-archive resolved alerts older than 6 months:

Navigate to: System → Data Management
Action: Archive Old Alerts
Criteria: Resolved + 6+ months old

Keeps database performant
```

**4. Reconcile Statistics**
```
Run reconciliation report:
- Compare database totals with manual records
- Verify sum of all remittances
- Check candidate counts
- Validate proof compliance percentages

Document any discrepancies
```

### Quality Metrics Dashboard

**Monitor Monthly:**

| Metric | Target | Current | Status |
|--------|--------|---------|--------|
| Proof Compliance Rate | >90% | | |
| Data Entry Error Rate | <2% | | |
| Duplicate Records | 0 | | |
| Missing Required Fields | <1% | | |
| Verification Turnaround | <48hrs | | |
| Alert Resolution Rate | >80% | | |

---

## User Management

### Staff Roles and Permissions

**Creating New User:**

1. Navigate to: Admin → Users → Create
2. Fill information:
   - Name
   - Email
   - Role: [Select appropriate role]
   - Department
3. Send credentials securely
4. Provide training materials

**Role Assignment:**

| Role | Permissions |
|------|-------------|
| Super Admin | Full access, configuration, user management |
| Remittance Admin | Verify, manage alerts, all reports, bulk operations |
| Remittance Staff | Create/edit remittances, upload proof, basic reports |
| OEP Coordinator | View candidates' remittances, basic reports |
| Finance Officer | All reports, export data, read-only remittances |
| Viewer | Read-only access, view reports |

### Training New Staff

**Week 1: Basics**
- System navigation
- Viewing remittances
- Reading reports
- Understanding alerts

**Week 2: Data Entry**
- Creating remittances
- Uploading proof
- Editing records
- Using filters

**Week 3: Verification**
- Verification process
- Quality checks
- Handling alerts
- Communication templates

**Week 4: Reporting**
- Generating reports
- Custom filters
- Data export
- Analysis basics

**Training Materials:**
- User Guide (REMITTANCE_USER_GUIDE.md)
- Video tutorials (if available)
- Practice environment
- FAQ document

---

## Configuration Management

### Alert Threshold Management

**When to Adjust Thresholds:**

- High false positive rate
- Changing economic conditions
- Policy updates
- Feedback from stakeholders

**Process:**

1. **Analyze Current Thresholds**
```
File: config/remittance.php

Current values:
'missing_remittance_days' => 90
'proof_upload_days' => 30
'first_remittance_days' => 60
'low_frequency_months' => 6
'min_expected_remittances' => 3
```

2. **Gather Data**
```
- Alert generation rate
- False positive rate
- Average time to first remittance
- Typical remittance frequency
```

3. **Make Adjustments**
```
Edit config/remittance.php:
'missing_remittance_days' => 120  # Increased from 90

Clear cache:
php artisan config:cache
```

4. **Monitor Impact**
```
Track for 30 days:
- Alert volume changes
- Effectiveness
- User feedback
```

5. **Document Changes**
```
Create change log entry:
Date: [DATE]
Change: Increased missing_remittance_days from 90 to 120
Reason: Reduce false positives during economic downturn
Approved by: [Name]
Results: [To be monitored]
```

### System Configuration

**Important Settings (config/remittance.php):**

```php
// File upload limits
'file_uploads' => [
    'max_size' => 5120,  // Adjust if users report upload issues
    'allowed_types' => ['pdf', 'jpg', 'jpeg', 'png'],
],

// Pagination
'pagination' => [
    'per_page' => 20,  // Adjust based on user preference
],

// Report caching
'reports' => [
    'cache_duration' => 3600,  // Adjust for performance
],
```

---

## Performance Monitoring

### Daily Checks

**System Health:**
```
1. Response Time
   - Dashboard load time should be <3 seconds
   - Report generation <10 seconds
   - API calls <2 seconds

2. Database Performance
   - Run: php artisan db:monitor
   - Check slow query log

3. Storage
   - Check disk space
   - Monitor upload storage

4. Errors
   - Review: storage/logs/laravel.log
   - No critical errors
```

### Weekly Performance Review

**Metrics to Track:**

1. **Database Size**
```bash
# Check database size
SELECT
    table_name AS "Table",
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS "Size (MB)"
FROM information_schema.TABLES
WHERE table_schema = "btevta"
    AND table_name LIKE 'remittance%'
ORDER BY (data_length + index_length) DESC;
```

2. **Query Performance**
```
Identify slow queries:
- Enable query logging
- Review common queries
- Add indexes if needed
```

3. **Cache Hit Rate**
```
Monitor cache effectiveness:
- Report cache hits vs misses
- Adjust cache duration if needed
```

### Monthly Optimization

**Tasks:**

1. **Database Optimization**
```bash
# Optimize tables
php artisan db:optimize

# Analyze query patterns
# Add indexes for frequently filtered fields
```

2. **Clear Old Data**
```bash
# Archive old alerts
php artisan remittance:archive-alerts --older-than=180

# Clean temporary files
php artisan cache:clear
php artisan view:clear
```

3. **Review Logs**
```bash
# Check for errors
grep "ERROR" storage/logs/laravel.log

# Monitor warnings
grep "WARNING" storage/logs/laravel.log

# Archive old logs
tar -czf logs_$(date +%Y%m).tar.gz storage/logs/*.log
rm storage/logs/*.log
```

---

## Troubleshooting Guide

### Common Issues and Solutions

#### Issue 1: Alerts Not Generating

**Symptoms:**
- No new alerts appearing
- Last alert generation was days ago

**Diagnosis:**
```bash
# Check if scheduler is running
php artisan schedule:list

# Check cron job
crontab -l

# Run manually
php artisan remittance:generate-alerts
```

**Solutions:**
1. Verify cron job is active
2. Check alert generation logs
3. Verify database connectivity
4. Check alert thresholds (may be too high)

#### Issue 2: Slow Report Generation

**Symptoms:**
- Reports taking >30 seconds to load
- Timeout errors

**Diagnosis:**
```bash
# Enable query log
DB::enableQueryLog();
# Generate report
# Check queries
dd(DB::getQueryLog());
```

**Solutions:**
1. Add database indexes
2. Increase cache duration
3. Optimize queries (use eager loading)
4. Consider report pre-generation for heavy reports

#### Issue 3: File Upload Failures

**Symptoms:**
- Users can't upload proof documents
- Upload succeeds but file not saved

**Diagnosis:**
```bash
# Check storage permissions
ls -la storage/app/remittance-receipts

# Check disk space
df -h

# Check PHP upload settings
php -i | grep upload
```

**Solutions:**
1. Fix permissions: `chmod -R 775 storage`
2. Free disk space if needed
3. Increase PHP upload limits
4. Check storage path configuration

#### Issue 4: Duplicate Remittances

**Symptoms:**
- Same transaction appears multiple times
- Data quality alerts

**Diagnosis:**
```sql
SELECT transaction_reference, COUNT(*)
FROM remittances
GROUP BY transaction_reference
HAVING COUNT(*) > 1;
```

**Solutions:**
1. Identify duplicates
2. Verify which is correct
3. Soft delete duplicates
4. Strengthen validation rules

---

## Best Practices

### Daily Operations

1. **Start Each Day Review**
   - Check critical alerts first
   - Review yesterday's entries
   - Plan daily priorities

2. **Consistent Verification**
   - Verify remittances within 24 hours
   - Don't batch too many verifications
   - Document verification decisions

3. **Proactive Communication**
   - Contact candidates early about issues
   - Provide clear instructions
   - Follow up on requests

4. **Documentation**
   - Add notes to all significant actions
   - Document special cases
   - Keep change logs

### Alert Management

1. **Prioritize by Impact**
   - Critical alerts first
   - Long-standing issues next
   - Info alerts as time permits

2. **Document Resolutions**
   - Always add resolution notes
   - Be specific about actions taken
   - Reference any communications

3. **Look for Patterns**
   - Weekly review of alert types
   - Identify systemic issues
   - Implement preventive measures

4. **Follow Up**
   - Set reminders for pending actions
   - Track candidate responses
   - Close loop on all alerts

### Reporting

1. **Timely Delivery**
   - Stick to schedules
   - Prepare reports in advance
   - Have templates ready

2. **Accuracy First**
   - Verify numbers before sending
   - Double-check calculations
   - Cross-reference sources

3. **Actionable Insights**
   - Don't just report numbers
   - Provide analysis
   - Include recommendations

4. **Clear Presentation**
   - Use charts and visualizations
   - Highlight key findings
   - Make data accessible

### Data Quality

1. **Regular Audits**
   - Weekly spot checks
   - Monthly comprehensive review
   - Quarterly deep analysis

2. **Training**
   - Continuous staff training
   - Share common errors
   - Update procedures

3. **Validation Rules**
   - Enforce at entry
   - Prevent bad data
   - Guide users

4. **Cleanup**
   - Regular maintenance
   - Archive old data
   - Remove duplicates

---

## Emergency Procedures

### Data Breach/Unauthorized Access

**Immediate Actions:**
1. Notify IT Security
2. Change affected passwords
3. Review access logs
4. Document incident
5. Notify management

### System Downtime

**Steps:**
1. Notify users (email/notice)
2. Check server status
3. Contact IT support
4. Provide estimated restoration time
5. Document incident

### Data Loss

**Recovery:**
1. Stop all operations
2. Don't make changes
3. Contact database administrator
4. Restore from latest backup
5. Verify data integrity
6. Resume operations
7. Document incident

---

**Document Version:** 1.0
**Last Updated:** November 2025

**Emergency Contacts:**
- IT Support: support@btevta.gov.pk
- Database Admin: dba@btevta.gov.pk
- Management: management@btevta.gov.pk

**For routine questions:**
- Email: remittance-admin@btevta.gov.pk
- Internal Extension: [XXX]
