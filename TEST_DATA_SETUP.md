# Test Data Setup Guide

This guide explains how to populate the BTEVTA Management System with comprehensive test data for testing all modules.

---

## Overview

The `TestDataSeeder` creates realistic sample data across all modules:

- ✅ **7 Users** (1 admin, 3 campus admins, 2 regular users)
- ✅ **4 Campuses** (Lahore, Karachi, Islamabad, Peshawar)
- ✅ **3 OEPs** (Overseas Employment Promoters)
- ✅ **10 Trades** (Electrician, Plumber, Welder, etc.)
- ✅ **6 Batches** (Various statuses: completed, ongoing, scheduled)
- ✅ **41 Candidates** (At different workflow stages)
- ✅ **Training Records** (Ongoing & completed)
- ✅ **Screening Records** (Passed candidates)
- ✅ **Registration Data** (Documents, Next of Kin, Undertakings)
- ✅ **Visa Processing** (Applications & approvals)
- ✅ **Departures** (Post-departure tracking)
- ✅ **8 Complaints** (Various categories & statuses)
- ✅ **10 Correspondence** (Incoming/outgoing)
- ✅ **Remittances** (For departed candidates)
- ✅ **Document Archive** (Sample documents)

---

## Prerequisites

Before running the seeder, ensure:

1. **Database is configured** - Check `.env` file
2. **Migrations are run** - `php artisan migrate`
3. **Composer dependencies installed** - `composer install`

---

## Running the Seeder

### Option 1: Run Complete Test Data (Recommended for Testing)

```bash
php artisan db:seed --class=TestDataSeeder
```

**What this does:**
- Creates sample data for ALL modules
- Candidates at various stages (applied → departed)
- Realistic workflow progression
- Complete relationship data

**Time:** ~5-10 seconds

---

### Option 2: Run Basic Setup Only

```bash
php artisan db:seed
```

**What this does:**
- Basic users, campuses, OEPs, trades
- Minimal data for getting started
- Faster, but less comprehensive

**Time:** ~2-3 seconds

---

## Test Data Details

### 1. Users & Credentials

| Role | Email | Password | Campus |
|------|-------|----------|--------|
| Admin | admin@btevta.gov.pk | password | - |
| Campus Admin | lahore@btevta.gov.pk | password | Lahore |
| Campus Admin | karachi@btevta.gov.pk | password | Karachi |
| Campus Admin | islamabad@btevta.gov.pk | password | Islamabad |
| User | ahmed@btevta.gov.pk | password | Lahore |
| User | fatima@btevta.gov.pk | password | Karachi |

**Note:** All passwords are `password` for testing purposes.

---

### 2. Candidate Workflow Stages

The seeder creates candidates at each stage of the workflow:

| Stage | Count | Description |
|-------|-------|-------------|
| **Applied** | 5 | Fresh applications |
| **Screening Pending** | 3 | Awaiting screening call |
| **Screening Passed** | 4 | Passed screening, pending training |
| **In Training** | 8 | Currently enrolled in batches |
| **Training Completed** | 6 | Finished training, pending registration |
| **Registered** | 5 | Completed registration with all documents |
| **Visa Processing** | 4 | Visa application in progress |
| **Visa Approved** | 3 | Visa approved, ready to depart |
| **Departed** | 3 | Already departed to Saudi Arabia |

**Total:** 41 candidates covering the entire workflow

---

### 3. Module-Specific Data

#### **Registration Module** (Issues #6)
- ✅ 17 candidates with complete registration
- ✅ Documents: CNIC, Passport, Education, Police Clearance
- ✅ Next of Kin information
- ✅ Signed undertakings

**Test Flow:**
1. Login as admin
2. Go to `/registration`
3. View candidates pending registration
4. Click "Manage" on any candidate
5. See documents, next of kin, undertakings

---

#### **Departure Module** (Issue #9)
- ✅ 3 departed candidates
- ✅ Complete departure records with:
  - Pre-departure briefing
  - Flight information
  - Iqama details
  - Absher & WPS registration
  - First salary receipt
  - 90-day compliance tracking

**Test Flow:**
1. Login as admin
2. Go to `/departure`
3. View departed candidates
4. Click on any candidate
5. See complete departure timeline

---

#### **Training Module** (Issue #7)
- ✅ 8 candidates in training
- ✅ 6 completed training with certificates
- ✅ Attendance & performance scores
- ✅ Various batches (ongoing & completed)

**Test Flow:**
1. Login as admin
2. Go to `/training`
3. View batches and enrolled candidates
4. Check attendance and performance

---

#### **Screening Module** (Issue #5)
- ✅ Screening records for qualified candidates
- ✅ Different screening stages
- ✅ Pass/fail outcomes

**Test Flow:**
1. Login as admin
2. Go to `/screening`
3. View screening records
4. Schedule new screenings

---

#### **Visa Processing Module** (Issue #8)
- ✅ 7 visa applications
- ✅ Processing & approved statuses
- ✅ Visa numbers for approved cases

**Test Flow:**
1. Login as admin
2. Go to `/visa-processing`
3. View visa applications
4. Track visa status

---

#### **Complaints Module**
- ✅ 8 sample complaints
- ✅ Various categories (salary, contract, accommodation)
- ✅ Different priorities (low → urgent)
- ✅ Multiple statuses (open → closed)

**Test Flow:**
1. Login as admin
2. Go to `/complaints`
3. View and manage complaints
4. Assign, investigate, resolve

---

#### **Correspondence Module** (Issue #10)
- ✅ 10 correspondence records
- ✅ Incoming & outgoing
- ✅ Different categories and priorities

**Test Flow:**
1. Login as admin
2. Go to `/correspondence`
3. View correspondence records
4. Create new correspondence

---

#### **Remittances Module**
- ✅ Remittance records for departed candidates
- ✅ Currency conversion (PKR ↔ SAR)
- ✅ Transaction references

**Test Flow:**
1. Login as admin
2. Go to `/remittances`
3. View remittance records
4. Track payments

---

#### **Admin Modules** (Issues #15, #19, #21)

**Campuses:**
- 4 campuses with complete details
- Contact information
- Active/inactive status

**Users:**
- 7 users with different roles
- Proper campus assignments
- Role-based access

**Settings:**
- System configuration
- Email settings
- Security settings

---

## Clearing Data (Optional)

If you need to start fresh, you can uncomment the `clearExistingData()` call in the seeder:

1. Open `database/seeders/TestDataSeeder.php`
2. Find line 42: `// $this->clearExistingData();`
3. Uncomment it: `$this->clearExistingData();`
4. Run the seeder again

**Warning:** This will delete ALL existing data except the original admin user.

---

## Testing Scenarios

### Scenario 1: Complete Candidate Journey
1. View applied candidates
2. Schedule screening → Pass
3. Assign to training batch
4. Complete training → Issue certificate
5. Register candidate (upload docs, add next of kin)
6. Process visa → Approve
7. Record departure
8. Track 90-day compliance

### Scenario 2: Campus Admin Testing
1. Login as `lahore@btevta.gov.pk`
2. Verify you only see Lahore campus data
3. Test creating candidates
4. Test viewing reports

### Scenario 3: Reporting & Analytics
1. Login as admin
2. Go to Reports section
3. View:
   - Campus performance
   - OEP performance
   - Visa timeline
   - Training statistics
   - Complaint analysis

### Scenario 4: Document Management
1. Go to Document Archive
2. View uploaded documents
3. Filter by candidate
4. Test document upload/download

---

## Troubleshooting

### Issue: "Class TestDataSeeder not found"

**Solution:**
```bash
composer dump-autoload
php artisan db:seed --class=TestDataSeeder
```

---

### Issue: "SQLSTATE[23000]: Integrity constraint violation"

**Cause:** Foreign key conflicts

**Solution:**
```bash
php artisan migrate:fresh
php artisan db:seed --class=TestDataSeeder
```

**Warning:** This will drop all tables and recreate them.

---

### Issue: "General error: 1364 Field doesn't have a default value"

**Cause:** Missing required fields in models

**Solution:** Check migration files and ensure all required fields have defaults or are provided in seeders.

---

## Data Characteristics

### Realistic Data
- ✅ Proper CNIC format: `12345-1234567-1`
- ✅ Valid phone numbers: `+92-3XX-XXXXXXX`
- ✅ Proper email format
- ✅ Logical date progressions
- ✅ Consistent relationships

### Workflow Integrity
- ✅ Candidates progress through stages logically
- ✅ Training records linked to batches
- ✅ Registration requires completed training
- ✅ Departures require approved visas
- ✅ Dates follow chronological order

### Coverage
- ✅ All user roles represented
- ✅ All candidate statuses covered
- ✅ All document types included
- ✅ All complaint categories present
- ✅ Various OEPs and trades

---

## Production Considerations

**⚠️ IMPORTANT:** This seeder is for **TESTING ONLY**.

**Do NOT run in production:**
- Uses weak passwords (`password`)
- Creates fictitious data
- May conflict with real data

**For production:**
1. Use the default `DatabaseSeeder` only
2. Create real users manually
3. Import actual candidate data
4. Use secure passwords

---

## Next Steps After Seeding

1. **Login** with admin credentials
2. **Explore each module** using the test data
3. **Test workflows** end-to-end
4. **Verify UI** on all pages
5. **Check reports** for accuracy
6. **Test user permissions** (campus admin vs admin)

---

## Support

For issues with test data:
1. Check migration files are up to date
2. Verify `.env` database connection
3. Ensure all tables exist
4. Check Laravel logs: `storage/logs/laravel.log`

---

**Last Updated:** 2025-12-10
**Version:** 1.0
**Purpose:** Comprehensive testing of BTEVTA Management System

