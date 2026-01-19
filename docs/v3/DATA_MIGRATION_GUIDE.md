# WASL v3 Data Migration Guide

**Project:** BTEVTA WASL v3
**Version:** 3.0.0
**Last Updated:** January 19, 2026
**Audience:** Database Administrators, System Administrators

---

## Table of Contents

1. [Overview](#overview)
2. [Migration Strategy](#migration-strategy)
3. [Pre-Migration](#pre-migration)
4. [Schema Changes](#schema-changes)
5. [Data Migration Steps](#data-migration-steps)
6. [Post-Migration Validation](#post-migration-validation)
7. [Rollback Procedure](#rollback-procedure)
8. [Troubleshooting](#troubleshooting)

---

## Overview

### Purpose

This guide provides step-by-step instructions for migrating existing WASL v2 data to the new WASL v3 schema with minimal disruption.

### Migration Scope

**Data to be Migrated:**
- Existing candidates (all status levels)
- Campuses, trades, OEPs
- User accounts and permissions
- Screenings (adapted to new workflow)
- Training schedules (adapted to dual status)
- Visa processes
- Departures (with new fields)
- Remittances
- Complaints (adapted to new workflow)
- Activity logs

**New Master Data (Seeded):**
- Programs
- Implementing Partners
- Courses
- Document Checklists
- Countries
- Payment Methods

**Data NOT Migrated:**
- Test/demo candidates
- Archived/deleted records (unless specified)
- Temporary/draft records

### Expected Downtime

**Estimated Downtime:** 2-4 hours

**Breakdown:**
- Database backup: 30 minutes
- Run migrations: 15 minutes
- Data transformation: 60-90 minutes
- Validation: 30 minutes
- Testing: 30 minutes

---

## Migration Strategy

### Approach

**In-Place Migration:**
- Run Laravel migrations to add new tables and columns
- Transform existing data to fit new schema
- Preserve historical data integrity
- Maintain referential integrity

**Benefits:**
- No need for separate database
- Maintains all relationships
- Preserves activity logs
- Rollback possible

### Migration Timeline

**Phase 1: Preparation** (1 week before)
- Review migration scripts
- Test on staging environment
- Backup production database
- Communicate to stakeholders

**Phase 2: Execution** (Deployment day)
- Enable maintenance mode
- Run migrations
- Transform data
- Validate results
- Disable maintenance mode

**Phase 3: Validation** (First week)
- Monitor data integrity
- Fix any discrepancies
- User acceptance testing

---

## Pre-Migration

### Step 1: Environment Assessment

**Check Current Database Version:**

```bash
php artisan migrate:status
```

**Verify Data Counts:**

```sql
SELECT 'Candidates' as Table_Name, COUNT(*) as Count FROM candidates
UNION ALL SELECT 'Screenings', COUNT(*) FROM candidate_screenings
UNION ALL SELECT 'Batches', COUNT(*) FROM batches
UNION ALL SELECT 'Training Schedules', COUNT(*) FROM training_schedules
UNION ALL SELECT 'Visa Processes', COUNT(*) FROM visa_processes
UNION ALL SELECT 'Departures', COUNT(*) FROM departures
UNION ALL SELECT 'Complaints', COUNT(*) FROM complaints
UNION ALL SELECT 'Users', COUNT(*) FROM users;
```

**Record Baseline Counts** (to verify after migration):
- Candidates: ______
- Screenings: ______
- Batches: ______
- Training Schedules: ______
- Visa Processes: ______
- Departures: ______
- Complaints: ______
- Users: ______

---

### Step 2: Backup Production Database

**Full Backup:**

```bash
# Create backup directory
mkdir -p /backups/wasl/pre-v3-migration

# Backup database
mysqldump -u wasl_user -p \
  --single-transaction \
  --routines \
  --triggers \
  --events \
  btevta_wasl > /backups/wasl/pre-v3-migration/btevta_wasl_$(date +%Y%m%d_%H%M%S).sql

# Compress backup
gzip /backups/wasl/pre-v3-migration/btevta_wasl_*.sql

# Verify backup
gunzip -t /backups/wasl/pre-v3-migration/btevta_wasl_*.sql.gz
```

**Checklist:**
- [ ] Backup completed
- [ ] Backup compressed
- [ ] Backup verified (can be decompressed)
- [ ] Backup size reasonable (similar to database size)

---

### Step 3: Test on Staging

**Create Staging Database:**

```bash
# Create staging database
mysql -u root -p
CREATE DATABASE btevta_wasl_staging CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;

# Restore production data to staging
gunzip < /backups/wasl/pre-v3-migration/btevta_wasl_*.sql.gz | mysql -u root -p btevta_wasl_staging
```

**Test Migration on Staging:**

```bash
# Update .env to point to staging
DB_DATABASE=btevta_wasl_staging

# Run migrations
php artisan migrate

# Run data transformation (see Step 5)
php artisan wasl:migrate-to-v3

# Validate results
php artisan wasl:validate-migration
```

**Checklist:**
- [ ] Staging database created
- [ ] Production data restored to staging
- [ ] Migrations ran successfully on staging
- [ ] Data transformation completed on staging
- [ ] Validation passed on staging
- [ ] No errors encountered

---

## Schema Changes

### New Tables (14)

1. **countries** - Destination countries
2. **payment_methods** - Payment configurations
3. **programs** - Training programs (e.g., TEC, SDP)
4. **implementing_partners** - Partner organizations
5. **employers** - Employer information
6. **candidate_employer** - Pivot: candidate-employer assignments
7. **document_checklists** - Document requirements configuration
8. **pre_departure_documents** - Candidate document uploads
9. **courses** - Training courses
10. **candidate_courses** - Pivot: candidate-course assignments
11. **training_assessments** - Assessment records
12. **post_departure_details** - Residency & employment details
13. **employment_histories** - Company switch tracking
14. **success_stories** - Success story collection

### Modified Tables (6)

1. **candidates**
   - Added: `program_id` (FK to programs)
   - Added: `implementing_partner_id` (FK to implementing_partners)
   - Added: `allocated_number` (unique identifier)

2. **candidate_screenings**
   - Added: `consent_for_work` (boolean)
   - Added: `placement_interest` (enum: local/international)
   - Added: `target_country_id` (FK to countries, nullable)
   - Added: `screening_status` (enum: screened/pending/deferred)
   - Added: `evidence_path` (file path)
   - Added: `reviewer_id` (FK to users)
   - Added: `reviewed_at` (timestamp)

3. **training_schedules**
   - Added: `technical_training_status` (enum: not_started/in_progress/completed)
   - Added: `soft_skills_status` (enum: not_started/in_progress/completed)
   - Modified: Old `status` field kept for compatibility

4. **visa_processes**
   - Added: `stage_details` (JSON: appointment date/time/center, result)
   - Added: `application_status` (enum: applied/not_applied/refused)
   - Added: `issued_status` (enum: confirmed/pending/refused)

5. **departures**
   - Added: 15 new fields for PTN, Protector, Ticket, Pre-Departure Briefing
   - See migration file for complete list

6. **complaints**
   - Added: `current_issue` (text)
   - Added: `support_steps_taken` (text)
   - Added: `suggestions` (text)
   - Added: `conclusion` (text)
   - Added: `evidence_type` (enum)
   - Added: `evidence_path` (file path)

### Enum Updates

**CandidateStatus Enum:**
- Old: 15 statuses
- New: 17 statuses (14 active + 3 terminal)
- Added: `PRE_DEPARTURE_DOCS`, `ALLOCATED`, `TRAINING_COMPLETE`

**ScreeningStatus Enum:**
- Old: `PASSED`, `FAILED`, `PENDING`
- New: `SCREENED`, `PENDING`, `DEFERRED`

**New Enums:**
- PlacementInterest
- TrainingType
- TrainingProgress
- PTNStatus
- ProtectorStatus
- DepartureStatus
- EvidenceType

---

## Data Migration Steps

### Step 1: Run Laravel Migrations

```bash
# Review migrations to be run
php artisan migrate:status

# Run all v3 migrations
php artisan migrate --force

# Verify all migrations ran
php artisan migrate:status | grep "2026_01"
```

**Expected Output:**
```
Ran 2026_01_15_create_countries_table
Ran 2026_01_15_create_payment_methods_table
...
(20 migrations total)
```

---

### Step 2: Seed Master Data

```bash
# Seed programs
php artisan db:seed --class=ProgramSeeder --force

# Seed courses
php artisan db:seed --class=CourseSeeder --force

# Seed document checklists
php artisan db:seed --class=DocumentChecklistSeeder --force

# Seed countries
php artisan db:seed --class=CountrySeeder --force

# Verify seeding
mysql -u wasl_user -p btevta_wasl -e "
SELECT 'Programs' as Type, COUNT(*) as Count FROM programs
UNION ALL SELECT 'Courses', COUNT(*) FROM courses
UNION ALL SELECT 'Doc Checklists', COUNT(*) FROM document_checklists
UNION ALL SELECT 'Countries', COUNT(*) FROM countries;
"
```

**Expected Counts:**
- Programs: 3-5
- Courses: 10-20
- Doc Checklists: 8-10
- Countries: 15-20

---

### Step 3: Transform Existing Data

**Create Data Transformation Command:**

The system includes `php artisan wasl:migrate-to-v3` command for data transformation.

**Transformation Tasks:**

#### 3.1: Assign Default Program

All existing candidates need a program assignment.

```sql
-- Assign default "Technical Education & Vocational Training" program
UPDATE candidates c
SET c.program_id = (SELECT id FROM programs WHERE code = 'TEC' LIMIT 1)
WHERE c.program_id IS NULL;
```

**Verification:**
```sql
SELECT COUNT(*) FROM candidates WHERE program_id IS NULL;
-- Expected: 0
```

---

#### 3.2: Transform Screening Status

Old screening statuses need mapping to new workflow.

```sql
-- Map old PASSED to new SCREENED
UPDATE candidate_screenings
SET screening_status = 'screened'
WHERE outcome = 'passed' OR outcome = 'approved';

-- Map old FAILED to new DEFERRED
UPDATE candidate_screenings
SET screening_status = 'deferred',
    evidence_path = 'Migrated from v2: marked as failed'
WHERE outcome = 'failed' OR outcome = 'rejected';

-- Map old PENDING to new PENDING
UPDATE candidate_screenings
SET screening_status = 'pending'
WHERE outcome = 'pending' OR outcome IS NULL;

-- Set default consent to true for existing screened candidates
UPDATE candidate_screenings
SET consent_for_work = 1
WHERE screening_status = 'screened';

-- Set default placement interest to international
UPDATE candidate_screenings
SET placement_interest = 'international'
WHERE screening_status = 'screened';
```

**Verification:**
```sql
SELECT screening_status, COUNT(*) FROM candidate_screenings GROUP BY screening_status;
-- Expected: screened, pending, deferred counts
```

---

#### 3.3: Transform Training Statuses

Existing training records need dual status.

```sql
-- Map old single status to both technical and soft skills statuses
UPDATE training_schedules
SET technical_training_status =
    CASE
        WHEN status = 'completed' THEN 'completed'
        WHEN status = 'in_progress' THEN 'in_progress'
        ELSE 'not_started'
    END,
    soft_skills_status =
    CASE
        WHEN status = 'completed' THEN 'completed'
        WHEN status = 'in_progress' THEN 'in_progress'
        ELSE 'not_started'
    END;
```

**Verification:**
```sql
SELECT technical_training_status, soft_skills_status, COUNT(*)
FROM training_schedules
GROUP BY technical_training_status, soft_skills_status;
```

---

#### 3.4: Generate Allocated Numbers

Candidates registered before v3 need allocated numbers.

```sql
-- This is complex and should be done via Artisan command
-- Command: php artisan wasl:generate-allocated-numbers
```

**Command Logic:**
1. Group candidates by campus, program (default), trade
2. Order by registration date
3. Generate batch numbers if not already assigned
4. Generate allocated numbers within each batch
5. Update candidates table

**Verification:**
```sql
SELECT COUNT(*) FROM candidates WHERE allocated_number IS NULL;
-- Expected: 0 (all candidates should have allocated numbers)

SELECT allocated_number FROM candidates LIMIT 10;
-- Expected format: ISB-TEC-WLD-2026-0001-0012
```

---

#### 3.5: Transform Departure Data

Map existing departure data to new structure.

```sql
-- Set default PTN status based on existing PTN data
UPDATE departures
SET ptn_status =
    CASE
        WHEN ptn_number IS NOT NULL AND ptn_number != '' THEN 'issued'
        ELSE 'pending'
    END,
    ptn_issued_at = ptn_date;

-- Set default Protector status
UPDATE departures
SET protector_status = 'pending';

-- Set default final departure status
UPDATE departures
SET final_departure_status =
    CASE
        WHEN departure_date IS NOT NULL THEN 'departed'
        WHEN ticket_number IS NOT NULL THEN 'ready_to_depart'
        ELSE 'processing'
    END;
```

**Verification:**
```sql
SELECT ptn_status, COUNT(*) FROM departures GROUP BY ptn_status;
SELECT final_departure_status, COUNT(*) FROM departures GROUP BY final_departure_status;
```

---

#### 3.6: Transform Complaint Data

Existing complaints adapted to new 4-step workflow.

```sql
-- Populate new workflow fields from existing description
UPDATE complaints
SET current_issue = description,
    support_steps_taken = CONCAT('Legacy complaint. Original status: ', status),
    suggestions = 'No suggestions recorded in v2',
    conclusion = CASE WHEN status = 'resolved' THEN 'Resolved in v2' ELSE 'Pending resolution' END;
```

**Verification:**
```sql
SELECT COUNT(*) FROM complaints WHERE current_issue IS NULL;
-- Expected: 0
```

---

### Step 4: Create Training Assessments from Training Schedules

Existing completed training should have assessment records.

```sql
-- Create interim assessments for completed trainings
INSERT INTO training_assessments (
    candidate_id,
    batch_id,
    assessment_type,
    assessment_date,
    score,
    max_score,
    passing_score,
    assessor_id,
    remarks,
    created_at,
    updated_at
)
SELECT
    ts.candidate_id,
    c.batch_id,
    'interim',
    ts.start_date + INTERVAL 30 DAY,  -- Assume interim 30 days after start
    75,  -- Default score for migrated data
    100,
    70,
    ts.instructor_id,
    'Migrated from v2 training record',
    ts.created_at,
    ts.updated_at
FROM training_schedules ts
JOIN candidates c ON ts.candidate_id = c.id
WHERE ts.technical_training_status = 'completed'
AND NOT EXISTS (
    SELECT 1 FROM training_assessments ta
    WHERE ta.candidate_id = ts.candidate_id
    AND ta.assessment_type = 'interim'
);

-- Create final assessments for completed trainings
INSERT INTO training_assessments (
    candidate_id,
    batch_id,
    assessment_type,
    assessment_date,
    score,
    max_score,
    passing_score,
    assessor_id,
    remarks,
    created_at,
    updated_at
)
SELECT
    ts.candidate_id,
    c.batch_id,
    'final',
    ts.completion_date,
    80,  -- Default score for migrated data
    100,
    70,
    ts.instructor_id,
    'Migrated from v2 training record - assumed passed',
    ts.created_at,
    ts.updated_at
FROM training_schedules ts
JOIN candidates c ON ts.candidate_id = c.id
WHERE ts.technical_training_status = 'completed'
AND ts.completion_date IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM training_assessments ta
    WHERE ta.candidate_id = ts.candidate_id
    AND ta.assessment_type = 'final'
);
```

**Verification:**
```sql
SELECT assessment_type, COUNT(*) FROM training_assessments GROUP BY assessment_type;
-- Should show roughly equal numbers of interim and final for completed trainings
```

---

### Step 5: Run Automated Transformation Command

```bash
# Run comprehensive data transformation
php artisan wasl:migrate-to-v3 --verbose

# Command performs:
# - Program assignment
# - Screening status transformation
# - Training status transformation
# - Allocated number generation
# - Departure data transformation
# - Complaint data transformation
# - Assessment creation

# Review transformation log
cat storage/logs/migration-v3.log
```

---

## Post-Migration Validation

### Step 1: Data Integrity Checks

**Run Validation Command:**

```bash
php artisan wasl:validate-migration
```

**Manual Validation Queries:**

**1. Verify All Candidates Have Required Fields:**
```sql
SELECT
    COUNT(*) as total_candidates,
    SUM(CASE WHEN program_id IS NULL THEN 1 ELSE 0 END) as missing_program,
    SUM(CASE WHEN allocated_number IS NULL THEN 1 ELSE 0 END) as missing_allocated_number,
    SUM(CASE WHEN campus_id IS NULL THEN 1 ELSE 0 END) as missing_campus
FROM candidates;
-- Expected: All zeros except total_candidates
```

**2. Verify Screenings Transformed:**
```sql
SELECT
    screening_status,
    COUNT(*) as count,
    SUM(CASE WHEN consent_for_work IS NULL THEN 1 ELSE 0 END) as missing_consent,
    SUM(CASE WHEN placement_interest IS NULL THEN 1 ELSE 0 END) as missing_placement
FROM candidate_screenings
GROUP BY screening_status;
-- screened records should have consent and placement_interest
```

**3. Verify Training Statuses:**
```sql
SELECT
    technical_training_status,
    soft_skills_status,
    COUNT(*) as count
FROM training_schedules
GROUP BY technical_training_status, soft_skills_status;
-- No NULL values expected
```

**4. Verify Referential Integrity:**
```sql
-- Check orphaned records
SELECT COUNT(*) as orphaned_screenings
FROM candidate_screenings cs
LEFT JOIN candidates c ON cs.candidate_id = c.id
WHERE c.id IS NULL;
-- Expected: 0

SELECT COUNT(*) as orphaned_training
FROM training_schedules ts
LEFT JOIN candidates c ON ts.candidate_id = c.id
WHERE c.id IS NULL;
-- Expected: 0
```

**5. Verify Master Data Seeded:**
```sql
SELECT 'programs' as table_name, COUNT(*) as count FROM programs
UNION ALL SELECT 'courses', COUNT(*) FROM courses
UNION ALL SELECT 'document_checklists', COUNT(*) FROM document_checklists
UNION ALL SELECT 'countries', COUNT(*) FROM countries;
-- All counts should be > 0
```

---

### Step 2: Compare Record Counts

**Pre-Migration vs. Post-Migration:**

```sql
-- Compare with baseline counts recorded in Pre-Migration Step 1
SELECT 'Candidates' as Table_Name, COUNT(*) as Count FROM candidates
UNION ALL SELECT 'Screenings', COUNT(*) FROM candidate_screenings
UNION ALL SELECT 'Batches', COUNT(*) FROM batches
UNION ALL SELECT 'Training Schedules', COUNT(*) FROM training_schedules
UNION ALL SELECT 'Visa Processes', COUNT(*) FROM visa_processes
UNION ALL SELECT 'Departures', COUNT(*) FROM departures
UNION ALL SELECT 'Complaints', COUNT(*) FROM complaints
UNION ALL SELECT 'Users', COUNT(*) FROM users;
```

**All counts should match pre-migration baseline.**

---

### Step 3: Functional Testing

**Test Critical Paths:**

1. **Registration:**
   - [ ] Can register new candidate with all allocation fields
   - [ ] Batch auto-generated
   - [ ] Allocated number assigned

2. **Screening:**
   - [ ] Can conduct new screening with new fields
   - [ ] Status options correct (screened/pending/deferred)
   - [ ] Old screenings visible and readable

3. **Training:**
   - [ ] Dual status tracking works
   - [ ] Can record assessments
   - [ ] Old training records visible

4. **Visa:**
   - [ ] Visa processing works with new fields
   - [ ] Old visa records visible

5. **Departure:**
   - [ ] New PTN/Protector/Ticket fields work
   - [ ] Old departure records visible

6. **Employer:**
   - [ ] Can create employer
   - [ ] Can assign candidates

7. **Success Stories:**
   - [ ] Can create success story
   - [ ] Video upload works

8. **Reports:**
   - [ ] All reports work
   - [ ] Data looks correct

---

### Step 4: Performance Check

```bash
# Check query performance
php artisan tinker

DB::enableQueryLog();
// Perform typical operations
DB::getQueryLog();

# Check database size
mysql -u wasl_user -p -e "
SELECT
    table_name AS 'Table',
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.TABLES
WHERE table_schema = 'btevta_wasl'
ORDER BY (data_length + index_length) DESC;
"

# Optimize tables if needed
mysqlcheck -u wasl_user -p --optimize btevta_wasl
```

---

## Rollback Procedure

### When to Rollback

**Rollback if:**
- Data corruption detected
- Critical data missing
- Referential integrity violations
- Functional testing fails
- Migration errors cannot be resolved quickly

### Rollback Steps

#### Step 1: Enable Maintenance Mode

```bash
php artisan down --message="Migration rollback in progress"
```

---

#### Step 2: Drop New Tables

```bash
mysql -u root -p btevta_wasl
```

```sql
-- Drop new v3 tables
DROP TABLE IF EXISTS success_stories;
DROP TABLE IF EXISTS employment_histories;
DROP TABLE IF EXISTS post_departure_details;
DROP TABLE IF EXISTS training_assessments;
DROP TABLE IF EXISTS candidate_courses;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS pre_departure_documents;
DROP TABLE IF EXISTS document_checklists;
DROP TABLE IF EXISTS candidate_employer;
DROP TABLE IF EXISTS employers;
DROP TABLE IF EXISTS implementing_partners;
DROP TABLE IF EXISTS programs;
DROP TABLE IF EXISTS payment_methods;
DROP TABLE IF EXISTS countries;

EXIT;
```

---

#### Step 3: Restore Database from Backup

```bash
# Restore full database from pre-migration backup
gunzip < /backups/wasl/pre-v3-migration/btevta_wasl_*.sql.gz | mysql -u wasl_user -p btevta_wasl
```

---

#### Step 4: Verify Restoration

```bash
# Check record counts match pre-migration
mysql -u wasl_user -p btevta_wasl -e "
SELECT 'Candidates' as Table_Name, COUNT(*) as Count FROM candidates;
"

# Check application works
php artisan tinker
Candidate::count();
User::count();
```

---

#### Step 5: Clear Caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

---

#### Step 6: Restart Services

```bash
systemctl restart wasl-worker
systemctl restart php8.2-fpm
systemctl restart nginx
```

---

#### Step 7: Disable Maintenance Mode

```bash
php artisan up
```

---

#### Step 8: Verify Application

- [ ] Login works
- [ ] Candidate listing works
- [ ] All modules accessible
- [ ] No errors in logs

---

## Troubleshooting

### Issue 1: Migration Fails Partway

**Symptom:** Migration stops with error

**Solution:**

```bash
# Check which migrations ran
php artisan migrate:status

# Roll back last batch
php artisan migrate:rollback --step=1

# Fix issue in migration file

# Re-run migration
php artisan migrate
```

---

### Issue 2: Foreign Key Constraint Violations

**Symptom:** "Cannot add foreign key constraint" error

**Solution:**

```bash
# Check foreign key issues
mysql -u wasl_user -p btevta_wasl

# Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS=0;

# Run problematic migration/query

# Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS=1;

EXIT;
```

---

### Issue 3: Duplicate Allocated Numbers

**Symptom:** Multiple candidates have same allocated_number

**Solution:**

```sql
-- Find duplicates
SELECT allocated_number, COUNT(*) as count
FROM candidates
WHERE allocated_number IS NOT NULL
GROUP BY allocated_number
HAVING count > 1;

-- Regenerate allocated numbers
php artisan wasl:generate-allocated-numbers --force
```

---

### Issue 4: Missing Master Data

**Symptom:** Dropdowns empty (programs, courses, etc.)

**Solution:**

```bash
# Re-seed master data
php artisan db:seed --class=ProgramSeeder --force
php artisan db:seed --class=CourseSeeder --force
php artisan db:seed --class=DocumentChecklistSeeder --force
php artisan db:seed --class=CountrySeeder --force

# Clear cache
php artisan cache:clear
```

---

### Issue 5: Performance Degradation

**Symptom:** Slow queries after migration

**Solution:**

```bash
# Optimize all tables
mysqlcheck -u wasl_user -p --optimize btevta_wasl

# Analyze tables
mysqlcheck -u wasl_user -p --analyze btevta_wasl

# Add missing indexes (if any)
mysql -u wasl_user -p btevta_wasl

CREATE INDEX idx_candidates_allocated_number ON candidates(allocated_number);
CREATE INDEX idx_training_assessments_candidate ON training_assessments(candidate_id);

EXIT;
```

---

## Success Criteria

Migration considered successful if:

- [ ] All 20 migrations completed
- [ ] All master data seeded
- [ ] All existing data transformed
- [ ] No orphaned records
- [ ] All foreign keys valid
- [ ] Record counts match baseline
- [ ] All functional tests pass
- [ ] Performance acceptable
- [ ] No critical errors in logs

---

## Support

**Migration Support:**
- Email: migration-support@btevta.gov.pk
- Phone: +92-51-9204567 (Ext. 456)
- Hours: 24/7 during migration window

**Escalation:**
- Database Administrator: [Phone]
- Technical Lead: [Phone]
- CTO: [Phone]

---

**Document End**

*This guide should be reviewed and tested on staging before production migration.*
