# Module 3: Registration Enhancement

## Overview

Module 3 (Registration) has been enhanced to support the new WASL specifications with additional features for:
- Entry gate validation (screened candidates only)
- Campus, Program, OEP, and Implementing Partner allocation
- Auto-batch creation based on Campus + Program + Trade combination
- Course assignment during registration
- Enhanced Next of Kin details with financial account information

## Registration Flow

### Prerequisites
1. Candidate must be in **SCREENED** or **SCREENING_PASSED** status
2. Candidate must have required documents uploaded
3. Next of Kin information must be provided with financial details

### Registration Process

```
SCREENED → Allocation Form → Auto-Batch Assignment → Course Assignment → REGISTERED
```

1. **Entry Gate**: Only screened candidates can access registration
2. **Allocation Form**: User selects Campus, Program, Trade, OEP, and Implementing Partner
3. **Auto-Batch**: System automatically assigns candidate to an existing batch or creates a new one
4. **Course Assignment**: User selects course and sets start/end dates
5. **Next of Kin**: User provides NOK details including financial account for remittance
6. **Completion**: Candidate status changes to REGISTERED

## Features

### 1. Screening Gate (RG-001)

Only candidates with `status = screened` or `status = screening_passed` can proceed to registration. This ensures all candidates have been properly screened before registration.

```php
// Gate validation in RegistrationController
if (!in_array($candidate->status, ['screened', 'screening_passed'])) {
    return redirect()->back()->with('error', 'Candidate must be screened before registration.');
}
```

### 2. Allocation Section (RG-004)

During registration, the following are allocated:
- **Campus**: Training location
- **Program**: Overseas employment program (e.g., KSA Workforce Program)
- **Trade**: Skill/trade category
- **OEP**: Overseas Employment Promoter (optional)
- **Implementing Partner**: Partner organization (optional)

### 3. Auto-Batch Creation (RG-005, RG-006, RG-007)

Batches are automatically created and managed based on:
- Campus + Program + Trade combination
- Configurable batch size (20, 25, or 30 candidates)
- Unique batch numbers with format: `{CAMPUS_CODE}-{PROGRAM_CODE}-{TRADE_CODE}-{YEAR}-{SEQ}`

Example: `LHR-KSAWP-ELEC-2026-0001`

The `AutoBatchService` handles:
- Finding available batches with space
- Creating new batches when needed
- Generating unique batch numbers
- Race condition protection for concurrent registrations

### 4. Allocated Number (RG-008)

Each candidate receives a unique allocated number within their batch:
- Format: `{BATCH_NUMBER}-{POSITION}`
- Example: `LHR-KSAWP-ELEC-2026-0001-025`
- Position is padded to 3 digits (001-999)

### 5. Course Assignment (RG-009, RG-010)

During registration, courses are assigned with:
- **Course**: Selected from active courses
- **Start Date**: Training start date
- **End Date**: Training end date (auto-calculated based on course duration)
- **Training Type**: Technical, Soft Skills, or Both

### 6. Enhanced Next of Kin (RG-002, RG-003)

Next of Kin details include financial account information for remittance:
- Name, Relationship, CNIC, Phone, Address
- **Payment Method**: Bank Account, EasyPaisa, JazzCash
- **Account Number**: For receiving remittances
- **Bank Name**: Required for bank accounts
- **ID Card Copy**: Optional upload of NOK's CNIC

## API Routes

| Method | Route | Description |
|--------|-------|-------------|
| GET | `/registration` | List candidates in registration phase |
| GET | `/registration/{candidate}` | View registration details |
| GET | `/registration/{candidate}/allocation` | Show allocation form |
| POST | `/registration/{candidate}/allocation` | Process allocation and complete registration |
| GET | `/registration/{candidate}/status` | Get registration status |
| POST | `/registration/{candidate}/start-training` | Transition to training |

## Database Schema

### New Tables

#### `programs`
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | varchar(150) | Program name |
| code | varchar(20) | Unique program code |
| description | text | Program description |
| duration_weeks | integer | Program duration in weeks |
| country_id | bigint | Foreign key to countries |
| is_active | boolean | Active status |

#### `implementing_partners`
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | varchar(200) | Partner name |
| code | varchar(20) | Unique partner code |
| contact_person | varchar(100) | Contact person name |
| contact_email | varchar(150) | Contact email |
| contact_phone | varchar(20) | Contact phone |
| address | text | Address |
| city | varchar(100) | City |
| country_id | bigint | Foreign key to countries |
| is_active | boolean | Active status |

#### `courses`
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | varchar(150) | Course name |
| code | varchar(30) | Unique course code |
| description | text | Course description |
| duration_days | integer | Duration in days |
| training_type | enum | technical, soft_skills, both |
| program_id | bigint | Foreign key to programs |
| is_active | boolean | Active status |

#### `candidate_courses`
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| candidate_id | bigint | Foreign key to candidates |
| course_id | bigint | Foreign key to courses |
| start_date | date | Course start date |
| end_date | date | Course end date |
| status | enum | assigned, in_progress, completed, cancelled |
| assigned_by | bigint | User who assigned |
| assigned_at | timestamp | Assignment timestamp |

#### `payment_methods`
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | varchar(50) | Method name |
| code | varchar(20) | Unique code |
| requires_account_number | boolean | Requires account number |
| requires_bank_name | boolean | Requires bank name |
| is_active | boolean | Active status |
| display_order | integer | Display order |

### Modified Tables

#### `candidates` (new columns)
- `program_id` - Foreign key to programs
- `implementing_partner_id` - Foreign key to implementing_partners
- `allocated_number` - Unique allocated number per batch

#### `next_of_kins` (new columns)
- `payment_method_id` - Foreign key to payment_methods
- `account_number` - Financial account number
- `bank_name` - Bank name (for bank accounts)
- `id_card_path` - Path to uploaded ID card

#### `batches` (new columns)
- `program_id` - Foreign key to programs

## Configuration

### config/wasl.php

```php
'batch_size' => env('WASL_BATCH_SIZE', 25),
'allowed_batch_sizes' => [20, 25, 30],

'registration' => [
    'screening_gate_enabled' => true,
    'auto_batch_creation' => true,
    'auto_oep_allocation' => true,
    'allocation_required_fields' => [
        'campus_id',
        'program_id',
        'trade_id',
    ],
],
```

## Services

### AutoBatchService

Located at `app/Services/AutoBatchService.php`

Key methods:
- `assignOrCreateBatch(Candidate $candidate)` - Main entry point for batch assignment
- `generateBatchNumber(Campus, Program, Trade)` - Generates unique batch number
- `generateAllocatedNumber(Candidate, Batch)` - Generates allocated number
- `canAcceptCandidates(Batch)` - Checks if batch has space
- `getBatchStatistics(Batch)` - Returns batch fill statistics

## Testing

### Unit Tests
- `tests/Unit/AutoBatchServiceTest.php` - 13 tests for batch creation and assignment

### Feature Tests
- `tests/Feature/RegistrationAllocationTest.php` - 8 tests for allocation workflow

Run tests:
```bash
php artisan test tests/Unit/AutoBatchServiceTest.php tests/Feature/RegistrationAllocationTest.php
```

## Validation Checklist

- [x] Programs table exists with seed data
- [x] Implementing Partners table exists with seed data
- [x] Courses table exists with seed data
- [x] Payment Methods table exists with EasyPaisa, JazzCash, Bank
- [x] Candidates table has program_id, implementing_partner_id, allocated_number columns
- [x] Next of Kin table has payment_method_id, account_number, bank_name, id_card_path
- [x] Batches table has program_id column
- [x] AutoBatchService generates correct batch numbers
- [x] AutoBatchService finds existing batches with space
- [x] AutoBatchService creates new batches when needed
- [x] Allocated numbers are unique per batch
- [x] Screened status gate works
- [x] Allocation form loads with all dropdowns
- [x] Course assignment saves correctly
- [x] NOK financial details save correctly
- [x] NOK ID card uploads correctly
- [x] Candidate status changes to 'registered'
- [x] All tests pass
- [x] Activity logging works

## Related Files

### Controllers
- `app/Http/Controllers/RegistrationController.php`

### Models
- `app/Models/Program.php`
- `app/Models/ImplementingPartner.php`
- `app/Models/Course.php`
- `app/Models/PaymentMethod.php`
- `app/Models/CandidateCourse.php`

### Services
- `app/Services/AutoBatchService.php`

### Requests
- `app/Http/Requests/RegistrationAllocationRequest.php`

### Views
- `resources/views/registration/allocation.blade.php`

### Seeders
- `database/seeders/ProgramsSeeder.php`
- `database/seeders/ImplementingPartnersSeeder.php`
- `database/seeders/CoursesSeeder.php`
- `database/seeders/PaymentMethodsSeeder.php`

---

*Last Updated: February 2026*
