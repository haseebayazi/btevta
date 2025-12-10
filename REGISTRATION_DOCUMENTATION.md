# Registration Module Documentation

## Overview

The **Registration** module manages the formal registration process for candidates who have successfully passed the screening stage. It handles document collection, next of kin information, undertakings, and final registration approval.

---

## Purpose

After a candidate passes the screening call, they must complete formal registration before entering training. This module ensures all required documentation and information is collected before candidates can proceed.

---

## Who Can Access

- **Admin**: Full access to all registration records
- **Campus Admin**: Access to candidates from their assigned campus only

---

## Candidate Registration Workflow

### Stage 1: Screening Passed
Candidates appear in the registration module when their status is:
- `screening_passed` - Passed screening, pending registration
- `pending_registration` - Registration in progress
- `registered` - Registration completed

### Stage 2: Document Collection

#### Required Documents
The following documents **must** be uploaded to complete registration:
1. **CNIC** (Computerized National Identity Card)
2. **Passport**
3. **Education Certificate**
4. **Police Clearance Certificate**

#### Optional Documents
- Medical Certificate
- Photo
- Other supporting documents

#### Document Upload Process
**Location**: `/registration/{candidate}/show`

**Steps**:
1. Select document type from dropdown
2. Enter document number (if applicable)
3. Provide issue date and expiry date (for documents with validity)
4. Upload file (PDF, JPG, PNG - max 5MB)
5. Click upload button

**Validation**:
- File types: PDF, JPG, JPEG, PNG only
- Maximum size: 5MB
- Documents are stored in: `storage/app/public/candidates/documents/`

---

### Stage 3: Next of Kin Information

**Purpose**: Emergency contact person for the candidate

**Required Fields**:
- Name (required)
- Relationship to candidate (required)
- CNIC - 13 digits (required)
- Phone number (required)
- Complete address (required)
- Occupation (optional)
- Email address (optional)

**Important**: Each candidate can have only ONE next of kin record. Updating overwrites the previous record.

---

### Stage 4: Undertakings

**Purpose**: Formal written commitments signed by the candidate

**Undertaking Types**:
1. **Employment**: Commitment to work in Pakistan after training
2. **Financial**: Acknowledgment of financial obligations
3. **Behavior**: Commitment to maintain discipline and conduct
4. **Other**: Custom undertaking types

**Fields**:
- Undertaking type (required)
- Content/Text of undertaking (required, max 2000 chars)
- Digital signature image (optional - JPG, PNG, max 1MB)
- Witness name (optional)
- Witness CNIC (optional - 13 digits)

**Note**: Candidates must sign at least ONE undertaking to complete registration.

---

### Stage 5: Complete Registration

**Button**: "Complete Registration" (appears on candidate's registration detail page)

**Pre-completion Checks**:
The system validates that ALL requirements are met:

✅ **Required Documents Uploaded**:
- CNIC
- Passport
- Education Certificate
- Police Clearance

✅ **Next of Kin Added**

✅ **At Least One Undertaking Signed**

If any requirement is missing, the system displays an error message indicating what's missing.

**Upon Successful Completion**:
1. Candidate status changes to `registered`
2. `registered_at` timestamp is recorded
3. Activity log is created
4. Candidate becomes eligible for training assignment

---

## Navigation

### Main Registration List
**URL**: `/registration`

**Displays**:
- Total pending registrations (card at top)
- Table of all candidates pending registration

**Table Columns**:
- BTEVTA ID
- Name
- CNIC
- Campus
- Trade
- Documents count (badge: green if uploaded, orange if none)
- Next of Kin status (green checkmark if added, red X if missing)
- Current status
- Actions (Manage, View Profile)

### Individual Registration Page
**URL**: `/registration/{candidate}/show`

**Sections**:
1. **Candidate Information Card**: Basic details, campus, trade
2. **Registration Documents**:
   - Upload form
   - List of uploaded documents with download/delete options
3. **Next of Kin Information**:
   - Form to add/edit next of kin
   - Display existing next of kin details
4. **Undertakings**:
   - Form to create new undertaking
   - List of signed undertakings
5. **Complete Registration Button** (if all requirements met)

---

## Routes

```php
// List all candidates pending registration
GET /registration - registration.index

// View individual candidate registration details
GET /registration/{candidate} - registration.show

// Upload a document
POST /registration/{candidate}/upload-document - registration.upload-document

// Delete a document
DELETE /documents/{document} - registration.delete-document

// Save next of kin information
POST /registration/{candidate}/next-of-kin - registration.save-next-of-kin

// Save undertaking
POST /registration/{candidate}/undertaking - registration.save-undertaking

// Complete registration
POST /registration/{candidate}/complete - registration.complete
```

---

## Authorization Rules

**View Registration List**:
- Admin: Can see all candidates
- Campus Admin: Can only see candidates from their campus

**Manage Registration**:
- Admin: Can manage any candidate
- Campus Admin: Can only manage candidates from their campus

**Delete Documents**:
- Campus admins can only delete documents for candidates from their campus
- Additional ownership validation prevents unauthorized deletions

---

## File Storage

### Documents
- **Path**: `storage/app/public/candidates/documents/`
- **Accessible URL**: `/storage/candidates/documents/{filename}`

### Signatures
- **Path**: `storage/app/public/undertakings/signatures/`
- **Accessible URL**: `/storage/undertakings/signatures/{filename}`

**Important**: Files are automatically deleted if database operations fail (transaction rollback protection).

---

## Activity Logging

All registration activities are logged for audit purposes:

- Document uploaded: `{document_type}`
- Document deleted: `{document_type}`
- Next of kin information saved
- Undertaking signed: `{undertaking_type}`
- Registration completed

Access activity logs via the candidate profile or system logs.

---

## Common Issues & Solutions

### Issue 1: "Missing required documents" error
**Solution**: Ensure all 4 required documents are uploaded:
- CNIC
- Passport
- Education Certificate
- Police Clearance

### Issue 2: Cannot complete registration
**Check**:
1. Are all 4 required documents uploaded?
2. Is next of kin information added?
3. Has at least one undertaking been signed?

### Issue 3: Document upload fails
**Possible causes**:
- File too large (max 5MB)
- Wrong file format (only PDF, JPG, JPEG, PNG allowed)
- Storage permissions issue

### Issue 4: Campus admin cannot see candidate
**Reason**: Candidate is assigned to a different campus. Campus admins can only see candidates from their assigned campus.

---

## Database Tables

### `registration_documents`
Stores uploaded candidate documents

### `next_of_kin`
Stores emergency contact information (one per candidate)

### `undertakings`
Stores signed undertakings (multiple per candidate)

---

## Next Steps After Registration

Once registration is completed (`registered` status), candidates can be:

1. **Assigned to Training Batches** (via Training module)
2. **Viewed in Dashboard** → Training tab
3. **Progressed through training** until completion

---

## Best Practices

1. **Verify Documents**: Review uploaded documents for authenticity before completing registration
2. **Complete Information**: Ensure all next of kin fields are accurate for emergency situations
3. **Clear Undertakings**: Write undertakings in clear, understandable language
4. **Regular Monitoring**: Check registration list regularly to avoid backlogs
5. **Batch Processing**: Process multiple candidates together for efficiency

---

## Support

For technical issues or questions about the registration module, contact the system administrator or refer to the main application documentation.

---

**Last Updated**: 2025-12-10
**Module Status**: Fully Functional ✅
**Issue Reference**: Resolves Issue #6 from user testing feedback
