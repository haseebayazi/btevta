# Remittance Management API Documentation

## Overview

The Remittance Management API provides RESTful endpoints for managing remittances, viewing analytics reports, and handling alerts. All endpoints are versioned under `/api/v1/` and require authentication.

**Base URL:** `/api/v1/`
**Authentication:** Web session authentication (required)
**Throttling:** 60 requests per minute
**Response Format:** JSON

---

## Table of Contents

1. [Remittance Endpoints](#remittance-endpoints)
2. [Report Endpoints](#report-endpoints)
3. [Alert Endpoints](#alert-endpoints)
4. [Response Formats](#response-formats)
5. [Error Handling](#error-handling)

---

## Remittance Endpoints

### 1. List Remittances

**GET** `/api/v1/remittances/`

Get a paginated list of remittances with optional filters.

**Query Parameters:**
- `candidate_id` (optional) - Filter by candidate ID
- `status` (optional) - Filter by status (pending, verified, flagged)
- `year` (optional) - Filter by year
- `date_from` (optional) - Filter by start date (YYYY-MM-DD)
- `date_to` (optional) - Filter by end date (YYYY-MM-DD)
- `per_page` (optional, default: 20) - Items per page

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "candidate_id": 123,
      "transaction_reference": "TXN123456",
      "amount": 50000,
      "currency": "PKR",
      "transfer_date": "2025-11-01",
      "status": "verified",
      "candidate": { ... },
      "departure": { ... },
      "recorded_by": { ... }
    }
  ],
  "current_page": 1,
  "last_page": 10,
  "per_page": 20,
  "total": 200
}
```

---

### 2. Get Single Remittance

**GET** `/api/v1/remittances/{id}`

Get detailed information about a specific remittance.

**Response:**
```json
{
  "id": 1,
  "candidate_id": 123,
  "transaction_reference": "TXN123456",
  "amount": 50000,
  "currency": "PKR",
  "amount_foreign": 150,
  "foreign_currency": "USD",
  "exchange_rate": 333.33,
  "transfer_date": "2025-11-01",
  "sender_name": "John Doe",
  "receiver_name": "Jane Doe",
  "primary_purpose": "family_support",
  "status": "verified",
  "has_proof": true,
  "candidate": { ... },
  "departure": { ... },
  "receipts": [ ... ],
  "usage_breakdown": [ ... ]
}
```

---

### 3. Get Remittances by Candidate

**GET** `/api/v1/remittances/candidate/{candidateId}`

Get all remittances for a specific candidate with summary statistics.

**Response:**
```json
{
  "candidate": {
    "id": 123,
    "full_name": "John Doe",
    "cnic": "12345-1234567-1"
  },
  "remittances": [ ... ],
  "summary": {
    "total_count": 15,
    "total_amount": 750000,
    "average_amount": 50000,
    "latest_remittance": { ... }
  }
}
```

---

### 4. Create Remittance

**POST** `/api/v1/remittances/`

Create a new remittance record.

**Request Body:**
```json
{
  "candidate_id": 123,
  "departure_id": 45,
  "transaction_reference": "TXN123456",
  "amount": 50000,
  "currency": "PKR",
  "amount_foreign": 150,
  "foreign_currency": "USD",
  "exchange_rate": 333.33,
  "transfer_date": "2025-11-01",
  "transfer_method": "bank_transfer",
  "sender_name": "John Doe",
  "sender_location": "Dubai, UAE",
  "receiver_name": "Jane Doe",
  "receiver_account": "1234567890",
  "bank_name": "HBL",
  "primary_purpose": "family_support",
  "purpose_description": "Monthly family support",
  "notes": "Regular monthly transfer"
}
```

**Response (201 Created):**
```json
{
  "message": "Remittance created successfully",
  "remittance": { ... }
}
```

---

### 5. Update Remittance

**PUT** `/api/v1/remittances/{id}`

Update an existing remittance.

**Request Body:** (All fields optional)
```json
{
  "amount": 55000,
  "status": "verified",
  "notes": "Updated amount"
}
```

**Response:**
```json
{
  "message": "Remittance updated successfully",
  "remittance": { ... }
}
```

---

### 6. Delete Remittance

**DELETE** `/api/v1/remittances/{id}`

Delete a remittance record.

**Response:**
```json
{
  "message": "Remittance deleted successfully"
}
```

---

### 7. Search Remittances

**GET** `/api/v1/remittances/search/query`

Search remittances by various criteria.

**Query Parameters:**
- `transaction_reference` (optional) - Partial match on transaction reference
- `candidate` (optional) - Search by candidate name or CNIC
- `min_amount` (optional) - Minimum amount filter
- `max_amount` (optional) - Maximum amount filter

**Response:**
```json
{
  "count": 5,
  "results": [ ... ]
}
```

---

### 8. Get Statistics

**GET** `/api/v1/remittances/stats/overview`

Get overall remittance statistics.

**Response:**
```json
{
  "total_remittances": 1250,
  "total_amount": 62500000,
  "average_amount": 50000,
  "total_candidates": 450,
  "with_proof": 1100,
  "proof_compliance_rate": 88.00,
  "by_status": {
    "pending": 50,
    "verified": 1150,
    "flagged": 50
  },
  "current_year": {
    "count": 500,
    "amount": 25000000
  },
  "current_month": {
    "count": 45,
    "amount": 2250000
  }
}
```

---

### 9. Verify Remittance

**POST** `/api/v1/remittances/{id}/verify`

Mark a remittance as verified.

**Response:**
```json
{
  "message": "Remittance verified successfully",
  "remittance": { ... }
}
```

---

## Report Endpoints

### 1. Dashboard Overview

**GET** `/api/v1/remittance/reports/dashboard`

Get combined dashboard statistics including trends and analysis.

**Response:**
```json
{
  "statistics": {
    "total_remittances": 1250,
    "total_amount": 62500000,
    "average_amount": 50000
  },
  "monthly_trends": [ ... ],
  "purpose_analysis": [ ... ]
}
```

---

### 2. Monthly Trends

**GET** `/api/v1/remittance/reports/monthly-trends`

Get monthly remittance trends for a specific year.

**Query Parameters:**
- `year` (optional, default: current year) - Year to analyze

**Response:**
```json
{
  "year": 2025,
  "trends": [
    {
      "month": 1,
      "month_name": "January",
      "total_amount": 4500000,
      "count": 90,
      "average_amount": 50000
    },
    ...
  ]
}
```

---

### 3. Purpose Analysis

**GET** `/api/v1/remittance/reports/purpose-analysis`

Get breakdown of remittances by purpose.

**Response:**
```json
[
  {
    "purpose": "family_support",
    "count": 750,
    "total_amount": 37500000,
    "percentage": 60.0
  },
  {
    "purpose": "education",
    "count": 250,
    "total_amount": 12500000,
    "percentage": 20.0
  },
  ...
]
```

---

### 4. Transfer Methods

**GET** `/api/v1/remittance/reports/transfer-methods`

Get analysis of transfer methods used.

**Response:**
```json
[
  {
    "method": "bank_transfer",
    "count": 800,
    "total_amount": 40000000,
    "average_amount": 50000
  },
  ...
]
```

---

### 5. Country Analysis

**GET** `/api/v1/remittance/reports/country-analysis`

Get breakdown by destination country.

**Response:**
```json
[
  {
    "country": "Saudi Arabia",
    "count": 500,
    "total_amount": 25000000,
    "average_amount": 50000,
    "candidates_count": 180
  },
  ...
]
```

---

### 6. Proof Compliance Report

**GET** `/api/v1/remittance/reports/proof-compliance`

Get proof documentation compliance report.

**Response:**
```json
{
  "total_remittances": 1250,
  "with_proof": 1100,
  "without_proof": 150,
  "compliance_rate": 88.00,
  "by_status": {
    "verified_with_proof": 1050,
    "verified_without_proof": 100,
    "pending_with_proof": 50,
    "pending_without_proof": 50
  }
}
```

---

### 7. Beneficiary Report

**GET** `/api/v1/remittance/reports/beneficiary-report`

Get analysis of beneficiary relationships and patterns.

**Response:**
```json
{
  "total_beneficiaries": 450,
  "by_relationship": [ ... ],
  "average_per_beneficiary": 138888.89
}
```

---

### 8. Impact Analytics

**GET** `/api/v1/remittance/reports/impact-analytics`

Get economic impact analytics.

**Response:**
```json
{
  "total_remitted": 62500000,
  "year_over_year_growth": 15.5,
  "average_per_candidate": 138888.89,
  "estimated_local_impact": 75000000,
  "employment_multiplier": 1.2
}
```

---

### 9. Top Candidates

**GET** `/api/v1/remittance/reports/top-candidates`

Get top remitting candidates.

**Query Parameters:**
- `limit` (optional, default: 10) - Number of candidates to return

**Response:**
```json
[
  {
    "candidate_id": 123,
    "candidate_name": "John Doe",
    "total_amount": 750000,
    "remittance_count": 15,
    "average_amount": 50000,
    "first_remittance_date": "2025-01-15",
    "last_remittance_date": "2025-11-01"
  },
  ...
]
```

---

## Alert Endpoints

### 1. List Alerts

**GET** `/api/v1/remittance/alerts/`

Get paginated list of remittance alerts.

**Query Parameters:**
- `status` (optional) - Filter by status (unresolved, resolved, all). Default: unresolved
- `severity` (optional) - Filter by severity (critical, warning, info)
- `type` (optional) - Filter by alert type
- `candidate_id` (optional) - Filter by candidate
- `per_page` (optional, default: 20) - Items per page

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "candidate_id": 123,
      "remittance_id": 45,
      "alert_type": "missing_remittance",
      "severity": "warning",
      "title": "No Recent Remittances",
      "message": "Candidate has not sent remittances in 90 days",
      "is_read": false,
      "is_resolved": false,
      "created_at": "2025-11-01T10:00:00Z",
      "candidate": { ... },
      "remittance": { ... }
    }
  ],
  "current_page": 1,
  "total": 50
}
```

---

### 2. Get Single Alert

**GET** `/api/v1/remittance/alerts/{id}`

Get detailed information about a specific alert. Automatically marks as read.

**Response:**
```json
{
  "id": 1,
  "candidate_id": 123,
  "alert_type": "missing_remittance",
  "severity": "warning",
  "title": "No Recent Remittances",
  "message": "Full alert message...",
  "metadata": {
    "days_since_departure": 120,
    "last_remittance_date": null
  },
  "is_read": true,
  "is_resolved": false,
  "candidate": { ... },
  "remittance": { ... },
  "resolved_by": null
}
```

---

### 3. Get Unread Count

**GET** `/api/v1/remittance/alerts/stats/unread-count`

Get count of unread alerts.

**Query Parameters:**
- `candidate_id` (optional) - Filter by candidate

**Response:**
```json
{
  "count": 15
}
```

---

### 4. Get Alert Statistics

**GET** `/api/v1/remittance/alerts/stats/overview`

Get comprehensive alert statistics.

**Response:**
```json
{
  "total_alerts": 150,
  "unresolved_alerts": 50,
  "critical_alerts": 10,
  "unread_alerts": 15,
  "by_type": {
    "missing_remittance": 20,
    "missing_proof": 15,
    "first_remittance_delay": 10,
    "low_frequency": 3,
    "unusual_amount": 2
  },
  "by_severity": {
    "critical": 10,
    "warning": 30,
    "info": 10
  }
}
```

---

### 5. Get Alerts by Candidate

**GET** `/api/v1/remittance/alerts/candidate/{candidateId}`

Get all alerts for a specific candidate with summary.

**Response:**
```json
{
  "candidate_id": 123,
  "alerts": [ ... ],
  "summary": {
    "total": 8,
    "unresolved": 3,
    "critical": 1
  }
}
```

---

### 6. Mark Alert as Read

**POST** `/api/v1/remittance/alerts/{id}/read`

Mark an alert as read.

**Response:**
```json
{
  "message": "Alert marked as read",
  "alert": { ... }
}
```

---

### 7. Resolve Alert

**POST** `/api/v1/remittance/alerts/{id}/resolve`

Resolve an alert with optional notes.

**Request Body:**
```json
{
  "resolution_notes": "Issue addressed, candidate contacted"
}
```

**Response:**
```json
{
  "message": "Alert resolved successfully",
  "alert": { ... }
}
```

---

### 8. Dismiss Alert

**POST** `/api/v1/remittance/alerts/{id}/dismiss`

Quick dismiss an alert (resolves with "Dismissed via API" note).

**Response:**
```json
{
  "message": "Alert dismissed successfully",
  "alert": { ... }
}
```

---

## Response Formats

### Success Response

All successful API calls return appropriate HTTP status codes:
- `200 OK` - Successful GET, PUT, POST requests
- `201 Created` - Successful resource creation
- `204 No Content` - Successful DELETE requests

### Error Response

Error responses follow a consistent format:

```json
{
  "error": "Resource not found"
}
```

Or for validation errors:

```json
{
  "errors": {
    "candidate_id": ["The candidate id field is required."],
    "amount": ["The amount must be at least 0."]
  }
}
```

---

## Error Handling

### HTTP Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Unprocessable Entity (Validation Error)
- `429` - Too Many Requests (Rate Limit)
- `500` - Internal Server Error

### Rate Limiting

All API endpoints are rate-limited to 60 requests per minute per authenticated user. When rate limit is exceeded, the API returns:

**Status:** `429 Too Many Requests`

**Headers:**
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 0
Retry-After: 45
```

---

## Authentication

All API endpoints require authentication via web session. Include session cookie with all requests.

**Example using cURL:**
```bash
curl -X GET "https://your-domain.com/api/v1/remittances/" \
  -H "Accept: application/json" \
  --cookie "session_cookie_here"
```

**Example using JavaScript Fetch:**
```javascript
fetch('/api/v1/remittances/', {
  method: 'GET',
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json'
  },
  credentials: 'include'  // Important for session auth
})
.then(response => response.json())
.then(data => console.log(data));
```

---

## Notes

1. **Date Format:** All dates should be in ISO 8601 format (YYYY-MM-DD or YYYY-MM-DDTHH:MM:SSZ)
2. **Currency Codes:** Use ISO 4217 3-letter currency codes (PKR, USD, SAR, etc.)
3. **Pagination:** All list endpoints support pagination with `per_page` parameter
4. **Role-Based Access:** Candidates can only access their own remittances; admins can access all
5. **Soft Deletes:** Deleted remittances may still exist in the database (soft delete)

---

## Support

For API support or to report issues, please contact the development team or refer to the main application documentation.

**API Version:** 1.0
**Last Updated:** November 2025
