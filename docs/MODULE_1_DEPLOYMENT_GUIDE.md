# Module 1 Deployment Guide
## Pre-Departure Document Collection System

**Version**: 1.0  
**Last Updated**: January 27, 2026  
**Branch**: `claude/implement-wasl-module-1-AJYER`

---

## Overview

This guide provides step-by-step instructions for deploying Module 1 (Pre-Departure Document Collection System) to production or staging environments.

---

## Prerequisites

Before deploying, ensure:

- ✅ PHP 8.2+ installed
- ✅ Composer installed
- ✅ MySQL/MariaDB database configured
- ✅ Laravel 11 application setup
- ✅ Redis configured (optional, for cache)
- ✅ Storage directories writable
- ✅ Git access to the repository

---

## Deployment Steps

### 1. Pull Latest Code

```bash
cd /path/to/btevta
git fetch origin
git checkout claude/implement-wasl-module-1-AJYER
git pull origin claude/implement-wasl-module-1-AJYER
```

### 2. Install Dependencies

```bash
composer install --no-dev --optimize-autoloader
npm install && npm run build
```

### 3. Environment Configuration

Ensure `.env` file contains:

```env
# Storage Configuration
FILESYSTEM_DISK=private

# Activity Log (Already configured via Spatie)
ACTIVITY_LOGGER_ENABLED=true

# File Upload Limits
UPLOAD_MAX_FILESIZE=5M
POST_MAX_SIZE=6M
```

Update `php.ini` or `.htaccess` if needed:

```ini
upload_max_filesize = 5M
post_max_size = 6M
max_execution_time = 60
```

### 4. Run Database Migrations

**IMPORTANT**: Backup your database before running migrations!

```bash
# Backup first
php artisan db:backup  # or use mysqldump

# Run migrations
php artisan migrate --force

# Expected output:
# Migration: 2026_01_27_123738_create_candidate_licenses_table
# Migrated:  2026_01_27_123738_create_candidate_licenses_table (XX.XXms)
```

### 5. Seed Document Checklists

If this is the first time deploying Module 1:

```bash
php artisan db:seed --class=DocumentChecklistsSeeder --force
```

**Expected Data**:
- 5 mandatory documents: CNIC, Passport, Domicile, FRC, PCC
- 3 optional documents: Pre-Medical, Certifications, Resume
- Total: 8 checklist items

Verify seeding:

```bash
php artisan tinker
>>> App\Models\DocumentChecklist::count();
=> 8
>>> App\Models\DocumentChecklist::mandatory()->count();
=> 5
```

### 6. Create Required Storage Directories

```bash
# Create private storage directories
mkdir -p storage/app/private/pre-departure-documents
mkdir -p storage/app/private/candidate-licenses
mkdir -p storage/app/public/reports/pre-departure

# Set permissions
chmod -R 775 storage/app/private
chmod -R 775 storage/app/public/reports

# Create symbolic link (if not exists)
php artisan storage:link
```

### 7. Clear All Caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize
```

### 8. Verify Policy Registration

Check that policies are registered:

```bash
php artisan tinker
>>> Gate::getPolicyFor(App\Models\PreDepartureDocument::class);
=> App\Policies\PreDepartureDocumentPolicy
>>> Gate::getPolicyFor(App\Models\CandidateLicense::class);
=> App\Policies\CandidateLicensePolicy
```

### 9. Test Workflow Gate

Test the critical workflow enforcement:

```bash
php artisan tinker

# Create test candidate
$candidate = App\Models\Candidate::factory()->create(['status' => 'new']);

# Check transition (should fail without documents)
$result = $candidate->canTransitionToScreening();
var_dump($result);

# Expected output:
# array(2) {
#   ["can_transition"]=> bool(false)
#   ["issues"]=> array(1) {
#     [0]=> string(XX) "All mandatory pre-departure documents must be uploaded..."
#   }
# }
```

### 10. Verify Routes

```bash
php artisan route:list --name=pre-departure

# Expected output should include:
# candidates.pre-departure-documents.index
# candidates.pre-departure-documents.store
# candidates.licenses.store
# reports.pre-departure.individual
# reports.pre-departure.bulk
# api.candidates.pre-departure-documents.index
```

---

## Post-Deployment Verification

### A. Database Verification

```sql
-- Check tables exist
SHOW TABLES LIKE '%license%';
-- Expected: candidate_licenses

-- Check document checklists
SELECT id, name, code, is_mandatory, is_active FROM document_checklists;
-- Expected: 8 rows (5 mandatory, 3 optional)

-- Check indexes
SHOW INDEX FROM candidate_licenses;
-- Expected: PRIMARY, candidate_id foreign key
```

### B. File Upload Test

1. Navigate to any candidate in 'new' status
2. Go to "Pre-Departure Documents" section
3. Upload a test document (PDF)
4. Verify file appears in `storage/app/private/pre-departure-documents/{candidate_id}/`
5. Test download functionality

### C. Workflow Gate Test

1. Create a new candidate
2. Attempt to change status to 'screening'
3. Should see error: "All mandatory pre-departure documents must be uploaded"
4. Upload all 5 mandatory documents
5. Attempt status change again
6. Should succeed

### D. API Endpoint Test

```bash
# Get auth token (via Sanctum)
curl -X POST http://your-domain.com/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@theleap.org","password":"your-password"}'

# Test document listing
curl -X GET http://your-domain.com/api/v1/candidates/{candidate_id}/pre-departure-documents \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"

# Expected: JSON response with documents array and status object
```

### E. Permission Test

Test role-based access:

1. **Super Admin**: Can view all candidates' documents
2. **Campus Admin**: Can only view their campus candidates
3. **OEP**: Can only view assigned candidates
4. **Read-only after 'new' status**: Documents locked for editing

---

## Rollback Procedure

If deployment fails:

### 1. Rollback Database

```bash
php artisan migrate:rollback --step=1 --force
```

This will rollback the `candidate_licenses` table creation.

### 2. Rollback Code

```bash
git checkout main  # or previous stable branch
composer install --no-dev
php artisan config:clear
php artisan cache:clear
php artisan optimize
```

### 3. Clean Storage

```bash
rm -rf storage/app/private/pre-departure-documents/*
rm -rf storage/app/private/candidate-licenses/*
rm -rf storage/app/public/reports/pre-departure/*
```

---

## Troubleshooting

### Issue: "Class 'DocumentChecklist' not found"

**Solution**:
```bash
composer dump-autoload
php artisan config:clear
php artisan cache:clear
```

### Issue: File upload fails with "413 Payload Too Large"

**Solution**: Increase upload limits in:
- `.env` or `php.ini`
- Nginx: `client_max_body_size 10M;`
- Apache: `LimitRequestBody 10485760`

### Issue: "Storage disk 'private' not configured"

**Solution**: Add to `config/filesystems.php`:
```php
'private' => [
    'driver' => 'local',
    'root' => storage_path('app/private'),
    'visibility' => 'private',
],
```

### Issue: Policies not working

**Solution**:
```bash
# Clear policy cache
php artisan optimize:clear

# Re-register policies
php artisan config:clear
```

### Issue: Workflow gate not enforcing

**Solution**:
1. Check `DocumentChecklist::mandatory()->active()->count()` returns 5
2. Verify candidate status is 'new'
3. Check `$candidate->preDepartureDocuments()->count()`
4. Clear model cache: `php artisan model:cache:clear` (if using model caching)

---

## Performance Optimization

### 1. Enable OpCache

In `php.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
```

### 2. Optimize Autoloader

```bash
composer dump-autoload --optimize --classmap-authoritative
```

### 3. Cache Configuration

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 4. Queue Workers (for reports)

```bash
# Start queue worker for report generation
php artisan queue:work --queue=reports --tries=3
```

---

## Monitoring

### 1. Activity Logs

All document operations are logged:

```bash
# View recent activity
php artisan tinker
>>> Spatie\Activitylog\Models\Activity::latest()->take(10)->get();
```

### 2. Error Logs

Monitor Laravel logs:
```bash
tail -f storage/logs/laravel.log | grep "Pre-departure"
```

### 3. File Storage Usage

```bash
# Check storage usage
du -sh storage/app/private/pre-departure-documents
du -sh storage/app/private/candidate-licenses
```

---

## Security Checklist

- ✅ All file uploads validated (type, size)
- ✅ Files stored in private disk (not publicly accessible)
- ✅ Policy-based authorization on every endpoint
- ✅ Activity logging enabled for audit trail
- ✅ Rate limiting applied (30 req/min uploads, 10 req/min reports)
- ✅ No SQL injection vulnerabilities (using Eloquent)
- ✅ No XSS vulnerabilities (Blade auto-escaping)
- ✅ CSRF protection enabled
- ✅ API authentication via Sanctum

---

## Support

For issues or questions:

1. Check troubleshooting section above
2. Review implementation summary: `docs/MODULE_1_IMPLEMENTATION_SUMMARY.md`
3. Check session logs: https://claude.ai/code/session_01JBqrxDd16aDdSkUSZXaUnu
4. Contact development team

---

## Appendix A: File Locations

| Component | Path |
|-----------|------|
| Migration | `database/migrations/2026_01_27_123738_create_candidate_licenses_table.php` |
| Models | `app/Models/CandidateLicense.php`, `app/Models/Candidate.php` (updated) |
| Service | `app/Services/PreDepartureDocumentService.php` |
| Policies | `app/Policies/PreDepartureDocumentPolicy.php`, `app/Policies/CandidateLicensePolicy.php` |
| Controllers | `app/Http/Controllers/PreDepartureDocumentController.php` (+ 3 more) |
| Routes | `routes/web.php` (lines 164-189), `routes/api.php` (lines 140-150) |
| Views | `resources/views/candidates/pre-departure-documents/` |
| Tests | `tests/Unit/`, `tests/Feature/` (7 test files) |

---

## Appendix B: Required Roles

Module 1 uses these roles (must exist in database):

- `super_admin`: Full access, can override all restrictions
- `project_director`: Can view and verify all documents
- `campus_admin`: Campus-specific access, can verify documents
- `oep`: Can manage assigned candidates' documents

Verify roles exist:
```bash
php artisan tinker
>>> Spatie\Permission\Models\Role::pluck('name');
```

---

**Deployment Complete!** 🎉

Module 1 is now live and the workflow gate is enforcing document requirements.
