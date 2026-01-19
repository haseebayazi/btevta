# WASL v3 API Documentation

## Overview

The WASL v3 API provides RESTful endpoints for managing the enhanced Workforce Abroad Skills & Linkages system. This includes programs, implementing partners, employers, courses, assessments, and enhanced workflow management.

**Base URL:** `/api/v1/`
**Authentication:** Web session authentication (required)
**Throttling:** 60 requests per minute
**Response Format:** JSON
**Version:** 3.0.0
**Last Updated:** January 19, 2026

---

## Table of Contents

1. [Programs API](#programs-api)
2. [Implementing Partners API](#implementing-partners-api)
3. [Employers API](#employers-api)
4. [Courses API](#courses-api)
5. [Document Checklists API](#document-checklists-api)
6. [Pre-Departure Documents API](#pre-departure-documents-api)
7. [Training Assessments API](#training-assessments-api)
8. [Post-Departure Details API](#post-departure-details-api)
9. [Success Stories API](#success-stories-api)
10. [Employment History API](#employment-history-api)
11. [Response Formats](#response-formats)
12. [Error Handling](#error-handling)

---

## Programs API

### 1. List Programs

**GET** `/api/v1/programs`

Get a paginated list of training programs.

**Query Parameters:**
- `is_active` (optional, boolean) - Filter by active status
- `search` (optional, string) - Search by name or description
- `per_page` (optional, default: 20) - Items per page

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Technical Education & Vocational Training",
      "code": "TEC",
      "description": "Comprehensive technical training program",
      "duration_weeks": 12,
      "is_active": true,
      "created_at": "2026-01-15T10:30:00Z",
      "updated_at": "2026-01-15T10:30:00Z"
    }
  ],
  "current_page": 1,
  "last_page": 5,
  "per_page": 20,
  "total": 85
}
```

---

### 2. Get Single Program

**GET** `/api/v1/programs/{id}`

Get detailed information about a specific program.

**Response:**
```json
{
  "id": 1,
  "name": "Technical Education & Vocational Training",
  "code": "TEC",
  "description": "Comprehensive technical training program",
  "duration_weeks": 12,
  "is_active": true,
  "candidates_count": 450,
  "created_at": "2026-01-15T10:30:00Z",
  "updated_at": "2026-01-15T10:30:00Z"
}
```

---

### 3. Create Program

**POST** `/api/v1/programs`

Create a new training program.

**Request Body:**
```json
{
  "name": "Advanced Technical Training",
  "code": "ATT",
  "description": "Advanced level technical skills development",
  "duration_weeks": 16,
  "is_active": true
}
```

**Validation Rules:**
- `name`: required, string, max:255, unique
- `code`: required, string, max:10, unique
- `description`: nullable, string
- `duration_weeks`: required, integer, min:1, max:52
- `is_active`: boolean, default: true

**Response:** HTTP 201 Created
```json
{
  "id": 2,
  "name": "Advanced Technical Training",
  "code": "ATT",
  "description": "Advanced level technical skills development",
  "duration_weeks": 16,
  "is_active": true,
  "created_at": "2026-01-19T14:30:00Z",
  "updated_at": "2026-01-19T14:30:00Z"
}
```

---

### 4. Update Program

**PUT/PATCH** `/api/v1/programs/{id}`

Update an existing program.

**Request Body:** (All fields optional)
```json
{
  "name": "Updated Program Name",
  "duration_weeks": 14,
  "is_active": false
}
```

**Response:** HTTP 200 OK (Returns updated program object)

---

### 5. Delete Program

**DELETE** `/api/v1/programs/{id}`

Soft delete a program (can be restored later).

**Response:** HTTP 204 No Content

**Note:** Programs with assigned candidates cannot be deleted. Returns HTTP 422 with error message if constraint violated.

---

## Implementing Partners API

### 1. List Implementing Partners

**GET** `/api/v1/implementing-partners`

Get a paginated list of implementing partners.

**Query Parameters:**
- `is_active` (optional, boolean) - Filter by active status
- `search` (optional, string) - Search by name, contact person, or email
- `per_page` (optional, default: 20) - Items per page

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "National Skills Development Corporation",
      "contact_person": "Ahmed Khan",
      "contact_email": "ahmed@nsdc.gov.pk",
      "contact_phone": "+92-51-9204567",
      "address": "Islamabad, Pakistan",
      "is_active": true,
      "candidates_count": 234,
      "created_at": "2026-01-15T10:30:00Z"
    }
  ],
  "current_page": 1,
  "last_page": 3,
  "per_page": 20,
  "total": 45
}
```

---

### 2. Get Single Implementing Partner

**GET** `/api/v1/implementing-partners/{id}`

Get detailed information about a specific implementing partner.

**Response:**
```json
{
  "id": 1,
  "name": "National Skills Development Corporation",
  "contact_person": "Ahmed Khan",
  "contact_email": "ahmed@nsdc.gov.pk",
  "contact_phone": "+92-51-9204567",
  "address": "Islamabad, Pakistan",
  "is_active": true,
  "candidates_count": 234,
  "created_at": "2026-01-15T10:30:00Z",
  "updated_at": "2026-01-15T10:30:00Z"
}
```

---

### 3. Create Implementing Partner

**POST** `/api/v1/implementing-partners`

Create a new implementing partner.

**Request Body:**
```json
{
  "name": "Skills Training Institute",
  "contact_person": "Fatima Ahmed",
  "contact_email": "fatima@sti.org",
  "contact_phone": "+92-42-3567890",
  "address": "Lahore, Pakistan",
  "is_active": true
}
```

**Validation Rules:**
- `name`: required, string, max:255, unique
- `contact_person`: required, string, max:255
- `contact_email`: required, email, unique
- `contact_phone`: required, string, max:50
- `address`: nullable, string
- `is_active`: boolean, default: true

**Response:** HTTP 201 Created

---

### 4. Update Implementing Partner

**PUT/PATCH** `/api/v1/implementing-partners/{id}`

Update an existing implementing partner.

**Response:** HTTP 200 OK

---

### 5. Delete Implementing Partner

**DELETE** `/api/v1/implementing-partners/{id}`

Soft delete an implementing partner.

**Response:** HTTP 204 No Content

---

## Employers API

### 1. List Employers

**GET** `/api/v1/employers`

Get a paginated list of employers.

**Query Parameters:**
- `country_id` (optional, integer) - Filter by country
- `is_active` (optional, boolean) - Filter by active status
- `search` (optional, string) - Search by company name or permission number
- `per_page` (optional, default: 20) - Items per page

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "permission_number": "KSA-2025-12345",
      "visa_issuing_company": "ARAMCO",
      "company_name": "Saudi Industrial Services",
      "country_id": 1,
      "country": {
        "id": 1,
        "name": "Saudi Arabia",
        "code": "SAU"
      },
      "sector": "Industrial",
      "trade": "Welding",
      "basic_salary": 2500.00,
      "currency": "SAR",
      "food_by_company": true,
      "transport_by_company": true,
      "accommodation_by_company": true,
      "other_conditions": "Medical insurance provided",
      "current_candidates_count": 15,
      "is_active": true,
      "created_at": "2026-01-15T10:30:00Z"
    }
  ],
  "current_page": 1,
  "last_page": 8,
  "per_page": 20,
  "total": 152
}
```

---

### 2. Get Single Employer

**GET** `/api/v1/employers/{id}`

Get detailed information about a specific employer.

**Response:**
```json
{
  "id": 1,
  "permission_number": "KSA-2025-12345",
  "visa_issuing_company": "ARAMCO",
  "company_name": "Saudi Industrial Services",
  "country_id": 1,
  "country": {
    "id": 1,
    "name": "Saudi Arabia",
    "code": "SAU"
  },
  "sector": "Industrial",
  "trade": "Welding",
  "basic_salary": 2500.00,
  "currency": "SAR",
  "food_by_company": true,
  "transport_by_company": true,
  "accommodation_by_company": true,
  "other_conditions": "Medical insurance provided",
  "evidence_path": "employers/evidence/12345.pdf",
  "evidence_url": "https://storage.example.com/employers/evidence/12345.pdf",
  "current_candidates": [
    {
      "id": 123,
      "full_name": "Muhammad Ali",
      "cnic": "12345-1234567-1",
      "assigned_at": "2025-12-01",
      "assigned_by": {
        "id": 1,
        "name": "Admin User"
      }
    }
  ],
  "is_active": true,
  "created_at": "2026-01-15T10:30:00Z",
  "updated_at": "2026-01-15T10:30:00Z"
}
```

---

### 3. Create Employer

**POST** `/api/v1/employers`

Create a new employer record.

**Request Body:** (multipart/form-data)
```
permission_number: KSA-2025-67890
visa_issuing_company: SABIC
company_name: Saudi Basic Industries
country_id: 1
sector: Manufacturing
trade: Electrician
basic_salary: 2800.00
currency: SAR
food_by_company: true
transport_by_company: true
accommodation_by_company: true
other_conditions: Annual leave 30 days
evidence_document: [FILE]
```

**Validation Rules:**
- `permission_number`: required, string, max:255, unique
- `visa_issuing_company`: required, string, max:255
- `company_name`: nullable, string, max:255
- `country_id`: required, exists:countries,id
- `sector`: nullable, string, max:255
- `trade`: nullable, string, max:255
- `basic_salary`: required, numeric, min:0
- `currency`: required, string, max:10
- `food_by_company`: boolean
- `transport_by_company`: boolean
- `accommodation_by_company`: boolean
- `other_conditions`: nullable, string
- `evidence_document`: nullable, file, mimes:pdf,jpg,jpeg,png, max:5120

**Response:** HTTP 201 Created

---

### 4. Update Employer

**PUT/PATCH** `/api/v1/employers/{id}`

Update an existing employer record.

**Response:** HTTP 200 OK

---

### 5. Delete Employer

**DELETE** `/api/v1/employers/{id}`

Soft delete an employer.

**Response:** HTTP 204 No Content

**Note:** Employers with current candidate assignments cannot be deleted.

---

### 6. Assign Candidates to Employer

**POST** `/api/v1/employers/{id}/assign-candidates`

Assign candidates to an employer.

**Request Body:**
```json
{
  "candidate_ids": [123, 456, 789]
}
```

**Validation Rules:**
- `candidate_ids`: required, array
- `candidate_ids.*`: required, exists:candidates,id

**Response:** HTTP 200 OK
```json
{
  "message": "3 candidates assigned successfully",
  "assigned_count": 3,
  "employer_id": 1
}
```

---

### 7. Get Employer's Current Candidates

**GET** `/api/v1/employers/{id}/current-candidates`

Get list of candidates currently assigned to this employer.

**Response:**
```json
{
  "data": [
    {
      "id": 123,
      "full_name": "Muhammad Ali",
      "cnic": "12345-1234567-1",
      "trade": "Welding",
      "assigned_at": "2025-12-01",
      "assigned_by": {
        "id": 1,
        "name": "Admin User"
      }
    }
  ]
}
```

---

## Courses API

### 1. List Courses

**GET** `/api/v1/courses`

Get a paginated list of training courses.

**Query Parameters:**
- `training_type` (optional, enum) - Filter by type: technical, soft_skills, both
- `is_active` (optional, boolean) - Filter by active status
- `search` (optional, string) - Search by name or description
- `per_page` (optional, default: 20) - Items per page

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Advanced Welding Techniques",
      "code": "WLD-101",
      "description": "Advanced welding techniques for industrial applications",
      "duration_days": 45,
      "training_type": "technical",
      "is_active": true,
      "assigned_candidates_count": 78,
      "created_at": "2026-01-15T10:30:00Z"
    }
  ],
  "current_page": 1,
  "last_page": 6,
  "per_page": 20,
  "total": 112
}
```

---

### 2. Get Single Course

**GET** `/api/v1/courses/{id}`

Get detailed information about a specific course.

**Response:**
```json
{
  "id": 1,
  "name": "Advanced Welding Techniques",
  "code": "WLD-101",
  "description": "Advanced welding techniques for industrial applications",
  "duration_days": 45,
  "training_type": "technical",
  "is_active": true,
  "assigned_candidates_count": 78,
  "created_at": "2026-01-15T10:30:00Z",
  "updated_at": "2026-01-15T10:30:00Z"
}
```

---

### 3. Create Course

**POST** `/api/v1/courses`

Create a new training course.

**Request Body:**
```json
{
  "name": "Effective Communication Skills",
  "code": "COM-101",
  "description": "Essential soft skills for workplace communication",
  "duration_days": 15,
  "training_type": "soft_skills",
  "is_active": true
}
```

**Validation Rules:**
- `name`: required, string, max:255
- `code`: required, string, max:50, unique
- `description`: nullable, string
- `duration_days`: required, integer, min:1, max:365
- `training_type`: required, enum: technical, soft_skills, both
- `is_active`: boolean, default: true

**Response:** HTTP 201 Created

---

### 4. Update Course

**PUT/PATCH** `/api/v1/courses/{id}`

Update an existing course.

**Response:** HTTP 200 OK

---

### 5. Delete Course

**DELETE** `/api/v1/courses/{id}`

Soft delete a course.

**Response:** HTTP 204 No Content

---

### 6. Assign Course to Candidates

**POST** `/api/v1/courses/{id}/assign-candidates`

Assign this course to multiple candidates.

**Request Body:**
```json
{
  "candidate_ids": [123, 456, 789],
  "start_date": "2026-02-01",
  "end_date": "2026-03-15"
}
```

**Validation Rules:**
- `candidate_ids`: required, array
- `candidate_ids.*`: required, exists:candidates,id
- `start_date`: required, date, after_or_equal:today
- `end_date`: required, date, after:start_date

**Response:** HTTP 200 OK
```json
{
  "message": "Course assigned to 3 candidates successfully",
  "assigned_count": 3,
  "course_id": 1
}
```

---

## Document Checklists API

### 1. List Document Checklists

**GET** `/api/v1/document-checklists`

Get list of document checklist items.

**Query Parameters:**
- `is_mandatory` (optional, boolean) - Filter by mandatory status
- `category` (optional, string) - Filter by category
- `is_active` (optional, boolean) - Filter by active status

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "CNIC (Computerized National Identity Card)",
      "description": "Original and copy of CNIC",
      "is_mandatory": true,
      "category": "identification",
      "display_order": 1,
      "is_active": true,
      "created_at": "2026-01-15T10:30:00Z"
    }
  ]
}
```

---

### 2. Get Single Document Checklist Item

**GET** `/api/v1/document-checklists/{id}`

Get detailed information about a specific checklist item.

**Response:**
```json
{
  "id": 1,
  "name": "CNIC (Computerized National Identity Card)",
  "description": "Original and copy of CNIC",
  "is_mandatory": true,
  "category": "identification",
  "display_order": 1,
  "is_active": true,
  "created_at": "2026-01-15T10:30:00Z",
  "updated_at": "2026-01-15T10:30:00Z"
}
```

---

### 3. Create Document Checklist Item

**POST** `/api/v1/document-checklists`

Create a new document checklist item.

**Request Body:**
```json
{
  "name": "Passport",
  "description": "Valid passport with minimum 6 months validity",
  "is_mandatory": true,
  "category": "travel",
  "display_order": 2,
  "is_active": true
}
```

**Validation Rules:**
- `name`: required, string, max:255
- `description`: nullable, string
- `is_mandatory`: boolean, default: false
- `category`: nullable, string, max:100
- `display_order`: integer, min:1
- `is_active`: boolean, default: true

**Response:** HTTP 201 Created

---

### 4. Update Document Checklist Item

**PUT/PATCH** `/api/v1/document-checklists/{id}`

Update an existing checklist item.

**Response:** HTTP 200 OK

---

### 5. Delete Document Checklist Item

**DELETE** `/api/v1/document-checklists/{id}`

Delete a checklist item.

**Response:** HTTP 204 No Content

---

## Pre-Departure Documents API

### 1. List Candidate Documents

**GET** `/api/v1/candidates/{candidateId}/pre-departure-documents`

Get list of pre-departure documents for a candidate.

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "candidate_id": 123,
      "document_type": "CNIC",
      "file_path": "documents/candidate-123/cnic.pdf",
      "file_url": "https://storage.example.com/documents/candidate-123/cnic.pdf",
      "is_mandatory": true,
      "is_verified": true,
      "verified_at": "2026-01-18T14:30:00Z",
      "verified_by": {
        "id": 1,
        "name": "Admin User"
      },
      "notes": "Document verified and accepted",
      "uploaded_at": "2026-01-17T10:00:00Z"
    }
  ]
}
```

---

### 2. Upload Pre-Departure Document

**POST** `/api/v1/candidates/{candidateId}/pre-departure-documents`

Upload a pre-departure document for a candidate.

**Request Body:** (multipart/form-data)
```
document_type: CNIC
document_file: [FILE]
notes: Original document scanned
```

**Validation Rules:**
- `document_type`: required, string, max:100
- `document_file`: required, file, mimes:pdf,jpg,jpeg,png, max:5120
- `notes`: nullable, string

**Response:** HTTP 201 Created

---

### 3. Verify Document

**POST** `/api/v1/pre-departure-documents/{id}/verify`

Mark a document as verified.

**Request Body:**
```json
{
  "notes": "Document verified successfully"
}
```

**Response:** HTTP 200 OK

---

### 4. Download Document

**GET** `/api/v1/pre-departure-documents/{id}/download`

Download a specific pre-departure document.

**Response:** File download (application/pdf or image/*)

---

### 5. Bulk Document Status Report

**GET** `/api/v1/pre-departure-documents/status-report`

Get bulk report of document completion status across candidates.

**Query Parameters:**
- `campus_id` (optional, integer) - Filter by campus
- `batch_id` (optional, integer) - Filter by batch
- `status` (optional, enum) - Filter by status: complete, incomplete, pending_verification

**Response:**
```json
{
  "data": [
    {
      "candidate_id": 123,
      "full_name": "Muhammad Ali",
      "cnic": "12345-1234567-1",
      "mandatory_documents_count": 5,
      "mandatory_documents_uploaded": 4,
      "mandatory_documents_verified": 3,
      "optional_documents_count": 3,
      "optional_documents_uploaded": 1,
      "completion_percentage": 80,
      "verification_percentage": 60,
      "status": "incomplete"
    }
  ],
  "summary": {
    "total_candidates": 50,
    "complete": 32,
    "incomplete": 15,
    "pending_verification": 3
  }
}
```

---

## Training Assessments API

### 1. List Training Assessments

**GET** `/api/v1/training-assessments`

Get a paginated list of training assessments.

**Query Parameters:**
- `candidate_id` (optional, integer) - Filter by candidate
- `batch_id` (optional, integer) - Filter by batch
- `assessment_type` (optional, enum) - Filter by type: interim, final
- `per_page` (optional, default: 20) - Items per page

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "candidate_id": 123,
      "batch_id": 10,
      "assessment_type": "interim",
      "assessment_date": "2026-01-15",
      "score": 85.5,
      "max_score": 100,
      "passing_score": 70,
      "passed": true,
      "assessor_id": 5,
      "assessor": {
        "id": 5,
        "name": "Instructor Khan"
      },
      "remarks": "Excellent performance in practical tests",
      "evidence_path": "assessments/batch-10/candidate-123-interim.pdf",
      "created_at": "2026-01-15T16:00:00Z"
    }
  ],
  "current_page": 1,
  "last_page": 12,
  "per_page": 20,
  "total": 235
}
```

---

### 2. Get Single Assessment

**GET** `/api/v1/training-assessments/{id}`

Get detailed information about a specific assessment.

**Response:**
```json
{
  "id": 1,
  "candidate_id": 123,
  "candidate": {
    "id": 123,
    "full_name": "Muhammad Ali",
    "cnic": "12345-1234567-1"
  },
  "batch_id": 10,
  "batch": {
    "id": 10,
    "batch_code": "ISB-TEC-WLD-2026-0001"
  },
  "assessment_type": "interim",
  "assessment_date": "2026-01-15",
  "score": 85.5,
  "max_score": 100,
  "passing_score": 70,
  "passed": true,
  "assessor_id": 5,
  "assessor": {
    "id": 5,
    "name": "Instructor Khan"
  },
  "remarks": "Excellent performance in practical tests",
  "evidence_path": "assessments/batch-10/candidate-123-interim.pdf",
  "evidence_url": "https://storage.example.com/assessments/batch-10/candidate-123-interim.pdf",
  "created_at": "2026-01-15T16:00:00Z",
  "updated_at": "2026-01-15T16:00:00Z"
}
```

---

### 3. Create Assessment

**POST** `/api/v1/training-assessments`

Record a new training assessment.

**Request Body:** (multipart/form-data)
```
candidate_id: 123
batch_id: 10
assessment_type: interim
assessment_date: 2026-01-15
score: 85.5
max_score: 100
passing_score: 70
remarks: Excellent performance
evidence_document: [FILE]
```

**Validation Rules:**
- `candidate_id`: required, exists:candidates,id
- `batch_id`: required, exists:batches,id
- `assessment_type`: required, enum: interim, final
- `assessment_date`: required, date
- `score`: required, numeric, min:0
- `max_score`: required, numeric, min:0, gte:score
- `passing_score`: required, numeric, min:0, lte:max_score
- `remarks`: nullable, string
- `evidence_document`: nullable, file, mimes:pdf, max:5120

**Response:** HTTP 201 Created

---

### 4. Update Assessment

**PUT/PATCH** `/api/v1/training-assessments/{id}`

Update an existing assessment record.

**Response:** HTTP 200 OK

---

### 5. Delete Assessment

**DELETE** `/api/v1/training-assessments/{id}`

Delete an assessment record.

**Response:** HTTP 204 No Content

---

### 6. Get Batch Assessment Statistics

**GET** `/api/v1/batches/{batchId}/assessment-statistics`

Get assessment statistics for a batch.

**Response:**
```json
{
  "batch_id": 10,
  "batch_code": "ISB-TEC-WLD-2026-0001",
  "total_candidates": 25,
  "interim_assessments": {
    "completed": 25,
    "pending": 0,
    "average_score": 82.3,
    "pass_rate": 92.0,
    "highest_score": 98.0,
    "lowest_score": 65.5
  },
  "final_assessments": {
    "completed": 23,
    "pending": 2,
    "average_score": 85.7,
    "pass_rate": 95.6,
    "highest_score": 99.5,
    "lowest_score": 68.0
  },
  "training_completion_status": "in_progress"
}
```

---

## Post-Departure Details API

### 1. Get Post-Departure Details

**GET** `/api/v1/candidates/{candidateId}/post-departure-details`

Get post-departure details for a candidate.

**Response:**
```json
{
  "id": 1,
  "candidate_id": 123,
  "residency_number": "2345678901",
  "residency_expiry": "2027-12-31",
  "residency_proof_path": "post-departure/candidate-123/iqama.pdf",
  "foreign_license_number": "LIC-KSA-123456",
  "foreign_mobile": "+966-50-1234567",
  "foreign_bank_account": "SA0380000000608010167519",
  "tracking_app_registered": true,
  "tracking_app_id": "ABSHER-123456",
  "employer_company_name": "Saudi Industrial Services",
  "employer_contact_person": "Ahmed Al-Rashid",
  "employer_contact_mobile": "+966-50-9876543",
  "employer_location": "Riyadh, Saudi Arabia",
  "final_salary": 2800.00,
  "final_currency": "SAR",
  "employment_terms": "Full-time permanent position",
  "employment_commencement_date": "2025-12-15",
  "job_contract_path": "post-departure/candidate-123/qiwa-contract.pdf",
  "created_at": "2025-12-20T10:00:00Z",
  "updated_at": "2026-01-10T14:30:00Z"
}
```

---

### 2. Create/Update Post-Departure Details

**POST/PUT** `/api/v1/candidates/{candidateId}/post-departure-details`

Create or update post-departure details for a candidate.

**Request Body:** (multipart/form-data)
```
residency_number: 2345678901
residency_expiry: 2027-12-31
foreign_license_number: LIC-KSA-123456
foreign_mobile: +966-50-1234567
foreign_bank_account: SA0380000000608010167519
tracking_app_registered: true
tracking_app_id: ABSHER-123456
employer_company_name: Saudi Industrial Services
employer_contact_person: Ahmed Al-Rashid
employer_contact_mobile: +966-50-9876543
employer_location: Riyadh, Saudi Arabia
final_salary: 2800.00
final_currency: SAR
employment_terms: Full-time permanent position
employment_commencement_date: 2025-12-15
residency_proof: [FILE]
job_contract: [FILE]
```

**Validation Rules:**
- `residency_number`: nullable, string, max:100
- `residency_expiry`: nullable, date, after:today
- `foreign_license_number`: nullable, string, max:100
- `foreign_mobile`: nullable, string, max:50
- `foreign_bank_account`: nullable, string, max:100
- `tracking_app_registered`: boolean
- `tracking_app_id`: nullable, string, max:100
- `employer_company_name`: nullable, string, max:255
- `employer_contact_person`: nullable, string, max:255
- `employer_contact_mobile`: nullable, string, max:50
- `employer_location`: nullable, string, max:255
- `final_salary`: nullable, numeric, min:0
- `final_currency`: nullable, string, max:10
- `employment_terms`: nullable, string
- `employment_commencement_date`: nullable, date
- `residency_proof`: nullable, file, mimes:pdf,jpg,jpeg,png, max:5120
- `job_contract`: nullable, file, mimes:pdf, max:5120

**Response:** HTTP 200 OK

---

## Success Stories API

### 1. List Success Stories

**GET** `/api/v1/success-stories`

Get a paginated list of success stories.

**Query Parameters:**
- `country_id` (optional, integer) - Filter by country
- `per_page` (optional, default: 20) - Items per page

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "candidate_id": 123,
      "candidate": {
        "id": 123,
        "full_name": "Muhammad Ali",
        "trade": "Welding"
      },
      "story_text": "After completing my training at BTEVTA, I secured a position with a leading company in Saudi Arabia...",
      "evidence_type": "video",
      "evidence_path": "success-stories/candidate-123/story-video.mp4",
      "is_featured": true,
      "is_published": true,
      "recorded_at": "2026-01-10T14:00:00Z",
      "created_at": "2026-01-10T15:30:00Z"
    }
  ],
  "current_page": 1,
  "last_page": 5,
  "per_page": 20,
  "total": 87
}
```

---

### 2. Get Single Success Story

**GET** `/api/v1/success-stories/{id}`

Get detailed information about a specific success story.

**Response:**
```json
{
  "id": 1,
  "candidate_id": 123,
  "candidate": {
    "id": 123,
    "full_name": "Muhammad Ali",
    "cnic": "12345-1234567-1",
    "trade": "Welding",
    "campus": "Islamabad",
    "current_country": "Saudi Arabia"
  },
  "story_text": "After completing my training at BTEVTA, I secured a position with a leading company in Saudi Arabia. The technical and soft skills training prepared me well for the challenges of working abroad. I am now supporting my family and building a better future.",
  "evidence_type": "video",
  "evidence_path": "success-stories/candidate-123/story-video.mp4",
  "evidence_url": "https://storage.example.com/success-stories/candidate-123/story-video.mp4",
  "is_featured": true,
  "is_published": true,
  "recorded_by": {
    "id": 2,
    "name": "Media Officer"
  },
  "recorded_at": "2026-01-10T14:00:00Z",
  "created_at": "2026-01-10T15:30:00Z",
  "updated_at": "2026-01-11T09:00:00Z"
}
```

---

### 3. Create Success Story

**POST** `/api/v1/success-stories`

Record a new success story.

**Request Body:** (multipart/form-data)
```
candidate_id: 123
story_text: After completing my training at BTEVTA...
evidence_type: video
evidence_file: [FILE]
is_featured: true
is_published: false
recorded_at: 2026-01-10 14:00:00
```

**Validation Rules:**
- `candidate_id`: required, exists:candidates,id
- `story_text`: required, string
- `evidence_type`: required, enum: audio, video, written, photo, none
- `evidence_file`: nullable, file, max:51200 (50MB for video/audio)
  - For video: mimes: mp4,mov,avi,mkv
  - For audio: mimes: mp3,m4a,wav
  - For photo: mimes: jpg,jpeg,png
  - For written: mimes: pdf,doc,docx
- `is_featured`: boolean, default: false
- `is_published`: boolean, default: false
- `recorded_at`: nullable, date

**Response:** HTTP 201 Created

**Note:** Video files are processed asynchronously using the ProcessVideoUpload job.

---

### 4. Update Success Story

**PUT/PATCH** `/api/v1/success-stories/{id}`

Update an existing success story.

**Response:** HTTP 200 OK

---

### 5. Delete Success Story

**DELETE** `/api/v1/success-stories/{id}`

Delete a success story.

**Response:** HTTP 204 No Content

---

### 6. Publish/Unpublish Success Story

**POST** `/api/v1/success-stories/{id}/publish`

Toggle publication status of a success story.

**Request Body:**
```json
{
  "is_published": true
}
```

**Response:** HTTP 200 OK

---

## Employment History API

### 1. Get Candidate Employment History

**GET** `/api/v1/candidates/{candidateId}/employment-history`

Get employment history (company switches) for a candidate.

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "candidate_id": 123,
      "switch_number": 1,
      "previous_company": "Saudi Industrial Services",
      "new_company": "Advanced Manufacturing Co.",
      "switch_date": "2026-06-15",
      "switch_reason": "Career advancement opportunity",
      "new_salary": 3200.00,
      "new_currency": "SAR",
      "new_location": "Jeddah, Saudi Arabia",
      "new_employer_contact": "+966-50-1122334",
      "new_contract_path": "employment-history/candidate-123/switch-1-contract.pdf",
      "recorded_by": {
        "id": 1,
        "name": "Admin User"
      },
      "created_at": "2026-06-20T10:00:00Z"
    }
  ]
}
```

---

### 2. Record Company Switch

**POST** `/api/v1/candidates/{candidateId}/employment-history`

Record a company switch for a candidate.

**Request Body:** (multipart/form-data)
```
switch_number: 1
previous_company: Saudi Industrial Services
new_company: Advanced Manufacturing Co.
switch_date: 2026-06-15
switch_reason: Career advancement
new_salary: 3200.00
new_currency: SAR
new_location: Jeddah, Saudi Arabia
new_employer_contact: +966-50-1122334
new_contract: [FILE]
```

**Validation Rules:**
- `switch_number`: required, integer, in:1,2
- `previous_company`: required, string, max:255
- `new_company`: required, string, max:255
- `switch_date`: required, date
- `switch_reason`: nullable, string
- `new_salary`: nullable, numeric, min:0
- `new_currency`: nullable, string, max:10
- `new_location`: nullable, string, max:255
- `new_employer_contact`: nullable, string, max:50
- `new_contract`: nullable, file, mimes:pdf, max:5120

**Response:** HTTP 201 Created

---

### 3. Update Employment History Record

**PUT/PATCH** `/api/v1/employment-history/{id}`

Update an employment history record.

**Response:** HTTP 200 OK

---

### 4. Delete Employment History Record

**DELETE** `/api/v1/employment-history/{id}`

Delete an employment history record.

**Response:** HTTP 204 No Content

---

## Response Formats

### Success Response Format

All successful responses follow this format:

```json
{
  "data": { ... },
  "message": "Optional success message"
}
```

For paginated responses:
```json
{
  "data": [ ... ],
  "current_page": 1,
  "last_page": 10,
  "per_page": 20,
  "total": 200,
  "from": 1,
  "to": 20
}
```

---

## Error Handling

### Error Response Format

All error responses follow this format:

```json
{
  "message": "Error message description",
  "errors": {
    "field_name": [
      "Specific validation error message"
    ]
  }
}
```

### HTTP Status Codes

- **200 OK** - Request successful
- **201 Created** - Resource created successfully
- **204 No Content** - Resource deleted successfully
- **400 Bad Request** - Invalid request syntax
- **401 Unauthorized** - Authentication required
- **403 Forbidden** - Insufficient permissions
- **404 Not Found** - Resource not found
- **422 Unprocessable Entity** - Validation errors
- **429 Too Many Requests** - Rate limit exceeded
- **500 Internal Server Error** - Server error

### Common Error Examples

**Validation Error (422):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "name": [
      "The name field is required."
    ],
    "email": [
      "The email has already been taken."
    ]
  }
}
```

**Authorization Error (403):**
```json
{
  "message": "You are not authorized to perform this action."
}
```

**Resource Not Found (404):**
```json
{
  "message": "The requested employer was not found."
}
```

**Rate Limit Exceeded (429):**
```json
{
  "message": "Too Many Attempts. Please try again in 60 seconds."
}
```

---

## Authentication

All API endpoints require authentication via web session. Include session cookie with all requests.

**Example Request Header:**
```
Cookie: laravel_session=your_session_token_here
Accept: application/json
Content-Type: application/json
```

---

## Rate Limiting

API requests are limited to **60 requests per minute** per authenticated user.

**Rate Limit Headers:**
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1642598400
```

---

## Versioning

Current API version: **v1**

All endpoints are prefixed with `/api/v1/`

---

## Support

For API support and questions:
- **Email:** support@btevta.gov.pk
- **Documentation:** https://docs.btevta.gov.pk
- **Issue Tracker:** https://github.com/btevta/wasl/issues

---

**Last Updated:** January 19, 2026
**Version:** 3.0.0
