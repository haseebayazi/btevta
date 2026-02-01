# Module 1: Candidate Listing & Pre-Departure Documents

**Version:** 1.5.0
**Release Date:** February 2026
**Status:** Ready for Testing

---

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [User Interface](#user-interface)
- [User Roles & Permissions](#user-roles--permissions)
- [Workflow](#workflow)
- [Technical Details](#technical-details)
- [Testing Checklist](#testing-checklist)

---

## Overview

Module 1 provides comprehensive candidate management from initial listing through pre-departure document collection. This module ensures all required documentation is collected and verified before candidates can proceed to the screening phase.

### Key Capabilities

- **Candidate Listing:** Import, search, filter, and manage candidates
- **Pre-Departure Documents:** Collect and verify required documents
- **Bulk Operations:** Perform actions on multiple candidates simultaneously
- **Progress Tracking:** Visual indicators for document completion status
- **Reports:** Generate individual and bulk document reports

---

## Features

### 1. Candidate Listing

#### 1.1 Import from Excel
- Download standardized Excel template
- Bulk import candidates with validation
- Auto-assignment of "Listed" status

#### 1.2 Search & Filter
| Filter | Description |
|--------|-------------|
| Search | Name, CNIC, or TheLeap ID |
| Status | All candidate workflow statuses |
| Trade | Filter by assigned trade |
| Batch | Filter by assigned batch |

#### 1.3 Bulk Operations
| Operation | Description | Admin Only |
|-----------|-------------|------------|
| Change Status | Update status for selected candidates | No |
| Assign Batch | Assign candidates to a batch | No |
| Export | Download as CSV, Excel, or PDF | No |
| Delete | Permanently remove candidates | Yes |

### 2. Pre-Departure Documents

#### 2.1 Mandatory Documents
| Document | Code | Description |
|----------|------|-------------|
| CNIC Front & Back | CNIC | National ID card (both sides) |
| Passport Pages | PASSPORT | 1st and 2nd pages of valid passport |
| Domicile Certificate | DOMICILE | Proof of permanent residence |
| Family Registration Certificate | FRC | NADRA family registration |
| Police Character Certificate | PCC | Police clearance/good conduct |

#### 2.2 Optional Documents
| Document | Code | Description |
|----------|------|-------------|
| Driving License | DL | If applicable for the trade |
| Professional License | PL | Trade-specific certifications |
| Pre-Medical Reports | PMR | Optional health documentation |

#### 2.3 Document Management
- **Upload:** PDF, JPG, PNG (max 5MB per file)
- **Download:** Retrieve any uploaded document
- **Verify:** Mark documents as verified with optional notes
- **Reject:** Return documents with reason for re-upload
- **Delete:** Remove documents (restricted by status)

#### 2.4 Licenses Management
- Add driving or professional licenses
- Track license numbers, categories, and expiry dates
- Upload license copy documents
- Visual status indicators (Active, Expiring Soon, Expired)

### 3. Reports

#### 3.1 Individual Candidate Report
- PDF format with document checklist
- Excel format for data analysis
- Includes verification status and timestamps

#### 3.2 Bulk Reports
- Filter by campus, status, or date range
- Summary of document completion across candidates

---

## User Interface

### Candidate Listing Page (`/candidates/`)

```
┌─────────────────────────────────────────────────────────────┐
│  Candidates Listing              [Import Excel] [Add New]   │
├─────────────────────────────────────────────────────────────┤
│  [Search...] [Status ▼] [Trade ▼] [Batch ▼] [Search]       │
├─────────────────────────────────────────────────────────────┤
│  ☐ | Name          | TheLeap ID | CNIC    | Trade | Status │
│  ☐ | John Doe      | TL-001     | 12345...| Carp  | Listed │
│  ☐ | Jane Smith    | TL-002     | 67890...| Elec  | Screen │
└─────────────────────────────────────────────────────────────┘
```

### Pre-Departure Documents Page (`/candidates/{id}/pre-departure-documents`)

```
┌─────────────────────────────────────────────────────────────┐
│  Pre-Departure Documents                    [← Back]        │
├─────────────────────────────────────────────────────────────┤
│  [Candidate] [TheLeap ID] [Status: Listed] [Docs: 3/5]     │
├─────────────────────────────────────────────────────────────┤
│  ⚠️ Action Required: Upload remaining mandatory documents   │
├─────────────────────────────────────────────────────────────┤
│  MANDATORY DOCUMENTS (3/5)                                  │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐        │
│  │ ✓ CNIC     │  │ ✓ Passport  │  │ ⬆ Domicile │        │
│  │ [Download] │  │ [Download]  │  │ [Upload]    │        │
│  └─────────────┘  └─────────────┘  └─────────────┘        │
├─────────────────────────────────────────────────────────────┤
│  OPTIONAL DOCUMENTS                                         │
│  ┌─────────────┐  ┌─────────────┐                          │
│  │ ⬆ Driving  │  │ ⬆ Prof Lic │                          │
│  │ [Upload]    │  │ [Upload]    │                          │
│  └─────────────┘  └─────────────┘                          │
├─────────────────────────────────────────────────────────────┤
│  LICENSES                                    [+ Add License]│
│  (No licenses added)                                        │
├─────────────────────────────────────────────────────────────┤
│  GENERATE REPORTS                                           │
│  [📄 PDF Report]  [📊 Excel Report]                         │
└─────────────────────────────────────────────────────────────┘
```

---

## User Roles & Permissions

### Permission Matrix

| Action | Super Admin | Project Director | Campus Admin | OEP | Viewer |
|--------|:-----------:|:----------------:|:------------:|:---:|:------:|
| **Candidate Listing** |
| View all candidates | ✓ | ✓ | Own campus | Own | ✓ |
| Create candidate | ✓ | ✓ | ✓ | ✓ | ✗ |
| Edit candidate | ✓ | ✓ | Own campus | Own | ✗ |
| Delete candidate | ✓ | ✓ | ✗ | ✗ | ✗ |
| Bulk operations | ✓ | ✓ | Own campus | ✗ | ✗ |
| **Pre-Departure Documents** |
| View documents | ✓ | ✓ | Own campus | Own | ✓ |
| Upload documents | ✓ | ✓ | Own campus | Own | ✗ |
| Verify documents | ✓ | ✓ | Own campus | ✗ | ✗ |
| Reject documents | ✓ | ✓ | Own campus | ✗ | ✗ |
| Delete documents | ✓ | ✗ | Own campus | Own | ✗ |
| Generate reports | ✓ | ✓ | Own campus | Own | ✓ |

### Role Descriptions

- **Super Admin:** Full system access, can perform all operations
- **Project Director:** Full access except document deletion
- **Campus Admin:** Full access to their campus's candidates only
- **OEP (Overseas Employment Promoter):** Manage their assigned candidates
- **Viewer:** Read-only access to view data and reports

---

## Workflow

### Candidate Lifecycle (Module 1 Scope)

```
┌─────────────┐     ┌─────────────────────┐     ┌───────────┐
│   Import/   │────▶│  Pre-Departure      │────▶│ Screening │
│   Create    │     │  Documents          │     │  (Module 2)│
│  (Listed)   │     │  Collection         │     │           │
└─────────────┘     └─────────────────────┘     └───────────┘
                              │
                              ▼
                    ┌─────────────────────┐
                    │ All Mandatory Docs  │
                    │ Uploaded? ──────────│──▶ Block until complete
                    │      │              │
                    │      ▼              │
                    │ Documents Verified? │
                    │      │              │
                    │      ▼              │
                    │ Ready for Screening │
                    └─────────────────────┘
```

### Document Verification Flow

```
┌──────────┐     ┌──────────┐     ┌──────────────┐
│ Uploaded │────▶│ Pending  │────▶│   Verified   │
│          │     │ Review   │     │      ✓       │
└──────────┘     └────┬─────┘     └──────────────┘
                      │
                      ▼
                ┌──────────┐
                │ Rejected │────▶ Re-upload required
                │    ✗     │
                └──────────┘
```

---

## Technical Details

### Database Tables

| Table | Description |
|-------|-------------|
| `candidates` | Main candidate records |
| `pre_departure_documents` | Uploaded document records |
| `document_checklists` | Document type definitions |
| `candidate_licenses` | License records |

### API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/candidates` | List candidates with filters |
| GET | `/candidates/{id}` | View candidate details |
| POST | `/candidates` | Create new candidate |
| PUT | `/candidates/{id}` | Update candidate |
| DELETE | `/candidates/{id}` | Delete candidate |
| GET | `/candidates/{id}/pre-departure-documents` | View documents |
| POST | `/candidates/{id}/pre-departure-documents` | Upload document |
| POST | `/candidates/{id}/pre-departure-documents/{doc}/verify` | Verify document |
| POST | `/candidates/{id}/pre-departure-documents/{doc}/reject` | Reject document |
| DELETE | `/candidates/{id}/pre-departure-documents/{doc}` | Delete document |
| GET | `/candidates/{id}/pre-departure-documents/{doc}/download` | Download document |

### File Storage

- Documents stored in `storage/app/private/pre-departure-documents/`
- Organized by candidate ID
- Secure access through authenticated download routes
- File validation: magic bytes check, extension validation, size limits

---

## Testing Checklist

### Pre-Testing Setup

- [ ] Database seeded with document checklists: `php artisan db:seed --class=DocumentChecklistsSeeder`
- [ ] Storage link created: `php artisan storage:link`
- [ ] Test users created for each role
- [ ] Sample PDF, JPG, PNG files prepared (under 5MB)

---

### 1. Candidate Listing Tests

#### 1.1 Access & Display
- [ ] Navigate to `/candidates/` - page loads without errors
- [ ] Navigate to `/dashboard/candidates-listing` - page loads without errors
- [ ] Both pages display the same candidates
- [ ] Table shows columns: Name, TheLeap ID, CNIC, Campus, Trade, Status

#### 1.2 Search Functionality
- [ ] Search by full name - returns matching candidates
- [ ] Search by partial name - returns matching candidates
- [ ] Search by CNIC - returns matching candidate
- [ ] Search by TheLeap ID - returns matching candidate
- [ ] Search with no results - shows "No candidates found" message
- [ ] Clear search - shows all candidates again

#### 1.3 Filter Functionality
- [ ] Filter by Status "Listed" - shows only listed candidates
- [ ] Filter by Status "Screening" - shows only screening candidates
- [ ] Filter by Trade - shows only candidates with selected trade
- [ ] Filter by Batch - shows only candidates in selected batch
- [ ] Combine multiple filters - results match all criteria
- [ ] Reset filters - shows all candidates

#### 1.4 Bulk Operations (as Admin)
- [ ] Select single candidate - checkbox checked
- [ ] Select all candidates - all checkboxes checked
- [ ] Clear selection - all checkboxes unchecked
- [ ] Bulk status change - confirm dialog appears, status updated
- [ ] Bulk batch assignment - candidates assigned to batch
- [ ] Bulk export CSV - file downloads
- [ ] Bulk export Excel - file downloads
- [ ] Bulk export PDF - file downloads
- [ ] Bulk delete - confirm dialog (twice), candidates deleted

#### 1.5 Pagination
- [ ] Navigate to next page - shows different candidates
- [ ] Navigate to previous page - returns to original candidates
- [ ] Page size is 20 candidates

---

### 2. Pre-Departure Documents Tests

#### 2.1 Access
- [ ] From candidate profile, click "Pre-Departure Documents" card - navigates to documents page
- [ ] From candidate profile, click "Upload Documents" button - navigates to documents page
- [ ] Direct URL `/candidates/{id}/pre-departure-documents` - page loads
- [ ] Non-existent candidate ID - shows 404 error

#### 2.2 Page Display
- [ ] Header shows candidate name and TheLeap ID
- [ ] Status cards display correct information
- [ ] Progress shows X/Y mandatory documents
- [ ] Mandatory documents section shows red header
- [ ] Optional documents section shows blue header
- [ ] Licenses section shows purple header

#### 2.3 Document Upload
- [ ] Click upload button on empty document card - form appears
- [ ] Select PDF file - file name shown
- [ ] Select JPG file - file name shown
- [ ] Select PNG file - file name shown
- [ ] Add optional notes - text saved
- [ ] Submit upload - success message, card shows uploaded state
- [ ] Upload file > 5MB - error message
- [ ] Upload invalid file type (.exe) - error message

#### 2.4 Document Actions (Uploaded State)
- [ ] Download button - file downloads correctly
- [ ] File name matches original upload
- [ ] File size displayed correctly
- [ ] Upload date displayed correctly
- [ ] Uploader name displayed

#### 2.5 Document Verification (as Admin/Project Director)
- [ ] Verify button visible - click opens modal
- [ ] Enter verification notes - submit
- [ ] Document shows "Verified" badge
- [ ] Verifier name and date displayed
- [ ] Verify button no longer visible

#### 2.6 Document Rejection (as Admin/Project Director)
- [ ] Reject button visible - click opens modal
- [ ] Enter rejection reason (required) - submit
- [ ] Document shows rejection notes
- [ ] Document can be re-uploaded/deleted

#### 2.7 Document Deletion
- [ ] Delete button visible - click shows confirmation
- [ ] Confirm deletion - document removed
- [ ] Document card returns to upload state

#### 2.8 Progress Tracking
- [ ] Upload 1 mandatory document - progress shows 1/5
- [ ] Upload all mandatory documents - progress shows 5/5
- [ ] Warning message disappears when all mandatory uploaded
- [ ] Success message appears when complete

---

### 3. Licenses Tests

#### 3.1 Add License
- [ ] Click "Add License" button - modal opens
- [ ] Select "Driving License" type - category field shown
- [ ] Select "Professional License" type - category field hidden
- [ ] Enter required fields - submit
- [ ] License appears in table

#### 3.2 License Display
- [ ] License type badge shows correctly
- [ ] License name displayed
- [ ] License number displayed
- [ ] Category displayed (if driving)
- [ ] Issue date displayed
- [ ] Expiry date displayed
- [ ] Status badge: Active (green), Expiring Soon (yellow), Expired (red)

#### 3.3 Delete License
- [ ] Delete button visible - click shows confirmation
- [ ] Confirm deletion - license removed from table

---

### 4. Reports Tests

#### 4.1 PDF Report
- [ ] Click "PDF Report" button - download starts
- [ ] File is valid PDF
- [ ] Report contains candidate information
- [ ] Report lists all documents with status

#### 4.2 Excel Report
- [ ] Click "Excel Report" button - download starts
- [ ] File is valid Excel format
- [ ] Data matches displayed information

---

### 5. Role-Based Access Tests

#### 5.1 Super Admin
- [ ] Can view all candidates
- [ ] Can upload documents for any candidate
- [ ] Can verify documents
- [ ] Can reject documents
- [ ] Can delete documents
- [ ] Can delete candidates

#### 5.2 Project Director
- [ ] Can view all candidates
- [ ] Can upload documents
- [ ] Can verify documents
- [ ] Can reject documents
- [ ] Cannot delete documents (button hidden)

#### 5.3 Campus Admin
- [ ] Can only view own campus candidates
- [ ] Can upload documents for own campus
- [ ] Can verify documents for own campus
- [ ] Cannot see other campus candidates

#### 5.4 OEP
- [ ] Can only view assigned candidates
- [ ] Can upload documents for assigned candidates
- [ ] Cannot verify documents (button hidden)
- [ ] Cannot reject documents (button hidden)

#### 5.5 Viewer
- [ ] Can view candidates (read-only)
- [ ] Cannot upload documents (button hidden)
- [ ] Cannot verify/reject documents
- [ ] Can download reports

---

### 6. Edge Cases & Error Handling

#### 6.1 Status Restrictions
- [ ] Candidate in "Screening" status - documents read-only
- [ ] Candidate in "Training" status - documents read-only
- [ ] Only "Listed" and "Pre-Departure Docs" status allow edits

#### 6.2 Verified Document Protection
- [ ] Verified document - delete button hidden for non-admin
- [ ] Verified document - cannot be re-uploaded without deletion

#### 6.3 Error Handling
- [ ] Network error during upload - error message shown
- [ ] Session timeout - redirected to login
- [ ] Unauthorized access - 403 error page

---

### Test Sign-Off

| Test Section | Tester | Date | Pass/Fail | Notes |
|--------------|--------|------|-----------|-------|
| 1. Candidate Listing | | | | |
| 2. Pre-Departure Documents | | | | |
| 3. Licenses | | | | |
| 4. Reports | | | | |
| 5. Role-Based Access | | | | |
| 6. Edge Cases | | | | |

**Overall Status:** ________________

**Tested By:** ________________
**Date:** ________________
**Approved By:** ________________

---

## Known Issues

*None currently reported*

---

## Support

For technical issues or questions:
- Email: [support email]
- Documentation: `/docs/` folder in project root

---

*Document Version: 1.0 | Last Updated: February 2026*
