# Remittance Management - User Guide

## Table of Contents

1. [Overview](#overview)
2. [Getting Started](#getting-started)
3. [Managing Remittances](#managing-remittances)
4. [Working with Alerts](#working-with-alerts)
5. [Viewing Reports](#viewing-reports)
6. [Managing Beneficiaries](#managing-beneficiaries)
7. [Common Tasks](#common-tasks)
8. [Troubleshooting](#troubleshooting)

---

## Overview

The Remittance Management Module helps BTEVTA track and monitor money transfers (remittances) sent by deployed candidates to their families in Pakistan. This system provides:

- **Complete remittance tracking** - Record all money transfers
- **Automated alerts** - Get notified of missing remittances or issues
- **Comprehensive reports** - Analyze remittance patterns and trends
- **Proof management** - Upload and verify transfer receipts
- **Beneficiary tracking** - Manage recipient information

---

## Getting Started

### Accessing the Remittance Module

1. Log in to the BTEVTA system
2. Navigate to **Remittances** from the main menu
3. You'll see the remittance dashboard with key statistics

### Dashboard Overview

The dashboard displays:
- **Total Remittances** - Overall count and amount
- **This Month** - Current month statistics
- **Pending Verification** - Remittances awaiting verification
- **Active Alerts** - Unresolved alerts requiring attention

---

## Managing Remittances

### Recording a New Remittance

1. Click **"Add Remittance"** button
2. Fill in required information:

#### Candidate Information
- **Candidate** - Select from deployed candidates
- **Departure** - Select the deployment record

#### Transfer Details
- **Transaction Reference** - Unique transaction ID (e.g., TXN123456)
- **Amount** - Amount in PKR
- **Transfer Date** - Date of transfer
- **Transfer Method** - Bank transfer, online transfer, money exchange, etc.

#### Sender Information (Abroad)
- **Sender Name** - Candidate's name or representative
- **Sender Location** - City and country (e.g., Riyadh, Saudi Arabia)

#### Receiver Information (Pakistan)
- **Receiver Name** - Beneficiary name
- **Receiver Account** - Bank account or IBAN
- **Bank Name** - Receiving bank

#### Purpose
- **Primary Purpose** - Select from dropdown:
  - Family Support
  - Education
  - Healthcare
  - Debt Repayment
  - Savings
  - Investment
  - Other
- **Purpose Description** - Additional details (optional)

#### Proof Documentation
- **Has Proof** - Check if you have transfer receipt
- Upload receipt documents if available

3. Click **"Save Remittance"**

### Viewing Remittance Details

1. Click on any remittance in the list
2. View complete information including:
   - Transfer details
   - Candidate information
   - Proof documents
   - Usage breakdown
   - Verification status

### Editing a Remittance

1. Open the remittance details
2. Click **"Edit"** button
3. Update necessary fields
4. Click **"Update Remittance"**

**Note:** Transaction reference cannot be changed after creation.

### Verifying a Remittance

For authorized staff only:

1. Open remittance details
2. Review all information and proof documents
3. Click **"Verify"** button
4. Remittance status changes to "Verified"

**Verification Criteria:**
- Valid transaction reference
- Proof documents uploaded
- Amount matches receipt
- Sender/receiver information correct

### Uploading Proof Documents

1. Open remittance details
2. Scroll to "Proof Documents" section
3. Click **"Upload Receipt"**
4. Select file (PDF, JPG, PNG - max 5MB)
5. Add description (optional)
6. Click **"Upload"**

**Supported formats:** PDF, JPG, JPEG, PNG

### Deleting a Remittance

For administrators only:

1. Open remittance details
2. Click **"Delete"** button
3. Confirm deletion
4. Remittance is soft-deleted (can be restored)

---

## Working with Alerts

### Understanding Alert Types

The system generates 5 types of alerts:

1. **Missing Remittance** (Warning/Critical)
   - Candidate hasn't sent remittances in 90+ days
   - Becomes critical after 180 days

2. **Missing Proof** (Warning/Critical)
   - Remittance lacks proof documentation
   - Alert after 30 days, critical after 60 days

3. **First Remittance Delay** (Warning/Critical)
   - Candidate hasn't sent first remittance
   - Alert after 60 days, critical after 90 days

4. **Low Frequency** (Info)
   - Candidate sending remittances infrequently
   - Less than 3 remittances in 6 months

5. **Unusual Amount** (Info)
   - Remittance amount significantly different from average
   - More than 3 standard deviations from mean

### Viewing Alerts

1. Navigate to **Remittances → Alerts**
2. View all active alerts with filters:
   - **Status** - Unresolved, Resolved, All
   - **Severity** - Critical, Warning, Info
   - **Type** - Specific alert type
   - **Read Status** - Read, Unread

### Handling an Alert

1. Click on alert to view details
2. Review alert information and candidate data
3. Take appropriate action:
   - Contact candidate
   - Request proof upload
   - Verify remittance
4. Once resolved, add resolution notes
5. Click **"Resolve"** or **"Dismiss"**

### Resolving an Alert

1. Open alert details
2. Click **"Resolve"** button
3. Enter resolution notes (required)
4. Click **"Confirm"**

**Example resolution notes:**
- "Contacted candidate, proof uploaded"
- "Remittance verified, issue resolved"
- "Candidate provided explanation"

### Dismissing an Alert

For non-actionable alerts:

1. Open alert details
2. Click **"Dismiss"**
3. Alert is marked as resolved

**Note:** Dismissed alerts cannot be reopened.

### Bulk Actions

For multiple alerts:

1. Select alerts using checkboxes
2. Choose action from dropdown:
   - Mark as Read
   - Resolve Selected
   - Dismiss Selected
3. Confirm action

---

## Viewing Reports

### Accessing Reports

Navigate to **Remittances → Reports** to access analytics dashboard.

### Available Reports

#### 1. Dashboard Overview

Shows comprehensive statistics:
- Total remittances and amounts
- Monthly trends
- Status breakdown
- Proof compliance rate
- Year-over-year growth

#### 2. Monthly Report

View remittances by month:
- Filter by year
- Monthly totals and averages
- Visual trend charts
- Export to Excel/PDF

#### 3. Purpose Analysis

Understand why candidates send remittances:
- Breakdown by purpose (Family Support, Education, etc.)
- Amounts and percentages
- Trend over time

#### 4. Proof Compliance Report

Track documentation compliance:
- Overall compliance rate
- Compliance by purpose
- Monthly compliance trends
- List of remittances without proof

#### 5. Beneficiary Report

Analyze recipient patterns:
- Total beneficiaries
- Active vs inactive
- Relationship breakdown
- Banking information completeness

#### 6. Impact Analytics

Measure economic impact:
- Total inflow to Pakistan
- Families benefited
- Average per family
- Purpose breakdown
- Estimated multiplier effect

#### 7. Country Analysis

View remittances by destination:
- Remittances by country
- Average amounts per country
- Candidate count per country

#### 8. Top Remitters

See highest contributing candidates:
- Top 10/25/50 candidates
- Total amounts sent
- Remittance frequency
- Average amounts

### Filtering Reports

Most reports support filtering:
- **Date Range** - Custom start and end dates
- **Year** - Specific year
- **Month** - Specific month
- **Purpose** - Filter by purpose
- **Status** - Verified, Pending, Flagged
- **Candidate** - Specific candidate

### Exporting Reports

1. Open desired report
2. Click **"Export"** button
3. Choose format:
   - **Excel** - For data analysis
   - **PDF** - For printing/sharing
   - **CSV** - For external systems
4. File downloads automatically

---

## Managing Beneficiaries

### Adding a Beneficiary

1. Navigate to **Remittances → Beneficiaries**
2. Click **"Add Beneficiary"**
3. Fill in information:
   - Candidate
   - Beneficiary name
   - Relationship (Spouse, Parent, Child, etc.)
   - Contact information
   - Banking details (Account, IBAN)
   - Mobile wallet (if applicable)
4. Set as primary beneficiary (optional)
5. Click **"Save"**

### Editing Beneficiary Information

1. Find beneficiary in list
2. Click **"Edit"**
3. Update information
4. Click **"Update"**

### Setting Primary Beneficiary

Each candidate can have one primary beneficiary:

1. Open beneficiary record
2. Click **"Set as Primary"**
3. Previous primary beneficiary is automatically unmarked

### Deactivating a Beneficiary

1. Open beneficiary record
2. Click **"Deactivate"**
3. Beneficiary marked as inactive (not deleted)

**Note:** Inactive beneficiaries don't appear in active lists but maintain historical data.

---

## Common Tasks

### Tracking a Candidate's Remittances

1. Go to **Candidates** module
2. Search for candidate
3. Open candidate profile
4. Click **"Remittances"** tab
5. View complete remittance history with summary

### Finding a Specific Remittance

**By Transaction Reference:**
1. Use search box in remittances list
2. Enter transaction reference
3. Results appear instantly

**By Date Range:**
1. Click **"Filter"** button
2. Select date range
3. Apply filter

**By Amount:**
1. Click **"Advanced Search"**
2. Enter minimum/maximum amount
3. Search

### Generating Monthly Summary

1. Go to **Reports → Monthly Report**
2. Select desired month and year
3. Click **"Generate Report"**
4. Review statistics
5. Export if needed

### Checking Compliance Rate

1. Go to **Reports → Proof Compliance**
2. View overall compliance percentage
3. Identify remittances without proof
4. Follow up with candidates

### Following Up on Alerts

**Daily Routine:**
1. Check alert dashboard (Home → Alerts)
2. Review critical alerts first (red)
3. Contact relevant candidates
4. Request missing documentation
5. Resolve or dismiss as appropriate

**Weekly Review:**
1. Generate alert statistics report
2. Identify patterns (specific candidates, countries)
3. Plan proactive interventions

---

## Troubleshooting

### Common Issues

#### Cannot Save Remittance

**Error: "Transaction reference already exists"**
- Solution: Use a unique transaction reference
- Each remittance must have unique reference number

**Error: "Amount must be positive"**
- Solution: Enter positive amount value
- Negative amounts not allowed

**Error: "Transfer date cannot be in the future"**
- Solution: Select past or current date
- Future dates not accepted

#### Upload Failed

**Error: "File too large"**
- Solution: Reduce file size to under 5MB
- Compress PDF or reduce image resolution

**Error: "Invalid file type"**
- Solution: Use PDF, JPG, or PNG format
- Other file types not supported

#### Alert Not Generating

**Issue: Expected alert not appearing**
- Wait for automatic generation (runs daily)
- Or click **"Generate Alerts Now"** (Admin only)
- Check alert thresholds in configuration

#### Report Shows Zero Data

**Issue: Report empty or showing zeros**
- Check date range filter
- Verify data exists for selected criteria
- Clear filters and try again

### Getting Help

**For Technical Issues:**
- Contact IT Support
- Email: support@btevta.gov.pk
- Phone: [Support Number]

**For Process Questions:**
- Contact Remittance Coordinator
- Refer to Admin Manual
- Check system notifications

---

## Best Practices

### Data Entry

1. **Enter remittances promptly** - Record within 24 hours of notification
2. **Verify information** - Double-check transaction references and amounts
3. **Upload proof immediately** - Don't wait to upload receipts
4. **Use consistent naming** - Standardize location names (e.g., "Riyadh, Saudi Arabia")
5. **Complete all fields** - Fill optional fields when information is available

### Alert Management

1. **Review daily** - Check alerts every morning
2. **Prioritize critical** - Handle critical alerts first
3. **Document actions** - Always add resolution notes
4. **Be proactive** - Contact candidates before alerts become critical
5. **Track patterns** - Note recurring issues for policy improvements

### Reporting

1. **Regular reviews** - Generate monthly reports consistently
2. **Share insights** - Distribute relevant reports to management
3. **Archive reports** - Save important reports for records
4. **Compare periods** - Track month-over-month and year-over-year trends
5. **Act on data** - Use insights to improve candidate support

### Data Quality

1. **Validate entries** - Review data before saving
2. **Update beneficiaries** - Keep beneficiary information current
3. **Request proof** - Always ask for transfer receipts
4. **Verify amounts** - Cross-check with proof documents
5. **Clean data regularly** - Review and correct inconsistencies

---

## Keyboard Shortcuts

- **Ctrl + N** - New Remittance
- **Ctrl + F** - Search
- **Ctrl + E** - Export current view
- **Esc** - Close modal/dialog
- **Enter** - Submit form

---

## Glossary

- **Remittance** - Money transfer from deployed candidate to family in Pakistan
- **Beneficiary** - Person receiving the remittance
- **Proof** - Receipt or documentation of money transfer
- **Verification** - Official confirmation that remittance is valid
- **Alert** - Automated notification of potential issue
- **Compliance Rate** - Percentage of remittances with proof documentation
- **Primary Purpose** - Main reason for sending money
- **Transaction Reference** - Unique identifier for money transfer
- **Deployment** - Candidate's overseas work assignment

---

## Appendix: Field Definitions

| Field | Description | Required | Example |
|-------|-------------|----------|---------|
| Transaction Reference | Unique ID from bank/transfer service | Yes | TXN123456789 |
| Amount | Transfer amount in PKR | Yes | 50000 |
| Currency | Local currency (defaults to PKR) | Yes | PKR |
| Foreign Amount | Amount in foreign currency | No | 150 USD |
| Transfer Date | Date money was sent | Yes | 2025-11-01 |
| Transfer Method | How money was sent | Yes | Bank Transfer |
| Sender Name | Person sending money | Yes | Muhammad Ahmad |
| Sender Location | Sender's city and country | No | Riyadh, Saudi Arabia |
| Receiver Name | Person receiving money | Yes | Fatima Ahmad |
| Receiver Account | Bank account or IBAN | No | PK36MEZN0000001234567890 |
| Bank Name | Receiving bank name | No | Habib Bank Limited |
| Primary Purpose | Main reason for transfer | Yes | Family Support |
| Purpose Description | Additional details | No | Monthly household expenses |
| Has Proof | Whether receipt is available | No | Yes/No |

---

**Document Version:** 1.0
**Last Updated:** November 2025
**For questions or feedback, contact:** remittance-admin@btevta.gov.pk
