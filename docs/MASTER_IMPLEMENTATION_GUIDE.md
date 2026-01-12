# BTEVTA WASL - MASTER IMPLEMENTATION GUIDE
## Complete System Rebuild - AI Execution Manual

**Document Version:** 2.0  
**Last Updated:** January 11, 2026  
**Implementation Type:** Systematic, Phase-by-Phase  
**Total Estimated Time:** 12 Weeks  

---

## 📖 HOW TO USE THIS GUIDE

**For AI Models (Claude, GitHub Copilot, etc.):**

This guide is designed for **sequential, systematic execution**. Each phase builds on the previous one.

### Execution Protocol:

1. **Start with Phase 0** (Foundation) - Already detailed in Part 1
2. **Complete all tasks** in sequence
3. **Run verification** after each major section
4. **Do not skip** ahead if verifications fail
5. **Commit code** after each completed phase
6. **Document issues** as you proceed

### File Organization:

```
btevta-wasl-v2/
├── IMPLEMENTATION_GUIDE_PART_1.md    ← Phase 0: Foundation (COMPLETED)
├── IMPLEMENTATION_GUIDE_PART_2.md    ← Phases 1-5 (DATABASE → VISA)
├── IMPLEMENTATION_GUIDE_PART_3.md    ← Phases 6-10 (DEPARTURE → REMITTANCE)
├── IMPLEMENTATION_GUIDE_PART_4.md    ← Phases 11-15 (ADVANCED → DEPLOYMENT)
└── IMPLEMENTATION_LOG.md              ← Track progress here
```

---

## 📊 IMPLEMENTATION PROGRESS TRACKER

**Instructions:** Update this table as you complete each phase.

| Phase | Module | Status | Started | Completed | Verified | Committed |
|-------|--------|--------|---------|-----------|----------|-----------|
| 0 | Foundation & Setup | 🟡 | - | - | ☐ | ☐ |
| 1 | Database Architecture | ⚪ | - | - | ☐ | ☐ |
| 2 | Authentication & Authorization | ⚪ | - | - | ☐ | ☐ |
| 3 | Module 1: Candidate Listing | ⚪ | - | - | ☐ | ☐ |
| 4 | Module 2: Candidate Screening | ⚪ | - | - | ☐ | ☐ |
| 5 | Module 3: Registration at Campus | ⚪ | - | - | ☐ | ☐ |
| 6 | Module 4: Training Management | ⚪ | - | - | ☐ | ☐ |
| 7 | Module 5: Visa Processing | ⚪ | - | - | ☐ | ☐ |
| 8 | Module 6: Departure & Post-Deployment | ⚪ | - | - | ☐ | ☐ |
| 9 | Module 7: Correspondence | ⚪ | - | - | ☐ | ☐ |
| 10 | Module 8: Complaints & Grievance | ⚪ | - | - | ☐ | ☐ |
| 11 | Module 9: Document Archive | ⚪ | - | - | ☐ | ☐ |
| 12 | Module 10: Remittance Management | ⚪ | - | - | ☐ | ☐ |
| 13 | Advanced Features | ⚪ | - | - | ☐ | ☐ |
| 14 | Testing & QA | ⚪ | - | - | ☐ | ☐ |
| 15 | Production Deployment | ⚪ | - | - | ☐ | ☐ |

**Legend:**
- ⚪ Not Started
- 🟡 In Progress
- 🟢 Complete
- 🔴 Blocked/Failed

---

## 🎯 PHASE OVERVIEW & DEPENDENCIES

```
Phase 0: Foundation (PREREQUISITE FOR ALL)
    ↓
Phase 1: Database Architecture (PREREQUISITE FOR ALL MODULES)
    ↓
Phase 2: Auth & Authorization (PREREQUISITE FOR ALL MODULES)
    ↓
Phase 3: Candidate Listing (FIRST WORKING MODULE)
    ↓
Phase 4: Screening (DEPENDS ON: Phase 3)
    ↓
Phase 5: Registration (DEPENDS ON: Phase 3, 4)
    ↓
Phase 6: Training (DEPENDS ON: Phase 3, 4, 5)
    ↓
Phase 7: Visa Processing (DEPENDS ON: Phase 3, 4, 5)
    ↓
Phase 8: Departure (DEPENDS ON: Phase 3, 4, 5, 7)
    ↓
Phase 9: Correspondence (INDEPENDENT, can be done anytime after Phase 2)
    ↓
Phase 10: Complaints (INDEPENDENT, can be done anytime after Phase 2)
    ↓
Phase 11: Document Archive (DEPENDS ON: Phase 3, 4, 5, 6, 7, 8)
    ↓
Phase 12: Remittance (DEPENDS ON: Phase 3, 8)
    ↓
Phase 13: Advanced Features (DEPENDS ON: ALL MODULES)
    ↓
Phase 14: Testing & QA (DEPENDS ON: ALL MODULES)
    ↓
Phase 15: Production Deployment (DEPENDS ON: Phase 14 PASSING)
```

---

## 📋 COMPLETE FILE MANIFEST

This is a complete list of all files that will be created during implementation.

### Database Files (Phase 1)

**Migrations:**
- `database/migrations/2024_01_01_000001_create_campuses_table.php`
- `database/migrations/2024_01_01_000002_create_trades_table.php`
- `database/migrations/2024_01_01_000003_create_oeps_table.php`
- `database/migrations/2024_01_01_000004_create_batches_table.php`
- `database/migrations/2024_01_01_000005_create_candidates_table.php`
- `database/migrations/2024_01_01_000006_create_screenings_table.php`
- `database/migrations/2024_01_01_000007_create_registrations_table.php`
- `database/migrations/2024_01_01_000008_create_trainings_table.php`
- `database/migrations/2024_01_01_000009_create_training_attendance_table.php`
- `database/migrations/2024_01_01_000010_create_training_assessments_table.php`
- `database/migrations/2024_01_01_000011_create_visa_processes_table.php`
- `database/migrations/2024_01_01_000012_create_visa_stages_table.php`
- `database/migrations/2024_01_01_000013_create_departures_table.php`
- `database/migrations/2024_01_01_000014_create_correspondences_table.php`
- `database/migrations/2024_01_01_000015_create_complaints_table.php`
- `database/migrations/2024_01_01_000016_create_document_archives_table.php`
- `database/migrations/2024_01_01_000017_create_remittances_table.php`
- `database/migrations/2024_01_01_000018_create_password_histories_table.php`
- `database/migrations/2024_01_01_000019_add_uuid_to_users_table.php`

**Seeders:**
- `database/seeders/RoleSeeder.php`
- `database/seeders/CampusSeeder.php`
- `database/seeders/TradeSeeder.php`
- `database/seeders/UserSeeder.php`
- `database/seeders/DatabaseSeeder.php`

**Factories:**
- `database/factories/CandidateFactory.php`
- `database/factories/ScreeningFactory.php`
- `database/factories/TrainingFactory.php`
- `database/factories/VisaProcessFactory.php`

### Models (Phase 1)

- `app/Models/User.php` (modified)
- `app/Models/Campus.php`
- `app/Models/Trade.php`
- `app/Models/Oep.php`
- `app/Models/Batch.php`
- `app/Models/Candidate.php`
- `app/Models/Screening.php`
- `app/Models/Registration.php`
- `app/Models/Training.php`
- `app/Models/TrainingAttendance.php`
- `app/Models/TrainingAssessment.php`
- `app/Models/VisaProcess.php`
- `app/Models/VisaStage.php`
- `app/Models/Departure.php`
- `app/Models/Correspondence.php`
- `app/Models/Complaint.php`
- `app/Models/DocumentArchive.php`
- `app/Models/Remittance.php`
- `app/Models/PasswordHistory.php`

### Enums (Phase 1)

- `app/Enums/CandidateStatus.php`
- `app/Enums/ScreeningOutcome.php`
- `app/Enums/TrainingStatus.php`
- `app/Enums/VisaStage.php`
- `app/Enums/ComplaintStatus.php`
- `app/Enums/ComplaintPriority.php`
- `app/Enums/DocumentType.php`
- `app/Enums/UserRole.php`

### Policies (Phase 2)

- `app/Policies/CandidatePolicy.php`
- `app/Policies/ScreeningPolicy.php`
- `app/Policies/RegistrationPolicy.php`
- `app/Policies/TrainingPolicy.php`
- `app/Policies/VisaProcessPolicy.php`
- `app/Policies/DeparturePolicy.php`
- `app/Policies/CorrespondencePolicy.php`
- `app/Policies/ComplaintPolicy.php`
- `app/Policies/DocumentArchivePolicy.php`
- `app/Policies/RemittancePolicy.php`

### Controllers (Phases 3-12)

**Admin Controllers:**
- `app/Http/Controllers/Admin/DashboardController.php`
- `app/Http/Controllers/Admin/UserController.php`
- `app/Http/Controllers/Admin/CampusController.php`
- `app/Http/Controllers/Admin/TradeController.php`
- `app/Http/Controllers/Admin/OepController.php`
- `app/Http/Controllers/Admin/ReportController.php`

**Module Controllers:**
- `app/Http/Controllers/CandidateController.php`
- `app/Http/Controllers/ScreeningController.php`
- `app/Http/Controllers/RegistrationController.php`
- `app/Http/Controllers/TrainingController.php`
- `app/Http/Controllers/TrainingAttendanceController.php`
- `app/Http/Controllers/TrainingAssessmentController.php`
- `app/Http/Controllers/VisaProcessController.php`
- `app/Http/Controllers/DepartureController.php`
- `app/Http/Controllers/CorrespondenceController.php`
- `app/Http/Controllers/ComplaintController.php`
- `app/Http/Controllers/DocumentArchiveController.php`
- `app/Http/Controllers/RemittanceController.php`

**Import/Export Controllers:**
- `app/Http/Controllers/Import/CandidateImportController.php`
- `app/Http/Controllers/Export/CandidateExportController.php`
- `app/Http/Controllers/Export/ReportExportController.php`

### Services (Phases 3-12)

- `app/Services/CandidateService.php`
- `app/Services/ScreeningService.php`
- `app/Services/RegistrationService.php`
- `app/Services/TrainingService.php`
- `app/Services/VisaProcessService.php`
- `app/Services/DepartureService.php`
- `app/Services/ComplaintService.php`
- `app/Services/DocumentService.php`
- `app/Services/RemittanceService.php`
- `app/Services/ReportService.php`
- `app/Services/ImportService.php`
- `app/Services/ExportService.php`

### Requests (Validation) (Phases 3-12)

- `app/Http/Requests/StoreCandidateRequest.php`
- `app/Http/Requests/UpdateCandidateRequest.php`
- `app/Http/Requests/StoreScreeningRequest.php`
- `app/Http/Requests/StoreRegistrationRequest.php`
- `app/Http/Requests/StoreTrainingRequest.php`
- `app/Http/Requests/StoreVisaProcessRequest.php`
- `app/Http/Requests/StoreDepartureRequest.php`
- `app/Http/Requests/StoreCorrespondenceRequest.php`
- `app/Http/Requests/StoreComplaintRequest.php`
- `app/Http/Requests/StoreDocumentRequest.php`
- `app/Http/Requests/StoreRemittanceRequest.php`
- `app/Http/Requests/BulkCandidateRequest.php`

### Views (Phases 3-12)

**Layouts:**
- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/guest.blade.php`
- `resources/views/layouts/sidebar.blade.php`
- `resources/views/layouts/navigation.blade.php`

**Dashboard:**
- `resources/views/dashboard.blade.php`

**Candidates Module:**
- `resources/views/candidates/index.blade.php`
- `resources/views/candidates/create.blade.php`
- `resources/views/candidates/edit.blade.php`
- `resources/views/candidates/show.blade.php`
- `resources/views/candidates/import.blade.php`

**Screening Module:**
- `resources/views/screening/index.blade.php`
- `resources/views/screening/show.blade.php`
- `resources/views/screening/log-call.blade.php`

**Registration Module:**
- `resources/views/registration/index.blade.php`
- `resources/views/registration/show.blade.php`
- `resources/views/registration/complete.blade.php`

**Training Module:**
- `resources/views/training/index.blade.php`
- `resources/views/training/batches.blade.php`
- `resources/views/training/attendance.blade.php`
- `resources/views/training/assessments.blade.php`

**Visa Module:**
- `resources/views/visa/index.blade.php`
- `resources/views/visa/show.blade.php`
- `resources/views/visa/update-stage.blade.php`

**Departure Module:**
- `resources/views/departure/index.blade.php`
- `resources/views/departure/show.blade.php`
- `resources/views/departure/create.blade.php`

**Correspondence Module:**
- `resources/views/correspondence/index.blade.php`
- `resources/views/correspondence/create.blade.php`
- `resources/views/correspondence/show.blade.php`

**Complaints Module:**
- `resources/views/complaints/index.blade.php`
- `resources/views/complaints/create.blade.php`
- `resources/views/complaints/show.blade.php`

**Documents Module:**
- `resources/views/documents/index.blade.php`
- `resources/views/documents/upload.blade.php`

**Remittance Module:**
- `resources/views/remittances/index.blade.php`
- `resources/views/remittances/create.blade.php`
- `resources/views/remittances/show.blade.php`

**Admin Views:**
- `resources/views/admin/dashboard.blade.php`
- `resources/views/admin/users/index.blade.php`
- `resources/views/admin/campuses/index.blade.php`
- `resources/views/admin/trades/index.blade.php`
- `resources/views/admin/oeps/index.blade.php`
- `resources/views/admin/reports/index.blade.php`

### Tests (Phase 14)

**Unit Tests:**
- `tests/Unit/Models/CandidateTest.php`
- `tests/Unit/Models/ScreeningTest.php`
- `tests/Unit/Services/CandidateServiceTest.php`
- `tests/Unit/Enums/CandidateStatusTest.php`

**Feature Tests:**
- `tests/Feature/Auth/LoginTest.php`
- `tests/Feature/Auth/PasswordExpiryTest.php`
- `tests/Feature/Candidates/CandidateListingTest.php`
- `tests/Feature/Candidates/CandidateImportTest.php`
- `tests/Feature/Candidates/BulkOperationsTest.php`
- `tests/Feature/Screening/ScreeningWorkflowTest.php`
- `tests/Feature/Registration/RegistrationProcessTest.php`
- `tests/Feature/Training/AttendanceTest.php`
- `tests/Feature/Training/AssessmentTest.php`
- `tests/Feature/Visa/VisaProcessingTest.php`
- `tests/Feature/Departure/DepartureTrackingTest.php`
- `tests/Feature/Complaints/ComplaintManagementTest.php`
- `tests/Feature/Documents/DocumentUploadTest.php`
- `tests/Feature/Remittances/RemittanceTrackingTest.php`

---

## 🔧 QUICK REFERENCE: COMMON PATTERNS

### Pattern 1: Creating a Migration

```bash
php artisan make:migration create_table_name_table
```

**Template:**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('table_name', function (Blueprint $table) {
            $table->id();
            // Add columns here
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_name');
    }
};
```

### Pattern 2: Creating a Model

```bash
php artisan make:model ModelName
```

**Template:**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasActivityLog;
use App\Traits\HasUuid;

class ModelName extends Model
{
    use HasFactory, SoftDeletes, HasActivityLog, HasUuid;

    protected $fillable = [
        // Add fillable fields
    ];

    protected $casts = [
        // Add casts
    ];

    // Add relationships
}
```

### Pattern 3: Creating a Controller

```bash
php artisan make:controller ControllerName --resource
```

**Template:**
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ControllerName extends Controller
{
    public function index()
    {
        return view('module.index');
    }

    public function create()
    {
        return view('module.create');
    }

    public function store(Request $request)
    {
        // Validation
        // Store logic
        return redirect()->route('module.index')
            ->with('success', 'Created successfully');
    }

    public function show($id)
    {
        return view('module.show');
    }

    public function edit($id)
    {
        return view('module.edit');
    }

    public function update(Request $request, $id)
    {
        // Validation
        // Update logic
        return redirect()->route('module.index')
            ->with('success', 'Updated successfully');
    }

    public function destroy($id)
    {
        // Delete logic
        return redirect()->route('module.index')
            ->with('success', 'Deleted successfully');
    }
}
```

### Pattern 4: Creating a Policy

```bash
php artisan make:policy PolicyName --model=ModelName
```

**Template:**
```php
<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ModelName;

class PolicyName
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function view(User $user, ModelName $model): bool
    {
        return can_user_access_candidate($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'campus_admin']);
    }

    public function update(User $user, ModelName $model): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function delete(User $user, ModelName $model): bool
    {
        return $user->hasRole('super_admin');
    }
}
```

### Pattern 5: Creating a Test

```bash
php artisan make:test FeatureNameTest
```

**Template:**
```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

class FeatureNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_example_feature()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/route');

        $response->assertStatus(200);
    }
}
```

---

## ⚙️ IMPLEMENTATION WORKFLOW

For each phase, follow this workflow:

### Step 1: Read Phase Documentation
- Review all requirements
- Understand dependencies
- Check prerequisites complete

### Step 2: Create Database Layer (if applicable)
- Create migrations
- Run migrations
- Create models
- Create factories
- Create seeders

### Step 3: Create Business Logic
- Create services
- Create enums (if needed)
- Create policies
- Create observers (if needed)

### Step 4: Create HTTP Layer
- Create controllers
- Create form requests
- Define routes

### Step 5: Create Views
- Create blade templates
- Add to navigation
- Style with Tailwind

### Step 6: Create Tests
- Write unit tests
- Write feature tests
- Run all tests

### Step 7: Verify & Commit
- Run verification script
- Test manually
- Fix any issues
- Commit changes
- Tag release

---

## 🚦 VERIFICATION GATES

Before moving to the next phase, you MUST pass these checks:

### Gate 1: Database (After Phase 1)
```bash
php artisan migrate:status  # All migrations RUN
php artisan db:seed         # Seeds successfully
php artisan tinker          # Can query models
```

### Gate 2: Authentication (After Phase 2)
```bash
php artisan test --filter=Auth  # All auth tests pass
# Manual: Can login as each role
# Manual: Password policy enforced
```

### Gate 3: Module Complete (After Each Module Phase)
```bash
php artisan test --filter=ModuleName  # All module tests pass
# Manual: Can perform all CRUD operations
# Manual: All views render without errors
# Manual: All links work
```

### Gate 4: Integration (After Phase 13)
```bash
php artisan test  # ALL tests pass
# Manual: Complete workflow works end-to-end
# Manual: No JavaScript errors in console
```

### Gate 5: Production Ready (After Phase 15)
```bash
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
# Manual: Deployed to staging
# Manual: All features tested on staging
```

---

## 📝 IMPLEMENTATION LOG TEMPLATE

Create a file `IMPLEMENTATION_LOG.md` in your project root and update it daily:

```markdown
# BTEVTA WASL - Implementation Log

## Week 1: Foundation & Database
**Dates:** [Start] to [End]

### Monday
- ✅ Completed Phase 0: Environment Setup
- ✅ All verification checks passed
- ⚠️ Issue: Database connection failed initially - fixed by updating .env

### Tuesday
- 🟡 Started Phase 1: Database Architecture
- ✅ Created 10 migrations
- ⚠️ Remaining: 9 migrations

### Wednesday
- ✅ Completed all migrations
- ✅ Created 15 models
- ✅ All models tested in tinker

... continue logging daily ...
```

---

## 🎯 DELIVERABLES CHECKLIST

By the end of implementation, you should have:

### Code Deliverables
- [ ] All 19 database migrations created and tested
- [ ] All 18 models created with relationships
- [ ] All 8 enums defined
- [ ] All 10 policies implemented
- [ ] All 25+ controllers created
- [ ] All 50+ views created
- [ ] All 100+ tests written and passing

### Documentation Deliverables
- [ ] README.md updated with actual features
- [ ] API documentation (if applicable)
- [ ] User guide for each module
- [ ] Admin guide
- [ ] Deployment guide
- [ ] Troubleshooting guide

### Testing Deliverables
- [ ] Unit tests coverage > 80%
- [ ] Feature tests for all workflows
- [ ] Manual testing checklist completed
- [ ] Security audit passed
- [ ] Performance testing completed

### Deployment Deliverables
- [ ] Production environment configured
- [ ] Database backup strategy implemented
- [ ] Monitoring set up
- [ ] SSL certificate installed
- [ ] All environment variables configured

---

## 🆘 TROUBLESHOOTING GUIDE

### Issue: Migration Fails

**Symptoms:**
```
SQLSTATE[42S01]: Base table or view already exists
```

**Solution:**
```bash
php artisan migrate:rollback
php artisan migrate:fresh
```

### Issue: Class Not Found

**Symptoms:**
```
Class 'App\Models\Something' not found
```

**Solution:**
```bash
composer dump-autoload
php artisan optimize:clear
```

### Issue: Tests Failing

**Symptoms:**
```
Error: Call to undefined method
```

**Solution:**
1. Check test database configuration
2. Run: `php artisan config:clear`
3. Ensure using `RefreshDatabase` trait
4. Check factory definitions

### Issue: Views Not Rendering

**Symptoms:**
```
View [module.index] not found
```

**Solution:**
```bash
php artisan view:clear
# Check file exists in resources/views/module/index.blade.php
```

---

## 📚 REFERENCE MATERIALS

### Laravel Documentation
- [Laravel 11 Docs](https://laravel.com/docs/11.x)
- [Eloquent ORM](https://laravel.com/docs/11.x/eloquent)
- [Blade Templates](https://laravel.com/docs/11.x/blade)
- [Testing](https://laravel.com/docs/11.x/testing)

### Package Documentation
- [Spatie Permission](https://spatie.be/docs/laravel-permission)
- [Spatie Activity Log](https://spatie.be/docs/laravel-activitylog)
- [Laravel Excel](https://docs.laravel-excel.com)
- [Laravel DomPDF](https://github.com/barryvdh/laravel-dompdf)

---

## 🎓 NEXT STEPS

1. **If starting fresh:** Begin with Phase 0 (See Part 1 Guide)
2. **If Phase 0 complete:** Proceed to Phase 1 (Database Architecture)
3. **If you encounter issues:** Check Troubleshooting Guide
4. **Need help:** Create detailed issue with error messages

---

**Remember:** Build slowly, test thoroughly, commit frequently.

**Quality over speed. Correctness over completion.**

---

## 📄 Document Index

- **Part 1:** Phase 0 - Foundation & Setup ✅
- **Part 2:** Phases 1-5 - Database, Auth, Core Modules (Coming Next)
- **Part 3:** Phases 6-10 - Advanced Modules
- **Part 4:** Phases 11-15 - Features, Testing, Deployment

Continue to next document when ready to implement Phase 1.
