# Database Data Audit Results - Nullable Field Analysis

**Generated:** 2025-11-09
**Purpose:** Pre-deployment audit to identify NULL values before applying nullable field constraints

---

## Audit Queries to Run

Before deploying the nullable field migration, run these queries on your database:

### High Priority - Critical Contact Fields

```sql
-- Check campuses.email
SELECT
    'campuses.email' as field,
    COUNT(*) as null_count,
    (SELECT COUNT(*) FROM campuses) as total_count,
    ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM campuses)), 2) as null_percentage
FROM campuses
WHERE email IS NULL;

-- Check oeps.email
SELECT
    'oeps.email' as field,
    COUNT(*) as null_count,
    (SELECT COUNT(*) FROM oeps) as total_count,
    ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM oeps)), 2) as null_percentage
FROM oeps
WHERE email IS NULL;

-- Check candidates.email
SELECT
    'candidates.email' as field,
    COUNT(*) as null_count,
    (SELECT COUNT(*) FROM candidates) as total_count,
    ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM candidates)), 2) as null_percentage
FROM candidates
WHERE email IS NULL;

-- Check instructors.name
SELECT
    'instructors.name' as field,
    COUNT(*) as null_count,
    (SELECT COUNT(*) FROM instructors) as total_count,
    ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM instructors)), 2) as null_percentage
FROM instructors
WHERE name IS NULL;

-- Check instructors.phone
SELECT
    'instructors.phone' as field,
    COUNT(*) as null_count,
    (SELECT COUNT(*) FROM instructors) as total_count,
    ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM instructors)), 2) as null_percentage
FROM instructors
WHERE phone IS NULL;
```

### Medium Priority - Content Fields

```sql
-- Check correspondences.subject
SELECT
    'correspondences.subject' as field,
    COUNT(*) as null_count,
    (SELECT COUNT(*) FROM correspondences) as total_count,
    ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM correspondences)), 2) as null_percentage
FROM correspondences
WHERE subject IS NULL;

-- Check correspondences.message
SELECT
    'correspondences.message' as field,
    COUNT(*) as null_count,
    (SELECT COUNT(*) FROM correspondences) as total_count,
    ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM correspondences)), 2) as null_percentage
FROM correspondences
WHERE message IS NULL;

-- Check complaints.subject
SELECT
    'complaints.subject' as field,
    COUNT(*) as null_count,
    (SELECT COUNT(*) FROM complaints) as total_count,
    ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM complaints)), 2) as null_percentage
FROM complaints
WHERE subject IS NULL;

-- Check complaints.description
SELECT
    'complaints.description' as field,
    COUNT(*) as null_count,
    (SELECT COUNT(*) FROM complaints) as total_count,
    ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM complaints)), 2) as null_percentage
FROM complaints
WHERE description IS NULL;

-- Check document_archives.document_name
SELECT
    'document_archives.document_name' as field,
    COUNT(*) as null_count,
    (SELECT COUNT(*) FROM document_archives) as total_count,
    ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM document_archives)), 2) as null_percentage
FROM document_archives
WHERE document_name IS NULL;

-- Check document_archives.document_type
SELECT
    'document_archives.document_type' as field,
    COUNT(*) as null_count,
    (SELECT COUNT(*) FROM document_archives) as total_count,
    ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM document_archives)), 2) as null_percentage
FROM document_archives
WHERE document_type IS NULL;

-- Check document_archives.file_path
SELECT
    'document_archives.file_path' as field,
    COUNT(*) as null_count,
    (SELECT COUNT(*) FROM document_archives) as total_count,
    ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM document_archives)), 2) as null_percentage
FROM document_archives
WHERE file_path IS NULL;

-- Check registration_documents.document_type
SELECT
    'registration_documents.document_type' as field,
    COUNT(*) as null_count,
    (SELECT COUNT(*) FROM registration_documents) as total_count,
    ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM registration_documents)), 2) as null_percentage
FROM registration_documents
WHERE document_type IS NULL;

-- Check complaint_updates.message
SELECT
    'complaint_updates.message' as field,
    COUNT(*) as null_count,
    (SELECT COUNT(*) FROM complaint_updates) as total_count,
    ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM complaint_updates)), 2) as null_percentage
FROM complaint_updates
WHERE message IS NULL;

-- Check complaint_evidence.file_name
SELECT
    'complaint_evidence.file_name' as field,
    COUNT(*) as null_count,
    (SELECT COUNT(*) FROM complaint_evidence) as total_count,
    ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM complaint_evidence)), 2) as null_percentage
FROM complaint_evidence
WHERE file_name IS NULL;

-- Check complaint_evidence.file_path
SELECT
    'complaint_evidence.file_path' as field,
    COUNT(*) as null_count,
    (SELECT COUNT(*) FROM complaint_evidence) as total_count,
    ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM complaint_evidence)), 2) as null_percentage
FROM complaint_evidence
WHERE file_path IS NULL;

-- Check training_classes.class_name
SELECT
    'training_classes.class_name' as field,
    COUNT(*) as null_count,
    (SELECT COUNT(*) FROM training_classes) as total_count,
    ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM training_classes)), 2) as null_percentage
FROM training_classes
WHERE class_name IS NULL;
```

### Low Priority - Foreign Key Fields (Requires Manual Review)

```sql
-- Check departures.candidate_id (potential orphaned records)
SELECT
    'departures.candidate_id' as field,
    COUNT(*) as null_count,
    (SELECT COUNT(*) FROM departures) as total_count
FROM departures
WHERE candidate_id IS NULL;

-- Check undertakings.candidate_id
SELECT
    'undertakings.candidate_id' as field,
    COUNT(*) as null_count,
    (SELECT COUNT(*) FROM undertakings) as total_count
FROM undertakings
WHERE candidate_id IS NULL;

-- Check undertakings.undertaking_date
SELECT
    'undertakings.undertaking_date' as field,
    COUNT(*) as null_count,
    (SELECT COUNT(*) FROM undertakings) as total_count
FROM undertakings
WHERE undertaking_date IS NULL;

-- Check visa_processes.candidate_id
SELECT
    'visa_processes.candidate_id' as field,
    COUNT(*) as null_count,
    (SELECT COUNT(*) FROM visa_processes) as total_count
FROM visa_processes
WHERE candidate_id IS NULL;

-- Check training_attendances.candidate_id
SELECT
    'training_attendances.candidate_id' as field,
    COUNT(*) as null_count,
    (SELECT COUNT(*) FROM training_attendances) as total_count
FROM training_attendances
WHERE candidate_id IS NULL;

-- Check training_attendances.batch_id
SELECT
    'training_attendances.batch_id' as field,
    COUNT(*) as null_count,
    (SELECT COUNT(*) FROM training_attendances) as total_count
FROM training_attendances
WHERE batch_id IS NULL;

-- Check training_attendances.date
SELECT
    'training_attendances.date' as field,
    COUNT(*) as null_count,
    (SELECT COUNT(*) FROM training_attendances) as total_count
FROM training_attendances
WHERE date IS NULL;

-- Check training_assessments.candidate_id
SELECT
    'training_assessments.candidate_id' as field,
    COUNT(*) as null_count,
    (SELECT COUNT(*) FROM training_assessments) as total_count
FROM training_assessments
WHERE candidate_id IS NULL;

-- Check training_assessments.batch_id
SELECT
    'training_assessments.batch_id' as field,
    COUNT(*) as null_count,
    (SELECT COUNT(*) FROM training_assessments) as total_count
FROM training_assessments
WHERE batch_id IS NULL;

-- Check training_assessments.assessment_date
SELECT
    'training_assessments.assessment_date' as field,
    COUNT(*) as null_count,
    (SELECT COUNT(*) FROM training_assessments) as total_count
FROM training_assessments
WHERE assessment_date IS NULL;

-- Check training_assessments.assessment_type
SELECT
    'training_assessments.assessment_type' as field,
    COUNT(*) as null_count,
    (SELECT COUNT(*) FROM training_assessments) as total_count
FROM training_assessments
WHERE assessment_type IS NULL;

-- Check training_assessments.score
SELECT
    'training_assessments.score' as field,
    COUNT(*) as null_count,
    (SELECT COUNT(*) FROM training_assessments) as total_count
FROM training_assessments
WHERE score IS NULL;

-- Check training_certificates.candidate_id
SELECT
    'training_certificates.candidate_id' as field,
    COUNT(*) as null_count,
    (SELECT COUNT(*) FROM training_certificates) as total_count
FROM training_certificates
WHERE candidate_id IS NULL;

-- Check training_certificates.batch_id
SELECT
    'training_certificates.batch_id' as field,
    COUNT(*) as null_count,
    (SELECT COUNT(*) FROM training_certificates) as total_count
FROM training_certificates
WHERE batch_id IS NULL;

-- Check training_certificates.issue_date
SELECT
    'training_certificates.issue_date' as field,
    COUNT(*) as null_count,
    (SELECT COUNT(*) FROM training_certificates) as total_count
FROM training_certificates
WHERE issue_date IS NULL;

-- Check audit_logs.action
SELECT
    'audit_logs.action' as field,
    COUNT(*) as null_count,
    (SELECT COUNT(*) FROM audit_logs) as total_count
FROM audit_logs
WHERE action IS NULL;
```

---

## Comprehensive Audit Query (All Fields at Once)

Run this single query to get a complete overview:

```sql
SELECT
    'High Priority' as category,
    'campuses.email' as field_name,
    COUNT(*) as null_count,
    (SELECT COUNT(*) FROM campuses) as total_records
FROM campuses WHERE email IS NULL

UNION ALL

SELECT
    'High Priority',
    'oeps.email',
    COUNT(*),
    (SELECT COUNT(*) FROM oeps)
FROM oeps WHERE email IS NULL

UNION ALL

SELECT
    'High Priority',
    'candidates.email',
    COUNT(*),
    (SELECT COUNT(*) FROM candidates)
FROM candidates WHERE email IS NULL

UNION ALL

SELECT
    'High Priority',
    'instructors.name',
    COUNT(*),
    (SELECT COUNT(*) FROM instructors)
FROM instructors WHERE name IS NULL

UNION ALL

SELECT
    'High Priority',
    'instructors.phone',
    COUNT(*),
    (SELECT COUNT(*) FROM instructors)
FROM instructors WHERE phone IS NULL

UNION ALL

SELECT
    'Medium Priority',
    'correspondences.subject',
    COUNT(*),
    (SELECT COUNT(*) FROM correspondences)
FROM correspondences WHERE subject IS NULL

UNION ALL

SELECT
    'Medium Priority',
    'correspondences.message',
    COUNT(*),
    (SELECT COUNT(*) FROM correspondences)
FROM correspondences WHERE message IS NULL

UNION ALL

SELECT
    'Medium Priority',
    'complaints.subject',
    COUNT(*),
    (SELECT COUNT(*) FROM complaints)
FROM complaints WHERE subject IS NULL

UNION ALL

SELECT
    'Medium Priority',
    'complaints.description',
    COUNT(*),
    (SELECT COUNT(*) FROM complaints)
FROM complaints WHERE description IS NULL

UNION ALL

SELECT
    'Medium Priority',
    'document_archives.document_name',
    COUNT(*),
    (SELECT COUNT(*) FROM document_archives)
FROM document_archives WHERE document_name IS NULL

UNION ALL

SELECT
    'Medium Priority',
    'document_archives.document_type',
    COUNT(*),
    (SELECT COUNT(*) FROM document_archives)
FROM document_archives WHERE document_type IS NULL

UNION ALL

SELECT
    'Medium Priority',
    'document_archives.file_path',
    COUNT(*),
    (SELECT COUNT(*) FROM document_archives)
FROM document_archives WHERE file_path IS NULL

ORDER BY category, field_name;
```

---

## Default Values Applied by Migration

The migration will automatically set these default values for NULL records:

### Email Fields
- **Default Value:** `noreply@btevta.gov.pk`
- **Applied to:** campuses.email, oeps.email, candidates.email
- **Rationale:** Provides a valid email format for system communications while indicating no reply address

### Name Fields
- **instructors.name:** `Unknown Instructor`
- **Rationale:** Maintains referential integrity while clearly indicating missing data

### Phone Fields
- **instructors.phone:** `0000000000`
- **Rationale:** Provides a valid phone format while indicating placeholder value

### Subject/Message Fields
- **correspondences.subject:** `No Subject`
- **correspondences.message:** `No message provided`
- **complaints.subject:** `No Subject`
- **complaints.description:** `No description provided`
- **complaint_updates.message:** `No update message`
- **Rationale:** Maintains data integrity while clearly indicating missing content

### Document Fields
- **document_archives.document_name:** `Unnamed Document`
- **document_archives.document_type:** `general`
- **document_archives.file_path:** `missing/file.pdf`
- **registration_documents.document_type:** `general`
- **Rationale:** Prevents constraint violations while flagging incomplete records

### Evidence Fields
- **complaint_evidence.file_name:** `unknown.pdf`
- **complaint_evidence.file_path:** `missing/evidence.pdf`
- **Rationale:** Maintains record integrity while indicating missing evidence

### Training Fields
- **training_classes.class_name:** `Unnamed Class`
- **Rationale:** Ensures class records remain valid

---

## Decision Matrix for Foreign Key Fields

These fields with NULL values require manual review before applying NOT NULL constraints:

| Table | Field | Risk Level | Recommended Action |
|-------|-------|------------|-------------------|
| departures | candidate_id | **HIGH** | DELETE orphaned records OR link to valid candidate |
| undertakings | candidate_id | **HIGH** | DELETE orphaned records OR link to valid candidate |
| undertakings | undertaking_date | **MEDIUM** | Set to record creation date OR soft delete |
| visa_processes | candidate_id | **HIGH** | DELETE orphaned records OR link to valid candidate |
| training_attendances | candidate_id | **HIGH** | DELETE orphaned records |
| training_attendances | batch_id | **HIGH** | DELETE orphaned records |
| training_attendances | date | **MEDIUM** | Set to record creation date OR delete |
| training_assessments | candidate_id | **HIGH** | DELETE orphaned records |
| training_assessments | batch_id | **HIGH** | DELETE orphaned records |
| training_assessments | assessment_date | **MEDIUM** | Set to record creation date OR delete |
| training_assessments | assessment_type | **MEDIUM** | Set to 'general' OR delete |
| training_assessments | score | **LOW** | Set to 0 OR delete |
| training_certificates | candidate_id | **HIGH** | DELETE orphaned records |
| training_certificates | batch_id | **HIGH** | DELETE orphaned records |
| training_certificates | issue_date | **MEDIUM** | Set to record creation date OR delete |
| audit_logs | action | **MEDIUM** | Set to 'unknown_action' OR delete |

### Action Codes:
- **DELETE orphaned records:** Records without valid foreign keys should be removed
- **Set to [value]:** Apply a default value if business logic permits
- **Soft delete:** Mark as deleted without permanent removal

---

## Pre-Deployment Checklist

- [ ] Run all audit queries and document results
- [ ] Review NULL counts and percentages for each field
- [ ] Confirm default values are acceptable for your business logic
- [ ] For foreign key fields with NULLs:
  - [ ] Identify root cause (data entry error, migration issue, etc.)
  - [ ] Decide on remediation strategy (delete, link, or set default)
  - [ ] Update migration file if different approach is needed
- [ ] Test migration on a database copy first
- [ ] Backup production database before deployment
- [ ] Plan rollback procedure if needed

---

## Post-Deployment Validation

After running the migration, validate the changes:

```sql
-- Verify no NULLs remain in critical fields
SELECT
    'campuses.email' as field,
    COUNT(*) as remaining_nulls
FROM campuses WHERE email IS NULL

UNION ALL

SELECT
    'oeps.email',
    COUNT(*)
FROM oeps WHERE email IS NULL

UNION ALL

SELECT
    'candidates.email',
    COUNT(*)
FROM candidates WHERE email IS NULL

-- Add similar checks for all other fields
-- All counts should be 0
```

---

## Contact & Support

If you encounter issues or need clarification:
1. Review the migration file: `2025_11_09_120003_prepare_nullable_field_fixes.php`
2. Check the main audit notes: `MIGRATION_AUDIT_NOTES.md`
3. Consult with your database administrator before making foreign key decisions
4. Test thoroughly on staging before production deployment

---

*Document Version: 1.0*
*Last Updated: 2025-11-09*
