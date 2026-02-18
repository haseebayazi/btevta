# Module 6: Departure Enhancement

**Version:** 2.0 (Enhancement)
**Date:** February 2026
**Status:** Implemented

---

## Overview

Module 6 (Departure) has been enhanced with structured status tracking, pre-departure briefing media uploads, and an enhanced departure dashboard. This document covers the enhancements only; for the original module documentation see the base departure system.

---

## Changes Summary

| Change ID | Type | Description |
|-----------|------|-------------|
| DP-001 | MODIFIED | PTN Status restructured with JSON sub-details |
| DP-002 | NEW | Protector Status with color/icon metadata |
| DP-003 | MODIFIED | Ticket Status with full flight sub-details |
| DP-004 | NEW | Pre-Departure Briefing with document upload |
| DP-005 | NEW | Pre-Departure Briefing video upload |
| DP-006 | MODIFIED | Final Departure Status enum with CANCELLED case |
| DP-007 | NEW | Enhanced Departure Dashboard |

---

## New Database Columns

Migration: `2026_02_18_000001_add_enhanced_departure_columns.php`

| Column | Type | Description |
|--------|------|-------------|
| `ptn_details` | JSON | PTN structured data (status, issued_date, expiry_date, evidence_path, notes) |
| `protector_details` | JSON | Protector structured data (applied_date, completion_date, certificate_path, notes) |
| `ticket_details` | JSON | Full flight data (airline, flight_number, dates, airports, PNR) |
| `briefing_status` | ENUM | not_scheduled / scheduled / completed |
| `briefing_details` | JSON | Briefing data (scheduled_date, completed_date, document_path, video_path, ack) |
| `departure_status` | ENUM | processing / ready_to_depart / departed / cancelled |
| `departed_at` | TIMESTAMP | Actual departure timestamp |

---

## New Files

### Enums

| File | Description |
|------|-------------|
| `app/Enums/BriefingStatus.php` | Pre-departure briefing status |

### Modified Enums

| File | Changes |
|------|---------|
| `app/Enums/ProtectorStatus.php` | Added `color()` and `icon()` methods |
| `app/Enums/DepartureStatus.php` | Added `CANCELLED` case, `color()` and `icon()` methods |

### Value Objects

| File | Description |
|------|-------------|
| `app/ValueObjects/PTNDetails.php` | PTN structured data with `isIssued()`, `isExpired()` |
| `app/ValueObjects/TicketDetails.php` | Ticket structured data with `isComplete()`, `getDepartureDateTime()` |
| `app/ValueObjects/BriefingDetails.php` | Briefing structured data with `isComplete()`, `hasDocuments()` |

### Form Requests

| File | Description |
|------|-------------|
| `app/Http/Requests/UpdateTicketDetailsRequest.php` | Validates full ticket fields |
| `app/Http/Requests/CompleteBriefingRequest.php` | Validates briefing completion with file size limits |

### Views

| File | Description |
|------|-------------|
| `resources/views/departure/enhanced-dashboard.blade.php` | Enhanced dashboard with all status breakdowns |
| `resources/views/departure/checklist.blade.php` | Per-departure checklist with inline forms |

### Tests

| File | Description |
|------|-------------|
| `tests/Unit/DepartureEnhancedTest.php` | Value object and enum unit tests |
| `tests/Feature/DepartureEnhancedTest.php` | HTTP controller feature tests |

---

## New Routes

All routes are under the `departure.` named group, protected by `role:admin,project_director,campus_admin,oep,visa_partner,viewer` middleware.

| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET | `/departure/enhanced-dashboard` | `departure.enhanced-dashboard` | Enhanced dashboard |
| GET | `/departure/{departure}/checklist` | `departure.checklist` | Per-departure checklist |
| POST | `/departure/{departure}/ptn` | `departure.update-ptn` | Update PTN details |
| POST | `/departure/{departure}/protector` | `departure.update-protector` | Update protector status |
| POST | `/departure/{departure}/ticket` | `departure.update-ticket` | Update ticket details |
| POST | `/departure/{departure}/briefing/schedule` | `departure.schedule-briefing` | Schedule briefing |
| POST | `/departure/{departure}/briefing/complete` | `departure.complete-briefing` | Complete briefing |
| POST | `/departure/{departure}/ready` | `departure.mark-ready` | Mark ready to depart |
| POST | `/departure/{departure}/depart` | `departure.record-departure-actual` | Record actual departure |

---

## Architecture

### Departure Checklist Flow

```
PTN Issued
    ↓
Protector Clearance (done)
    ↓
Ticket Details Complete (airline + flight + date)
    ↓
Briefing Complete (completed_date + acknowledgment_signed)
    ↓
[canMarkReadyToDepart() = true]
    ↓
markReadyToDepart() → departure_status = READY_TO_DEPART
    ↓
recordDeparture() → departure_status = DEPARTED + departed_at timestamp
```

### Value Object Pattern

```php
// Reading
$ptn = $departure->ptn_details_object; // PTNDetails instance
$ptn->isIssued();       // bool
$ptn->isExpired();      // bool

$ticket = $departure->ticket_details_object; // TicketDetails instance
$ticket->isComplete();
$ticket->getDepartureDateTime(); // Carbon|null

$briefing = $departure->briefing_details_object; // BriefingDetails instance
$briefing->isComplete();
$briefing->hasDocuments();

// Writing (via model methods)
$departure->updatePTN($ptnNumber, $issuedDate, $expiryDate, $file);
$departure->updateProtectorStatus($status, $details, $file);
$departure->updateTicketDetails($ticketData, $file);
$departure->scheduleBriefing($date);
$departure->completeBriefing($ackSigned, $notes, $docFile, $videoFile, $ackFile);
$departure->markReadyToDepart();
$departure->recordDeparture($actualTime);
```

---

## File Upload Storage

All files are stored in `storage/app/private/departures/{candidate_id}/`:

| Subfolder | Contents |
|-----------|----------|
| `ptn/` | PTN evidence files |
| `protector/` | Protector certificate files |
| `ticket/` | Ticket PDF/image files |
| `briefing/documents/` | Briefing document PDFs |
| `briefing/videos/` | Briefing video files (MP4/MOV/AVI, max 100MB) |
| `briefing/acknowledgments/` | Signed acknowledgment files |

Access these files through `SecureFileController` as with all private storage.

---

## Usage Examples

### In Controllers

```php
// Get enhanced dashboard data
$dashboard = $this->departureService->getEnhancedDashboard($campusId);

// Get checklist for a departure
$checklist = $this->departureService->getDepartureChecklist($departure);
// $checklist['percentage'] → 75
// $checklist['can_mark_ready'] → false

// Issue PTN
$this->departureService->issuePTN($departure, 'PTN-001', '2026-02-01', '2026-08-01', $file);

// Update protector
$this->departureService->updateProtector($departure, 'done', 'Clearance obtained', $certFile);

// Update ticket
$this->departureService->updateTicket($departure, $ticketData, $ticketFile);

// Briefing workflow
$this->departureService->scheduleBriefing($departure, '2026-02-20');
$this->departureService->completeBriefing($departure, true, 'Notes', $docFile, $videoFile, $ackFile);

// Departure workflow
$this->departureService->markReadyToDepart($departure);
$this->departureService->recordActualDeparture($departure, '2026-03-01 10:00:00');
```

### In Blade Templates

```blade
{{-- Check briefing status --}}
<span class="badge badge-{{ $departure->briefing_status->color() }}">
    {{ $departure->briefing_status->label() }}
</span>

{{-- Display ticket info --}}
@if($departure->ticket_details_object->isComplete())
    {{ $departure->ticket_details_object->airline }} {{ $departure->ticket_details_object->flightNumber }}
@endif

{{-- Show checklist progress --}}
{{ $checklist['completed'] }}/{{ $checklist['total'] }} ({{ $checklist['percentage'] }}%)
```

---

## Testing

```bash
# Run enhanced tests
php artisan test tests/Unit/DepartureEnhancedTest.php
php artisan test tests/Feature/DepartureEnhancedTest.php

# Run all departure tests
php artisan test --filter=Departure
```

---

*Module 6 Enhancement implemented February 2026*
