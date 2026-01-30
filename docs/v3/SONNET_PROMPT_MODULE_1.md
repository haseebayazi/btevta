# Sonnet Prompt: Complete Module 1 Pre-Departure Documents

## CRITICAL: READ THIS FIRST

**Module 1 is 90%+ ALREADY IMPLEMENTED.** Do NOT recreate existing code. Your job is to fix 5 specific gaps.

Read `docs/v3/MODULE_1_ACTUAL_GAPS.md` before doing anything.

---

## PROMPT TO COPY FOR SONNET

```
I need you to complete Module 1 (Pre-Departure Documents) for the BTEVTA WASL application.

IMPORTANT: Most of Module 1 is ALREADY IMPLEMENTED. Do NOT recreate existing files. Your job is to fix 5 specific gaps.

## EXISTING CODE (DO NOT MODIFY UNLESS SPECIFIED)

These files already exist and work correctly:
- app/Models/PreDepartureDocument.php ✓
- app/Models/DocumentChecklist.php ✓
- app/Services/PreDepartureDocumentService.php ✓ (584 lines, complete)
- app/Http/Controllers/PreDepartureDocumentController.php ✓
- app/Http/Controllers/CandidateLicenseController.php ✓
- app/Models/CandidateLicense.php ✓
- resources/views/candidates/pre-departure-documents/ ✓ (4 blade files)
- tests/Unit/CandidatePreDepartureTest.php ✓ (6 tests)
- tests/Unit/PreDepartureDocumentPolicyTest.php ✓
- Routes are registered in routes/web.php ✓

## YOUR 5 TASKS (IN ORDER)

### Task 1: Fix Bug in PreDepartureReportController
File: app/Http/Controllers/PreDepartureReportController.php

The file uses `PreDepartureDocument::class` on line 24 but doesn't import it. Add this import:
```php
use App\Models\PreDepartureDocument;
```

Verification: `php artisan route:list | grep pre-departure` should work without errors.

### Task 2: Create DocumentChecklistSeeder
Run: `php artisan make:seeder DocumentChecklistSeeder`

Implement with this exact data:
```php
<?php

namespace Database\Seeders;

use App\Models\DocumentChecklist;
use Illuminate\Database\Seeder;

class DocumentChecklistSeeder extends Seeder
{
    public function run(): void
    {
        $checklists = [
            // Mandatory (5)
            ['name' => 'CNIC (Front & Back)', 'code' => 'CNIC', 'description' => 'Computerized National Identity Card - both sides', 'category' => 'mandatory', 'is_mandatory' => true, 'display_order' => 1, 'is_active' => true],
            ['name' => 'Passport (1st & 2nd Page)', 'code' => 'PASSPORT', 'description' => 'Valid passport with at least 6 months validity', 'category' => 'mandatory', 'is_mandatory' => true, 'display_order' => 2, 'is_active' => true],
            ['name' => 'Domicile Certificate', 'code' => 'DOMICILE', 'description' => 'Valid domicile certificate', 'category' => 'mandatory', 'is_mandatory' => true, 'display_order' => 3, 'is_active' => true],
            ['name' => 'Family Registration Certificate (FRC)', 'code' => 'FRC', 'description' => 'Family Registration Certificate from NADRA', 'category' => 'mandatory', 'is_mandatory' => true, 'display_order' => 4, 'is_active' => true],
            ['name' => 'Police Character Certificate (PCC)', 'code' => 'PCC', 'description' => 'Police Clearance Certificate', 'category' => 'mandatory', 'is_mandatory' => true, 'display_order' => 5, 'is_active' => true],

            // Optional (3)
            ['name' => 'Pre-Medical Results', 'code' => 'PRE_MEDICAL', 'description' => 'Medical examination results', 'category' => 'optional', 'is_mandatory' => false, 'display_order' => 6, 'is_active' => true],
            ['name' => 'Professional Certifications', 'code' => 'CERTIFICATIONS', 'description' => 'Trade or professional certificates', 'category' => 'optional', 'is_mandatory' => false, 'display_order' => 7, 'is_active' => true],
            ['name' => 'Resume/CV', 'code' => 'RESUME', 'description' => 'Curriculum Vitae or Resume', 'category' => 'optional', 'is_mandatory' => false, 'display_order' => 8, 'is_active' => true],
        ];

        foreach ($checklists as $checklist) {
            DocumentChecklist::updateOrCreate(
                ['code' => $checklist['code']],
                $checklist
            );
        }
    }
}
```

### Task 3: Create PDF Report View
File to create: resources/views/reports/pre-departure/individual-pdf.blade.php

The PreDepartureDocumentService->generateIndividualPdfReport() method at line 422 loads this view with these variables:
- $candidate (Candidate model)
- $documents (Collection of PreDepartureDocument)
- $status (array with completion stats)
- $mandatory (Collection of mandatory DocumentChecklist items)
- $optional (Collection of optional DocumentChecklist items)
- $generated_at (Carbon datetime)
- $generated_by (User model)

Create a professional PDF-ready Blade template that displays:
1. Header: "Pre-Departure Documents Report"
2. Candidate Info: Name, BTEVTA ID, CNIC, Campus, Trade
3. Completion Status: X/Y mandatory uploaded, Z% complete
4. Document Table:
   - Document Name
   - Category (Mandatory/Optional)
   - Status (Uploaded/Missing/Verified)
   - Uploaded Date
   - Verified Date (if applicable)
5. Footer: Generated at [date] by [user name]

Use simple inline CSS for PDF compatibility (no Tailwind, use inline styles).

### Task 4: Update DatabaseSeeder
File: database/seeders/DatabaseSeeder.php

Add this line inside the run() method:
```php
$this->call(DocumentChecklistSeeder::class);
```

### Task 5: Create Feature Tests for PreDepartureDocumentController
File to create: tests/Feature/PreDepartureDocumentControllerTest.php

Write tests for these scenarios:
1. test_authenticated_user_can_view_candidate_documents()
2. test_can_upload_document_for_new_candidate()
3. test_cannot_upload_document_for_non_new_candidate()
4. test_campus_admin_can_only_view_own_campus_candidates()
5. test_admin_can_verify_document()
6. test_admin_can_reject_document_with_reason()
7. test_can_download_uploaded_document()
8. test_upload_fails_for_invalid_file_type()

Use the existing test at tests/Unit/CandidatePreDepartureTest.php as a reference for how to seed DocumentChecklist data.

## VERIFICATION STEPS

After completing all tasks, run:
```bash
# 1. Verify no PHP syntax errors
php artisan route:list | grep pre-departure

# 2. Run existing unit tests
php artisan test tests/Unit/CandidatePreDepartureTest.php

# 3. Run your new feature tests
php artisan test tests/Feature/PreDepartureDocumentControllerTest.php

# 4. Run all tests
php artisan test
```

## DO NOT:
- Create new models (they exist)
- Create new services (complete)
- Create new controllers (just fix the import)
- Modify the CandidateStatus enum (PRE_DEPARTURE_DOCS already exists)
- Add new routes (already registered)
- Modify existing tests (they pass)

## COMMIT MESSAGE FORMAT
After each task, commit with:
```
git add <files>
git commit -m "Module 1: <task description>

https://claude.ai/code/session_<session_id>"
```
```

---

## Why Previous Attempts Failed

1. **Plans treated everything as new** - 90% was already implemented
2. **Tasks were not atomic** - "Create PreDepartureDocumentService" when it already exists
3. **No verification steps** - Sonnet couldn't confirm success
4. **Missing context** - Didn't explain what already existed
5. **Overwhelming scope** - 50 tasks for what's really 5 gaps

---

## Expected Outcome

| Metric | Before | After |
|--------|--------|-------|
| New Lines of Code | N/A | ~350 lines |
| Files Modified | 0 | 2 |
| Files Created | 0 | 3 |
| Tests Added | 0 | 8 |
| Bugs Fixed | 1 | 0 |

---

*This prompt was created based on actual codebase analysis on 2026-01-30*
