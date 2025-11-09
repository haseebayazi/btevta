# BTEVTA System - Issues, Bugs & Phase-wise Fix Plan

**Generated:** 2025-11-09
**System:** BTEVTA Overseas Employment Management System
**Framework:** Laravel 11.x | PHP 8.2+ | MySQL 8.0+

---

## Executive Summary

This document outlines all identified issues, inconsistencies, bugs, and missing components in the BTEVTA Laravel application, organized into manageable phases for systematic resolution.

### Overall Health Assessment

| Category | Status | Coverage |
|----------|--------|----------|
| **Controllers** | ✅ Complete | 19/19 (100%) |
| **Models** | ✅ Complete | 19/19 (100%) |
| **Middleware** | ✅ Complete | 10/10 (100%) |
| **Services** | ✅ Complete | 8/8 (100%) |
| **Views** | ⚠️ Issues Found | 113 files + 66 duplicates |
| **Routes** | ✅ Complete | ~180 routes |
| **Migrations** | ✅ Complete | 17 migrations |
| **Factories** | ⚠️ Incomplete | 6/19 (31%) |
| **Tests** | ⚠️ Limited | 6 files (~10% coverage) |
| **Kernel.php** | 🔴 CRITICAL | RTF format - BROKEN |

---

## Issues Classification

### 🔴 **CRITICAL ISSUES** (Must Fix Immediately)
- **1 issue** - System won't run properly

### 🟠 **HIGH PRIORITY** (Fix in Phase 1)
- **3 issues** - Code cleanup, duplicate removal

### 🟡 **MEDIUM PRIORITY** (Fix in Phase 2)
- **6 issues** - Missing components, incomplete features

### 🟢 **LOW PRIORITY** (Fix in Phase 3)
- **5 issues** - Optimization, best practices

**Total Issues Identified:** 15

---

## 🔴 CRITICAL ISSUES

### ISSUE #1: Kernel.php File Corruption

**Location:** `/app/Http/Kernel.php`

**Problem:**
The file is in RTF (Rich Text Format) instead of plain PHP text format. The file starts with:
```
{\rtf1\ansi\ansicpg1252\cocoartf1561\cocoasubrtf610
```

**Impact:**
- PHP parser will fail to read this file
- Application will throw fatal errors
- Routes and middleware won't work
- System is completely broken

**How to Reproduce:**
```bash
php artisan route:list
# Will fail with parse error
```

**Fix Required:**
Convert the RTF file to plain text PHP or recreate it from scratch.

**Fix Command:**
```bash
# Backup the corrupted file
cp app/Http/Kernel.php app/Http/Kernel.php.corrupted

# Create a new Kernel.php with proper format
# (Implementation in Phase 0 - Emergency Fixes)
```

**Priority:** CRITICAL - Fix before anything else

---

## 🟠 HIGH PRIORITY ISSUES

### ISSUE #2: Duplicate Nested View Files

**Location:** `/resources/views/resources/views/`

**Problem:**
There's an incorrectly nested directory structure containing 66 duplicate view files:
```
/resources/views/resources/views/admin/
/resources/views/resources/views/candidates/
/resources/views/resources/views/complaints/
... (and more)
```

**Impact:**
- Wasted disk space (~500KB+)
- Confusion during development
- Risk of serving wrong views
- Laravel may load incorrect templates
- Version control bloat

**Files Affected:** 66 duplicate blade files

**Fix Required:**
Delete the entire nested `/resources/views/resources/` directory.

**Fix Command:**
```bash
# Verify the duplicates first
ls -la resources/views/resources/views/

# Remove the nested directory
rm -rf resources/views/resources/

# Verify views still work
php artisan view:clear
php artisan view:cache
```

**Priority:** HIGH

---

### ISSUE #3: Backup Configuration Files in Production

**Location:** `/config/`

**Problem:**
Multiple backup/temporary config files present:
- `database.php.backup`
- `database.php.bak`
- `database.php.fixed`

**Impact:**
- Confusion about which config is active
- Potential security risk if backups contain sensitive data
- Disk space waste
- Unprofessional codebase

**Fix Required:**
Remove backup files or move to a dedicated `.old/` directory outside the main codebase.

**Fix Command:**
```bash
# Option 1: Delete backups
rm config/database.php.backup
rm config/database.php.bak
rm config/database.php.fixed

# Option 2: Archive backups
mkdir -p storage/old_configs
mv config/*.backup storage/old_configs/
mv config/*.bak storage/old_configs/
mv config/*.fixed storage/old_configs/
```

**Priority:** HIGH

---

### ISSUE #4: Backup Migration File

**Location:** `/database/migrations/2025_10_31_112521_create_complaints_table.php.bak`

**Problem:**
A backup migration file exists in the migrations directory.

**Impact:**
- Could confuse migration system
- Appears in migration listings
- Version control bloat

**Fix Required:**
Remove or archive the backup file.

**Fix Command:**
```bash
rm database/migrations/2025_10_31_112521_create_complaints_table.php.bak
```

**Priority:** HIGH

---

## 🟡 MEDIUM PRIORITY ISSUES

### ISSUE #5: Incomplete Factory Implementation

**Location:** `/database/factories/`

**Problem:**
Only 6 out of 19 models have factory definitions (31% coverage).

**Models WITH Factories:**
1. ✅ Candidate
2. ✅ User
3. ✅ Campus
4. ✅ Trade
5. ✅ Batch
6. ✅ Oep

**Models MISSING Factories:**
1. ❌ Complaint
2. ❌ CandidateScreening
3. ❌ Correspondence
4. ❌ Departure
5. ❌ DocumentArchive
6. ❌ NextOfKin
7. ❌ RegistrationDocument
8. ❌ SystemSetting
9. ❌ TrainingAssessment
10. ❌ TrainingAttendance
11. ❌ TrainingCertificate
12. ❌ Undertaking
13. ❌ VisaProcess

**Impact:**
- Limited test data generation
- Difficult to seed realistic data
- Harder to write comprehensive tests
- Manual data creation needed for testing

**Fix Required:**
Create factory classes for all 13 missing models.

**Example Template:**
```php
// database/factories/ComplaintFactory.php
namespace Database\Factories;

use App\Models\Candidate;
use App\Models\Complaint;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ComplaintFactory extends Factory
{
    protected $model = Complaint::class;

    public function definition(): array
    {
        return [
            'candidate_id' => Candidate::factory(),
            'complaint_category' => fake()->randomElement([
                'training', 'salary', 'conduct', 'visa', 'accommodation', 'other'
            ]),
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'status' => 'open',
            'priority' => fake()->randomElement(['low', 'normal', 'high', 'urgent']),
        ];
    }
}
```

**Priority:** MEDIUM

---

### ISSUE #6: Limited Test Coverage

**Location:** `/tests/`

**Problem:**
Only 6 test files exist for an application with:
- 19 Controllers
- 19 Models
- 8 Services
- ~180 Routes

**Current Test Files:**
1. `CreatesApplication.php` (setup)
2. `TestCase.php` (base)
3. `Feature/AuthenticationTest.php`
4. `Feature/CandidateManagementTest.php`
5. `Feature/CandidateModelTest.php`
6. `Unit/CandidateModelTest.php`

**Coverage Estimate:** ~10%

**Impact:**
- High risk of undetected bugs
- Difficult refactoring
- No regression protection
- Hard to maintain code quality

**Fix Required:**
Create comprehensive test suite covering:
- All controller methods
- Model relationships
- Service logic
- API endpoints
- Validation rules
- Authorization policies

**Test Files Needed:**
```
tests/
├── Feature/
│   ├── AuthenticationTest.php ✅
│   ├── CandidateManagementTest.php ✅
│   ├── ScreeningManagementTest.php ❌
│   ├── RegistrationManagementTest.php ❌
│   ├── TrainingManagementTest.php ❌
│   ├── VisaProcessingTest.php ❌
│   ├── DepartureManagementTest.php ❌
│   ├── ComplaintManagementTest.php ❌
│   ├── CorrespondenceTest.php ❌
│   ├── DocumentArchiveTest.php ❌
│   ├── ReportGenerationTest.php ❌
│   ├── ImportExportTest.php ❌
│   └── AdminManagementTest.php ❌
├── Unit/
│   ├── CandidateModelTest.php ✅
│   ├── ComplaintServiceTest.php ❌
│   ├── ScreeningServiceTest.php ❌
│   ├── TrainingServiceTest.php ❌
│   ├── VisaProcessingServiceTest.php ❌
│   ├── DepartureServiceTest.php ❌
│   ├── NotificationServiceTest.php ❌
│   └── DocumentArchiveServiceTest.php ❌
```

**Priority:** MEDIUM

---

### ISSUE #7: Empty/Incomplete .env.example File

**Location:** `/.env.example`

**Problem:**
The `.env.example` file contains only 1 line (essentially empty).

**Impact:**
- New developers can't set up environment easily
- No documentation of required environment variables
- Deployment errors likely
- Missing configuration examples

**Fix Required:**
Create a comprehensive `.env.example` with all required variables.

**Example Content Needed:**
```env
APP_NAME="BTEVTA Overseas Employment System"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=Asia/Karachi
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=btevta
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database
CACHE_PREFIX=

SESSION_DRIVER=database
SESSION_LIFETIME=120

MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@btevta.gov.pk"
MAIL_FROM_NAME="${APP_NAME}"

# Activity Log
ACTIVITYLOG_ENABLED=true
ACTIVITYLOG_DELETE_RECORDS_OLDER_THAN_DAYS=365

# File Upload Settings
MAX_UPLOAD_SIZE=10240
ALLOWED_DOCUMENT_TYPES=pdf,jpg,jpeg,png,doc,docx

# SLA Configuration
COMPLAINT_SLA_HOURS=72
DOCUMENT_EXPIRY_ALERT_DAYS=30
```

**Priority:** MEDIUM

---

### ISSUE #8: Missing Config Files

**Location:** `/config/`

**Problem:**
Standard Laravel config files are missing or not published:
- `cache.php` - Not visible
- `mail.php` - Not visible
- `queue.php` - Not visible
- `session.php` - Not visible

**Impact:**
- Relies on framework defaults
- Can't customize caching strategy
- Email configuration unclear
- Queue configuration hidden
- Session settings not documented

**Fix Required:**
Publish missing configuration files if needed.

**Fix Command:**
```bash
# Publish all config files
php artisan config:publish

# Or publish individually
php artisan vendor:publish --tag=config
```

**Priority:** MEDIUM (only if production deployment needs customization)

---

### ISSUE #9: No API Documentation

**Location:** N/A

**Problem:**
The application has API endpoints but no API documentation:
- `/api/candidates/search`
- `/api/campuses/list`
- `/api/oeps/list`
- `/api/trades/list`
- `/api/batches/by-campus/{id}`
- `/api/notifications`

**Impact:**
- Frontend developers have no reference
- Integration difficult
- No request/response examples
- Can't test API easily

**Fix Required:**
Create OpenAPI/Swagger documentation or use Laravel API Documentation packages.

**Recommended Tools:**
```bash
# Option 1: Scribe
composer require --dev knuckleswtf/scribe

# Option 2: L5-Swagger
composer require darkaonline/l5-swagger

# Option 3: Manual OpenAPI YAML
touch docs/api-documentation.yaml
```

**Priority:** MEDIUM

---

### ISSUE #10: Inconsistent Migration Dates

**Location:** `/database/migrations/`

**Problem:**
Two migrations have the same filename on different dates:
- `2025_11_01_xxxxx_add_missing_columns.php`
- `2025_11_04_xxxxx_add_missing_columns.php`

**Impact:**
- Confusion about what columns are added where
- Difficult to track schema changes
- Could indicate incomplete migration planning

**Fix Required:**
Rename migrations to be more descriptive:
```
add_missing_columns.php → add_candidate_status_columns.php
add_missing_columns.php → add_complaint_sla_columns.php
```

**Priority:** MEDIUM

---

## 🟢 LOW PRIORITY ISSUES

### ISSUE #11: Large Route File

**Location:** `/routes/web.php` (323 lines)

**Problem:**
All ~180 routes are defined in a single file.

**Impact:**
- Harder to maintain
- Difficult to locate specific routes
- Merge conflicts more likely

**Fix Required:**
Consider splitting routes into logical groups:
```php
routes/
├── web.php (main + auth)
├── admin.php (admin routes)
├── candidate.php (candidate management)
├── training.php (training routes)
├── visa.php (visa processing)
├── api.php (API routes)
```

**Example Implementation:**
```php
// routes/web.php
Route::prefix('admin')->middleware(['auth', 'role:admin'])->group(
    base_path('routes/admin.php')
);
```

**Priority:** LOW

---

### ISSUE #12: No Docker/Container Setup

**Location:** N/A

**Problem:**
No Docker or containerization setup found.

**Impact:**
- Inconsistent development environments
- Harder to onboard new developers
- Deployment variations across servers

**Fix Required:**
Add Docker support:
```bash
# Laravel Sail (recommended)
php artisan sail:install

# Or manual docker-compose.yml
touch docker-compose.yml
touch Dockerfile
```

**Priority:** LOW (but recommended)

---

### ISSUE #13: Missing CI/CD Pipeline

**Location:** N/A

**Problem:**
No GitHub Actions, GitLab CI, or other CI/CD configuration found.

**Impact:**
- Manual testing required
- No automated quality checks
- Deployment not automated

**Fix Required:**
Add CI/CD pipeline configuration.

**Example GitHub Actions:**
```yaml
# .github/workflows/tests.yml
name: Tests

on: [push, pull_request]

jobs:
  tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - name: Install Dependencies
        run: composer install
      - name: Run Tests
        run: php artisan test
```

**Priority:** LOW

---

### ISSUE #14: No Postman Collection or API Testing Setup

**Location:** N/A

**Problem:**
No Postman collection or API testing documentation.

**Impact:**
- Manual API testing
- No shared test scenarios
- Difficult to verify API functionality

**Fix Required:**
Create Postman collection or API test suite.

**Priority:** LOW

---

### ISSUE #15: Missing Cache Configuration

**Location:** Cache usage in code but no clear cache config

**Problem:**
Models and services may use caching but no visible cache.php configuration.

**Impact:**
- Unclear cache strategy
- Performance optimization harder
- Cache invalidation not documented

**Fix Required:**
Publish cache configuration and document caching strategy.

**Priority:** LOW

---

## Phase-wise Fix Plan

### 🚨 PHASE 0: EMERGENCY FIXES (Immediate - Day 1)
**Objective:** Make the application functional

#### Tasks:
- [ ] **Fix Kernel.php RTF format issue** (#1)
  - Backup corrupted file
  - Create new Kernel.php with proper PHP code
  - Test middleware registration
  - Verify routes work

**Estimated Time:** 30 minutes
**Risk:** HIGH - System won't run without this

**Validation:**
```bash
php artisan route:list
php artisan config:clear
php artisan serve
# Should work without errors
```

---

### 🧹 PHASE 1: CLEANUP & HOUSEKEEPING (Week 1)
**Objective:** Remove duplicates, clean up codebase

#### Tasks:
- [ ] **Remove duplicate nested views** (#2)
  - Backup views directory (optional)
  - Delete `/resources/views/resources/` directory
  - Clear view cache
  - Test all view routes

- [ ] **Clean up backup config files** (#3)
  - Archive or delete `database.php.backup`
  - Archive or delete `database.php.bak`
  - Archive or delete `database.php.fixed`
  - Update `.gitignore` to prevent future backups

- [ ] **Remove backup migration file** (#4)
  - Delete `.bak` migration file
  - Verify migration integrity

- [ ] **Create comprehensive .env.example** (#7)
  - Document all environment variables
  - Add comments for each variable
  - Include example values
  - Update README with setup instructions

**Estimated Time:** 4-6 hours
**Risk:** LOW - These are cleanup tasks

**Validation:**
```bash
# Test that views work
php artisan view:clear
php artisan view:cache

# Test config
php artisan config:cache

# Test migrations
php artisan migrate:status
```

---

### 🏗️ PHASE 2: MISSING COMPONENTS (Week 2-3)
**Objective:** Complete factory and test coverage

#### Tasks:
- [ ] **Create missing model factories** (#5)
  - ComplaintFactory
  - CandidateScreeningFactory
  - CorrespondenceFactory
  - DepartureFactory
  - DocumentArchiveFactory
  - NextOfKinFactory
  - RegistrationDocumentFactory
  - SystemSettingFactory
  - TrainingAssessmentFactory
  - TrainingAttendanceFactory
  - TrainingCertificateFactory
  - UndertakingFactory
  - VisaProcessFactory

- [ ] **Expand test coverage** (#6)
  - Create Feature tests for all controllers
  - Create Unit tests for all services
  - Create Model tests for relationships
  - Add Integration tests for workflows
  - Target: 70%+ code coverage

- [ ] **Fix migration naming** (#10)
  - Rename ambiguous migrations
  - Add descriptive comments
  - Update migration documentation

**Estimated Time:** 2-3 weeks
**Risk:** MEDIUM - Requires good understanding of models

**Validation:**
```bash
# Test factories
php artisan tinker
>>> App\Models\Complaint::factory()->create()

# Run tests
php artisan test
php artisan test --coverage
```

---

### 📚 PHASE 3: DOCUMENTATION & OPTIMIZATION (Week 4)
**Objective:** Improve documentation and code organization

#### Tasks:
- [ ] **Create API documentation** (#9)
  - Install Scribe or L5-Swagger
  - Document all API endpoints
  - Add request/response examples
  - Include authentication details

- [ ] **Publish missing config files** (#8)
  - Publish cache.php
  - Publish mail.php
  - Publish queue.php
  - Publish session.php
  - Customize as needed

- [ ] **Split route files** (#11)
  - Create route groups
  - Organize by module
  - Update RouteServiceProvider

**Estimated Time:** 1 week
**Risk:** LOW - Organizational improvements

**Validation:**
```bash
# Verify routes still work
php artisan route:list

# Check API docs generated
php artisan scribe:generate
```

---

### 🚀 PHASE 4: DEVOPS & AUTOMATION (Week 5 - Optional)
**Objective:** Improve development workflow

#### Tasks:
- [ ] **Add Docker support** (#12)
  - Install Laravel Sail
  - Create docker-compose.yml
  - Document Docker setup
  - Test on clean environment

- [ ] **Setup CI/CD pipeline** (#13)
  - Create GitHub Actions workflow
  - Add automated testing
  - Add code style checks
  - Setup automated deployment

- [ ] **Create Postman collection** (#14)
  - Export all API routes
  - Add test scenarios
  - Share with team

- [ ] **Document caching strategy** (#15)
  - Configure cache drivers
  - Document cache keys
  - Setup cache invalidation

**Estimated Time:** 1-2 weeks
**Risk:** LOW - Optional improvements

---

## Quick Reference: Fix Priority Matrix

| Issue | Severity | Impact | Effort | Phase | Days to Fix |
|-------|----------|--------|--------|-------|-------------|
| #1 - Kernel.php RTF | 🔴 Critical | 10/10 | Low | 0 | 0.1 |
| #2 - Duplicate Views | 🟠 High | 6/10 | Low | 1 | 0.5 |
| #3 - Backup Configs | 🟠 High | 4/10 | Low | 1 | 0.3 |
| #4 - Backup Migration | 🟠 High | 3/10 | Low | 1 | 0.1 |
| #5 - Missing Factories | 🟡 Medium | 5/10 | High | 2 | 10 |
| #6 - Limited Tests | 🟡 Medium | 7/10 | Very High | 2 | 15 |
| #7 - Empty .env.example | 🟡 Medium | 5/10 | Low | 1 | 1 |
| #8 - Missing Configs | 🟡 Medium | 4/10 | Low | 3 | 0.5 |
| #9 - No API Docs | 🟡 Medium | 5/10 | Medium | 3 | 3 |
| #10 - Migration Names | 🟡 Medium | 2/10 | Low | 2 | 0.5 |
| #11 - Large Route File | 🟢 Low | 3/10 | Medium | 3 | 2 |
| #12 - No Docker | 🟢 Low | 4/10 | Medium | 4 | 3 |
| #13 - No CI/CD | 🟢 Low | 5/10 | Medium | 4 | 4 |
| #14 - No Postman | 🟢 Low | 3/10 | Low | 4 | 1 |
| #15 - Cache Config | 🟢 Low | 2/10 | Low | 4 | 0.5 |

**Total Estimated Time:**
- Phase 0: 0.5 days
- Phase 1: 3 days
- Phase 2: 25 days
- Phase 3: 6 days
- Phase 4: 10 days
- **TOTAL: ~45 days** (9 weeks with 1 developer)

---

## Testing Strategy

### After Each Phase:

#### Phase 0 Tests:
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:list
php artisan serve
# Access http://localhost:8000
```

#### Phase 1 Tests:
```bash
php artisan view:clear
php artisan config:cache
php artisan migrate:status
git status # Should show no unnecessary files
```

#### Phase 2 Tests:
```bash
php artisan test
php artisan test --coverage --min=70
php artisan tinker # Test all factories
```

#### Phase 3 Tests:
```bash
php artisan scribe:generate
php artisan route:cache
php artisan config:cache
```

#### Phase 4 Tests:
```bash
docker-compose up
./vendor/bin/sail up
# Run CI pipeline
```

---

## Risk Assessment

### Critical Risks:
1. **Kernel.php fix** - If done incorrectly, entire app breaks
   - **Mitigation:** Test immediately after fix, have backup

### Medium Risks:
2. **View deletion** - Could delete wrong files
   - **Mitigation:** Backup views directory first
3. **Test creation** - May reveal existing bugs
   - **Mitigation:** Fix bugs as discovered, prioritize critical ones

### Low Risks:
4. **Route splitting** - Could break route references
   - **Mitigation:** Keep route names unchanged
5. **Config publishing** - Could override custom settings
   - **Mitigation:** Compare before overwriting

---

## Success Metrics

### Phase 0 Success:
- ✅ Application starts without errors
- ✅ Middleware loads correctly
- ✅ Routes are accessible

### Phase 1 Success:
- ✅ No duplicate files in repository
- ✅ Clean git status
- ✅ Comprehensive .env.example
- ✅ All views render correctly

### Phase 2 Success:
- ✅ All 19 models have factories
- ✅ Test coverage ≥ 70%
- ✅ All migrations have descriptive names
- ✅ Can seed database with realistic data

### Phase 3 Success:
- ✅ API documentation accessible
- ✅ All config files published and customized
- ✅ Routes organized logically
- ✅ Documentation is comprehensive

### Phase 4 Success:
- ✅ Docker setup works on clean machine
- ✅ CI/CD pipeline runs successfully
- ✅ Postman collection covers all endpoints
- ✅ Cache strategy documented

---

## Recommendations Beyond Fixes

### Security Enhancements:
1. Add rate limiting to all routes (not just login)
2. Implement CSRF protection verification
3. Add SQL injection prevention checks
4. Setup security headers middleware
5. Enable audit logging for sensitive operations

### Performance Optimizations:
1. Add database indexes for frequently queried columns
2. Implement Redis caching for sessions
3. Setup query optimization monitoring
4. Add lazy loading for relationships
5. Implement CDN for static assets

### Code Quality:
1. Setup PHP CodeSniffer (PSR-12)
2. Add PHPStan for static analysis
3. Setup pre-commit hooks
4. Document all public methods
5. Add type hints everywhere

### User Experience:
1. Add loading states in views
2. Implement better error messages
3. Add data validation on frontend
4. Setup real-time notifications
5. Add progress indicators for long operations

---

## Appendix: Useful Commands

### Development:
```bash
# Clear all caches
php artisan optimize:clear

# Rebuild caches
php artisan optimize

# Run migrations fresh with seed
php artisan migrate:fresh --seed

# Generate IDE helper files
php artisan ide-helper:generate
php artisan ide-helper:models -N

# Code style fixing
./vendor/bin/pint
```

### Testing:
```bash
# Run all tests
php artisan test

# Run specific test
php artisan test --filter CandidateManagementTest

# Run with coverage
php artisan test --coverage

# Create new test
php artisan make:test ComplaintManagementTest
```

### Database:
```bash
# Fresh migration
php artisan migrate:fresh

# Rollback last migration
php artisan migrate:rollback

# Check migration status
php artisan migrate:status

# Seed database
php artisan db:seed
```

### Factories:
```bash
# Create factory
php artisan make:factory ComplaintFactory --model=Complaint

# Test factory in tinker
php artisan tinker
>>> App\Models\Complaint::factory()->count(10)->create()
```

---

## Contact & Support

For questions about this fix plan:
- Review each phase carefully before implementation
- Test in development environment first
- Create backups before major changes
- Document any additional issues found

**Last Updated:** 2025-11-09
**Version:** 1.0
**Maintainer:** Development Team
