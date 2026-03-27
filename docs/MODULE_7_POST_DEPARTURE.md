# Module 7: Post-Departure Enhancement - Documentation

**Project:** BTEVTA WASL
**Module:** Module 7 - Post-Departure (Enhancement)
**Status:** Implemented
**Date:** March 2026

---

## Overview

Module 7 provides comprehensive post-departure tracking for candidates deployed overseas, building on the existing departure infrastructure. It covers:

- **Residency & Identity** - Iqama tracking with expiry alerts
- **Foreign Contact** - Mobile number, carrier, and address
- **Foreign Bank Account** - Bank details with IBAN/SWIFT
- **Tracking App** - Absher registration and verification
- **WPS** - Wage Protection System registration
- **Employment Contract** - Qiwa Agreement management
- **Employment History** - Initial and subsequent employers
- **Company Switches** - Up to 2 employer changes with approval workflow
- **90-Day Compliance** - Checklist-based verification

---

## Architecture

### Enums

| Enum | Location | Values |
|------|----------|--------|
| `IqamaStatus` | `app/Enums/IqamaStatus.php` | pending, issued, expired, renewed |
| `ContractStatus` | `app/Enums/ContractStatus.php` | pending, active, completed, terminated |
| `EmploymentStatus` | `app/Enums/EmploymentStatus.php` | current, previous, terminated |
| `SwitchStatus` | `app/Enums/SwitchStatus.php` | pending, approved, completed, rejected |

### Models

| Model | Table | Description |
|-------|-------|-------------|
| `PostDepartureDetail` | `post_departure_details` | One-per-candidate post-departure record |
| `EmploymentHistory` | `employment_histories` | Employment records (initial + switches) |
| `CompanySwitchLog` | `company_switch_logs` | Company switch workflow tracking |

### Service

`PostDepartureService` (`app/Services/PostDepartureService.php`) handles all business logic:

- `getOrCreateDetails()` - Get/create post-departure record
- `updateIqama()` - Iqama/residency management
- `updateForeignLicense()` - Foreign license tracking
- `updateForeignContact()` - Contact details
- `updateForeignBank()` - Bank account details
- `registerTrackingApp()` - Absher registration
- `registerWPS()` - WPS registration
- `updateContract()` - Qiwa contract management
- `addInitialEmployment()` - First employer recording
- `initiateCompanySwitch()` - Switch workflow initiation
- `approveCompanySwitch()` - Admin approval
- `completeCompanySwitch()` - Switch finalization
- `verifyCompliance()` - 90-day compliance verification
- `getDashboard()` - Dashboard statistics

### Controller

`PostDepartureController` (`app/Http/Controllers/PostDepartureController.php`)

### Routes

All routes under `post-departure/` prefix with `post-departure.` name prefix:

| Method | URI | Action | Name |
|--------|-----|--------|------|
| GET | `/dashboard` | `dashboard` | `post-departure.dashboard` |
| GET | `/candidate/{candidate}` | `show` | `post-departure.show` |
| POST | `/{detail}/iqama` | `updateIqama` | `post-departure.update-iqama` |
| POST | `/{detail}/foreign-contact` | `updateForeignContact` | `post-departure.update-contact` |
| POST | `/{detail}/foreign-bank` | `updateForeignBank` | `post-departure.update-bank` |
| POST | `/{detail}/tracking-app` | `registerTrackingApp` | `post-departure.register-tracking` |
| POST | `/{detail}/wps` | `registerWPS` | `post-departure.register-wps` |
| POST | `/{detail}/contract` | `updateContract` | `post-departure.update-contract` |
| POST | `/{detail}/employment` | `addEmployment` | `post-departure.add-employment` |
| POST | `/{detail}/switch` | `initiateSwitch` | `post-departure.initiate-switch` |
| POST | `/{detail}/verify-compliance` | `verifyCompliance` | `post-departure.verify-compliance` |
| POST | `/switch/{switch}/approve` | `approveSwitch` | `post-departure.approve-switch` |
| POST | `/switch/{switch}/complete` | `completeSwitch` | `post-departure.complete-switch` |

### Views

| View | Description |
|------|-------------|
| `post-departure/dashboard.blade.php` | Overview dashboard with statistics |
| `post-departure/show.blade.php` | Candidate detail page |
| `post-departure/partials/compliance-checklist.blade.php` | 90-day compliance checklist |
| `post-departure/partials/iqama-card.blade.php` | Iqama/residency form |
| `post-departure/partials/contact-card.blade.php` | Foreign contact form |
| `post-departure/partials/bank-card.blade.php` | Foreign bank account form |
| `post-departure/partials/employment-card.blade.php` | Employment history and form |
| `post-departure/partials/switch-card.blade.php` | Company switch workflow |

---

## Company Switch Workflow

1. **Initiate** - User submits switch request with new company details and release letter
2. **Approve** - Admin reviews and approves the switch
3. **Complete** - Previous employment marked as "previous", new employment becomes "current"

Maximum of **2 company switches** allowed per candidate.

---

## 90-Day Compliance Checklist

Six items must be completed for compliance verification:

1. Iqama/Residency - Status must be "Issued"
2. Tracking App (Absher) - Must be registered
3. WPS Registration - Must be registered
4. Employment Contract - Status must be "Active"
5. Foreign Bank Account - Must be recorded
6. Foreign Contact Details - Mobile number must be recorded

---

## Testing

- Unit tests: `tests/Unit/PostDepartureTest.php` - Enum validation
- Feature tests: `tests/Feature/PostDepartureControllerTest.php` - Controller/integration tests

---

*Last Updated: March 2026*
