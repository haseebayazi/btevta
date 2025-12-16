# Troubleshooting: "No Sample Data Appearing"

If you've run the seeder command but still can't see test data in your application, follow these diagnostic steps.

---

## 🔍 Quick Diagnosis (Start Here)

### Step 1: Verify if Data Actually Exists in Database

Run this command to check your database RIGHT NOW:

```bash
cd /path/to/btevta
php verify-data.php
```

**This will tell you:**
- ✅ If data exists in your database
- ❌ If database is empty
- 📊 Exact record counts for all tables
- 👥 Sample candidates with details

---

## 📊 Interpreting Results

### Result A: "✅ SUCCESS! Test data exists in your database"

**This means:** Data IS in your database, but your application can't see it.

**Solution:** Clear all caches

```bash
# Clear all Laravel caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

# If using OPcache, restart PHP-FPM or Apache
sudo systemctl restart php-fpm
# OR
sudo systemctl restart apache2
```

**Then check:**
1. Login to your application as admin
2. Go to Candidates menu
3. Data should now appear

**If still not visible:**
- Check user permissions (make sure you're logged in as admin)
- Check database connection in `.env` file
- Clear browser cache (Ctrl+Shift+Delete)
- Try a different browser or incognito mode

---

### Result B: "❌ NO CANDIDATES FOUND" + Total Records = 0

**This means:** Seeder didn't run successfully at all.

**Solution:** Run the diagnostic seeder

```bash
php run-seeder.php
```

**This will:**
- Show database connection status
- Run seeder with verbose output
- Show BEFORE and AFTER record counts
- Display any errors that occur
- Show sample data if created

**Common errors and fixes:**

#### Error: "Class 'TestDataSeeder' not found"
```bash
composer dump-autoload
php artisan db:seed --class=TestDataSeeder
```

#### Error: "SQLSTATE[23000]: Integrity constraint violation"
**Cause:** Foreign key constraints failing

**Fix:** Check if all required tables exist
```bash
php artisan migrate:status
```

If migrations are missing:
```bash
php artisan migrate
php artisan db:seed --class=TestDataSeeder
```

#### Error: "SQLSTATE[42S02]: Base table or view not found"
**Cause:** Missing database tables

**Fix:**
```bash
# Rebuild database from scratch
php artisan migrate:fresh
php artisan db:seed --class=TestDataSeeder
```

⚠️ **WARNING:** `migrate:fresh` will delete ALL existing data!

---

### Result C: "❌ NO CANDIDATES FOUND" + Some Other Data Exists

**This means:** Seeder started but failed partway through.

**Solution:** Run diagnostic seeder to see where it fails

```bash
php run-seeder.php
```

**Watch for the last "✓ Created" message before the error.**

**Possible issues:**

1. **Missing required fields in Candidate model**
   - Check `database/migrations/*_create_candidates_table.php`
   - Ensure all fields in seeder exist in migration

2. **Foreign key constraints**
   - Ensure campuses, trades, and OEPs exist before candidates
   - Check if foreign key fields are correct

3. **Database transaction rollback**
   - If any part fails, entire seeder might rollback
   - Run seeder again to see exact error

---

## 🛠️ Advanced Troubleshooting

### Check Database Connection

```bash
php artisan tinker
```

```php
// Test connection
DB::connection()->getPdo();

// Check which database you're using
DB::connection()->getDatabaseName();

// Count records directly
DB::table('candidates')->count();

exit
```

---

### Verify .env Database Configuration

Check your `.env` file:

```bash
cat .env | grep DB_
```

Should show:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

**Common issues:**
- Using `localhost` instead of `127.0.0.1` (try both)
- Wrong database name
- Wrong credentials
- Database doesn't exist

---

### Manually Run Seeder with Verbose Output

```bash
php artisan db:seed --class=TestDataSeeder --verbose
```

This shows each step as it happens.

---

### Check Laravel Logs

```bash
tail -n 50 storage/logs/laravel.log
```

Look for any errors related to database, seeding, or models.

---

### Verify Models Exist

```bash
ls -la app/Models/
```

Required models:
- ✅ User.php
- ✅ Campus.php
- ✅ Trade.php
- ✅ Oep.php
- ✅ Batch.php
- ✅ Candidate.php
- ✅ CandidateTraining.php
- ✅ CandidateScreening.php
- ✅ RegistrationDocument.php
- ✅ VisaProcessing.php
- ✅ Departure.php
- ✅ Complaint.php
- ✅ Correspondence.php
- ✅ Remittance.php
- ✅ DocumentArchive.php

---

## 🔄 Complete Reset (Last Resort)

If nothing else works, start fresh:

```bash
# 1. Backup your current .env file
cp .env .env.backup

# 2. Drop and recreate database
php artisan migrate:fresh

# 3. Run seeder with diagnostic
php run-seeder.php

# 4. Verify data
php verify-data.php

# 5. Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# 6. Check application
```

---

## 📋 Diagnostic Checklist

Work through this checklist:

- [ ] Ran `php verify-data.php` to check database
- [ ] Database connection is working (correct .env settings)
- [ ] Migrations have been run (`php artisan migrate`)
- [ ] All model files exist in `app/Models/`
- [ ] Ran seeder: `php artisan db:seed --class=TestDataSeeder`
- [ ] No errors shown in seeder output
- [ ] Data exists in database (verified with `verify-data.php`)
- [ ] Cleared all caches (`cache:clear`, `config:clear`, `view:clear`)
- [ ] Logged in as admin user
- [ ] Checked Laravel logs for errors
- [ ] Tried different browser or incognito mode

---

## 🎯 Expected Results

After successful seeding, you should see:

**In Database:**
- 7 Users (1 admin, 3 campus admins, 3 users)
- 4 Campuses (Lahore, Rawalpindi, Faisalabad, Multan)
- 10 Trades (Electrician, Plumber, Welder, etc.)
- 3 OEPs (employment agencies)
- 6 Batches (various statuses)
- 41 Candidates (at different workflow stages)
- Training records
- Screening records
- Registration documents
- Visa processing records
- Departure records
- Complaints
- Correspondence
- Remittances
- Document archives

**In Application:**
When you login and go to Candidates menu, you should see 41 candidates with:
- Different statuses (Applied, In Training, Departed, etc.)
- Complete profiles with CNIC, contact info
- Associated campuses and trades
- Different stages in the workflow

---

## 💡 Quick Reference

| Command | Purpose | When to Use |
|---------|---------|-------------|
| `php verify-data.php` | Check if data exists NOW | First step - always run this |
| `php run-seeder.php` | Run seeder with diagnostics | If database is empty |
| `php artisan cache:clear` | Clear application cache | If data exists but not visible |
| `php artisan migrate` | Create database tables | If tables missing |
| `php artisan migrate:fresh` | Reset entire database | Last resort only |

---

## 🆘 Still Stuck?

If you've tried everything and still have issues:

1. **Share the output of:**
   ```bash
   php verify-data.php
   ```

2. **Share the output of:**
   ```bash
   php run-seeder.php
   ```

3. **Check Laravel logs:**
   ```bash
   tail -n 100 storage/logs/laravel.log
   ```

4. **Verify environment:**
   ```bash
   php artisan about
   ```

---

## 🗑️ Cleanup After Success

Once everything is working and you have verified the data:

```bash
# Delete diagnostic scripts
rm verify-data.php
rm run-seeder.php
rm check-database.php
rm fix-admin.php
```

**Keep these files:**
- ✅ `database/seeders/TestDataSeeder.php` (for future use)
- ✅ `TEST_DATA_SETUP.md` (documentation)
- ✅ `FIX_ADMIN_LOGIN.md` (reference)

---

**Last Updated:** 2025-12-16
**For:** BTEVTA Laravel Application
**Laravel Version:** 11.x

