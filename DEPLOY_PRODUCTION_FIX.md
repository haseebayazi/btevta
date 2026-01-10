# Production Deployment - Correspondence Fixes

## ✅ Status: Fixes are READY on GitHub Main Branch

**Git Commits:**
- `e3a1147` - fix: Align Correspondence model fillable array with actual database schema
- `0c92330` - fix: Correct correspondence seeder column names to match schema
- Merged via PR #116, currently on `main` branch at `origin/main`

## 🔧 What Was Fixed

### 1. Correspondence Model (`app/Models/Correspondence.php`)
**Problem:** Model had 26 columns in fillable array but only 11 exist in database
**Fix:** Aligned fillable array to match actual schema

**Correct fillable array:**
```php
protected $fillable = [
    'campus_id',
    'oep_id',
    'candidate_id',
    'subject',
    'message',          // ✓ Correct
    'requires_reply',
    'replied',
    'sent_at',          // ✓ Correct (was: correspondence_date)
    'replied_at',       // ✓ Correct (was: reply_date)
    'status',
    'attachment_path',
];
```

### 2. TestDataSeeder (`database/seeders/TestDataSeeder.php`)
**Problem:** Seeder using non-existent column names
**Fix:** Updated to use correct column names

**Correct seeder columns:**
```php
Correspondence::create([
    'subject' => 'Subject...',
    'message' => 'Content...',      // ✓ Changed from 'content'
    'sent_at' => now()->subDays(),  // ✓ Changed from 'correspondence_date'
    'replied_at' => now(),          // ✓ Changed from 'reply_date'
    'status' => 'pending',
    // ...
]);
```

## 📋 Production Server Deployment Steps

**Run these commands on your production server:**

### Step 1: Navigate to project directory
```bash
cd ~/oep.jaamiah.com
# or wherever your production Laravel app is located
```

### Step 2: Check current git status
```bash
git status
git log --oneline -5
```

### Step 3: Pull latest changes from GitHub
```bash
git fetch origin main
git pull origin main
```

**Expected output should show:**
```
Updating [old-commit]..a4b8010
Fast-forward
 app/Models/Correspondence.php           | XX +-
 database/seeders/TestDataSeeder.php     | XX +-
 ...
```

### Step 4: Verify files are correct
```bash
# Check Correspondence model has correct fillable array
grep -A 12 "protected \$fillable" app/Models/Correspondence.php

# Should show 11 columns: campus_id, oep_id, candidate_id, subject,
# message, requires_reply, replied, sent_at, replied_at, status, attachment_path
```

```bash
# Check seeder has correct column names
grep -A 10 "Correspondence::create" database/seeders/TestDataSeeder.php

# Should show: 'message', 'sent_at', 'replied_at' (NOT 'content', 'correspondence_date', 'reply_date')
```

### Step 5: Clear all Laravel caches
```bash
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

### Step 6: Run migrations and seeder
```bash
# IMPORTANT: Only run migrate:fresh on development/staging!
# For production with existing data, skip this step

# For fresh database (development/testing only):
php artisan migrate:fresh --seed --seeder=TestDataSeeder
```

### Step 7: Verify database
```bash
php artisan tinker
```

Then in tinker:
```php
// Check table structure
DB::select('DESCRIBE correspondences');

// Try creating a correspondence
\App\Models\Correspondence::create([
    'subject' => 'Test',
    'message' => 'Test message',
    'sent_at' => now(),
    'status' => 'pending',
]);

// If successful, you should see the created record
\App\Models\Correspondence::latest()->first();

exit
```

## 🔍 Verification Checklist

- [ ] Production server shows latest commit `a4b8010` or later
- [ ] `app/Models/Correspondence.php` has 11 columns in fillable array
- [ ] `database/seeders/TestDataSeeder.php` uses 'message', 'sent_at', 'replied_at'
- [ ] No SQL errors when running seeder
- [ ] Can create Correspondence records via tinker
- [ ] Application works without errors

## ⚠️ Troubleshooting

### Error: "Column not found: 'correspondence_date'"
**Solution:** Files not updated. Re-pull from GitHub:
```bash
git reset --hard origin/main
php artisan optimize:clear
```

### Error: "Duplicate column name 'trainer_id'"
**Solution:** Migration has been fixed with hasColumn() checks:
```bash
php artisan migrate:refresh
```

### Error: Still not working after pull
**Solution:** Check file permissions and ownership:
```bash
ls -la app/Models/Correspondence.php
ls -la database/seeders/TestDataSeeder.php

# If needed, fix permissions:
sudo chown -R www-data:www-data .
sudo chmod -R 755 .
```

## 📊 Database Schema Reference

**Correspondences table actual columns (from migration):**
```sql
CREATE TABLE correspondences (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    campus_id BIGINT UNSIGNED NULL,
    oep_id BIGINT UNSIGNED NULL,
    candidate_id BIGINT UNSIGNED NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    requires_reply BOOLEAN DEFAULT 0,
    replied BOOLEAN DEFAULT 0,
    sent_at TIMESTAMP NULL,
    replied_at TIMESTAMP NULL,
    status VARCHAR(50) DEFAULT 'pending',
    attachment_path VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL
);
```

## 📝 Related Documentation

- Full audit report: `MODEL_SCHEMA_AUDIT_2026-01-09.md`
- System map: `SYSTEM_MAP.md` (v1.3.0+)
- GitHub PR: #116 (merged to main)

---

**Last Updated:** 2026-01-10
**Status:** ✅ Ready for production deployment
