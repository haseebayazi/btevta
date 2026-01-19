# WASL v3 Deployment Checklist

**Project:** BTEVTA WASL v3
**Version:** 3.0.0
**Last Updated:** January 19, 2026

---

## Pre-Deployment Checklist

### 1. Code Review & Testing

- [ ] All code reviewed and approved
- [ ] All 86+ unit and integration tests passing
  ```bash
  php artisan test
  ```
- [ ] Code coverage meets minimum threshold (>80%)
- [ ] No critical or high-severity security vulnerabilities
  ```bash
  composer audit
  npm audit
  ```
- [ ] Code quality checks passed (PHPStan, PHP CS Fixer)
- [ ] All TODOs and FIXMEs resolved or documented

### 2. Database Preparation

- [ ] All 20 WASL v3 migrations tested
- [ ] Migration rollback tested successfully
- [ ] Database seeders prepared:
  - [ ] ProgramSeeder
  - [ ] CourseSeeder
  - [ ] DocumentChecklistSeeder
  - [ ] CountrySeeder
- [ ] Database backup strategy verified
- [ ] Migration order documented

### 3. Environment Configuration

- [ ] Production `.env` file prepared
- [ ] All environment variables documented
- [ ] Database credentials secured
- [ ] APP_DEBUG set to false
- [ ] APP_ENV set to production
- [ ] APP_URL configured correctly
- [ ] Mail configuration tested
- [ ] Queue configuration verified
- [ ] File storage configuration verified
- [ ] Redis/Cache configuration (if using)

### 4. Server Requirements

- [ ] PHP 8.2+ installed and configured
- [ ] MySQL 8.0+ installed and configured
- [ ] Composer installed
- [ ] Node.js 18+ and npm installed
- [ ] FFMpeg installed (for video processing)
- [ ] Nginx or Apache configured
- [ ] SSL certificate installed (Let's Encrypt)
- [ ] Firewall rules configured
- [ ] Backup system in place

### 5. Dependencies

- [ ] All PHP dependencies installed
  ```bash
  composer install --no-dev --optimize-autoloader
  ```
- [ ] All Node dependencies installed
  ```bash
  npm ci --production
  ```
- [ ] Production assets compiled
  ```bash
  npm run build
  ```

### 6. Security

- [ ] All dependencies up to date
- [ ] Security patches applied
- [ ] File permissions set correctly (775 for storage, 644 for files)
- [ ] .env file secured (600 permissions)
- [ ] Git repository excluded from public access
- [ ] Sensitive files in .gitignore
- [ ] CORS configured properly
- [ ] CSRF protection enabled
- [ ] Rate limiting configured

### 7. Documentation

- [ ] API Documentation complete (`docs/v3/API_DOCUMENTATION.md`)
- [ ] User Manual complete (`docs/v3/USER_MANUAL.md`)
- [ ] Admin Guide complete (`docs/v3/ADMIN_GUIDE.md`)
- [ ] Deployment Guide complete (this document)
- [ ] Data Migration Guide complete
- [ ] Training materials prepared
- [ ] README updated with v3 information

### 8. Backup & Recovery

- [ ] Backup strategy documented
- [ ] Automated backup script tested
- [ ] Backup restoration procedure tested
- [ ] Disaster recovery plan documented
- [ ] Backup retention policy defined
- [ ] Off-site backup location configured

### 9. Monitoring & Logging

- [ ] Application logging configured
- [ ] Log rotation configured
- [ ] Error monitoring setup (Sentry, Bugsnag, etc.)
- [ ] Performance monitoring setup (optional)
- [ ] Health check endpoint configured
- [ ] Uptime monitoring configured (UptimeRobot, Pingdom)

### 10. Stakeholder Communication

- [ ] Deployment schedule communicated to stakeholders
- [ ] Maintenance window announced
- [ ] Training sessions scheduled for staff
- [ ] Support team briefed
- [ ] Rollback plan communicated

---

## Deployment Steps

### Phase 1: Pre-Deployment (1 Day Before)

#### Step 1: Final Testing

```bash
# Run all tests
php artisan test --coverage

# Run security audit
composer audit
npm audit

# Check code quality
./vendor/bin/phpstan analyse

# Test migrations on staging
php artisan migrate --pretend
```

**Checklist:**
- [ ] All tests passing
- [ ] No security vulnerabilities
- [ ] Code quality acceptable
- [ ] Migrations verified

---

#### Step 2: Backup Current Production

```bash
# Backup database
mysqldump -u wasl_user -p btevta_wasl > backup_pre_v3_$(date +%Y%m%d).sql

# Backup files
tar -czf backup_pre_v3_files_$(date +%Y%m%d).tar.gz /var/www/btevta/storage/app

# Backup current .env
cp /var/www/btevta/.env /backups/env_backup_$(date +%Y%m%d)
```

**Checklist:**
- [ ] Database backed up
- [ ] Files backed up
- [ ] .env backed up
- [ ] Backups verified (can be extracted)

---

#### Step 3: Prepare Deployment Package

```bash
# On development machine
git checkout main
git pull origin main
git tag -a v3.0.0 -m "WASL v3.0.0 Release"
git push origin v3.0.0

# Create deployment package
tar -czf wasl-v3.0.0.tar.gz --exclude=node_modules --exclude=vendor --exclude=.git /path/to/btevta
```

**Checklist:**
- [ ] Code tagged in Git
- [ ] Deployment package created
- [ ] Package transferred to server
- [ ] Package checksum verified

---

### Phase 2: Deployment Day

#### Step 1: Enable Maintenance Mode

```bash
cd /var/www/btevta

# Enable maintenance mode with bypass secret
php artisan down --secret="btevta-v3-deploy-2026"

# Verify maintenance page displays
curl https://wasl.btevta.gov.pk
```

**Maintenance Page:**
- Displays "System Under Maintenance"
- Provides estimated completion time
- Provides support contact

**Checklist:**
- [ ] Maintenance mode enabled
- [ ] Maintenance page verified
- [ ] Bypass URL shared with testers

---

#### Step 2: Update Codebase

```bash
# Navigate to web root
cd /var/www/btevta

# Pull latest code (if using Git)
git fetch --all
git checkout v3.0.0

# Or extract deployment package
# tar -xzf wasl-v3.0.0.tar.gz -C /var/www/

# Set permissions
chown -R www-data:www-data /var/www/btevta
chmod -R 755 /var/www/btevta
chmod -R 775 /var/www/btevta/storage
chmod -R 775 /var/www/btevta/bootstrap/cache
```

**Checklist:**
- [ ] Code updated to v3.0.0
- [ ] Permissions set correctly
- [ ] File ownership correct

---

#### Step 3: Install Dependencies

```bash
# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node dependencies
npm ci --production

# Compile assets
npm run build
```

**Checklist:**
- [ ] Composer dependencies installed
- [ ] Node dependencies installed
- [ ] Assets compiled successfully
- [ ] No errors in build process

---

#### Step 4: Run Database Migrations

```bash
# Review migrations to be run
php artisan migrate:status

# Run migrations
php artisan migrate --force

# Verify migrations
php artisan migrate:status

# Seed new master data (if needed)
php artisan db:seed --class=WASLv3Seeder --force
```

**Expected Output:**
```
Migrating: 2026_01_15_create_countries_table
Migrated:  2026_01_15_create_countries_table (50ms)
...
(20 migrations total)
```

**Checklist:**
- [ ] All 20 v3 migrations ran successfully
- [ ] Migration status verified
- [ ] Master data seeded
- [ ] No migration errors

---

#### Step 5: Update Configuration

```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Cache configuration for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize

# Optimize application
php artisan optimize
```

**Checklist:**
- [ ] Caches cleared
- [ ] Configuration cached
- [ ] Routes cached
- [ ] Views cached
- [ ] Application optimized

---

#### Step 6: Storage & Permissions

```bash
# Create storage link (if not exists)
php artisan storage:link

# Set storage permissions
chown -R www-data:www-data storage
chmod -R 775 storage

# Create new v3 directories
mkdir -p storage/app/private/employers/evidence
mkdir -p storage/app/private/assessments
mkdir -p storage/app/private/documents
mkdir -p storage/app/public/success-stories

# Set permissions
chown -R www-data:www-data storage/app
chmod -R 775 storage/app
```

**Checklist:**
- [ ] Storage link created
- [ ] Storage permissions correct
- [ ] New directories created
- [ ] Directory permissions correct

---

#### Step 7: Update Queue Worker

```bash
# Update systemd service (if modified)
nano /etc/systemd/system/wasl-worker.service

# Reload systemd
systemctl daemon-reload

# Restart queue worker
systemctl restart wasl-worker

# Verify worker running
systemctl status wasl-worker
```

**Service File:**
```ini
[Unit]
Description=WASL v3 Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/btevta
ExecStart=/usr/bin/php /var/www/btevta/artisan queue:work --sleep=3 --tries=3 --max-time=3600
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

**Checklist:**
- [ ] Service file updated
- [ ] Systemd reloaded
- [ ] Worker restarted
- [ ] Worker status verified

---

#### Step 8: Assign WASL v3 Permissions

```bash
# Assign new v3 permissions to existing roles
php artisan wasl:assign-v3-permissions

# Verify permissions assigned
php artisan permission:show
```

**New Permissions Assigned:**
- view employers, create employers, edit employers, delete employers
- view success stories, create success stories, publish success stories
- view pre-departure documents, verify pre-departure documents
- view training assessments, create training assessments
- view post-departure details, record company switches

**Checklist:**
- [ ] V3 permissions assigned
- [ ] Permissions verified
- [ ] Role assignments correct

---

#### Step 9: Smoke Testing

**Test Critical Paths (While in Maintenance Mode with Bypass):**

```bash
# Access via bypass URL
https://wasl.btevta.gov.pk?secret=btevta-v3-deploy-2026
```

**Test Cases:**

1. **Login:**
   - [ ] Admin user can login
   - [ ] Data entry user can login
   - [ ] Campus director can login

2. **Candidate Registration:**
   - [ ] Can access registration form
   - [ ] Allocation fields visible (Campus, Program, Partner)
   - [ ] Course assignment dropdown populated
   - [ ] Can save registration
   - [ ] Batch auto-generated
   - [ ] Allocated number assigned

3. **Pre-Departure Documents:**
   - [ ] Document checklist displays
   - [ ] Can upload document
   - [ ] Can verify document (supervisor)

4. **Initial Screening:**
   - [ ] Screening form loads
   - [ ] Consent checkbox visible
   - [ ] Placement interest dropdown works
   - [ ] Can save screening with status "SCREENED"

5. **Training Assessment:**
   - [ ] Assessment form loads
   - [ ] Can select candidate from batch
   - [ ] Can enter scores
   - [ ] Pass/Fail calculated correctly
   - [ ] Evidence upload works

6. **Employer Module:**
   - [ ] Employers list displays
   - [ ] Can create new employer
   - [ ] Employment package fields work
   - [ ] Evidence upload works
   - [ ] Can assign candidates

7. **Success Stories:**
   - [ ] Success stories list displays
   - [ ] Can create new story
   - [ ] Evidence type selection works
   - [ ] Video upload works (queued)
   - [ ] Can publish story

8. **Post-Departure:**
   - [ ] Post-departure form loads
   - [ ] Residency & Identity section visible
   - [ ] Employment Details section visible
   - [ ] Company SWITCH section visible
   - [ ] Can save all details

9. **Complaints (Enhanced):**
   - [ ] Complaint form loads
   - [ ] 4-step workflow fields visible
   - [ ] Evidence upload works
   - [ ] Can save complaint

10. **Reports:**
    - [ ] All report links work
    - [ ] Filters functional
    - [ ] PDF export works
    - [ ] Excel export works

**Checklist:**
- [ ] All 10 smoke tests passed
- [ ] No critical errors
- [ ] No JavaScript errors (check console)
- [ ] All new v3 features accessible

---

#### Step 10: Disable Maintenance Mode

```bash
# Disable maintenance mode
php artisan up

# Verify site accessible
curl https://wasl.btevta.gov.pk
```

**Checklist:**
- [ ] Maintenance mode disabled
- [ ] Site accessible to all users
- [ ] Login page loads
- [ ] Dashboard loads

---

### Phase 3: Post-Deployment

#### Step 1: Monitor Application

**First 30 Minutes:**

```bash
# Monitor application logs
tail -f /var/www/btevta/storage/logs/laravel.log

# Monitor error log
tail -f /var/log/nginx/error.log

# Monitor queue worker
journalctl -u wasl-worker -f

# Monitor system resources
htop
```

**Look For:**
- [ ] No critical errors in logs
- [ ] Queue worker processing jobs
- [ ] Normal memory usage
- [ ] Normal CPU usage

---

#### Step 2: User Acceptance Testing

**Coordinate with Test Team:**

- [ ] Data entry staff test candidate registration flow
- [ ] Screening officers test new screening workflow
- [ ] Training officers test assessment recording
- [ ] Admin staff test employer management
- [ ] Media team test success stories
- [ ] Report viewers test new reports

**Expected Duration:** 2-4 hours

---

#### Step 3: Monitor Performance

**First 24 Hours:**

- [ ] Monitor response times
- [ ] Monitor database query performance
- [ ] Monitor file upload success rates
- [ ] Monitor video processing job success rates
- [ ] Monitor user login success rates

**Use Tools:**
- Laravel Telescope (if enabled)
- MySQL slow query log
- Nginx access logs
- Application logs

---

#### Step 4: Gather Feedback

- [ ] Collect user feedback via survey
- [ ] Document any issues encountered
- [ ] Create tickets for non-critical bugs
- [ ] Plan hotfix releases if needed

---

#### Step 5: Update Documentation

- [ ] Update CHANGELOG.md
- [ ] Update README.md with v3 badge
- [ ] Publish v3 documentation to portal
- [ ] Send announcement email to all users

---

## Rollback Plan

### When to Rollback

**Rollback if:**
- Critical functionality broken
- Data corruption detected
- Security vulnerability exposed
- System performance unacceptable
- Majority of users unable to work

**Do NOT rollback for:**
- Minor UI issues
- Non-critical bugs
- Performance issues that can be optimized
- Issues affecting <10% of users

### Rollback Steps

#### Step 1: Enable Maintenance Mode

```bash
php artisan down --message="System rollback in progress"
```

---

#### Step 2: Restore Database

```bash
# Stop queue worker
systemctl stop wasl-worker

# Drop database
mysql -u root -p
DROP DATABASE btevta_wasl;
CREATE DATABASE btevta_wasl CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;

# Restore from backup
mysql -u wasl_user -p btevta_wasl < backup_pre_v3_20260119.sql
```

---

#### Step 3: Restore Code

```bash
# Checkout previous stable version
cd /var/www/btevta
git checkout v2.9.5  # or previous stable tag

# Or restore from backup
# rm -rf /var/www/btevta
# tar -xzf backup_pre_v3_files_20260119.tar.gz -C /var/www/

# Reinstall dependencies
composer install --no-dev --optimize-autoloader
npm ci --production
npm run build
```

---

#### Step 4: Restore Configuration

```bash
# Restore .env
cp /backups/env_backup_20260119 /var/www/btevta/.env

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Recache
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

#### Step 5: Restart Services

```bash
# Restart queue worker
systemctl start wasl-worker

# Restart PHP-FPM
systemctl restart php8.2-fpm

# Restart Nginx
systemctl restart nginx
```

---

#### Step 6: Verify Rollback

- [ ] Application loads
- [ ] Login works
- [ ] Critical paths functional
- [ ] No errors in logs

---

#### Step 7: Disable Maintenance Mode

```bash
php artisan up
```

---

#### Step 8: Communicate

- [ ] Notify stakeholders of rollback
- [ ] Explain reason for rollback
- [ ] Provide timeline for v3 re-deployment
- [ ] Document issues encountered

---

## Post-Deployment Tasks

### Week 1

- [ ] Daily log review
- [ ] Daily performance monitoring
- [ ] Collect user feedback
- [ ] Address critical bugs immediately
- [ ] Document issues and resolutions

### Week 2-4

- [ ] Weekly log review
- [ ] Weekly performance report
- [ ] Plan minor updates/hotfixes
- [ ] Conduct user training sessions
- [ ] Update documentation based on feedback

### Month 2

- [ ] Monthly security audit
- [ ] Performance optimization review
- [ ] User satisfaction survey
- [ ] Plan v3.1 enhancements

---

## Success Criteria

Deployment considered successful if:

- [ ] All 20 migrations completed without errors
- [ ] All critical functionality working
- [ ] No data loss
- [ ] No security vulnerabilities
- [ ] User acceptance rate >90%
- [ ] System performance acceptable (<2s page load)
- [ ] No critical bugs in first week
- [ ] Rollback not required

---

## Contact Information

### Deployment Team

**Project Manager:**
- Name: [Name]
- Phone: [Phone]
- Email: [Email]

**Technical Lead:**
- Name: [Name]
- Phone: [Phone]
- Email: [Email]

**Database Administrator:**
- Name: [Name]
- Phone: [Phone]
- Email: [Email]

**System Administrator:**
- Name: [Name]
- Phone: [Phone]
- Email: [Email]

### Emergency Contacts

**On-Call Support:**
- Phone: +92-51-9204567
- Email: emergency@btevta.gov.pk

**Escalation:**
- Director IT: [Phone]
- CTO: [Phone]

---

## Deployment Sign-off

**Pre-Deployment Approval:**

| Role | Name | Signature | Date |
|------|------|-----------|------|
| Project Manager | | | |
| Technical Lead | | | |
| Database Administrator | | | |
| System Administrator | | | |

**Post-Deployment Verification:**

| Role | Name | Signature | Date |
|------|------|-----------|------|
| Project Manager | | | |
| Technical Lead | | | |
| QA Lead | | | |
| User Acceptance | | | |

---

## Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2026-01-19 | WASL Dev Team | Initial deployment checklist for v3.0.0 |

---

**Document End**

*This checklist should be reviewed and updated before each deployment.*
